<?php
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

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Start session if not already started
ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error handling based on config (nhưng vẫn log errors, không display)
if (Config::isDebug()) {
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    error_reporting(0);
}
// LUÔN TẮT display_errors để đảm bảo JSON response sạch
ini_set('display_errors', 0);

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function to send error response
function sendError($message, $statusCode = 400) {
    sendResponse(['error' => $message], $statusCode);
}

// Get request path
$request = $_GET['request'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Parse path
$path = explode('/', trim($request, '/'));
$resource = $path[0] ?? '';
$id = $path[1] ?? $_GET['id'] ?? null; // Also check query param
$action = $path[2] ?? null;

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
                    sendResponse(['success' => true, 'next_id' => $row['next_id'] ?? 1]);
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
                    sendResponse($controller->index($_GET));
                    
                } elseif ($method === 'GET' && $id && $id !== 'nextid') {
                    // Get single product
                    sendResponse($controller->show($id));
                    
                } elseif ($method === 'POST' && !$id) {
                    // Create new product
                    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                        $result = $controller->store($_POST);
                    } else {
                        $result = $controller->store($input);
                    }
                    sendResponse($result, 201);
                    
                } elseif ($method === 'POST' && $id) {
                    // Update existing product (using POST with ID)
                    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                        $result = $controller->update($id, $_POST);
                    } else {
                        $result = $controller->update($id, $input);
                    }
                    sendResponse($result);
                    
                } elseif ($method === 'PUT' && $id) {
                    // Update existing product (standard REST)
                    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                        $result = $controller->update($id, $input);
                    } else {
                        $result = $controller->update($id, $input);
                    }
                    sendResponse($result);
                    
                } elseif ($method === 'DELETE' && $id) {
                    // Delete product
                    sendResponse($controller->delete($id));
                    
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
                    sendResponse($controller->index());
                } elseif ($method === 'GET' && $id) {
                    sendResponse($controller->show($id));
                } elseif ($method === 'POST' && !$id) {
                    sendResponse($controller->create($input), 201);
                } elseif ($method === 'PUT' && $id) {
                    sendResponse($controller->update($id, $input));
                } elseif ($method === 'DELETE' && $id) {
                    sendResponse($controller->delete($id));
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
                    sendResponse($controller->get());
                } elseif ($method === 'POST' && $id === 'add') {
                    sendResponse($controller->add($input));
                } elseif ($method === 'PUT' && $id === 'update') {
                    sendResponse($controller->update($input));
                } elseif ($method === 'DELETE' && $id === 'remove' && $action) {
                    sendResponse($controller->remove($action));
                } elseif ($method === 'DELETE' && $id === 'clear') {
                    sendResponse($controller->clear());
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
                    sendResponse($controller->index());
                } elseif ($method === 'GET' && $id) {
                    sendResponse($controller->show($id));
                } elseif ($method === 'PUT' && $id) {
                    sendResponse($controller->update($id, $input));
                } elseif ($method === 'DELETE' && $id) {
                    sendResponse($controller->delete($id));
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
                    sendResponse($controller->index());
                } elseif ($method === 'GET' && $id) {
                    sendResponse($controller->show($id));
                } elseif ($method === 'POST' && !$id) {
                    sendResponse($controller->create($input), 201);
                } elseif ($method === 'POST' && $id && $action === 'send-receipt') {
                    // Send receipt email
                    try {
                        require_once __DIR__ . '/../services/EmailService.php';
                        $emailService = new EmailService();
                        $order = $controller->show($id);
                        
                        // Get customer email
                        $billing = json_decode($order['billing_address'], true);
                        $customerEmail = $billing['email'] ?? $order['guest_email'] ?? null;
                        
                        if (!$customerEmail) {
                            sendError('No customer email found', 400);
                        }
                        
                        $result = $emailService->sendReceipt($order, $customerEmail);
                        if ($result['success']) {
                            sendResponse($result);
                        } else {
                            sendError($result['error'], 500);
                        }
                    } catch (Exception $e) {
                        error_log("Send receipt error: " . $e->getMessage());
                        sendError('Failed to send receipt: ' . $e->getMessage(), 500);
                    }
                } elseif ($method === 'POST' && $id && $action === 'send-tax-invoice') {
                    // Send tax invoice email
                    try {
                        require_once __DIR__ . '/../services/EmailService.php';
                        $emailService = new EmailService();
                        $order = $controller->show($id);
                        // Get customer email
                        $billing = json_decode($order['billing_address'], true);
                        $customerEmail = $billing['email'] ?? $order['guest_email'] ?? null;
                        if (!$customerEmail) {
                            sendError('No customer email found', 400);
                        }
                        $result = $emailService->sendTaxInvoice($order, $customerEmail);
                        if ($result['success']) {
                            sendResponse($result);
                        } else {
                            sendError($result['error'], 500);
                        }
                    } catch (Exception $e) {
                        error_log("Send tax invoice error: " . $e->getMessage());
                        sendError('Failed to send tax invoice: ' . $e->getMessage(), 500);
                    }
                } elseif ($method === 'PUT' && $id) {
                    sendResponse($controller->update($id, $input));
                } elseif ($method === 'DELETE' && $id) {
                    sendResponse($controller->delete($id));
                } else {
                    sendError('Method not allowed', 405);
                }
            } catch (Exception $e) {
                error_log("Orders API Error: " . $e->getMessage());
                error_log("Orders API Stack Trace: " . $e->getTraceAsString());
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
                    sendResponse($controller->login($input));
                } elseif ($method === 'POST' && $id === 'register') {
                    sendResponse($controller->register($input), 201);
                } elseif ($method === 'POST' && $id === 'logout') {
                    sendResponse($controller->logout());
                } elseif ($method === 'GET' && $id === 'me') {
                    sendResponse($controller->me());
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
                    sendResponse($controller->syncProducts());
                } elseif ($method === 'GET' && $id === 'sync-inventory') {
                    sendResponse($controller->syncInventory());
                } elseif ($method === 'POST' && $id === 'push-order' && $action) {
                    sendResponse($controller->pushOrder($action));
                } elseif ($method === 'GET' && $id === 'status') {
                    sendResponse($controller->status());
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
                    sendResponse($controller->index());
                } elseif ($method === 'POST' && $action === 'add') {
                    sendResponse($controller->add($input));
                } elseif ($method === 'DELETE' && $id) {
                    sendResponse($controller->remove($id));
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
                sendResponse($controller->search($_GET));
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