<?php
require '../config.php';
$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Inventory Dashboard</h2>
<table border="1" cellpadding="8">
<tr><th>Item Code</th><th>Name</th><th>Stock</th><th>Min</th><th>Status</th></tr>
<?php foreach ($products as $p): ?>
<tr>
<td><?= $p['item_code'] ?></td>
<td><?= $p['name'] ?></td>
<td><?= $p['stock'] ?></td>
<td><?= $p['min_stock'] ?></td>
<td><?= ($p['stock'] <= $p['min_stock']) ? '<span style="color:red;">LOW</span>' : 'OK' ?></td>
</tr>
<?php endforeach; ?>
</table>