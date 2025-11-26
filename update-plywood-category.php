<?php
require_once 'backend/config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Update all products from category 18 (Treated Plywood) to category 4 (Plywood)
$stmt = $conn->prepare("UPDATE products SET category_id = 4 WHERE category_id = 18");
$stmt->execute();

echo "Updated " . $stmt->rowCount() . " products from 'Treated Plywood' to 'Plywood' category\n";

// Also update category 12 (Untreated Plywood) products
$stmt2 = $conn->prepare("UPDATE products SET category_id = 4 WHERE category_id = 12");
$stmt2->execute();

echo "Updated " . $stmt2->rowCount() . " products from 'Untreated Plywood' to 'Plywood' category\n";

echo "\nAll plywood products are now in category 4 (Plywood)\n";
