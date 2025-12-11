<?php
// scripts/auto_fix_duplicate_slug.php
require_once __DIR__ . '/../backend/config/database.php';

$db = Database::getInstance();
$slug = 'acp-seratone';

$rows = $db->fetchAll("SELECT id, name, slug FROM categories WHERE slug = :slug ORDER BY id ASC", ['slug' => $slug]);

if (count($rows) <= 1) {
    echo "No duplicates to fix.\n";
    exit(0);
}

// Keep the lowest ID, fix the rest
$first = array_shift($rows);
echo "Keeping ID {$first['id']} as '$slug'.\n";
$i = 2;
foreach ($rows as $row) {
    $new_slug = $slug . '-' . $i;
    $db->update('categories', ['slug' => $new_slug], 'id = :id', ['id' => $row['id']]);
    echo "Updated ID {$row['id']} ('{$row['name']}') to slug '$new_slug'.\n";
    $i++;
}
echo "Auto-fix complete.\n";
