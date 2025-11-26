<?php
require 'config.php';
require 'stock_functions.php';
require 'xero_functions.php';

$orderItems = $_POST['items'] ?? json_decode(file_get_contents('php://input'), true)['items'];

$pdo->beginTransaction();
$stmt = $pdo->prepare("INSERT INTO orders () VALUES ()");
$stmt->execute();
$orderId = $pdo->lastInsertId();

$invoiceLines = [];

foreach ($orderItems as $item) {
    $productId = $item['id'];
    $qty       = $item['qty'];
    $product = getProduct($productId);
    updateStock($productId, $qty);

    $stmt = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, qty, price)
         VALUES (?,?,?,?)"
    );
    $stmt->execute([$orderId, $productId, $qty, $product['price']]);

    $invoiceLines[] = [
        'name'      => $product['name'],
        'item_code' => $product['item_code'],
        'qty'       => $qty,
        'price'     => $product['price']
    ];
}

$pdo->commit();

sendInvoiceToXero($invoiceLines);

echo "Order processed.";
?>