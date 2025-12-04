<?php
require 'backend/config.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=demolition_traders', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query('SELECT id, name, parent_id, position FROM categories WHERE parent_id IS NULL ORDER BY position ASC, name ASC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Main Categories Order in Database:\n";
    echo "====================================\n";
    foreach($rows as $i => $row) {
        echo ($i+1) . '. ' . $row['name'] . ' (position: ' . $row['position'] . ', id: ' . $row['id'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
