<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $config;
    private $mailer;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/email.php';
        // Initialize PHPMailer
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/pdf_invoice.php';
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }
    
    /**
     * Setup PHPMailer configuration
     */
    private function setupMailer() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = $this->config['smtp_host'];
            $this->mailer->SMTPAuth   = $this->config['smtp_auth'];
            $this->mailer->Username   = $this->config['smtp_username'];
            $this->mailer->Password   = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = $this->config['smtp_secure'];
            $this->mailer->Port       = $this->config['smtp_port'];
            
            // From
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mailer->addReplyTo($this->config['reply_to'], $this->config['from_name']);
            
            // Content type
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("PHPMailer setup error: " . $e->getMessage());
        }
    }
    
    /**
     * Send Tax Invoice email
     */
    public function sendTaxInvoice($order, $customerEmail) {
        if (!$this->config['enabled']) {
            return ['success' => false, 'error' => 'Email sending is disabled'];
        }
        try {
            $billing = json_decode($order['billing_address'], true);
            $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Customer';
            $toEmail = $this->config['dev_mode'] ? $this->config['dev_email'] : $customerEmail;
            // Sử dụng HTML giống frontend (đã có CSS receipt)
            $invoiceHtml = $this->generateTaxInvoiceHTML($order, $billing);
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $customerName);
            $this->mailer->Subject = "Tax Invoice - Order #{$order['order_number']}";
            $this->mailer->Body = "Hi {$customerName},<br><br>Thank you for your order, please find attached the receipt/tax invoice.<br><br>Regards,<br>Demolition Traders Team";
            // Generate PDF from HTML and attach
            $pdfPath = generate_invoice_pdf_html($invoiceHtml, 'invoice');
            $this->mailer->addAttachment($pdfPath, 'Tax_Invoice_Order_' . $order['order_number'] . '.pdf');
            $this->mailer->send();
            if (file_exists($pdfPath)) unlink($pdfPath);
            error_log("Tax Invoice sent to: $toEmail for order #{$order['order_number']}");
            return ['success' => true, 'message' => 'Tax Invoice sent successfully'];
        } catch (Exception $e) {
            error_log("Failed to send Tax Invoice: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    /**
     * Send Receipt email
     */
    public function sendReceipt($order, $customerEmail) {
        if (!$this->config['enabled']) {
            return ['success' => false, 'error' => 'Email sending is disabled'];
        }
        try {
            $billing = json_decode($order['billing_address'], true);
            $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Customer';
            $toEmail = $this->config['dev_mode'] ? $this->config['dev_email'] : $customerEmail;
            // Sử dụng HTML giống frontend (đã có CSS receipt)
            $receiptHtml = $this->generateReceiptHTML($order, $billing);
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $customerName);
            $this->mailer->Subject = "Receipt - Order #{$order['order_number']}";
            $this->mailer->Body = "Hi {$customerName},<br><br>Thank you for your order, please find attached the receipt/tax invoice.<br><br>Regards,<br>Demolition Traders Team";
            // Generate PDF from HTML and attach
            $pdfPath = generate_invoice_pdf_html($receiptHtml, 'receipt');
            $this->mailer->addAttachment($pdfPath, 'Receipt_Order_' . $order['order_number'] . '.pdf');
            $this->mailer->send();
            if (file_exists($pdfPath)) unlink($pdfPath);
            error_log("Receipt sent to: $toEmail for order #{$order['order_number']}");
            return ['success' => true, 'message' => 'Receipt sent successfully'];
        } catch (Exception $e) {
            error_log("Failed to send Receipt: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generate Tax Invoice HTML for email
     */
    private function generateTaxInvoiceHTML($order, $billing) {
        $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Guest';
        $orderDate = new DateTime($order['created_at']);
        $items = $order['items'] ?? [];
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "<tr>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6;'><strong>{$item['product_name']}</strong></td>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6;'>{$item['sku']}</td>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6; text-align: right;'>{$item['quantity']}</td>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6; text-align: right;'>$" . number_format($item['unit_price'], 2) . "</td>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6; text-align: right;'><strong>$" . number_format($item['subtotal'], 2) . "</strong></td>"
                . "</tr>";
        }
        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Tax Invoice - Order #{$order['order_number']}</title></head><body style='margin:0;padding:0;font-family:Arial,sans-serif;background:#f8f9fa;'>"
            . "<div style='max-width:800px;margin:0 auto;background:white;padding:30px;border-radius:5px;'>"
            . "<div style='background:#2f3192;color:white;padding:30px;text-align:center;border-radius:5px 5px 0 0;'><h1 style='margin:0;font-size:32px;'>TAX INVOICE</h1><p style='margin:10px 0 0 0;font-size:18px;'>Demolition Traders</p></div>"
            . "<div style='display:flex;justify-content:space-between;margin:30px 0;'>"
            . "<div><strong style='color:#2f3192;'>Demolition Traders</strong><br>249 Kahikatea Drive<br>Hamilton 3204<br>Phone: 07-847-4989<br>Email: admin@demolitiontraders.co.nz<br><strong>GST: 45-514-609</strong></div>"
            . "<div style='text-align:right;'><strong>Invoice #:</strong> {$order['order_number']}<br><strong>Date:</strong>" . $orderDate->format('d/m/Y') . "<br><strong>Time:</strong>" . $orderDate->format('h:i A') . "</div>"
            . "</div>"
            . "<div style='background:#f8f9fa;padding:20px;margin-bottom:30px;border-left:4px solid #2f3192;'><strong style='color:#2f3192;'>BILL TO:</strong><br><strong>{$customerName}</strong><br>" . ($billing['address'] ?? '') . "<br>" . ($billing['city'] ?? '') . " " . ($billing['postcode'] ?? '') . "<br>Email: " . ($billing['email'] ?? $order['guest_email'] ?? 'N/A') . "<br>Phone: " . ($billing['phone'] ?? 'N/A') . "</div>"
            . "<table style='width:100%;border-collapse:collapse;margin-bottom:30px;'><thead style='background:#2f3192;color:white;'><tr><th style='padding:12px;text-align:left;'>Description</th><th style='padding:12px;text-align:left;'>SKU</th><th style='padding:12px;text-align:right;'>Qty</th><th style='padding:12px;text-align:right;'>Unit Price</th><th style='padding:12px;text-align:right;'>Amount</th></tr></thead><tbody>{$itemsHtml}</tbody></table>"
            . "<div style='text-align:right;'><div style='display:inline-block;min-width:300px;'><div style='background:#2f3192;color:white;padding:20px;font-size:18px;font-weight:bold;margin-bottom:15px;'><div style='display:flex;justify-content:space-between;'><span>TOTAL AMOUNT DUE</span><span>$" . number_format($order['total_amount'], 2) . "</span></div></div>"
            . "<div style='background:#f8f9fa;padding:15px;border:1px solid #dee2e6;'><div style='display:flex;justify-content:space-between;padding:5px 0;'><span>Subtotal (excl GST):</span><span>$" . number_format($order['subtotal'], 2) . "</span></div><div style='display:flex;justify-content:space-between;padding:5px 0;'><span>GST (15%):</span><span>$" . number_format($order['tax_amount'], 2) . "</span></div>"
            . (floatval($order['shipping_amount']) > 0 ? "<div style='display:flex;justify-content:space-between;padding:5px 0;'><span>Shipping:</span><span>$" . number_format($order['shipping_amount'], 2) . "</span></div>" : "")
            . "<div style='display:flex;justify-content:space-between;padding:10px 0 5px 0;border-top:2px solid #dee2e6;margin-top:10px;font-weight:bold;font-size:16px;'><span>Total (incl GST):</span><span>$" . number_format($order['total_amount'], 2) . "</span></div></div></div></div>"
            . "<div style='background:#fff3cd;border:2px solid #ffc107;padding:20px;margin-top:30px;border-radius:5px;'><h3 style='margin:0 0 15px 0;color:#856404;'>PAYMENT INFORMATION</h3><table style='width:100%;font-size:14px;'><tr><td style='padding:5px;width:150px;'><strong style='color:#856404;'>Bank:</strong></td><td style='padding:5px;'>BNZ (Bank of New Zealand)</td></tr><tr><td style='padding:5px;'><strong style='color:#856404;'>Account Name:</strong></td><td style='padding:5px;'>Demolition Traders</td></tr><tr><td style='padding:5px;'><strong style='color:#856404;'>Account Number:</strong></td><td style='padding:5px;'>02-0341-0083457-00</td></tr><tr><td style='padding:5px;'><strong style='color:#856404;'>Reference:</strong></td><td style='padding:5px;'>" . ($billing['last_name'] ?? 'Customer') . "</td></tr></table><p style='margin:15px 0 0 0;'><strong>Please use your last name as payment reference.</strong></p></div>"
            . "<div style='background:#f8f9fa;border:2px solid #dee2e6;padding:20px;margin-top:30px;border-radius:5px;'><h3 style='margin:0 0 15px 0;color:#2f3192;'>Company Terms of Trade</h3><div style='font-size:13px;color:#333;'>Demolition Traders Ltd offers a 30 day refund period on all goods.<br>Goods must be returned and inspected within 30 days of original purchase date for a refund.<br>Proof of original purchase is required as a condition of the returns policy.<br>Goods must be returned in the original purchase condition and be unused and undamaged.<br>Any items with scratch or scuff marks and that have been cut down or altered will not be refunded - whether in transit or during 3rd party handling.<br>Items with custom liners are non-refundable.<br>Any credit card transaction fees are non-refundable.</div></div>"
            . "</div></body></html>";
        return $html;
    }
    
    /**
     * Generate Receipt HTML for email
     */
    private function generateReceiptHTML($order, $billing) {
        $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Guest';
        $orderDate = new DateTime($order['created_at']);
        $items = $order['items'] ?? [];
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "<tr>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6;'><strong>{$item['product_name']}</strong></td>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6;'>{$item['sku']}</td>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6; text-align: right;'>{$item['quantity']}</td>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6; text-align: right;'>$" . number_format($item['unit_price'], 2) . "</td>"
                . "<td style='padding: 12px; border-bottom: 1px solid #dee2e6; text-align: right;'><strong>$" . number_format($item['subtotal'], 2) . "</strong></td>"
                . "</tr>";
        }
        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Receipt - Order #{$order['order_number']}</title></head><body style='margin:0;padding:0;font-family:Arial,sans-serif;background:#f8f9fa;'>"
            . "<div style='max-width:800px;margin:0 auto;background:white;padding:30px;border-radius:5px;'>"
            . "<div style='background:#2f3192;color:white;padding:30px;text-align:center;border-radius:5px 5px 0 0;'><h1 style='margin:0;font-size:32px;'>RECEIPT</h1><p style='margin:10px 0 0 0;font-size:18px;'>Demolition Traders</p></div>"
            . "<div style='display:flex;justify-content:space-between;margin:30px 0;'>"
            . "<div><strong style='color:#2f3192;'>Demolition Traders</strong><br>249 Kahikatea Drive<br>Hamilton 3204<br>Phone: 07-847-4989<br>Email: admin@demolitiontraders.co.nz<br><strong>GST: 45-514-609</strong></div>"
            . "<div style='text-align:right;'><strong>Receipt #:</strong> {$order['order_number']}<br><strong>Date:</strong>" . $orderDate->format('d/m/Y') . "<br><strong>Time:</strong>" . $orderDate->format('h:i A') . "</div>"
            . "</div>"
            . "<div style='background:#f8f9fa;padding:20px;margin-bottom:30px;border-left:4px solid #2f3192;'><strong style='color:#2f3192;'>BILL TO:</strong><br><strong>{$customerName}</strong><br>" . ($billing['address'] ?? '') . "<br>" . ($billing['city'] ?? '') . " " . ($billing['postcode'] ?? '') . "<br>Email: " . ($billing['email'] ?? $order['guest_email'] ?? 'N/A') . "<br>Phone: " . ($billing['phone'] ?? 'N/A') . "</div>"
            . "<table style='width:100%;border-collapse:collapse;margin-bottom:30px;'><thead style='background:#2f3192;color:white;'><tr><th style='padding:12px;text-align:left;'>Description</th><th style='padding:12px;text-align:left;'>SKU</th><th style='padding:12px;text-align:right;'>Qty</th><th style='padding:12px;text-align:right;'>Unit Price</th><th style='padding:12px;text-align:right;'>Amount</th></tr></thead><tbody>{$itemsHtml}</tbody></table>"
            . "<div style='text-align:right;'><div style='display:inline-block;min-width:300px;'><div style='background:#2f3192;color:white;padding:20px;font-size:18px;font-weight:bold;margin-bottom:15px;'><div style='display:flex;justify-content:space-between;'><span>TOTAL PAID</span><span>$" . number_format($order['total_amount'], 2) . "</span></div></div>"
            . "<div style='background:#f8f9fa;padding:15px;border:1px solid #dee2e6;'><div style='display:flex;justify-content:space-between;padding:5px 0;'><span>Subtotal (excl GST):</span><span>$" . number_format($order['subtotal'], 2) . "</span></div><div style='display:flex;justify-content:space-between;padding:5px 0;'><span>GST (15%):</span><span>$" . number_format($order['tax_amount'], 2) . "</span></div>"
            . (floatval($order['shipping_amount']) > 0 ? "<div style='display:flex;justify-content:space-between;padding:5px 0;'><span>Shipping:</span><span>$" . number_format($order['shipping_amount'], 2) . "</span></div>" : "")
            . "<div style='display:flex;justify-content:space-between;padding:10px 0 5px 0;border-top:2px solid #dee2e6;margin-top:10px;font-weight:bold;font-size:16px;'><span>Total (incl GST):</span><span>$" . number_format($order['total_amount'], 2) . "</span></div></div></div></div>"
            . "<div style='background:#28a745;color:white;padding:15px 0;text-align:center;font-size:20px;font-weight:bold;margin:30px 0 0 0;border-radius:5px;'>PAID IN FULL</div>"
            . "<div style='background:#f8f9fa;border:2px solid #dee2e6;padding:20px;margin-top:30px;border-radius:5px;'><h3 style='margin:0 0 15px 0;color:#2f3192;'>Company Terms of Trade</h3><div style='font-size:13px;color:#333;'>Demolition Traders Ltd offers a 30 day refund period on all goods.<br>Goods must be returned and inspected within 30 days of original purchase date for a refund.<br>Proof of original purchase is required as a condition of the returns policy.<br>Goods must be returned in the original purchase condition and be unused and undamaged.<br>Any items with scratch or scuff marks and that have been cut down or altered will not be refunded - whether in transit or during 3rd party handling.<br>Items with custom liners are non-refundable.<br>Any credit card transaction fees are non-refundable.</div></div>"
            . "</div></body></html>";
        return $html;
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
}
