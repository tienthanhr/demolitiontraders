<?php
/**
 * Export Products to CSV
 */
require_once '../../core/bootstrap.php'; // Ensures session is started securely
require_once 'csrf_middleware.php';   // Handles admin auth and CSRF validation

    $db = Database::getInstance();
    
    // Fetch all products with category names
    $products = $db->fetchAll(
        "SELECT p.*, c.name as category_name 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         ORDER BY p.id ASC"
    );
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=products_export_' . date('Y-m-d_His') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write CSV header
    fputcsv($output, [
        'ID',
        'SKU',
        'Name',
        'Description',
        'Category',
        'Price',
        'Stock',
        'Condition',
        'Status',
        'Featured',
        'Images',
        'Created At',
        'Updated At'
    ]);
    
    // Write product data
    foreach ($products as $product) {
        fputcsv($output, [
            $product['id'],
            $product['sku'] ?? '',
            $product['name'],
            $product['description'] ?? '',
            $product['category_name'] ?? '',
            $product['price'],
            $product['stock_quantity'] ?? 0,
            $product['condition'] ?? 'new',
            $product['status'] ?? 'active',
            $product['is_featured'] ? 'Yes' : 'No',
            $product['image_url'] ?? '',
            $product['created_at'] ?? '',
            $product['updated_at'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error exporting products: ' . $e->getMessage());
}
