<?php
/**
 * IdealPOS Controller
 * Handles IdealPOS sync operations
 */

require_once __DIR__ . '/../services/IdealPOSService.php';

class IdealPOSController {
    private $service;
    
    public function __construct() {
        $this->service = new IdealPOSService();
        
        // Check admin authorization (session already started in API index.php)
        if (empty($_SESSION['is_admin'])) {
            throw new Exception('Admin access required');
        }
    }
    
    /**
     * Sync products from IdealPOS
     */
    public function syncProducts() {
        return $this->service->syncProducts();
    }
    
    /**
     * Sync inventory from IdealPOS
     */
    public function syncInventory() {
        return $this->service->syncInventory();
    }
    
    /**
     * Push order to IdealPOS
     */
    public function pushOrder($orderId) {
        return $this->service->pushOrder($orderId);
    }
    
    /**
     * Get sync status
     */
    public function status() {
        return $this->service->getSyncStatus();
    }
}
