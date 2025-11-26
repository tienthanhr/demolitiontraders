<?php
function getProduct($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateStock($productId, $qtySold) {
    global $pdo;
    $product = getProduct($productId);
    $newStock = $product['stock'] - $qtySold;

    $stmt = $pdo->prepare("UPDATE products SET stock=? WHERE id=?");
    $stmt->execute([$newStock, $productId]);

    if ($newStock <= $product['min_stock']) {
        sendLowStockAlert($product, $newStock);
        autoCreateReorderSuggestion($product);
    }
}

function sendLowStockAlert($product, $newStock) {
    $to = "your@email.com";
    $subject = "LOW STOCK: {$product['name']}";
    $message = "Item: {$product['name']}
Current Stock: {$newStock}
Recommended Reorder: {$product['reorder_qty']}
Item Code: {$product['item_code']}";
    mail($to, $subject, $message);
}

function autoCreateReorderSuggestion($product) {
    file_put_contents(__DIR__ . "/reorder_suggestions.txt",
        "{$product['item_code']} - {$product['name']} - ORDER {$product['reorder_qty']}
",
        FILE_APPEND
    );
}
?>