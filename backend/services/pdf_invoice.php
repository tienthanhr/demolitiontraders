<?php
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/POP3.php';

// Simple PDF generator using FPDF
require_once __DIR__ . '/fpdf.php';

require_once __DIR__ . '/mpdf_autoload.php';

function generate_invoice_pdf($order, $billing, $type = 'invoice') {
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, $type === 'invoice' ? 'Tax Invoice' : 'Receipt', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Demolition Traders', 0, 1, 'C');
    $pdf->Cell(0, 10, '249 Kahikatea Drive, Hamilton', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Phone: 07-847-4989', 0, 1, 'C');
    $pdf->Cell(0, 10, 'GST: 45-514-609', 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Order #: ' . $order['order_number'], 0, 1);
    $pdf->Cell(0, 10, 'Date: ' . date('d/m/y', strtotime($order['created_at'])), 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'Bill To: ' . ($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''), 0, 1);
    $pdf->Cell(0, 10, 'Email: ' . ($billing['email'] ?? ''), 0, 1);
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(80, 10, 'Product', 1);
    $pdf->Cell(30, 10, 'Qty', 1);
    $pdf->Cell(40, 10, 'Unit Price', 1);
    $pdf->Cell(40, 10, 'Total', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 12);
    foreach ($order['items'] as $item) {
        $pdf->Cell(80, 10, $item['product_name'], 1);
        $pdf->Cell(30, 10, $item['quantity'], 1);
        $pdf->Cell(40, 10, number_format($item['unit_price'], 2), 1);
        $pdf->Cell(40, 10, number_format($item['subtotal'], 2), 1);
        $pdf->Ln();
    }
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'Total: $' . number_format($order['total_amount'], 2), 0, 1, 'R');
    $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid($type . '_') . '.pdf';
    $pdf->Output('F', $filename);
    return $filename;
}

function generate_invoice_pdf_html($html, $type = 'invoice') {
    $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid($type . '_') . '.pdf';
    $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir()]);
    $mpdf->WriteHTML($html);
    $mpdf->Output($filename, \Mpdf\Output\Destination::FILE);
    return $filename;
}
