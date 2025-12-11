<?php
// scripts/fix_duplicate_slug.php
require_once __DIR__ . '/../backend/config/database.php';

$db = Database::getInstance();
$slug = 'acp-seratone';

$rows = $db->fetchAll("SELECT id, name, slug FROM categories WHERE slug = :slug", ['slug' => $slug]);

if (!$rows) {
    echo "No duplicate found for slug '$slug'.\n";
    exit(0);
}

if (count($rows) == 1) {
    echo "Only one entry found for slug '$slug', nothing to fix.\n";
    exit(0);
}

// Keep the first, rename the rest
$first = array_shift($rows);
echo "Keeping ID {$first['id']} as '$slug'.\n";
$i = 1;
foreach ($rows as $row) {
    $new_slug = $slug . '-' . $i;
    $db->update('categories', ['slug' => $new_slug], 'id = :id', ['id' => $row['id']]);
    echo "Updated ID {$row['id']} ('{$row['name']}') to slug '$new_slug'.\n";
    $i++;
}
echo "Duplicate slugs fixed.\n";
