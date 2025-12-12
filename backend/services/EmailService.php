<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $config;
    private $mailer;
    private $debugLog = '';

    /**
     * Decode address JSON that may have been HTML-escaped by escape_output()
     */
    private function decodeAddress($addressJson) {
        if (is_array($addressJson)) {
            return $addressJson;
        }

        if (!$addressJson) {
            return [];
        }

        // First try normal decode
        $decoded = json_decode($addressJson, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // If the JSON was escaped (e.g. &quot;), decode HTML entities then try again
        $decoded = json_decode(html_entity_decode($addressJson, ENT_QUOTES, 'UTF-8'), true);
        return is_array($decoded) ? $decoded : [];
    }

    public function __construct() {
        $this->config = require __DIR__ . '/../config/email.php';
        // Test PHP error logging
        error_log("[DemolitionTraders] EmailService.php loaded at " . date('Y-m-d H:i:s'));
        error_log('[DemolitionTraders] BREVO_API_KEY loaded: ' . ($this->config['brevo_api_key'] ?? 'NULL'));
        // Initialize PHPMailer
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/pdf_invoice.php';
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }
    
    /**
     * Try to send via PHPMailer with retries to handle transient network issues
     * Returns array ['success' => bool, 'error' => string|null]
     */
    private function smtpSendWithRetries($maxAttempts = 3, $delaySeconds = 1) {
        $attempt = 0;
        $lastError = null;
        while ($attempt < $maxAttempts) {
            $attempt++;
            $startTs = microtime(true);
            try {
                if ($this->mailer->send()) {
                    $duration = round((microtime(true) - $startTs) * 1000);
                    error_log("[DemolitionTraders] smtpSendWithRetries attempt $attempt succeeded in ${duration}ms");
                    return ['success' => true, 'error' => null, 'attempts' => $attempt];
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                $duration = round((microtime(true) - $startTs) * 1000);
                error_log('[DemolitionTraders] smtpSendWithRetries attempt ' . $attempt . ' failed in ' . $duration . 'ms: ' . $lastError);
            }
            // Wait before retry (don't block too long)
            if ($attempt < $maxAttempts) {
                sleep($delaySeconds);
            }
        }
        return ['success' => false, 'error' => $lastError, 'attempts' => $attempt];
    }
    
    /**
     * Setup PHPMailer configuration
     */
    private function setupMailer() {
        try {
            // Nothing to send here - just setup PHPMailer. Keep fromEmail and flags available for send time.
            $fromEmail = $this->config['force_from_email'] ?? $this->config['from_email'] ?? $this->config['smtp_username'];
            $preferBrevo = !empty($this->config['prefer_brevo']) && !empty($this->config['brevo_api_key']);
            $allowBrevoFallback = !empty($this->config['allow_brevo_fallback']) && !empty($this->config['brevo_api_key']);
            // Check if email is configured
            if (empty($this->config['smtp_username']) || empty($this->config['smtp_password'])) {
                error_log("Email service disabled - SMTP credentials not configured");
                $this->config['enabled'] = false;
                return;
            }
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = $this->config['smtp_host'];
            $this->mailer->SMTPAuth   = $this->config['smtp_auth'];
            $this->mailer->Username   = $this->config['smtp_username'];
            $this->mailer->Password   = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = $this->config['smtp_secure'];
            $this->mailer->Port       = $this->config['smtp_port'];
            
            // Add timeouts to prevent hanging
            $this->mailer->Timeout    = 10;
            // Enable SMTP debug if configured. Use SMTP_DEBUG env=1 for verbose logs.
            $smtpDebug = (bool)($_ENV['SMTP_DEBUG'] ?? false);
            $this->mailer->SMTPDebug  = $smtpDebug ? 2 : 0;

            // Capture debug output so we can store it in the email_logs and application logs
            $this->debugLog = '';
            $this->mailer->Debugoutput = function($str, $level) {
                try {
                    $message = sprintf("[PHPMailer DEBUG-%s] %s", $level, trim($str));
                    error_log($message);
                    // keep the last 64k of debug info to avoid huge storage
                    if (isset($this->debugLog)) {
                        $this->debugLog .= $message . "\n";
                        $this->debugLog = substr($this->debugLog, -65536);
                    }
                } catch (Exception $e) {
                    // Avoid any debug logging errors preventing email sends
                }
            };
            
            // Fix for SSL certificate issues in some environments
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // From. Allow forced from email via env (e.g., FORCE_FROM_EMAIL)
            $fromEmail = $this->config['force_from_email'] ?? $this->config['from_email'] ?? $this->config['smtp_username'];
            $this->mailer->setFrom($fromEmail, $this->config['from_name']);
            // Set envelope sender to authenticated user to avoid SendAsDenied errors with Exchange
            $this->mailer->Sender = $this->config['smtp_username'];
            if (!empty($fromEmail) && !empty($this->config['smtp_username']) && strtolower($fromEmail) !== strtolower($this->config['smtp_username'])) {
                error_log('[DemolitionTraders] Warning: from_email (' . $fromEmail . ') does not match SMTP username (' . $this->config['smtp_username'] . '). This may trigger SendAsDenied on some SMTP providers.');
            }
            $this->mailer->addReplyTo($this->config['reply_to'], $this->config['from_name']);
            
            // Content type
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("PHPMailer setup error: " . $e->getMessage());
        }
    }
    
    /**
     * Send email via Brevo API (HTTP) to bypass SMTP blocks
     */
    private function sendViaBrevoApi($toEmail, $toName, $subject, $htmlContent, $attachments = []) {
        $apiKey = $this->config['brevo_api_key'] ?? null;
        if (!$apiKey) {
            return false;
        }

        $url = 'https://api.brevo.com/v3/smtp/email';
        $fromEmail = $this->config['force_from_email'] ?? $this->config['from_email'] ?? $this->config['smtp_username'];
        $data = [
            'sender' => ['name' => $this->config['from_name'], 'email' => $fromEmail],
            'to' => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];
        
        // Handle attachments
        if (!empty($attachments)) {
            $data['attachment'] = [];
            foreach ($attachments as $path => $name) {
                if (file_exists($path)) {
                    $content = base64_encode(file_get_contents($path));
                    $data['attachment'][] = ['content' => $content, 'name' => $name];
                }
            }
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'http_code' => $httpCode, 'response' => $response];
        }
        
        error_log("Brevo API Error ($httpCode): " . $response . " Curl Error: " . $curlError);
        throw new Exception("Brevo API failed with status $httpCode: $response");
    }

    /**
     * Log outgoing email to database for auditing
     */
    private function logEmail($payload) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();
            // If the email_logs table doesn't exist, try to create it using the migration SQL
            if (!$db->tableExists('email_logs')) {
                try {
                    $migrationFile = __DIR__ . '/../../database/add-email-logs.sql';
                    if (file_exists($migrationFile)) {
                        $sql = file_get_contents($migrationFile);
                        // Basic split and execute
                        $statements = preg_split('/;\s*\n/', $sql);
                        foreach ($statements as $stmt) {
                            $s = trim($stmt);
                            if (!$s) continue;
                            try {
                                $db->query($s);
                            } catch (Exception $e) {
                                // If create failed, log and continue - a subsequent insert will still fail and be caught
                                error_log('[DemolitionTraders] Failed to run email_logs migration: ' . $e->getMessage());
                            }
                        }
                    }
                } catch (Exception $migrEx) {
                    error_log('[DemolitionTraders] Migration for email_logs failed: ' . $migrEx->getMessage());
                }
            }
            // Insert fields mapping
            $data = [
                'order_id' => $payload['order_id'] ?? null,
                'user_id' => $payload['user_id'] ?? null,
                'type' => $payload['type'] ?? null,
                'send_method' => $payload['send_method'] ?? null,
                'to_email' => $payload['to_email'] ?? null,
                'from_email' => $payload['from_email'] ?? ($this->config['smtp_username'] ?? null),
                'subject' => $payload['subject'] ?? null,
                'status' => $payload['status'] ?? null,
                'error_message' => $payload['error_message'] ?? null,
                'response' => $payload['response'] ?? null,
                'resend_reason' => $payload['resend_reason'] ?? null,
            ];
            try {
                // Ensure the insert only includes columns that exist to avoid fatal SQL errors
                try {
                    $existingColumns = $db->fetchAll("SHOW COLUMNS FROM email_logs");
                    $colNames = [];
                    foreach ($existingColumns as $c) {
                        $colNames[] = $c['Field'] ?? $c['field'] ?? null;
                    }
                } catch (Exception $colEx) {
                    // If the columns query fails (e.g., table missing), attempt migration then proceed
                    $colNames = [];
                }
                if (!empty($colNames)) {
                    foreach (array_keys($data) as $k) {
                        if (!in_array($k, $colNames)) {
                            unset($data[$k]);
                        }
                    }
                }
                $db->insert('email_logs', $data);
            } catch (Exception $e) {
                $msg = $e->getMessage();
                // If it fails because the column doesn't exist, try to add column and retry once
                if (stripos($msg, 'Unknown column') !== false || stripos($msg, 'does not exist') !== false) {
                    error_log('[DemolitionTraders] logEmail insert failed due to missing column, attempting to add resend_reason: ' . $msg);
                    try {
                        // Check if column exists (MySQL)
                        $col = null;
                        try {
                            $col = $db->fetchOne("SHOW COLUMNS FROM email_logs LIKE 'resend_reason'");
                        } catch (Exception $e2) {
                            // ignore - fallback to information_schema query
                        }
                        if (empty($col)) {
                            // Add the column (MySQL)
                            $db->query("ALTER TABLE email_logs ADD COLUMN resend_reason TEXT NULL");
                        }
                    } catch (Exception $alterEx) {
                        error_log('[DemolitionTraders] Failed to add resend_reason column: ' . $alterEx->getMessage());
                    }
                    // Retry insert once
                    try {
                        $db->insert('email_logs', $data);
                    } catch (Exception $insertEx) {
                        error_log('[DemolitionTraders] Retry insert into email_logs failed: ' . $insertEx->getMessage());
                        throw $insertEx; // rethrow after retry
                    }
                } else {
                    throw $e; // not a missing column error
                }
            }
        } catch (Exception $e) {
            error_log('Failed to write email log: ' . $e->getMessage());
        }
    }

    /**
     * Send Tax Invoice email
     */
    public function sendTaxInvoice($order, $customerEmail, $forceSendToCustomer = false, $triggeredBy = null, $resendReason = null) {
        error_log('[DemolitionTraders] sendTaxInvoice called');
        if (!$this->config['enabled']) {
            return ['success' => false, 'error' => 'Email sending is disabled'];
        }
        error_log('[DemolitionTraders] sendTaxInvoice: config enabled');
        try {
            error_log('[DemolitionTraders] sendTaxInvoice: try block entered');
            $billing = $this->decodeAddress($order['billing_address'] ?? null);
            error_log('[DemolitionTraders] sendTaxInvoice: billing decoded');
            $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Customer';
            $toEmail = ($this->config['dev_mode'] && !$forceSendToCustomer) ? $this->config['dev_email'] : $customerEmail;
            // Sử dụng HTML giống frontend (đã có CSS receipt)
            $invoiceHtml = $this->generateTaxInvoiceHTML($order, $billing);
            error_log('[DemolitionTraders] sendTaxInvoice: invoice HTML generated');
                $subject = "Tax Invoice - Order #{$order['order_number']}";
                $fromEmail = $this->config['force_from_email'] ?? $this->config['from_email'] ?? $this->config['smtp_username'];
            $body = "Hi {$customerName},<br><br>Thank you for your order, please find attached the receipt/tax invoice.<br><br>Regards,<br>Demolition Traders Team";

            // Try to generate PDF
            $pdfPath = null;
            try {
                $pdfPath = generate_invoice_pdf_html($invoiceHtml, 'invoice');
                error_log('[DemolitionTraders] sendTaxInvoice: PDF generated');
            } catch (Exception $pdfEx) {
                error_log("Warning: PDF generation failed for order #{$order['order_number']}: " . $pdfEx->getMessage());
            }

            // Decide whether to prefer Brevo API for sending
            $preferBrevo = false; // Always use SMTP
            // Check if we should use Brevo API
            if ($preferBrevo) {
                error_log('[DemolitionTraders] sendTaxInvoice: using Brevo API');
                $attachments = [];
                if ($pdfPath && file_exists($pdfPath)) {
                    $attachments[$pdfPath] = 'Tax_Invoice_Order_' . $order['order_number'] . '.pdf';
                }
                $result = $this->sendViaBrevoApi($toEmail, $customerName, $subject, $body, $attachments);
                $this->logEmail([
                    'order_id' => $order['id'] ?? null,
                    'user_id' => $triggeredBy ?? null,
                    'type' => 'tax_invoice',
                    'send_method' => 'brevo',
                    'to_email' => $toEmail,
                        'from_email' => $fromEmail,
                    'subject' => $subject,
                    'status' => !empty($result['success']) ? 'success' : 'failure',
                    'response' => $result['response'] ?? null,
                    'resend_reason' => $resendReason ?? null,
                ]);
                error_log('[DemolitionTraders] sendTaxInvoice: Brevo API call finished');
            } else {
                error_log('[DemolitionTraders] sendTaxInvoice: using SMTP');
                // Use SMTP (PHPMailer)
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($toEmail, $customerName);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
                
                if ($pdfPath && file_exists($pdfPath)) {
                    $this->mailer->addAttachment($pdfPath, 'Tax_Invoice_Order_' . $order['order_number'] . '.pdf');
                }
                
                try {
                    // Log the SMTP host IP we resolved and attempt timings for diagnostics
                    $smtpHostIp = @gethostbyname($this->config['smtp_host']);
                    error_log('[DemolitionTraders] SMTP host resolved to: ' . ($smtpHostIp ?? 'unknown'));
                    $result = $this->smtpSendWithRetries(3, 1);
                    $sendResult = $result['success'];
                    $smtpErr = $result['error'] ?? null;
                    if (!$sendResult) {
                        error_log('[DemolitionTraders] sendTaxInvoice: smtp send failed after ' . ($result['attempts'] ?? 0) . ' attempts: ' . ($smtpErr ?? 'unknown'));
                    }
                } catch (Exception $mailEx) {
                    $sendResult = false;
                    $smtpErr = $mailEx->getMessage();
                }

                // If SMTP failed and fallback is explicitly allowed, try Brevo as a fallback
                // Pull allow_brevo_fallback from config here; it may not be available from setupMailer() local var
                $allowBrevoFallback = !empty($this->config['allow_brevo_fallback']) && !empty($this->config['brevo_api_key']);
                if (empty($sendResult) && $allowBrevoFallback) {
                    try {
                        error_log('[DemolitionTraders] sendTaxInvoice: SMTP failed, falling back to Brevo API.');
                        $attachments = [];
                        if (isset($pdfPath) && $pdfPath && file_exists($pdfPath)) {
                            $attachments[$pdfPath] = 'Tax_Invoice_Order_' . $order['order_number'] . '.pdf';
                        }
                        $brevoResp = $this->sendViaBrevoApi($toEmail, $customerName, $subject, $body, $attachments);
                        $this->logEmail([
                            'order_id' => $order['id'] ?? null,
                            'user_id' => $triggeredBy ?? null,
                            'type' => 'tax_invoice',
                            'send_method' => 'brevo',
                            'to_email' => $toEmail,
                            'from_email' => $this->config['from_email'] ?? $this->config['smtp_username'],
                            'subject' => $subject,
                            'status' => !empty($brevoResp['success']) ? 'success' : 'failure',
                            'response' => $brevoResp['response'] ?? null,
                            'error_message' => !empty($brevoResp['success']) ? null : ($brevoResp['response'] ?? null),
                            'resend_reason' => $resendReason ?? null,
                        ]);
                        // Set sendResult to indicate success if Brevo succeeded
                        $sendResult = !empty($brevoResp['success']);
                    } catch (Exception $brevoEx) {
                        error_log('[DemolitionTraders] Brevo fallback failed: ' . $brevoEx->getMessage());
                    }
                }

                // If send appears to succeed but we have debug info showing an issue, treat as failure

                // Ensure we pick up the latest mailer error info for diagnostics
                $smtpErr = $smtpErr ?? ($this->mailer->ErrorInfo ?? null);

                $this->logEmail([
                    'order_id' => $order['id'] ?? null,
                    'user_id' => $triggeredBy ?? null,
                    'type' => 'tax_invoice',
                    'send_method' => 'smtp',
                    'to_email' => $toEmail,
                    'from_email' => $this->config['from_email'] ?? $this->config['smtp_username'],
                    'subject' => $subject,
                    'status' => (!empty($sendResult) && empty($smtpErr)) ? 'success' : 'failure',
                    'error_message' => $smtpErr ?? null,
                    'response' => $this->debugLog ?? null,
                    'resend_reason' => $resendReason ?? null,
                ]);
                error_log('[DemolitionTraders] sendTaxInvoice: SMTP send finished');
            }
            
            // Clean up if PDF was created
            if (isset($pdfPath) && $pdfPath && file_exists($pdfPath)) {
                unlink($pdfPath);
                error_log('[DemolitionTraders] sendTaxInvoice: PDF cleaned up');
            }
            
            // Decide and return success only if a real send happened
            $finalSuccess = false;
            $finalMessage = 'Tax Invoice send failed';
            if ($preferBrevo) {
                $finalSuccess = !empty($result['success']);
                $finalMessage = $finalSuccess ? 'Tax Invoice sent successfully (brevo)' : 'Tax Invoice send failed (brevo)';
            } else {
                $finalSuccess = !empty($sendResult) && empty($smtpErr);
                if ($finalSuccess) {
                    $finalMessage = 'Tax Invoice sent successfully (smtp)';
                } else {
                    $finalMessage = 'Tax Invoice send failed: ' . ($smtpErr ?? 'unknown');
                }
            }
            if ($finalSuccess) {
                error_log("Tax Invoice sent to: $toEmail for order #{$order['order_number']}");
                return ['success' => true, 'message' => $finalMessage];
            }
            error_log('[DemolitionTraders] sendTaxInvoice final status: ' . $finalMessage);
            return ['success' => false, 'error' => $finalMessage];
        } catch (Exception $e) {
            error_log("Failed to send Tax Invoice: " . $e->getMessage());
            error_log("Exception Trace: " . print_r($e->getTraceAsString(), true));
            error_log("Order Data: " . print_r($order, true));
            error_log("Customer Email: " . print_r($customerEmail, true));
            // Log failure
            $this->logEmail([
                'order_id' => $order['id'] ?? null,
                'user_id' => $triggeredBy ?? null,
                'type' => 'tax_invoice',
                'send_method' => (!empty($this->config['brevo_api_key']) ? 'brevo' : 'smtp'),
                'to_email' => $customerEmail,
                'from_email' => $this->config['from_email'] ?? $this->config['smtp_username'],
                'subject' => $subject ?? null,
                'status' => 'failure',
                'error_message' => $e->getMessage(),
                'resend_reason' => $resendReason ?? null,
            ]);
            return ['success' => false, 'error' => $e->getMessage() ?: 'Unknown error'];
        }
    }
    /**
     * Send Receipt email
     */
    public function sendReceipt($order, $customerEmail, $forceSendToCustomer = false, $triggeredBy = null, $resendReason = null) {
        if (!$this->config['enabled']) {
            return ['success' => false, 'error' => 'Email sending is disabled'];
        }
        try {
            $billing = $this->decodeAddress($order['billing_address'] ?? null);
            $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Customer';
            $toEmail = ($this->config['dev_mode'] && !$forceSendToCustomer) ? $this->config['dev_email'] : $customerEmail;
            $fromEmail = $this->config['force_from_email'] ?? $this->config['from_email'] ?? $this->config['smtp_username'];
            // Sử dụng HTML giống frontend (đã có CSS receipt)
            $receiptHtml = $this->generateReceiptHTML($order, $billing);
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $customerName);
            $this->mailer->Subject = "Receipt - Order #{$order['order_number']}";
            $this->mailer->Body = "Hi {$customerName},<br><br>Thank you for your order, please find attached the receipt/tax invoice.<br><br>Regards,<br>Demolition Traders Team";
            // Generate PDF from HTML and attach
            $pdfPath = generate_invoice_pdf_html($receiptHtml, 'receipt');
            $brevoResp = [];
            $this->mailer->addAttachment($pdfPath, 'Receipt_Order_' . $order['order_number'] . '.pdf');
                try {
                    $smtpHostIp = @gethostbyname($this->config['smtp_host']);
                    error_log('[DemolitionTraders] SMTP host resolved to: ' . ($smtpHostIp ?? 'unknown'));
                    $result = $this->smtpSendWithRetries(3, 1);
                    $sendResult = $result['success'];
                    $smtpErr = $result['error'] ?? null;
                    if (!$sendResult) {
                        error_log('[DemolitionTraders] sendReceipt: smtp send failed after ' . ($result['attempts'] ?? 0) . ' attempts: ' . ($smtpErr ?? 'unknown'));
                    }
                } catch (Exception $mailEx) {
                    $sendResult = false;
                    $smtpErr = $mailEx->getMessage();
                }
            if (file_exists($pdfPath)) unlink($pdfPath);
            // If SMTP failed and Brevo is configured, try Brevo as a fallback
            $allowBrevoFallback = false; // Always use SMTP
            if ((empty($sendResult) || !empty($smtpErr)) && !empty($this->config['brevo_api_key']) && $allowBrevoFallback) {
                try {
                    error_log('[DemolitionTraders] sendReceipt: SMTP failed, falling back to Brevo API.');
                    $attachments = [];
                    if (isset($pdfPath) && $pdfPath && file_exists($pdfPath)) {
                        $attachments[$pdfPath] = 'Receipt_Order_' . $order['order_number'] . '.pdf';
                    }
                    $brevoResp = $this->sendViaBrevoApi($toEmail, $customerName, "Receipt - Order #{$order['order_number']}", $this->mailer->Body, $attachments);
                    $this->logEmail([
                        'order_id' => $order['id'] ?? null,
                        'user_id' => $triggeredBy ?? null,
                        'type' => 'receipt',
                        'send_method' => 'brevo',
                        'to_email' => $toEmail,
                        'from_email' => $fromEmail,
                        'subject' => "Receipt - Order #{$order['order_number']}",
                        'status' => !empty($brevoResp['success']) ? 'success' : 'failure',
                        'response' => $brevoResp['response'] ?? null,
                        'error_message' => !empty($brevoResp['success']) ? null : ($brevoResp['response'] ?? null),
                        'resend_reason' => $resendReason ?? null,
                    ]);
                    $sendResult = !empty($brevoResp['success']);
                } catch (Exception $brevoEx) {
                    error_log('[DemolitionTraders] Brevo fallback failed for receipt: ' . $brevoEx->getMessage());
                }
            }

                $this->logEmail([
                'order_id' => $order['id'] ?? null,
                'user_id' => $triggeredBy ?? null,
                'type' => 'receipt',
                'send_method' => 'smtp',
                'to_email' => $toEmail,
                    'from_email' => $fromEmail,
                'subject' => "Receipt - Order #{$order['order_number']}",
                'status' => (!empty($sendResult) && empty($smtpErr)) ? 'success' : 'failure',
                'error_message' => $smtpErr ?? null,
                'response' => $this->debugLog ?? null,
                'resend_reason' => $resendReason ?? null,
            ]);
            // Return success only when a real send happened
            $finalSuccess = !empty($sendResult) && empty($smtpErr);
            $finalMessage = $finalSuccess ? 'Receipt sent successfully' : ('Receipt send failed: ' . ($smtpErr ?? 'unknown'));
            if (!empty($this->config['brevo_api_key']) && empty($finalSuccess) && !empty($brevoResp['success'] ?? null)) {
                $finalSuccess = true;
                $finalMessage = 'Receipt sent successfully (brevo)';
            }
            if ($finalSuccess) {
                error_log("Receipt sent to: $toEmail for order #{$order['order_number']}");
                return ['success' => true, 'message' => $finalMessage];
            }
            error_log('[DemolitionTraders] sendReceipt final status: ' . $finalMessage);
            return ['success' => false, 'error' => $finalMessage];
        } catch (Exception $e) {
            error_log("Failed to send Receipt: " . $e->getMessage());
            error_log("Exception Trace: " . print_r($e->getTraceAsString(), true));
            error_log("Order Data: " . print_r($order, true));
            error_log("Customer Email: " . print_r($customerEmail, true));
            $this->logEmail([
                'order_id' => $order['id'] ?? null,
                'user_id' => $triggeredBy ?? null,
                'type' => 'receipt',
                'send_method' => 'smtp',
                'to_email' => $customerEmail,
                'from_email' => $this->config['from_email'] ?? $this->config['smtp_username'],
                'subject' => "Receipt - Order #{$order['order_number']}",
                'status' => 'failure',
                'error_message' => $e->getMessage(),
                'resend_reason' => $resendReason ?? null,
            ]);
            return ['success' => false, 'error' => $e->getMessage() ?: 'Unknown error'];
        }
    }
    
    /**
     * Generate Tax Invoice HTML for email (matching print template)
     */
    private function generateTaxInvoiceHTML($order, $billing) {
        $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Guest';
        $orderDate = new DateTime($order['created_at']);
        $items = $order['items'] ?? [];
        
        // Pre-calculate formatted values
        $totalAmountFormatted = number_format($order['total_amount'], 2);
        $subtotalFormatted = number_format($order['subtotal'], 2);
        $taxAmountFormatted = number_format($order['tax_amount'], 2);
        
        $itemsHtml = '';
        foreach ($items as $item) {
            $unitPriceFormatted = number_format($item['unit_price'], 2);
            $lineTotal = number_format($item['unit_price'] * $item['quantity'], 2);
            $itemsHtml .= "<tr>"
                . "<td><strong>{$item['product_name']}</strong></td>"
                . "<td>{$item['sku']}</td>"
                . "<td class='text-right'>{$item['quantity']}</td>"
                . "<td class='text-right'>\${$unitPriceFormatted}</td>"
                . "<td class='text-right'><strong>\${$lineTotal}</strong></td>"
                . "</tr>";
        }
        
        $shippingRow = floatval($order['shipping_amount']) > 0 
            ? "<tr><td>Shipping:</td><td align='right'>$" . number_format($order['shipping_amount'], 2) . "</td></tr>"
            : '';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Tax Invoice - Order #{$order['order_number']}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.4; margin: 0; padding: 0; }
        .company-name { font-size: 20pt; font-weight: bold; color: #2f3192; margin-bottom: 8px; }
        .company-details { font-size: 9pt; line-height: 1.5; }
        .invoice-title { font-size: 24pt; font-weight: bold; color: #2f3192; }
        .invoice-meta { font-size: 9pt; }
        .bill-to-section { margin: 15px 0; padding: 12px; background: #f8f9fa; border-left: 4px solid #2f3192; }
        .bill-to-title { font-weight: bold; font-size: 11pt; margin-bottom: 8px; color: #2f3192; }
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table thead { background: #2f3192; color: white; }
        .items-table th { padding: 8px; text-align: left; font-weight: bold; font-size: 10pt; }
        .items-table td { padding: 8px; border-bottom: 1px solid #dee2e6; font-size: 10pt; }
        .text-right { text-align: right; }
        .totals-table { width: 100%; margin-top: 15px; font-size: 10pt; }
        .totals-table td { padding: 5px; }
        .grand-total { font-size: 14pt; font-weight: bold; background: #2f3192; color: white; padding: 12px; text-align: center; margin-top: 10px; }
        .gst-breakdown { margin-top: 12px; padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; }
        .gst-breakdown h4 { margin: 0 0 8px 0; color: #2f3192; font-size: 10pt; }
        .payment-info { margin: 15px 0; padding: 12px; background: #fff3cd; border: 2px solid #ffc107; }
        .payment-info h4 { margin: 0 0 10px 0; color: #856404; font-size: 10pt; }
        .bank-table { width: 100%; font-size: 9pt; }
        .bank-table td { padding: 3px; }
        .terms { margin-top: 15px; padding: 12px; background: #f8f9fa; border-top: 2px solid #dee2e6; font-size: 8pt; line-height: 1.5; }
        .terms h4 { margin: 0 0 8px 0; color: #2f3192; font-size: 9pt; }
        .footer { margin-top: 15px; text-align: center; font-size: 8pt; color: #6c757d; border-top: 2px solid #dee2e6; padding-top: 12px; }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom: 3px solid #2f3192; padding-bottom: 12px; margin-bottom: 15px;">
        <tr>
            <td width="60%" valign="top">
                <div class="company-name">Demolition Traders</div>
                <div class="company-details">
                    249 Kahikatea Drive<br>
                    Hamilton 3204<br>
                    Phone: 07-847-4989<br>
                    Email: admin@demolitiontraders.co.nz<br>
                    <strong>GST Number: 45-514-609</strong>
                </div>
            </td>
            <td width="40%" valign="top" align="right">
                <div class="invoice-title">TAX INVOICE</div>
                <div class="invoice-meta">
                    <strong>Invoice #:</strong> {$order['order_number']}<br>
                    <strong>Date:</strong> {$orderDate->format('d/m/y')}<br>
                    <strong>Time:</strong> {$orderDate->format('H:i')}
                </div>
            </td>
        </tr>
    </table>
    
    <div class="bill-to-section">
        <div class="bill-to-title">BILL TO:</div>
        <strong>{$customerName}</strong><br>
        {$billing['address']}<br>
        {$billing['city']} {$billing['postcode']}<br>
        Email: {$billing['email']}<br>
        Phone: {$billing['phone']}
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 10%;" class="text-right">Qty</th>
                <th style="width: 12%;" class="text-right">Unit Price</th>
                <th style="width: 13%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>{$itemsHtml}</tbody>
    </table>
    
    <div class="grand-total">TOTAL AMOUNT DUE: \${$totalAmountFormatted}</div>
    
    <div class="gst-breakdown">
        <h4>GST Breakdown</h4>
        <table class="totals-table">
            <tr>
                <td width="60%">Subtotal (excl GST):</td>
                <td width="40%" align="right">\${$subtotalFormatted}</td>
            </tr>
            <tr>
                <td>GST Amount (15%):</td>
                <td align="right">\${$taxAmountFormatted}</td>
            </tr>
            {$shippingRow}
            <tr style="font-weight: bold;">
                <td>Total (incl GST):</td>
                <td align="right">\${$totalAmountFormatted}</td>
            </tr>
        </table>
    </div>
    
    <div class="payment-info">
        <h4>PAYMENT INFORMATION</h4>
        <table class="bank-table">
            <tr>
                <td width="150"><strong>Bank:</strong></td>
                <td>BNZ (Bank of New Zealand)</td>
            </tr>
            <tr>
                <td><strong>Account Name:</strong></td>
                <td>Demolition Traders</td>
            </tr>
            <tr>
                <td><strong>Account Number:</strong></td>
                <td>02-0341-0083457-00</td>
            </tr>
            <tr>
                <td><strong>Reference:</strong></td>
                <td>{$billing['last_name']}</td>
            </tr>
        </table>
        <p style="margin-top: 10px; font-size: 9pt;"><strong>Please use your last name as payment reference.</strong></p>
    </div>
    
    <div class="terms">
        <h4>TERMS & CONDITIONS</h4>
        <p><strong>Payment Terms:</strong> Payment due within 7 days of invoice date.</p>
        <p><strong>Refund Policy:</strong> Demolition Traders Ltd offers a 30 day refund period on all goods. Goods must be returned and inspected within 30 days of original purchase date for a refund. Proof of original purchase is required as a condition of the returns policy.</p>
        <p><strong>Returns:</strong> Goods must be returned in the original purchase condition and be unused and undamaged. Any items with scratch or scuff marks and that have been cut down or altered will not be refunded - whether in transit or during 3rd party handling.</p>
        <p><strong>Non-Refundable Items:</strong> Items with custom liners are non-refundable. Any credit card transaction fees are non-refundable.</p>
    </div>
    
    <div class="footer">
        Thank you for your business!<br>
        For any queries, please contact us at 07-847-4989 or info@demolitiontraders.co.nz
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Generate Receipt HTML for email (matching print template)
     */
    private function generateReceiptHTML($order, $billing) {
        $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Guest';
        $orderDate = new DateTime($order['created_at']);
        $items = $order['items'] ?? [];
        
        // Pre-calculate formatted values
        $totalAmountFormatted = number_format($order['total_amount'], 2);
        $subtotalFormatted = number_format($order['subtotal'], 2);
        $taxAmountFormatted = number_format($order['tax_amount'], 2);
        
        $itemsHtml = '';
        foreach ($items as $item) {
            $unitPriceFormatted = number_format($item['unit_price'], 2);
            $lineTotal = number_format($item['unit_price'] * $item['quantity'], 2);
            $itemsHtml .= "<tr>"
                . "<td><strong>{$item['product_name']}</strong></td>"
                . "<td>{$item['sku']}</td>"
                . "<td class='text-right'>{$item['quantity']}</td>"
                . "<td class='text-right'>\${$unitPriceFormatted}</td>"
                . "<td class='text-right'><strong>\${$lineTotal}</strong></td>"
                . "</tr>";
        }
        
        $shippingRow = floatval($order['shipping_amount']) > 0 
            ? "<tr><td>Shipping:</td><td align='right'>$" . number_format($order['shipping_amount'], 2) . "</td></tr>"
            : '';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Receipt - Order #{$order['order_number']}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.4; margin: 0; padding: 0; }
        .company-name { font-size: 20pt; font-weight: bold; color: #2f3192; margin-bottom: 8px; }
        .company-details { font-size: 9pt; line-height: 1.5; }
        .invoice-title { font-size: 24pt; font-weight: bold; color: #2f3192; text-transform: uppercase; }
        .invoice-meta { font-size: 9pt; }
        .bill-to-section { margin: 15px 0; padding: 12px; background: #f8f9fa; border-left: 4px solid #2f3192; }
        .bill-to-title { font-weight: bold; font-size: 11pt; margin-bottom: 8px; color: #2f3192; }
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table thead { background: #2f3192; color: white; }
        .items-table th { padding: 8px; text-align: left; font-weight: bold; font-size: 10pt; }
        .items-table td { padding: 8px; border-bottom: 1px solid #dee2e6; font-size: 10pt; }
        .text-right { text-align: right; }
        .totals-table { width: 100%; margin-top: 15px; font-size: 10pt; }
        .totals-table td { padding: 5px; }
        .grand-total { font-size: 14pt; font-weight: bold; background: #2f3192; color: white; padding: 12px; text-align: center; margin-top: 10px; }
        .gst-breakdown { margin-top: 12px; padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; }
        .gst-breakdown h4 { margin: 0 0 8px 0; color: #2f3192; font-size: 10pt; }
        .paid-box { margin: 15px 0; padding: 15px; background: #d4edda; border: 2px solid #28a745; text-align: center; font-size: 14pt; font-weight: bold; color: #155724; }
        .terms { margin-top: 15px; padding: 12px; background: #f8f9fa; border-top: 2px solid #dee2e6; font-size: 8pt; line-height: 1.5; }
        .terms h4 { margin: 0 0 8px 0; color: #2f3192; font-size: 9pt; }
        .footer { margin-top: 15px; text-align: center; font-size: 8pt; color: #6c757d; border-top: 2px solid #dee2e6; padding-top: 12px; }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom: 3px solid #2f3192; padding-bottom: 12px; margin-bottom: 15px;">
        <tr>
            <td width="60%" valign="top">
                <div class="company-name">Demolition Traders</div>
                <div class="company-details">
                    249 Kahikatea Drive<br>
                    Hamilton 3204<br>
                    Phone: 07-847-4989<br>
                    Email: admin@demolitiontraders.co.nz<br>
                    <strong>GST Number: 45-514-609</strong>
                </div>
            </td>
            <td width="40%" valign="top" align="right">
                <div class="invoice-title">RECEIPT</div>
                <div class="invoice-meta">
                    <strong>Receipt #:</strong> {$order['order_number']}<br>
                    <strong>Date:</strong> {$orderDate->format('d/m/y')}<br>
                    <strong>Time:</strong> {$orderDate->format('H:i')}
                </div>
            </td>
        </tr>
    </table>
    
    <div class="bill-to-section">
        <div class="bill-to-title">CUSTOMER:</div>
        <strong>{$customerName}</strong><br>
        {$billing['address']}<br>
        {$billing['city']} {$billing['postcode']}<br>
        Email: {$billing['email']}<br>
        Phone: {$billing['phone']}
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 10%;" class="text-right">Qty</th>
                <th style="width: 12%;" class="text-right">Unit Price</th>
                <th style="width: 13%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>{$itemsHtml}</tbody>
    </table>
    
    <div class="grand-total">TOTAL PAID: \${$totalAmountFormatted}</div>
    
    <div class="gst-breakdown">
        <h4>GST Breakdown</h4>
        <table class="totals-table">
            <tr>
                <td width="60%">Subtotal (excl GST):</td>
                <td width="40%" align="right">\${$subtotalFormatted}</td>
            </tr>
            <tr>
                <td>GST Amount (15%):</td>
                <td align="right">\${$taxAmountFormatted}</td>
            </tr>
            {$shippingRow}
            <tr style="font-weight: bold;">
                <td>Total (incl GST):</td>
                <td align="right">\${$totalAmountFormatted}</td>
            </tr>
        </table>
    </div>
    
    <div class="paid-box">PAID IN FULL</div>
    
    <div class="terms">
        <h4>TERMS & CONDITIONS</h4>
        <p><strong>Payment Terms:</strong> Payment has been received in full for goods listed above.</p>
        <p><strong>Refund Policy:</strong> Demolition Traders Ltd offers a 30 day refund period on all goods. Goods must be returned and inspected within 30 days of original purchase date for a refund. Proof of original purchase is required as a condition of the returns policy.</p>
        <p><strong>Returns:</strong> Goods must be returned in the original purchase condition and be unused and undamaged. Any items with scratch or scuff marks and that have been cut down or altered will not be refunded - whether in transit or during 3rd party handling.</p>
        <p><strong>Non-Refundable Items:</strong> Items with custom liners are non-refundable. Any credit card transaction fees are non-refundable.</p>
    </div>
    
    <div class="footer">
        Thank you for your purchase!<br>
        For any queries, please contact 07-847-4989 or info@demolitiontraders.co.nz
    </div>
</body>
</html>
HTML;
    }
    
  
    /**
     * Email wrapper template
     */
    private function getEmailWrapper($customerName, $content, $type) {
        $greeting = $type === 'receipt' ? 
            "Thank you for your payment!" : 
            "Thank you for your order!";
            
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background: #f8f9fa;'>
            <div style='max-width: 800px; margin: 0 auto; padding: 20px;'>
                <div style='background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    <p style='font-size: 16px; margin: 0 0 20px 0;'>Dear {$customerName},</p>
                    <p style='font-size: 16px; margin: 0 0 30px 0;'>{$greeting}</p>
                    
                    {$content}
                    
                    <div style='margin-top: 40px; padding-top: 30px; border-top: 2px solid #dee2e6; text-align: center; color: #6c757d; font-size: 14px;'>
                        <p><strong>Thank you for your business!</strong></p>
                        <p>For any queries, please contact us:</p>
                        <p>
                            <strong>Phone:</strong> 07-847-4989<br>
                            <strong>Email:</strong> admin@demolitiontraders.co.nz<br>
                            <strong>Address:</strong> 249 Kahikatea Drive, Hamilton 3204
                        </p>
                        <p style='margin-top: 20px; font-size: 12px; color: #adb5bd;'>
                            © " . date('Y') . " Demolition Traders. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Generic send email method
     */
    public function sendEmail($to, $subject, $body, $altBody = '', $forceSendToCustomer = false) {
        if (!$this->config['enabled']) {
            error_log("Email sending is disabled");
            return false;
        }
        
        try {
            $toEmail = ($this->config['dev_mode'] && !$forceSendToCustomer) ? $this->config['dev_email'] : $to;
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            if ($altBody) {
                $this->mailer->AltBody = $altBody;
            }
            $fromEmail = $this->config['force_from_email'] ?? $this->config['from_email'] ?? $this->config['smtp_username'];
            
            // Use the same SMTP helper which includes retries and timing diagnostics
            $sendResult = true;
            $sendError = null;
            try {
                $sendRes = $this->smtpSendWithRetries(3, 1);
                $sendResult = $sendRes['success'];
                $sendError = $sendRes['error'] ?? null;
                if (!$sendResult) {
                    throw new Exception('SMTP send failed: ' . ($sendError ?? 'unknown'));
                }
            } catch (Exception $e) {
                throw $e; // propagate to catch below
            }
            error_log("Email sent successfully to: $toEmail - Subject: $subject");
            // Log it
            $this->logEmail([
                'order_id' => null,
                'user_id' => null,
                'type' => 'generic',
                'send_method' => 'smtp',
                'to_email' => $toEmail,
                'from_email' => $fromEmail,
                'subject' => $subject,
                'status' => 'success',
            ]);
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
            $this->logEmail([
                'order_id' => null,
                'user_id' => null,
                'type' => 'generic',
                'send_method' => 'smtp',
                'to_email' => $to,
                'from_email' => $fromEmail,
                'subject' => $subject,
                'status' => 'failure',
                'error_message' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Send Contact Form Email to Admin
     */
    public function sendContactFormEmail($data) {
        if (!$this->config['enabled']) {
            return ['success' => false, 'error' => 'Email sending is disabled'];
        }
        
        try {
            $adminEmail = $this->config['dev_mode'] ? $this->config['dev_email'] : 'info@demolitiontraders.co.nz';
            
            $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2f3192; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; margin: -30px -30px 20px -30px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #2f3192; }
        .value { margin-top: 5px; padding: 10px; background: #f8f9fa; border-left: 3px solid #2f3192; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h2 style="margin: 0;">New Contact Form Submission</h2>
            </div>
            
            <div class="field">
                <div class="label">Name:</div>
                <div class="value">{$data['name']}</div>
            </div>
            
            <div class="field">
                <div class="label">Email:</div>
                <div class="value"><a href="mailto:{$data['email']}">{$data['email']}</a></div>
            </div>
            
            <div class="field">
                <div class="label">Phone:</div>
                <div class="value">{$data['phone']}</div>
            </div>
            
            <div class="field">
                <div class="label">Subject:</div>
                <div class="value">{$data['subject']}</div>
            </div>
            
            <div class="field">
                <div class="label">Message:</div>
                <div class="value">{$data['message']}</div>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                <strong>Action Required:</strong> Please reply to this customer at {$data['email']}
            </div>
        </div>
    </div>
</body>
</html>
HTML;
            
            // Check if we should use Brevo API
            if (!empty($this->config['brevo_api_key'])) {
                $result = $this->sendViaBrevoApi($adminEmail, 'Admin', "New Contact Form: {$data['subject']}", $html);
                $this->logEmail([
                    'order_id' => null,
                    'user_id' => null,
                    'type' => 'contact_form',
                    'send_method' => 'brevo',
                    'to_email' => $adminEmail,
                    'from_email' => $data['email'] ?? null,
                    'subject' => "New Contact Form: {$data['subject']}",
                    'status' => !empty($result['success']) ? 'success' : 'failure',
                    'response' => $result['response'] ?? null,
                ]);
            } else {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($adminEmail);
                $this->mailer->addReplyTo($data['email'], $data['name']);
                $this->mailer->Subject = "New Contact Form: {$data['subject']}";
                $this->mailer->Body = $html;
                $this->mailer->send();
                $this->logEmail([
                    'order_id' => null,
                    'user_id' => null,
                    'type' => 'contact_form',
                    'send_method' => 'smtp',
                    'to_email' => $adminEmail,
                    'from_email' => $data['email'] ?? null,
                    'subject' => "New Contact Form: {$data['subject']}",
                    'status' => 'success',
                ]);
            }
            
            error_log("Contact form email sent to admin");
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Failed to send contact form email: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send Sell to Us Email to Admin (Alias for sendSellToUsEmail)
     */
    public function sendSellToUsSubmissionEmail($data, $submissionId = null) {
        // Add submission ID to data if provided
        if ($submissionId) {
            $data['submission_id'] = $submissionId;
        }
        return $this->sendSellToUsEmail($data);
    }

    /**
     * Send Sell to Us Email to Admin
     */
    public function sendSellToUsEmail($data) {
        if (!$this->config['enabled']) {
            return ['success' => false, 'error' => 'Email sending is disabled'];
        }
        
        try {
            $adminEmail = $this->config['dev_mode'] ? $this->config['dev_email'] : 'info@demolitiontraders.co.nz';
            
            $photosHtml = '';
            if (!empty($data['photos'])) {
                $photosHtml = '<div class="field"><div class="label">Photos:</div><div class="value">';
                $photosHtml .= count($data['photos']) . ' photo(s) attached to this email';
                $photosHtml .= '</div></div>';
            }
            
            $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; margin: -30px -30px 20px -30px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #28a745; }
        .value { margin-top: 5px; padding: 10px; background: #f8f9fa; border-left: 3px solid #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h2 style="margin: 0;">New Sell to Us Submission</h2>
            </div>
            
            <div class="field">
                <div class="label">Name:</div>
                <div class="value">{$data['name']}</div>
            </div>
            
            <div class="field">
                <div class="label">Email:</div>
                <div class="value"><a href="mailto:{$data['email']}">{$data['email']}</a></div>
            </div>
            
            <div class="field">
                <div class="label">Phone:</div>
                <div class="value">{$data['phone']}</div>
            </div>
            
            <div class="field">
                <div class="label">Item Name:</div>
                <div class="value"><strong>{$data['item_name']}</strong></div>
            </div>
            
            <div class="field">
                <div class="label">Quantity:</div>
                <div class="value">{$data['quantity']}</div>
            </div>
            
            <div class="field">
                <div class="label">Pick Up or Delivery:</div>
                <div class="value">{$data['pickup_delivery']}</div>
            </div>
HTML;
            
            // Add pickup address and date conditionally if pickup option is selected
            if ($data['pickup_delivery'] === 'pickup_onsite' && !empty($data['location'])) {
                $html .= <<<HTML
            
            <div class="field">
                <div class="label">Pick Up Address:</div>
                <div class="value">{$data['location']}</div>
            </div>
HTML;
            }
            
            if (!empty($data['pickup_date'])) {
                $pickupDateDisplay = date('d/m/Y', strtotime($data['pickup_date']));
                $html .= <<<HTML
            
            <div class="field">
                <div class="label">Preferred Pick Up Date:</div>
                <div class="value">{$pickupDateDisplay}</div>
            </div>
HTML;
            }
            
            $html .= <<<HTML
            
            <div class="field">
                <div class="label">Description:</div>
                <div class="value">{$data['description']}</div>
            </div>
            
            {$photosHtml}
            
            <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 4px;">
                <strong>Action Required:</strong> Review the items and contact the seller at {$data['phone']} or {$data['email']}
            </div>
        </div>
    </div>
</body>
</html>
HTML;
            
            // Check if we should use Brevo API
            if (!empty($this->config['brevo_api_key'])) {
                $attachments = [];
                if (!empty($data['photos'])) {
                    $uploadsPath = __DIR__ . '/../../';
                    foreach ($data['photos'] as $photoPath) {
                        $fullPath = $uploadsPath . $photoPath;
                        if (file_exists($fullPath)) {
                            $attachments[$fullPath] = basename($photoPath);
                        }
                    }
                }
                $this->sendViaBrevoApi($adminEmail, 'Admin', "New Sell to Us Submission from {$data['name']}", $html, $attachments);
            } else {
                $this->mailer->clearAddresses();
                $this->mailer->clearAttachments();
                $this->mailer->addAddress($adminEmail);
                $this->mailer->addReplyTo($data['email'], $data['name']);
                $this->mailer->Subject = "New Sell to Us Submission from {$data['name']}";
                $this->mailer->Body = $html;
                
                // Attach photos if available
                if (!empty($data['photos'])) {
                    $uploadsPath = __DIR__ . '/../../';
                    foreach ($data['photos'] as $photoPath) {
                        $fullPath = $uploadsPath . $photoPath;
                        if (file_exists($fullPath)) {
                            $this->mailer->addAttachment($fullPath, basename($photoPath));
                        }
                    }
                }
                
                $this->mailer->send();
            }
            
            error_log("Sell to us email sent to admin");
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Failed to send sell to us email: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send Wanted Listing Email to Admin
     */
    public function sendWantedListingEmail($data) {
        if (!$this->config['enabled']) {
            return ['success' => false, 'error' => 'Email sending is disabled'];
        }
        
        try {
            $adminEmail = $this->config['dev_mode'] ? $this->config['dev_email'] : 'info@demolitiontraders.co.nz';
            
            $userStatus = $data['user_id'] ? "Registered User (ID: {$data['user_id']})" : "Guest";
            $notifyStatus = $data['notify'] ? "Yes" : "No";
            
            $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #ff6b35; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; margin: -30px -30px 20px -30px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #ff6b35; }
        .value { margin-top: 5px; padding: 10px; background: #f8f9fa; border-left: 3px solid #ff6b35; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h2 style="margin: 0;">New Wanted Listing Submission</h2>
            </div>
            
            <div class="field">
                <div class="label">Name:</div>
                <div class="value">{$data['name']}</div>
            </div>
            
            <div class="field">
                <div class="label">Email:</div>
                <div class="value"><a href="mailto:{$data['email']}">{$data['email']}</a></div>
            </div>
            
            <div class="field">
                <div class="label">Phone:</div>
                <div class="value">{$data['phone']}</div>
            </div>
            
            <div class="field">
                <div class="label">User Status:</div>
                <div class="value">{$userStatus}</div>
            </div>
            
            <div class="field">
                <div class="label">Category:</div>
                <div class="value">{$data['category']}</div>
            </div>
            
            <div class="field">
                <div class="label">Item Description:</div>
                <div class="value">{$data['description']}</div>
            </div>
            
            <div class="field">
                <div class="label">Quantity:</div>
                <div class="value">{$data['quantity']}</div>
            </div>
            
            <div class="field">
                <div class="label">Email Notifications:</div>
                <div class="value">{$notifyStatus}</div>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                <strong>Action Required:</strong> When similar items arrive, contact {$data['name']} at {$data['email']} or {$data['phone']}
            </div>
        </div>
    </div>
</body>
</html>
HTML;
            
            // Check if we should use Brevo API
            if (!empty($this->config['brevo_api_key'])) {
                $this->sendViaBrevoApi($adminEmail, 'Admin', "New Wanted Listing: {$data['category']} - {$data['name']}", $html);
            } else {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($adminEmail);
                $this->mailer->addReplyTo($data['email'], $data['name']);
                $this->mailer->Subject = "New Wanted Listing: {$data['category']} - {$data['name']}";
                $this->mailer->Body = $html;
                $this->mailer->send();
            }
            
            error_log("Wanted listing email sent to admin");
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Failed to send wanted listing email: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send Wanted Listing Confirmation to User
     */
    public function sendWantedListingConfirmationEmail($email, $name, $description) {
        if (!$this->config['enabled']) {
            return ['success' => false, 'error' => 'Email sending is disabled'];
        }
        
        try {
            // Always send to customer; if dev_mode, also send a copy to dev inbox for visibility
            $toEmail = $email;
            $bccEmail = ($this->config['dev_mode'] ?? false) ? ($this->config['dev_email'] ?? null) : null;
            
            $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2f3192; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; margin: -30px -30px 20px -30px; }
        .content { font-size: 16px; }
        .item-box { background: #f8f9fa; padding: 15px; border-left: 4px solid #2f3192; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h2 style="margin: 0;">Wanted Listing Confirmation</h2>
            </div>
            
            <div class="content">
                <p>Hi {$name},</p>
                
                <p>Thank you for submitting your wanted listing to Demolition Traders!</p>
                
                <p><strong>You're looking for:</strong></p>
                <div class="item-box">
                    {$description}
                </div>
                
                <p>We'll keep an eye out for items matching your description and notify you by email when we have a match.</p>
                
                <p>Our stock changes regularly as we receive new materials from demolition sites across New Zealand, so check back often!</p>
                
                <p>If you have any questions, feel free to contact us:</p>
                <ul>
                    <li>Phone: 07 847 4989</li>
                    <li>Email: info@demolitiontraders.co.nz</li>
                    <li>Freephone: 0800 DEMOLITION</li>
                </ul>
                
                <p>Best regards,<br>
                <strong>Demolition Traders Team</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
            
            $subject = "Your Wanted Listing - Demolition Traders";
            $fromEmail = $this->config['force_from_email'] ?? $this->config['from_email'] ?? $this->config['smtp_username'];

            // Prefer Brevo API if available so we can see logs in dashboard
            if (!empty($this->config['brevo_api_key'])) {
                $this->sendViaBrevoApi($toEmail, $name, $subject, $html);
                if (!empty($bccEmail) && filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->sendViaBrevoApi($bccEmail, 'Dev Copy', $subject . ' [DEV COPY]', $html);
                }
                $this->logEmail([
                    'order_id' => null,
                    'user_id' => null,
                    'type' => 'wanted_listing_confirmation',
                    'send_method' => 'brevo',
                    'to_email' => $toEmail,
                    'from_email' => $fromEmail,
                    'subject' => $subject,
                    'status' => 'success',
                    'resend_reason' => null,
                ]);
            } else {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($toEmail, $name);
                if (!empty($bccEmail) && filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->mailer->addBCC($bccEmail);
                }
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $html;
                $this->mailer->send();
                $this->logEmail([
                    'order_id' => null,
                    'user_id' => null,
                    'type' => 'wanted_listing_confirmation',
                    'send_method' => 'smtp',
                    'to_email' => $toEmail,
                    'from_email' => $fromEmail,
                    'subject' => $subject,
                    'status' => 'success',
                    'resend_reason' => null,
                ]);
            }
            
            $logTarget = $toEmail . ($bccEmail ? " (bcc: $bccEmail)" : '');
            error_log("Wanted listing confirmation sent to: $logTarget");
            return ['success' => true, 'message' => 'Confirmation email sent'];
            
        } catch (Exception $e) {
            error_log("Failed to send wanted listing confirmation: " . $e->getMessage());
            $this->logEmail([
                'order_id' => null,
                'user_id' => null,
                'type' => 'wanted_listing_confirmation',
                'send_method' => !empty($this->config['brevo_api_key']) ? 'brevo' : 'smtp',
                'to_email' => $email,
                'from_email' => $this->config['from_email'] ?? null,
                'subject' => "Your Wanted Listing - Demolition Traders",
                'status' => 'failure',
                'error_message' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
