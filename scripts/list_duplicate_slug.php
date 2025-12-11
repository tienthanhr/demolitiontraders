<?php
// scripts/list_duplicate_slug.php
require_once __DIR__ . '/../backend/config/database.php';

$db = Database::getInstance();
$slug = 'acp-seratone';

$rows = $db->fetchAll("SELECT * FROM categories WHERE slug = :slug", ['slug' => $slug]);

if (!$rows) {
    echo "No rows found for slug '$slug'.\n";
    exit(0);
}

foreach ($rows as $row) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Slug: {$row['slug']}\n";
}
echo "Total found: ".count($rows)."\n";
