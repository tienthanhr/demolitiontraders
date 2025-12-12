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
$action = $path[2] ?? $_GET['action'] ?? null;

error_log('[DemolitionTraders] Parsed: method=' . $method . ', resource=' . $resource . ', id=' . ($id ?? 'null') . ', action=' . ($action ?? 'null'));

// Get request body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Route requests
try {
    // Session info endpoint: Returns session info if logged in, otherwise 401
    if ($resource === 'session') {
        header('Content-Type: application/json');
        $sessInfo = ['is_admin' => $_SESSION['is_admin'] ?? false, 'user_id' => $_SESSION['user_id'] ?? null];
        if (!isset($_SESSION['user_id'])) {
            sendError('Authentication required', 401);
        }
        send_json_response(['success' => true, 'session' => $sessInfo]);
    }
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
                
                if ($method === 'GET' && (($id === 'revenue') || ($action === 'revenue' && !$id))) {
                    // admin-only revenue totals for a specific period or custom range
                    try {
                        // Log minimal session information for debugging (do not log full session)
                        $sessInfo = ['is_admin' => $_SESSION['is_admin'] ?? false, 'user_id' => $_SESSION['user_id'] ?? null];
                        error_log('[DemolitionTraders] resend-email session: ' . json_encode($sessInfo));
                        if (!(($_SESSION['is_admin'] ?? false) || ($_SESSION['role'] ?? '') === 'admin')) {
                            sendError('Unauthorized: Admin required', 401);
                        }
                        $period = $_GET['period'] ?? 'all';
                        $from = $_GET['from'] ?? null;
                        $to = $_GET['to'] ?? null;

                        $params = [];
                        $where = "WHERE status IN ('paid','processing','shipped','delivered')";
                        if ($period === 'today') {
                            $where .= " AND created_at >= :from AND created_at <= :to";
                            $params['from'] = date('Y-m-d 00:00:00');
                            $params['to'] = date('Y-m-d 23:59:59');
                        } elseif ($period === 'yesterday') {
                            $y = new DateTime('yesterday');
                            $params['from'] = $y->format('Y-m-d 00:00:00');
                            $params['to'] = $y->format('Y-m-d 23:59:59');
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        } elseif ($period === 'this_week') {
                            $start = new DateTime();
                            $start->setTime(0,0,0);
                            $start->modify('-' . $start->format('w') . ' days');
                            $params['from'] = $start->format('Y-m-d 00:00:00');
                            $params['to'] = (new DateTime())->format('Y-m-d 23:59:59');
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        } elseif ($period === 'this_month') {
                            $start = new DateTime('first day of this month');
                            $params['from'] = $start->format('Y-m-d 00:00:00');
                            $params['to'] = (new DateTime())->format('Y-m-d 23:59:59');
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        } elseif ($period === 'this_year') {
                            $start = new DateTime('first day of January this year');
                            $params['from'] = $start->format('Y-m-d 00:00:00');
                            $params['to'] = (new DateTime())->format('Y-m-d 23:59:59');
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        } elseif ($period === 'custom' && $from && $to) {
                            $params['from'] = substr($from, 0, 10) . ' 00:00:00';
                            $params['to'] = substr($to, 0, 10) . ' 23:59:59';
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        }

                        $sql = "SELECT COALESCE(SUM(total_amount),0) as total FROM orders " . $where;
                        $row = $db->fetchOne($sql, $params);
                        $total = floatval($row['total'] ?? 0);
                        send_json_response(['success' => true, 'total' => $total]);
                    } catch (Exception $e) {
                        error_log('Failed to compute revenue: ' . $e->getMessage());
                        sendError('Failed to compute revenue', 500);
                    }
                } elseif ($method === 'GET' && !$id) {
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
                    // Get single category by id or slug
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

                // Revenue summary endpoint (admin only)
                if ($method === 'GET' && (($id === 'revenue') || ($action === 'revenue' && !$id))) {
                    try {
                        if (!(($_SESSION['is_admin'] ?? false) || ($_SESSION['role'] ?? '') === 'admin')) {
                            sendError('Unauthorized: Admin required', 401);
                        }
                        $period = $_GET['period'] ?? 'all';
                        $from = $_GET['from'] ?? null;
                        $to = $_GET['to'] ?? null;
                        $params = [];
                        $where = "WHERE status IN ('paid','processing','shipped','delivered')";
                        if ($period === 'today') {
                            $where .= " AND created_at >= :from AND created_at <= :to";
                            $params['from'] = date('Y-m-d 00:00:00');
                            $params['to'] = date('Y-m-d 23:59:59');
                        } elseif ($period === 'yesterday') {
                            $y = new DateTime('yesterday');
                            $params['from'] = $y->format('Y-m-d 00:00:00');
                            $params['to'] = $y->format('Y-m-d 23:59:59');
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        } elseif ($period === 'this_week') {
                            $start = new DateTime();
                            $start->setTime(0,0,0);
                            $start->modify('-' . $start->format('w') . ' days');
                            $params['from'] = $start->format('Y-m-d 00:00:00');
                            $params['to'] = (new DateTime())->format('Y-m-d 23:59:59');
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        } elseif ($period === 'this_month') {
                            $start = new DateTime('first day of this month');
                            $params['from'] = $start->format('Y-m-d 00:00:00');
                            $params['to'] = (new DateTime())->format('Y-m-d 23:59:59');
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        } elseif ($period === 'this_year') {
                            $start = new DateTime('first day of January this year');
                            $params['from'] = $start->format('Y-m-d 00:00:00');
                            $params['to'] = (new DateTime())->format('Y-m-d 23:59:59');
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        } elseif ($period === 'custom' && $from && $to) {
                            $params['from'] = substr($from, 0, 10) . ' 00:00:00';
                            $params['to'] = substr($to, 0, 10) . ' 23:59:59';
                            $where .= " AND created_at >= :from AND created_at <= :to";
                        }
                        $sql = "SELECT COALESCE(SUM(total_amount),0) as total FROM orders " . $where;
                        $row = $db->fetchOne($sql, $params);
                        $total = floatval($row['total'] ?? 0);
                        send_json_response(['success' => true, 'total' => $total]);
                    } catch (Exception $e) {
                        error_log('Failed to compute revenue: ' . $e->getMessage());
                        sendError('Failed to compute revenue', 500);
                    }
                } elseif ($method === 'GET' && !$id) {
                    send_json_response($controller->index());
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
                    $triggeredBy = $_SESSION['user_id'] ?? null;
                    $result = $emailService->sendReceipt($order, $customerEmail, true, $triggeredBy);
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
                    $triggeredBy = $_SESSION['user_id'] ?? null;
                    try {
                        $result = $emailService->sendTaxInvoice($order, $customerEmail, true, $triggeredBy);
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
                    } catch (Throwable $te) {
                        error_log('sendTaxInvoice fatal for order ' . $id . ': ' . $te->getMessage());
                        sendError('Failed to send tax invoice: ' . $te->getMessage(), 500);
                    }
                } elseif ($method === 'PUT' && $id) {
                    send_json_response($controller->update($id, $input));
                } elseif ($method === 'GET' && $id && $action === 'email-logs') {
                    // Return email logs for a specific order
                    try {
                        // Authorization: only admins can view logs
                        if (!(($_SESSION['is_admin'] ?? false) || ($_SESSION['role'] ?? '') === 'admin')) {
                            sendError('Unauthorized: Admin required', 401);
                        }
                        $logs = $db->fetchAll('SELECT el.*, CONCAT(u.first_name, " ", u.last_name) as triggered_by_name FROM email_logs el LEFT JOIN users u ON u.id = el.user_id WHERE el.order_id = :id ORDER BY el.id DESC', ['id' => $id]);
                        send_json_response(['success' => true, 'logs' => $logs]);
                    } catch (Exception $e) {
                        error_log('Failed to fetch email logs for order ' . $id . ': ' . $e->getMessage());
                        $msg = $e->getMessage();
                        // Detect missing table errors (MySQL: 1146, PostgreSQL: does not exist)
                        if (strpos($msg, '1146') !== false || stripos($msg, 'does not exist') !== false || stripos($msg, 'doesn\'t exist') !== false) {
                            sendError('Failed to fetch email logs: table "email_logs" does not exist. Run database/add-email-logs.sql or use the provided `database/ensure-email-logs.php` script.', 500);
                        }
                        sendError('Failed to fetch email logs: ' . $msg, 500);
                    }
                } elseif ($method === 'POST' && $id && $action === 'resend-email') {
                    // Resend an email from a logged email_log (requires admin privileges)
                    try {
                        $rawInput = file_get_contents('php://input');
                        error_log('[DemolitionTraders] resend-email raw input: ' . var_export($rawInput, true));
                        $input = json_decode($rawInput, true) ?? $_POST ?? [];
                        $logId = $input['log_id'] ?? null;
                        $resendReason = trim($input['resend_reason'] ?? '');
                        if ($resendReason === '') $resendReason = null;
                        if ($resendReason && strlen($resendReason) > 1000) {
                            sendError('resend_reason too long', 400);
                        }
                        if (!$logId) {
                            sendError('log_id is required', 400);
                        }

                        $log = $db->fetchOne('SELECT * FROM email_logs WHERE id = :id', ['id' => $logId]);
                        if (!$log) {
                            sendError('Log not found', 404);
                        }
                        if ($log['order_id'] != $id) {
                            sendError('Log does not belong to order', 400);
                        }

                        // Authorization: only admins can resend
                        if (!(($_SESSION['is_admin'] ?? false) || ($_SESSION['role'] ?? '') === 'admin')) {
                            sendError('Unauthorized: Admin required', 401);
                        }

                        require_once __DIR__ . '/../services/EmailService.php';
                        $emailService = new EmailService();
                        $order = $controller->show($id);
                        $userId = $_SESSION['user_id'] ?? null;

                        // Allow admin to override recipient when resending; fallback to original logged to_email
                        $toEmail = trim($input['to_email'] ?? $log['to_email']);
                        if ($toEmail === '') $toEmail = $log['to_email'];
                        // Validate the email if provided
                        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                            sendError('Invalid to_email', 400);
                        }
                        $type = $log['type'];
                        $result = ['success' => false, 'message' => 'Unknown type'];
                        if ($type === 'tax_invoice') {
                            $result = $emailService->sendTaxInvoice($order, $toEmail, true, $userId, $resendReason);
                        } elseif ($type === 'receipt') {
                            $result = $emailService->sendReceipt($order, $toEmail, true, $userId, $resendReason);
                        } else {
                            // Generic resend using sendEmail
                            $subject = $log['subject'] ?? 'Resend Email';
                            $body = $log['response'] ?? $subject;
                            // For admin-initiated resends, force sending to the provided email address even in dev_mode
                            $ok = $emailService->sendEmail($toEmail, $subject, $body, '', true);
                            $result = ['success' => $ok, 'message' => $ok ? 'Email re-sent' : 'Failed to re-send'];
                        }

                        if ($result['success']) {
                            send_json_response(['success' => true, 'message' => 'Re-sent successfully']);
                        } else {
                            sendError($result['error'] ?? 'Failed to re-send', 500);
                        }
                    } catch (Exception $e) {
                        error_log('Resend email error: ' . $e->getMessage());
                        $msg = $e->getMessage();
                        if (strpos($msg, '1146') !== false || stripos($msg, 'does not exist') !== false || stripos($msg, 'doesn\'t exist') !== false) {
                            sendError('Email logs table missing: run database/add-email-logs.sql (or `php database/ensure-email-logs.php`) to create it.', 500);
                        }
                        sendError($msg, 500);
                    }
                } elseif ($method === 'GET' && $id) {
                    send_json_response($controller->show($id));
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
        case 'probe-smtp':
            try {
                // Publicly accessible, low-sensitivity probe endpoint to test SMTP connectivity
                require_once __DIR__ . '/../config/email.php';
                $emailCfg = require __DIR__ . '/../config/email.php';
                $host = $emailCfg['smtp_host'] ?? 'smtp.office365.com';
                $port = (int)($emailCfg['smtp_port'] ?? 587);
                $timeout = 5;
                $resolvedIp = gethostbyname($host);
                // Try fsockopen
                $fp_ok = false; $fp_msg = '';
                $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
                if ($fp) { $fp_ok = true; $fp_msg = 'OK'; fclose($fp); } else { $fp_ok = false; $fp_msg = "$errno - $errstr"; }
                // Try stream_socket_client
                $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
                $fp2_ok = false; $fp2_msg = '';
                $fp2 = @stream_socket_client("tcp://$host:$port", $errno2, $errstr2, $timeout, STREAM_CLIENT_CONNECT, $ctx);
                if ($fp2) { $fp2_ok = true; $fp2_msg = 'OK'; fclose($fp2); } else { $fp2_ok = false; $fp2_msg = "$errno2 - $errstr2"; }
                send_json_response([ 'success' => true, 'host'=>$host, 'port'=>$port, 'resolved_ip'=>$resolvedIp, 'fsockopen'=>['ok'=>$fp_ok,'msg'=>$fp_msg], 'stream_socket_client'=>['ok'=>$fp2_ok,'msg'=>$fp2_msg] ]);
            } catch (Exception $e) {
                error_log('Probe SMTP Error: ' . $e->getMessage());
                sendError('SMTP probe failed: ' . $e->getMessage(), 500);
            }
            break;
        case 'admin':
            try {
                // Simple admin-only diagnostics endpoints
                if (!(($_SESSION['is_admin'] ?? false) || ($_SESSION['role'] ?? '') === 'admin')) {
                    sendError('Unauthorized: Admin required', 401);
                }
                if ($method === 'GET' && ($id === 'probe-smtp' || $action === 'probe-smtp')) {
                    require_once __DIR__ . '/../config/email.php';
                    $emailCfg = require __DIR__ . '/../config/email.php';
                    $host = $emailCfg['smtp_host'] ?? 'smtp.office365.com';
                    $port = (int)($emailCfg['smtp_port'] ?? 587);
                    $timeout = 5;
                    $resolvedIp = gethostbyname($host);
                    // Try fsockopen
                    $fp_ok = false; $fp_msg = '';
                    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
                    if ($fp) {
                        $fp_ok = true;
                        $fp_msg = 'OK';
                        fclose($fp);
                    } else {
                        $fp_ok = false;
                        $fp_msg = "$errno - $errstr";
                    }
                    // Try stream_socket_client
                    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
                    $fp2_ok = false; $fp2_msg = '';
                    $fp2 = @stream_socket_client("tcp://$host:$port", $errno2, $errstr2, $timeout, STREAM_CLIENT_CONNECT, $ctx);
                    if ($fp2) {
                        $fp2_ok = true;
                        $fp2_msg = 'OK';
                        fclose($fp2);
                    } else {
                        $fp2_ok = false;
                        $fp2_msg = "$errno2 - $errstr2";
                    }
                    send_json_response([
                        'success' => true,
                        'host' => $host,
                        'port' => $port,
                        'resolved_ip' => $resolvedIp,
                        'fsockopen' => ['ok' => $fp_ok, 'msg' => $fp_msg],
                        'stream_socket_client' => ['ok' => $fp2_ok, 'msg' => $fp2_msg],
                    ]);
                }
            } catch (Exception $e) {
                error_log('Admin API Error: ' . $e->getMessage());
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
