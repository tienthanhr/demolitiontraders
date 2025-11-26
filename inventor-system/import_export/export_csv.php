<?php
require '../config.php';
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$out = fopen("export.csv", "w");
foreach ($products as $p) {
    fputcsv($out, $p);
}
echo "Export completed.";
?>