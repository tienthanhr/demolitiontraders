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
        $sessionId = $_SESSION['cart_id'] ?? null;
        
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
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $taxRate = 0.15; // 15% GST
        $taxAmount = $subtotal * $taxRate;
        $shippingAmount = floatval($data['shipping_amount'] ?? 0);
        $discountAmount = floatval($data['discount_amount'] ?? 0);
        $total = $subtotal + $taxAmount + $shippingAmount - $discountAmount;
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Create order
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            $orderId = $this->db->insert('orders', [
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'guest_email' => $data['email'] ?? null,
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
                $itemTotal = $item['price'] * $item['quantity'];
                $itemTax = $itemTotal * $taxRate;
                
                $this->db->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'sku' => $item['sku'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $itemTotal,
                    'tax_amount' => $itemTax,
                    'total' => $itemTotal + $itemTax
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
        
        $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $id]);
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
}
