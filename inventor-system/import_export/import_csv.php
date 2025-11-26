<?php
require '../config.php';
$csv = fopen("products.csv", "r");
while (($row = fgetcsv($csv)) !== false) {
    list($code, $name, $price, $stock, $min, $reorder) = $row;
    $stmt = $pdo->prepare("
        INSERT INTO products (item_code, name, price, stock, min_stock, reorder_qty)
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->execute([$code, $name, $price, $stock, $min, $reorder]);
}
echo "Import complete.";
?>