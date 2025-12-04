<?php
// Load via API 
$response = file_get_contents('http://localhost/demolitiontraders/api/index.php?request=categories');
$data = json_decode($response, true);
$categories = $data['data'] ?? $data ?? [];

function array_find_cat($arr, $callback) {
    foreach ($arr as $item) {
        if ($callback($item)) return $item;
    }
    return null;
}

echo "=== ALL CATEGORIES ===\n";
echo "Total: " . count($categories) . "\n\n";

echo "=== MAIN CATEGORIES (parent_id IS NULL) ===\n";
$mains = array_filter($categories, fn($c) => !$c['parent_id']);
usort($mains, fn($a, $b) => ($a['position'] ?? 999) - ($b['position'] ?? 999));

foreach ($mains as $i => $cat) {
    echo ($i+1) . ". ID=" . $cat['id'] . " | " . str_pad($cat['name'], 30) . " | pos=" . str_pad((string)($cat['position'] ?? 'NULL'), 3) . " | active=" . $cat['is_active'] . "\n";
}

echo "\n=== SUBCATEGORIES (WITH parent_id) ===\n";
$subs = array_filter($categories, fn($c) => $c['parent_id']);
usort($subs, fn($a, $b) => ($a['position'] ?? 999) - ($b['position'] ?? 999));

$byParent = [];
foreach ($subs as $sub) {
    $pid = $sub['parent_id'];
    if (!isset($byParent[$pid])) $byParent[$pid] = [];
    $byParent[$pid][] = $sub;
}

foreach ($byParent as $parentId => $subCats) {
    $parent = array_find_cat($categories, fn($c) => $c['id'] == $parentId);
    echo "  Parent: " . ($parent['name'] ?? "ID=$parentId") . " (ID=$parentId)\n";
    foreach ($subCats as $sub) {
        echo "    - " . str_pad($sub['name'], 30) . " | pos=" . str_pad((string)($sub['position'] ?? 'NULL'), 3) . " | active=" . $sub['is_active'] . "\n";
    }
}

echo "\n=== CATEGORIES WITH parent_id BUT NOT IN MAIN LIST ===\n";
$orphanParentIds = [];
foreach ($subs as $sub) {
    $parentId = $sub['parent_id'];
    if (!array_find_cat($mains, fn($c) => $c['id'] == $parentId)) {
        if (!in_array($parentId, $orphanParentIds)) {
            $orphanParentIds[] = $parentId;
        }
    }
}

if (empty($orphanParentIds)) {
    echo "None\n";
} else {
    foreach ($orphanParentIds as $parentId) {
        $parent = array_find_cat($categories, fn($c) => $c['id'] == $parentId);
        echo "ID=" . $parentId . " | " . ($parent['name'] ?? "NOT FOUND") . " | parent_id=" . ($parent['parent_id'] ?? 'NULL') . "\n";
    }
}
?>
