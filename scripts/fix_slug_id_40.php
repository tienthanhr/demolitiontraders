<?php
// scripts/fix_slug_id_40.php
require_once __DIR__ . '/../backend/config/database.php';

$db = Database::getInstance();
$new_slug = 'acp-seratone-2';
$id = 40;

$db->update('categories', ['slug' => $new_slug], 'id = :id', ['id' => $id]);
echo "Updated ID $id to slug '$new_slug'.\n";
