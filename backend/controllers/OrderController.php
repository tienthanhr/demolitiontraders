<?php
/**
 * Order Controller
 * Handles order creation and management
 */

require_once __DIR__ . '/../services/IdealPOSService.php';

class OrderController {
    private $db;
    private $idealpos;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->idealpos = new IdealPOSService();
    }
    
    /**
     * Get user orders (or all orders for admin)
     */
    public function index() {
        $userId = $_SESSION['user_id'] ?? null;
        $isAdmin = ($_SESSION['is_admin'] ?? false) || ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin';
        
        error_log("OrderController::index - userId: " . var_export($userId, true) . ", isAdmin: " . var_export($isAdmin, true));
        error_log("OrderController::index - Session: " . json_encode($_SESSION));
        
        // Get filter parameters
        $statusFilter = $_GET['status'] ?? null;
        
        // Build query
        $params = [];
        $whereConditions = [];
        
        // Admin can see all orders, regular users only see their own
        if (!$isAdmin) {
            if (!$userId) {
                throw new Exception('Authentication required');
            }
            $whereConditions[] = 'user_id = :user_id';
            $params['user_id'] = $userId;
        }
        
        // Add status filter if provided
        if ($statusFilter && $statusFilter !== '') {
            $whereConditions[] = 'status = :status';
            $params['status'] = $statusFilter;
        }
        
        // Build final query
        $sql = "SELECT * FROM orders";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        $sql .= " ORDER BY created_at DESC";
        
        error_log("OrderController::index - SQL: $sql");
        error_log("OrderController::index - Params: " . json_encode($params));
        
        $orders = $this->db->fetchAll($sql, $params);
        
        // Get order items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->db->fetchAll(
                "SELECT * FROM order_items WHERE order_id = :order_id",
                ['order_id' => $order['id']]
            );
        }
        
        return $orders;
    }
    
    /**
     * Get single order
     */
    public function show($id) {
        $userId = $_SESSION['user_id'] ?? null;
        $isAdmin = ($_SESSION['is_admin'] ?? false) || ($_SESSION['role'] ?? '') === 'admin';
        
        $order = $this->db->fetchOne(
            "SELECT * FROM orders WHERE id = :id",
            ['id' => $id]
        );
        
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        // Check authorization - admin can see all orders
        if (!$isAdmin && $order['user_id'] != $userId) {
            throw new Exception('Unauthorized access');
        }
        
        // Get order items
        $order['items'] = $this->db->fetchAll(
            "SELECT * FROM order_items WHERE order_id = :order_id",
            ['order_id' => $id]
        );
        
        return $order;
    }
    
    /**
     * Create new order
     */
    public function create($data) {
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id(); // Get actual PHP session ID
        
        error_log("OrderController::create - START - userId: " . var_export($userId, true) . ", sessionId: " . var_export($sessionId, true));
        
        // Validate required fields
        $required = ['billing_address', 'shipping_address', 'payment_method'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '{$field}' is required");
            }
        }
        
        // Get cart items
        if ($userId) {
            $cartItems = $this->db->fetchAll(
                "SELECT c.*, p.name, p.price, p.stock_quantity, p.sku
                 FROM cart c
                 JOIN products p ON c.product_id = p.id
                 WHERE c.user_id = :user_id",
                ['user_id' => $userId]
            );
        } else {
            $cartItems = $this->db->fetchAll(
                "SELECT c.*, p.name, p.price, p.stock_quantity, p.sku
                 FROM cart c
                 JOIN products p ON c.product_id = p.id
                 WHERE c.session_id = :session_id",
                ['session_id' => $sessionId]
            );
        }
        
        error_log("OrderController::create - Cart items count: " . count($cartItems));
        
        if (empty($cartItems)) {
            throw new Exception('Cart is empty');
        }
        
        // Check stock availability
        foreach ($cartItems as $item) {
            if ($item['stock_quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for {$item['name']}");
            }
        }
        
        // Calculate totals
        // Note: All prices already include GST (15%)
        $totalInclGST = 0;
        foreach ($cartItems as $item) {
            $totalInclGST += $item['price'] * $item['quantity'];
        }
        
        // Calculate GST component from GST-inclusive prices
        // If price includes GST: GST Amount = Total / 1.15 * 0.15
        // Subtotal (excl GST) = Total / 1.15
        $subtotal = $totalInclGST / 1.15; // Subtotal excluding GST
        $taxAmount = $subtotal * 0.15; // GST amount (15% of excl GST price)
        $shippingAmount = floatval($data['shipping_amount'] ?? 0);
        $discountAmount = floatval($data['discount_amount'] ?? 0);
        $total = $totalInclGST + $shippingAmount - $discountAmount;
        
        // Start transaction
        try {
            $this->db->beginTransaction();
            
            // Create order
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            $orderId = $this->db->insert('orders', [
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'guest_email' => $userId ? null : ($data['email'] ?? null),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $total,
                'billing_address' => json_encode($data['billing_address']),
                'shipping_address' => json_encode($data['shipping_address']),
                'customer_notes' => $data['notes'] ?? null
            ]);
            
            // Create order items
            foreach ($cartItems as $item) {
                // Price already includes GST
                $itemTotalInclGST = $item['price'] * $item['quantity'];
                $itemSubtotal = $itemTotalInclGST / 1.15; // Excl GST
                $itemTax = $itemSubtotal * 0.15; // GST component
                
                $this->db->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'sku' => $item['sku'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'], // This is GST-inclusive price
                    'subtotal' => $itemSubtotal, // Excl GST
                    'tax_amount' => $itemTax, // GST amount
                    'total' => $itemTotalInclGST // Incl GST (same as unit_price * quantity)
                ]);
                
                // Update product stock
                $this->db->query(
                    "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :id",
                    ['quantity' => $item['quantity'], 'id' => $item['product_id']]
                );
            }
            
            // Clear cart
            if ($userId) {
                $this->db->delete('cart', 'user_id = :user_id', ['user_id' => $userId]);
            } else {
                $this->db->delete('cart', 'session_id = :session_id', ['session_id' => $sessionId]);
            }
            
            // Save address to user account if logged in and requested
            if ($userId) {
                error_log("OrderController::create - userId: $userId, save_address flag: " . var_export($data['save_address'] ?? null, true));
                // Only save if explicitly requested via save_address flag
                if (!empty($data['save_address'])) {
                    error_log("OrderController::create - Calling saveAddressToUserAccount");
                    $this->saveAddressToUserAccount($userId, $data['billing_address'], $data['shipping_address']);
                } else {
                    error_log("OrderController::create - Not saving address (flag not set)");
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Send Tax Invoice email automatically (don't break if fails)
            try {
                require_once __DIR__ . '/../services/EmailService.php';
                $emailService = new EmailService();
                
                // Get customer email from billing address
                $billing = $data['billing_address'] ?? [];
                $customerEmail = $billing['email'] ?? $data['email'] ?? null;
                
                if ($customerEmail) {
                    // Get complete order with items for email
                    $completeOrder = $this->show($orderId);
                    $emailService->sendTaxInvoice($completeOrder, $customerEmail);
                    error_log("Tax Invoice email sent to: $customerEmail for order #$orderId");
                }
            } catch (Exception $e) {
                // Log but don't throw - email failure shouldn't break order creation
                error_log("Failed to send Tax Invoice email: " . $e->getMessage());
            }
            
            // Push to IdealPOS (async, don't block order creation)
            try {
                if ($this->idealpos->isEnabled()) {
                    $this->idealpos->pushOrder($orderId);
                }
            } catch (Exception $e) {
                error_log("Failed to push order to IdealPOS: " . $e->getMessage());
            }
            
            // Return order details
            return $this->show($orderId);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Update order (status, notes, etc.)
     */
    public function update($id, $data) {
        error_log("OrderController::update - Order ID: $id");
        error_log("OrderController::update - Data: " . json_encode($data));
        error_log("OrderController::update - Session: " . json_encode($_SESSION));
        
        $isAdmin = ($_SESSION['is_admin'] ?? false) || ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin';
        
        error_log("OrderController::update - isAdmin: " . var_export($isAdmin, true));
        
        if (!$isAdmin) {
            throw new Exception('Unauthorized: Admin access required');
        }
        
        $query = "SELECT * FROM orders WHERE id = :id";
        error_log("OrderController::update - Query: $query with id: $id");
        $order = $this->db->fetchOne($query, ['id' => $id]);
        error_log("OrderController::update - Order found: " . var_export($order !== false, true));
        
        if (!$order) {
            throw new Exception('Order not found');
        }

        $updates = [];
        $params = ['id' => $id];

        $restock = false;
        $deductStock = false;
        $oldStatus = $order['status'];

        if (isset($data['status'])) {
            $validStatuses = ['pending', 'paid', 'processing', 'ready', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($data['status'], $validStatuses)) {
                throw new Exception('Invalid status: ' . $data['status']);
            }
            $updates[] = 'status = :status';
            $params['status'] = $data['status'];
            error_log("OrderController::update - Updating status to: " . $data['status']);

            // If changing to cancelled and was not already cancelled, restock products
            if ($data['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
                $restock = true;
            }
            // If changing from cancelled to any other status, deduct stock again
            if ($oldStatus === 'cancelled' && $data['status'] !== 'cancelled') {
                $deductStock = true;
            }
        }

        if (isset($data['note']) && $data['note'] !== '') {
            $updates[] = 'admin_notes = :admin_notes';
            $params['admin_notes'] = $data['note'];
        }

        if (empty($updates)) {
            throw new Exception('No fields to update');
        }

        $updates[] = 'updated_at = NOW()';
        $sql = "UPDATE orders SET " . implode(', ', $updates) . " WHERE id = :id";

        error_log("OrderController::update - SQL: $sql");
        error_log("OrderController::update - Params: " . json_encode($params));

        $result = $this->db->query($sql, $params);
        error_log("OrderController::update - Query result: " . var_export($result, true));

        // Restock products if order is cancelled
        if ($restock) {
            $items = $this->db->fetchAll("SELECT product_id, quantity FROM order_items WHERE order_id = :order_id", ['order_id' => $id]);
            foreach ($items as $item) {
                $this->db->query(
                    "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :id",
                    ['quantity' => $item['quantity'], 'id' => $item['product_id']]
                );
            }
            error_log("OrderController::update - Restocked products for cancelled order #$id");
        }

        // Deduct stock if moving from cancelled to another status
        if ($deductStock) {
            $items = $this->db->fetchAll("SELECT product_id, quantity FROM order_items WHERE order_id = :order_id", ['order_id' => $id]);
            foreach ($items as $item) {
                $this->db->query(
                    "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :id",
                    ['quantity' => $item['quantity'], 'id' => $item['product_id']]
                );
            }
            error_log("OrderController::update - Deducted stock for re-activated order #$id");
        }

        return $this->show($id);
    }
    
    /**
     * Delete order
     */
    public function delete($id) {
        $isAdmin = ($_SESSION['is_admin'] ?? false) || ($_SESSION['role'] ?? '') === 'admin';
        
        if (!$isAdmin) {
            throw new Exception('Unauthorized: Admin access required');
        }
        
        $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $id]);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // If order is not cancelled, restore stock before deleting
        if ($order['status'] !== 'cancelled') {
            $items = $this->db->fetchAll("SELECT product_id, quantity FROM order_items WHERE order_id = :order_id", ['order_id' => $id]);
            foreach ($items as $item) {
                $this->db->query(
                    "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :id",
                    ['quantity' => $item['quantity'], 'id' => $item['product_id']]
                );
            }
        }

        // Delete order items first (foreign key constraint)
        $this->db->delete('order_items', 'order_id = :order_id', ['order_id' => $id]);
        
        // Delete order
        $this->db->delete('orders', 'id = :id', ['id' => $id]);
        
        return ['success' => true, 'message' => 'Order deleted successfully'];
    }
    
    /**
     * Save address to user account (max 5 addresses)
     */
    private function saveAddressToUserAccount($userId, $billingAddress, $shippingAddress) {
        try {
            // Get existing addresses
            $existingAddresses = $this->db->fetchAll(
                "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC",
                [$userId]
            );
            
            // Check if billing address already exists
            $billingExists = false;
            foreach ($existingAddresses as $addr) {
                if ($this->isSameAddress($addr, $billingAddress)) {
                    $billingExists = true;
                    break;
                }
            }
            
            // Add billing address if not exists and under limit
            if (!$billingExists && count($existingAddresses) < 5) {
                $isDefault = count($existingAddresses) === 0 ? 1 : 0;
                $addressId = $this->db->insert('addresses', [
                    'user_id' => $userId,
                    'address_type' => 'both',
                    'street_address' => $billingAddress['address'] ?? '',
                    'suburb' => $billingAddress['suburb'] ?? null,
                    'city' => $billingAddress['city'] ?? '',
                    'state' => $billingAddress['state'] ?? null,
                    'postcode' => $billingAddress['postcode'] ?? '',
                    'country' => $billingAddress['country'] ?? 'NZ',
                    'is_default' => $isDefault
                ]);
                error_log("Saved new billing address for user #$userId, address_id: $addressId");
            } elseif (!$billingExists) {
                error_log("Cannot save address - user #$userId already has 5 addresses (limit reached)");
            }
            
            // Check if shipping address is different and save if needed
            if (!$this->isSameAddress($billingAddress, $shippingAddress)) {
                $shippingExists = false;
                foreach ($existingAddresses as $addr) {
                    if ($this->isSameAddress($addr, $shippingAddress)) {
                        $shippingExists = true;
                        break;
                    }
                }
                
                // Refresh count after billing address might have been added
                $currentCount = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM addresses WHERE user_id = ?",
                    [$userId]
                );
                $addressCount = $currentCount['count'] ?? 0;
                
                if (!$shippingExists && $addressCount < 5) {
                    $shippingAddressId = $this->db->insert('addresses', [
                        'user_id' => $userId,
                        'address_type' => 'shipping',
                        'street_address' => $shippingAddress['address'] ?? '',
                        'suburb' => $shippingAddress['suburb'] ?? null,
                        'city' => $shippingAddress['city'] ?? '',
                        'state' => $shippingAddress['state'] ?? null,
                        'postcode' => $shippingAddress['postcode'] ?? '',
                        'country' => $shippingAddress['country'] ?? 'NZ',
                        'is_default' => 0
                    ]);
                    error_log("Saved shipping address for user #$userId, address_id: $shippingAddressId");
                }
            }
        } catch (Exception $e) {
            // Don't throw - address saving failure shouldn't break order creation
            error_log("Failed to save address: " . $e->getMessage());
        }
    }
    
    /**
     * Check if two addresses are the same
     */
    private function isSameAddress($addr1, $addr2) {
        $normalize = function($str) {
            return strtolower(trim(preg_replace('/\s+/', '', $str ?? '')));
        };
        
        return $normalize($addr1['address'] ?? '') === $normalize($addr2['address'] ?? '') &&
               $normalize($addr1['city'] ?? '') === $normalize($addr2['city'] ?? '') &&
               $normalize($addr1['postcode'] ?? '') === $normalize($addr2['postcode'] ?? '');
    }
}
