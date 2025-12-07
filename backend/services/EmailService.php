<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $config;
    private $mailer;

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
            $this->mailer->SMTPDebug  = 0;
            
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
            $billing = $this->decodeAddress($order['billing_address'] ?? null);
            $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Customer';
            $toEmail = $this->config['dev_mode'] ? $this->config['dev_email'] : $customerEmail;
            // Sử dụng HTML giống frontend (đã có CSS receipt)
            $invoiceHtml = $this->generateTaxInvoiceHTML($order, $billing);
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $customerName);
            $this->mailer->Subject = "Tax Invoice - Order #{$order['order_number']}";
            $this->mailer->Body = "Hi {$customerName},<br><br>Thank you for your order, please find attached the receipt/tax invoice.<br><br>Regards,<br>Demolition Traders Team";
            
            // Try to generate PDF and attach, but don't fail if PDF generation fails
            try {
                $pdfPath = generate_invoice_pdf_html($invoiceHtml, 'invoice');
                if ($pdfPath && file_exists($pdfPath)) {
                    $this->mailer->addAttachment($pdfPath, 'Tax_Invoice_Order_' . $order['order_number'] . '.pdf');
                }
            } catch (Exception $pdfEx) {
                error_log("Warning: PDF generation failed for order #{$order['order_number']}: " . $pdfEx->getMessage());
                // Continue without PDF attachment - email will still send
            }
            
            $this->mailer->send();
            
            // Clean up if PDF was created
            if (isset($pdfPath) && $pdfPath && file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            
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
            $billing = $this->decodeAddress($order['billing_address'] ?? null);
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
    public function sendEmail($to, $subject, $body, $altBody = '') {
        if (!$this->config['enabled']) {
            error_log("Email sending is disabled");
            return false;
        }
        
        try {
            $toEmail = $this->config['dev_mode'] ? $this->config['dev_email'] : $to;
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            if ($altBody) {
                $this->mailer->AltBody = $altBody;
            }
            
            $this->mailer->send();
            error_log("Email sent successfully to: $toEmail - Subject: $subject");
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
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
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($adminEmail);
            $this->mailer->addReplyTo($data['email'], $data['name']);
            $this->mailer->Subject = "New Contact Form: {$data['subject']}";
            $this->mailer->Body = $html;
            $this->mailer->send();
            
            error_log("Contact form email sent to admin");
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Failed to send contact form email: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($adminEmail);
            $this->mailer->addReplyTo($data['email'], $data['name']);
            $this->mailer->Subject = "New Wanted Listing: {$data['category']} - {$data['name']}";
            $this->mailer->Body = $html;
            $this->mailer->send();
            
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
            $toEmail = $this->config['dev_mode'] ? $this->config['dev_email'] : $email;
            
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
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $name);
            $this->mailer->Subject = "Your Wanted Listing - Demolition Traders";
            $this->mailer->Body = $html;
            $this->mailer->send();
            
            error_log("Wanted listing confirmation sent to: $toEmail");
            return ['success' => true, 'message' => 'Confirmation email sent'];
            
        } catch (Exception $e) {
            error_log("Failed to send wanted listing confirmation: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
