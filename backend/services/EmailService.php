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
}
