<?php
error_log('[DemolitionTraders] api/index.php loaded, request=' . ($_GET['request'] ?? '') . ', action=' . ($_GET['action'] ?? ''));
/**
 * Main API Router
 * Handles all API requests
 */

// CRITICAL: Tắt display errors để tránh HTML output làm hỏng JSON response
ini_set('display_errors', 0);
error_reporting(0);

// Enhanced CORS headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, ngrok-skip-browser-warning, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours

// Prevent caching for API responses
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Add health check endpoint
if (isset($_GET['request']) && $_GET['request'] === 'health') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'timestamp' => time(),
        'version' => '1.0'
    ]);
    exit;
}

error_log('[DemolitionTraders] After health check, about to load bootstrap');

// Initialize secure session and load configurations
require_once __DIR__ . '/../core/bootstrap.php';
error_log('[DemolitionTraders] Bootstrap loaded');
require_once __DIR__ . '/../middleware/rate_limit.php'; // Apply rate limiting to all API requests
error_log('[DemolitionTraders] Rate limit loaded');
// require_once __DIR__ . '/../middleware/rate_limit.php'; // Temporarily disabled for debugging
require_once __DIR__ . '/../config/config.php';
error_log('[DemolitionTraders] Config loaded');
require_once __DIR__ . '/../config/database.php';
error_log('[DemolitionTraders] Database config loaded');
// Initialize a DB instance for use in API endpoints that need to update orders
$db = Database::getInstance();
error_log('[DemolitionTraders] Database instance initialized in API');
require_once __DIR__ . '/../utils/security.php'; // Include for send_json_response
error_log('[DemolitionTraders] Security utils loaded');

// Set error handling based on config (nhưng vẫn log errors, không display)
error_log('[DemolitionTraders] About to check Config::isDebug()');
try {
    $isDebug = Config::isDebug();
    error_log('[DemolitionTraders] Config::isDebug() returned: ' . ($isDebug ? 'true' : 'false'));
    error_log('[DemolitionTraders] $isDebug (var_export): ' . var_export($isDebug, true));
    error_log('[DemolitionTraders] $isDebug type: ' . gettype($isDebug));
    if ($isDebug) {
        error_reporting(E_ALL);
        error_log('[DemolitionTraders] About to enable log_errors');
        ini_set('log_errors', 1);
        error_log('[DemolitionTraders] log_errors enabled, not setting error_log path from code');
        // NOTE: Setting error_log path at runtime can cause the PHP/Apache process to fail
        // in some Windows/XAMPP configurations. Manage error_log via php.ini or Apache configuration instead.
    } else {
        error_reporting(0);
    }
} catch (Exception $e) {
    error_log('[DemolitionTraders] Exception in Config::isDebug(): ' . $e->getMessage());
    // Default to no error reporting if config fails
    error_reporting(0);
}
error_log('[DemolitionTraders] After try-catch block');
// LUÔN TẮT display_errors để đảm bảo JSON response sạch
ini_set('display_errors', 0);
error_log('[DemolitionTraders] Error handling set up');

// Helper function to send error response is now part of security.php (send_json_response)
function sendError($message, $statusCode = 400) {
    send_json_response(['error' => $message], $statusCode);
}
error_log('[DemolitionTraders] sendError function defined');

