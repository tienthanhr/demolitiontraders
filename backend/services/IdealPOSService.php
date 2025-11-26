<?php
/**
 * IdealPOS API Integration Service
 * Handles all communication with IdealPOS system
 */

require_once __DIR__ . '/../config/config.php';

class IdealPOSService {
    private $apiUrl;
    private $apiKey;
    private $storeId;
    private $enabled;
    
    public function __construct() {
        $this->apiUrl = Config::get('IDEALPOS_API_URL');
        $this->apiKey = Config::get('IDEALPOS_API_KEY');
        $this->storeId = Config::get('IDEALPOS_STORE_ID');
        $this->enabled = Config::get('IDEALPOS_SYNC_ENABLED', true);
    }
    
    /**
     * Check if IdealPOS integration is enabled and configured
     */
    public function isEnabled() {
        return $this->enabled && !empty($this->apiKey) && !empty($this->storeId);
    }
    
    /**
     * Make API request to IdealPOS
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        if (!$this->isEnabled()) {
            throw new Exception('IdealPOS integration is not enabled or configured');
        }
        
        $url = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Store-ID: ' . $this->storeId
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $result['message'] ?? 'Unknown error';
            throw new Exception("API Error ({$httpCode}): {$errorMsg}");
        }
        
        return $result;
    }
    
    /**
     * Sync products from IdealPOS to website
     */
    public function syncProducts() {
        try {
            $products = $this->makeRequest('/products');
            
            $db = Database::getInstance();
            $synced = 0;
            $failed = 0;
            
            foreach ($products['data'] ?? [] as $posProduct) {
                try {
                    $this->importProduct($posProduct);
                    $synced++;
                } catch (Exception $e) {
                    $failed++;
                    error_log("Failed to import product: " . $e->getMessage());
                }
            }
            
            $this->logSync('products', 'from_pos', 'success', $synced, $failed);
            
            return [
                'success' => true,
                'synced' => $synced,
                'failed' => $failed
            ];
            
        } catch (Exception $e) {
            $this->logSync('products', 'from_pos', 'failed', 0, 0, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Import single product from POS
     */
    private function importProduct($posProduct) {
        $db = Database::getInstance();
        
        // Check if product exists
        $existing = $db->fetchOne(
            "SELECT id FROM products WHERE idealpos_product_id = :pos_id",
            ['pos_id' => $posProduct['id']]
        );
        
        $productData = [
            'sku' => $posProduct['sku'],
            'name' => $posProduct['name'],
            'slug' => $this->generateSlug($posProduct['name']),
            'description' => $posProduct['description'] ?? '',
            'short_description' => substr($posProduct['description'] ?? '', 0, 500),
            'price' => $posProduct['price'],
            'cost_price' => $posProduct['cost'] ?? null,
            'stock_quantity' => $posProduct['stock'] ?? 0,
            'idealpos_product_id' => $posProduct['id'],
            'last_synced_at' => date('Y-m-d H:i:s')
        ];
        
        if ($existing) {
            // Update existing product
            $db->update('products', $productData, 'id = :id', ['id' => $existing['id']]);
        } else {
            // Insert new product
            $db->insert('products', $productData);
        }
    }
    
    /**
     * Sync inventory levels from IdealPOS
     */
    public function syncInventory() {
        try {
            $inventory = $this->makeRequest('/inventory');
            
            $db = Database::getInstance();
            $synced = 0;
            $failed = 0;
            
            foreach ($inventory['data'] ?? [] as $item) {
                try {
                    $db->update(
                        'products',
                        ['stock_quantity' => $item['stock']],
                        'idealpos_product_id = :pos_id',
                        ['pos_id' => $item['product_id']]
                    );
                    $synced++;
                } catch (Exception $e) {
                    $failed++;
                }
            }
            
            $this->logSync('inventory', 'from_pos', 'success', $synced, $failed);
            
            return [
                'success' => true,
                'synced' => $synced,
                'failed' => $failed
            ];
            
        } catch (Exception $e) {
            $this->logSync('inventory', 'from_pos', 'failed', 0, 0, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Push order to IdealPOS
     */
    public function pushOrder($orderId) {
        try {
            $db = Database::getInstance();
            
            // Get order details
            $order = $db->fetchOne(
                "SELECT * FROM orders WHERE id = :id",
                ['id' => $orderId]
            );
            
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            // Get order items
            $items = $db->fetchAll(
                "SELECT * FROM order_items WHERE order_id = :order_id",
                ['order_id' => $orderId]
            );
            
            // Prepare POS order data
            $posOrderData = [
                'order_number' => $order['order_number'],
                'customer_email' => $order['guest_email'] ?? null,
                'total' => $order['total_amount'],
                'subtotal' => $order['subtotal'],
                'tax' => $order['tax_amount'],
                'items' => array_map(function($item) {
                    return [
                        'sku' => $item['sku'],
                        'name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['unit_price']
                    ];
                }, $items),
                'payment_method' => $order['payment_method'],
                'notes' => $order['customer_notes']
            ];
            
            // Send to IdealPOS
            $response = $this->makeRequest('/orders', 'POST', $posOrderData);
            
            // Update order with POS ID
            $db->update(
                'orders',
                [
                    'idealpos_order_id' => $response['data']['id'],
                    'synced_to_pos' => 1,
                    'synced_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $orderId]
            );
            
            $this->logSync('orders', 'to_pos', 'success', 1, 0);
            
            return $response;
            
        } catch (Exception $e) {
            $this->logSync('orders', 'to_pos', 'failed', 0, 1, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Sync customer to IdealPOS
     */
    public function syncCustomer($userId) {
        try {
            $db = Database::getInstance();
            
            $user = $db->fetchOne(
                "SELECT * FROM users WHERE id = :id",
                ['id' => $userId]
            );
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $customerData = [
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'phone' => $user['phone']
            ];
            
            if ($user['idealpos_customer_id']) {
                // Update existing customer
                $response = $this->makeRequest(
                    '/customers/' . $user['idealpos_customer_id'],
                    'PUT',
                    $customerData
                );
            } else {
                // Create new customer
                $response = $this->makeRequest('/customers', 'POST', $customerData);
                
                // Save POS customer ID
                $db->update(
                    'users',
                    ['idealpos_customer_id' => $response['data']['id']],
                    'id = :id',
                    ['id' => $userId]
                );
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Failed to sync customer: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get product from IdealPOS
     */
    public function getProduct($posProductId) {
        return $this->makeRequest("/products/{$posProductId}");
    }
    
    /**
     * Update inventory in IdealPOS
     */
    public function updateInventory($posProductId, $quantity) {
        return $this->makeRequest(
            "/products/{$posProductId}/inventory",
            'PUT',
            ['stock' => $quantity]
        );
    }
    
    /**
     * Log sync operation
     */
    private function logSync($type, $direction, $status, $processed, $failed, $error = null) {
        $db = Database::getInstance();
        
        $db->insert('idealpos_sync_log', [
            'sync_type' => $type,
            'direction' => $direction,
            'status' => $status,
            'records_processed' => $processed,
            'records_failed' => $failed,
            'error_message' => $error,
            'started_at' => date('Y-m-d H:i:s'),
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Generate URL slug from string
     */
    private function generateSlug($string) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
        return $slug;
    }
    
    /**
     * Get sync status
     */
    public function getSyncStatus() {
        $db = Database::getInstance();
        
        $recent = $db->fetchAll(
            "SELECT * FROM idealpos_sync_log ORDER BY created_at DESC LIMIT 10"
        );
        
        return [
            'enabled' => $this->isEnabled(),
            'recent_syncs' => $recent
        ];
    }
}