// Get request path
error_log('[DemolitionTraders] About to get request path');
$request = $_GET['request'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Parse path
$path = explode('/', trim($request, '/'));
$resource = $path[0] ?? '';
$id = $path[1] ?? $_GET['id'] ?? null; // Also check query param
$action = $path[2] ?? null;

error_log('[DemolitionTraders] Parsed: method=' . $method . ', resource=' . $resource . ', id=' . ($id ?? 'null') . ', action=' . ($action ?? 'null'));

// Get request body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Route requests
try {
    switch ($resource) {
        case 'products':
            // Handle nextid endpoint
            if ($method === 'GET' && $id === 'nextid') {
                try {
                    require_once __DIR__ . '/../config/database.php';
                    $db = Database::getInstance();
                    $row = $db->fetchOne('SELECT AUTO_INCREMENT as next_id FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "products"');
                    send_json_response(['success' => true, 'next_id' => $row['next_id'] ?? 1]);
                } catch (Exception $e) {
                    error_log("NextID Error: " . $e->getMessage());
                    sendError('Failed to get next ID: ' . $e->getMessage(), 500);
                }
            }
            
            try {
                require_once __DIR__ . '/../controllers/ProductController.php';
                $controller = new ProductController();
                
                if ($method === 'GET' && !$id) {
                    // Get all products
                    send_json_response($controller->index($_GET));
                    
                } elseif ($method === 'GET' && $id && $id !== 'nextid') {
                    // Get single product
                    send_json_response($controller->show($id));
                    
                } elseif ($method === 'POST' && !$id) {
                    // Create new product
                    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                        $result = $controller->store($_POST);
                    } else {
                        $result = $controller->store($input);
                    }
                    send_json_response($result, 201);
                    
                } elseif ($method === 'POST' && $id) {
                    // Update existing product (using POST with ID)
                    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                        $result = $controller->update($id, $_POST);
                    } else {
                        $result = $controller->update($id, $input);
                    }
                    send_json_response($result);
                    
                } elseif ($method === 'PUT' && $id) {
                    // Update existing product (standard REST)
                    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                        $result = $controller->update($id, $input);
                    } else {
                        $result = $controller->update($id, $input);
                    }
                    send_json_response($result);
                    
                } elseif ($method === 'DELETE' && $id) {
                    // Delete product
                    send_json_response($controller->delete($id));
                    
                } else {
                    sendError('Invalid product endpoint', 404);
                }
            } catch (Exception $e) {
                error_log("Product Controller Error: " . $e->getMessage());
                sendError($e->getMessage(), 500);
            }
            break;
            
        case 'categories':
            try {
                require_once __DIR__ . '/../controllers/CategoryController.php';
                $controller = new CategoryController();
                
                if ($method === 'GET' && !$id) {
                    send_json_response($controller->index());
                } elseif ($method === 'GET' && $id) {
                    send_json_response($controller->show($id));
                } elseif ($method === 'POST' && !$id) {
                    send_json_response($controller->create($input), 201);
                } elseif ($method === 'PUT' && $id) {
                    send_json_response($controller->update($id, $input));
                } elseif ($method === 'DELETE' && $id) {
                    send_json_response($controller->delete($id));
                } else {
                    sendError('Method not allowed', 405);
                }
            } catch (Exception $e) {
                error_log("Categories API Error: " . $e->getMessage());
                sendError($e->getMessage(), 500);
            }
            break;
            
        case 'cart':
            try {
                require_once __DIR__ . '/../controllers/CartController.php';
                $controller = new CartController();
                
                if ($method === 'GET' && $id === 'get') {
                    send_json_response($controller->get());
                } elseif ($method === 'POST' && $id === 'add') {
                    send_json_response($controller->add($input));
                } elseif ($method === 'PUT' && $id === 'update') {
                    send_json_response($controller->update($input));
                } elseif ($method === 'DELETE' && $id === 'remove' && $action) {
                    send_json_response($controller->remove($action));
                } elseif ($method === 'DELETE' && $id === 'clear') {
                    send_json_response($controller->clear());
                } else {
                    sendError('Method not allowed', 405);
                }
            } catch (Exception $e) {
                error_log("Cart API Error: " . $e->getMessage());
                sendError($e->getMessage(), 500);
            }
            break;
            
        case 'customers':
            try {
                require_once __DIR__ . '/../controllers/UserController.php';
                $controller = new UserController();
                
                if ($method === 'GET' && !$id) {
                    send_json_response($controller->index());
                } elseif ($method === 'GET' && $id) {
                    send_json_response($controller->show($id));
                } elseif ($method === 'PUT' && $id) {
                    send_json_response($controller->update($id, $input));
                } elseif ($method === 'DELETE' && $id) {
                    send_json_response($controller->delete($id));
                } else {
                    sendError('Method not allowed', 405);
                }
            } catch (Exception $e) {
                error_log("Customers API Error: " . $e->getMessage());
                $message = $e->getMessage();
                if (strpos($message, 'Authentication required') !== false) {
                    sendError($message, 401);
                } else {
                    sendError($message, 500);
                }
            }
            break;
            
        case 'orders':
            try {
                require_once __DIR__ . '/../controllers/OrderController.php';
                $controller = new OrderController();
                
                if ($method === 'GET' && !$id) {
                    send_json_response($controller->index());
                } elseif ($method === 'GET' && $id) {
                    send_json_response($controller->show($id));
                } elseif ($method === 'POST' && !$id) {
                    send_json_response($controller->create($input), 201);
                } elseif ($method === 'POST' && $id && $action === 'send-receipt') {
                    // Send receipt email
                    require_once __DIR__ . '/../services/EmailService.php';
                    $emailService = new EmailService();
                    $order = $controller->show($id);
                    
                    // Get customer email (billing_address may already be decoded by controller)
                    $billing = $order['billing_address'] ?? [];
                    if (is_string($billing)) {
                        $billing = json_decode($billing, true) ?? [];
                    }
                    $customerEmail = $billing['email'] ?? $order['guest_email'] ?? null;
                    
                    if (!$customerEmail) {
                        sendError('No customer email found', 400);
                    }
                    // Check if receipt already sent - allow override via force param
                    $force = ($input['force'] ?? false) === true || ($input['force'] ?? '') === 'true';
                    if (!empty($order['receipt_sent_at']) && !$force) {
                        // Already sent - return success with a note
                        send_json_response(['success' => true, 'message' => 'Receipt already sent', 'already_sent' => true, 'sent_at' => $order['receipt_sent_at']]);
                    }

                    // For admin-initiated sends, force send to the actual customer even when dev_mode is true
                    $result = $emailService->sendReceipt($order, $customerEmail, true);
                    if ($result['success']) {
                        // Update database to record a sent timestamp
                        try {
                            $db->query('UPDATE orders SET receipt_sent_at = NOW() WHERE id = :id', ['id' => $id]);
                        } catch (Exception $ex) {
                            error_log('Failed to update receipt_sent_at for order ' . $id . ': ' . $ex->getMessage());
                        }
                        send_json_response($result);
                    } else {
                        sendError($result['error'], 500);
                    }
                } elseif ($method === 'POST' && $id && $action === 'send-tax-invoice') {
                    // Send tax invoice email
                    require_once __DIR__ . '/../services/EmailService.php';
                    $emailService = new EmailService();
                    $order = $controller->show($id);
                    // Get customer email (billing_address may already be decoded by controller)
                    $billing = $order['billing_address'] ?? [];
                    if (is_string($billing)) {
                        $billing = json_decode($billing, true) ?? [];
                    }
                    $customerEmail = $billing['email'] ?? $order['guest_email'] ?? null;
                    if (!$customerEmail) {
                        sendError('No customer email found', 400);
                    }
                    error_log('[DemolitionTraders] About to call sendTaxInvoice (admin forced)');
                    // Check if tax invoice already sent - allow override via force param
                    $force = ($input['force'] ?? false) === true || ($input['force'] ?? '') === 'true';
                    if (!empty($order['tax_invoice_sent_at']) && !$force) {
                        send_json_response(['success' => true, 'message' => 'Tax invoice already sent', 'already_sent' => true, 'sent_at' => $order['tax_invoice_sent_at']]);
                    }
                    // For admin-initiated sends, bypass dev_mode so email goes to the actual customer
                    $result = $emailService->sendTaxInvoice($order, $customerEmail, true);
                    error_log('[DemolitionTraders] sendTaxInvoice returned: ' . print_r($result, true));
                    if ($result['success']) {
                        // Update tax_invoice_sent_at
                        try {
                            $db->query('UPDATE orders SET tax_invoice_sent_at = NOW() WHERE id = :id', ['id' => $id]);
                        } catch (Exception $ex) {
                            error_log('Failed to update tax_invoice_sent_at for order ' . $id . ': ' . $ex->getMessage());
                        }
                        send_json_response($result);
                    } else {
                        sendError($result['error'], 500);
                    }
                } elseif ($method === 'PUT' && $id) {
                    send_json_response($controller->update($id, $input));
                } elseif ($method === 'DELETE' && $id) {
                    send_json_response($controller->delete($id));
                } else {
                    sendError('Method not allowed', 405);
                }
            } catch (Exception $e) {
                error_log("Orders API Error: " . $e->getMessage());
                $message = $e->getMessage();
                // Return appropriate status code for authentication errors
                if (strpos($message, 'Authentication required') !== false) {
                    sendError($message, 401);
                } else {
                    sendError($message, 500);
                }
            }
            break;
            
        case 'auth':
            try {
                require_once __DIR__ . '/../controllers/AuthController.php';
                $controller = new AuthController();
                
                if ($method === 'POST' && $id === 'login') {
                    send_json_response($controller->login($input));
                } elseif ($method === 'POST' && $id === 'register') {
                    send_json_response($controller->register($input), 201);
                } elseif ($method === 'POST' && $id === 'logout') {
                    send_json_response($controller->logout());
                } elseif ($method === 'GET' && $id === 'me') {
                    send_json_response($controller->me());
                } else {
                    sendError('Method not allowed', 405);
                }
            } catch (Exception $e) {
                error_log("Auth API Error: " . $e->getMessage());
                sendError($e->getMessage(), 500);
            }
            break;
            
        case 'idealpos':
            try {
                require_once __DIR__ . '/../controllers/IdealPOSController.php';
                $controller = new IdealPOSController();
                
                if ($method === 'GET' && $id === 'sync-products') {
                    send_json_response($controller->syncProducts());
                } elseif ($method === 'GET' && $id === 'sync-inventory') {
                    send_json_response($controller->syncInventory());
                } elseif ($method === 'POST' && $id === 'push-order' && $action) {
                    send_json_response($controller->pushOrder($action));
                } elseif ($method === 'GET' && $id === 'status') {
                    send_json_response($controller->status());
                } else {
                    sendError('Method not allowed', 405);
                }
            } catch (Exception $e) {
                error_log("IdealPOS API Error: " . $e->getMessage());
                sendError($e->getMessage(), 500);
            }
            break;
            
        case 'wishlist':
            try {
                require_once __DIR__ . '/../controllers/WishlistController.php';
                $controller = new WishlistController();
                
                if ($method === 'GET') {
                    send_json_response($controller->index());
                } elseif ($method === 'POST' && $action === 'add') {
                    send_json_response($controller->add($input));
                } elseif ($method === 'DELETE' && $id) {
                    send_json_response($controller->remove($id));
                } else {
                    sendError('Method not allowed', 405);
                }
            } catch (Exception $e) {
                error_log("Wishlist API Error: " . $e->getMessage());
                sendError($e->getMessage(), 500);
            }
            break;
            
        case 'search':
            try {
                require_once __DIR__ . '/../controllers/SearchController.php';
                $controller = new SearchController();
                send_json_response($controller->search($_GET));
            } catch (Exception $e) {
                error_log("Search API Error: " . $e->getMessage());
                sendError($e->getMessage(), 500);
            }
            break;
            
        default:
            sendError('Resource not found', 404);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendError($e->getMessage(), 500);
}