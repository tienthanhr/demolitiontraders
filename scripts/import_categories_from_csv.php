<?php
// Simple CLI script to import categories from CSV into the categories table.
// Usage: php import_categories_from_csv.php path/to/export.csv [--commit]

require_once __DIR__ . '/../backend/config/database.php';

$argv = $_SERVER['argv'];
array_shift($argv); // drop script name

$csvPath = $argv[0] ?? __DIR__ . '/../export_table_products_cat_11Dec2025_12_19.csv';
$commit = in_array('--commit', $argv, true);

echo "Import categories from CSV: $csvPath\n";
echo $commit ? "Mode: COMMIT (will write to DB)\n" : "Mode: DRY-RUN (no DB writes)\n";

if (!file_exists($csvPath)) {
    echo "CSV file not found: $csvPath\n";
    exit(1);
}

$db = Database::getInstance();

// Read CSV
$rows = [];
$fh = fopen($csvPath, 'r');
if ($fh === false) {
    echo "Unable to open CSV file: $csvPath\n";
    exit(1);
}

$headers = [];
// Use explicit escape parameter to silence deprecation warnings in newer PHP versions
while (($line = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    if (empty($headers)) {
        $headers = $line;
        continue;
    }
    if (count(array_filter($line)) === 0) continue; // skip empty lines

    $row = array_combine($headers, $line);
    $rows[] = $row;
}
fclose($fh);

// Normalize header keys to simple keys
function h($k) {
    return strtolower(trim(str_replace(["\n","\r","\t"],'',$k)));
}

// Map CSV columns we expect
$map = [];
foreach ($headers as $i => $h) {
    $key = h($h);
    $map[$key] = $h;
}

function slugify($str) {
    $slug = strtolower(trim($str));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Load existing categories
$existing = $db->fetchAll("SELECT id, name, slug FROM categories");
$existingByName = [];
$existingBySlug = [];
foreach ($existing as $r) {
    $existingByName[strtolower($r['name'])] = $r['id'];
    $existingBySlug[$r['slug']] = $r['id'];
}

$created = 0; $updated = 0; $skipped = 0;
$toUpdateParent = [];

// First pass: create or update categories without parent or deferred parent
foreach ($rows as $row) {
    $name = trim($row[$map['title']] ?? ($row[$map['title']] ?? ''));
    if ($name === '') continue;
    $parentName = trim($row[$map['parent category']] ?? '');
    $slug = slugify($name);
    $description = trim($row[$map['description']] ?? '');
    $showOnline = strtoupper(trim($row[$map['show online']] ?? '')) === 'Y' ? 1 : 0;
    // Sort number may be used as display_order
    $displayOrderRaw = trim($row[$map['sort number']] ?? '');
    $displayOrder = 0;
    if ($displayOrderRaw !== '') {
        // try to parse as number (may contain dots)
        $displayOrder = intval(floatval(str_replace(',', '.', $displayOrderRaw)) * 1000);
    }

    $existsId = $existingByName[strtolower($name)] ?? null;
    if ($existsId) {
        // Update if needed
        $updateData = ['name' => $name, 'slug' => $slug, 'description' => $description, 'display_order' => $displayOrder, 'is_active' => $showOnline];
        if ($commit) {
            $db->update('categories', $updateData, 'id = :id', ['id' => $existsId]);
        }
        $updated++;
    } else {
        // Ensure slug uniqueness
        $uniqueSlug = $slug;
        $suffix = 1;
        while (isset($existingBySlug[$uniqueSlug])) {
            $uniqueSlug = $slug . '-' . $suffix;
            $suffix++;
        }
        $insertData = ['name' => $name, 'slug' => $uniqueSlug, 'description' => $description, 'parent_id' => null, 'display_order' => $displayOrder, 'is_active' => $showOnline, 'image' => null, 'meta_title' => null, 'meta_description' => null];
        if ($commit) {
            $newId = $db->insert('categories', $insertData);
            $existingByName[strtolower($name)] = $newId;
            $existingBySlug[$uniqueSlug] = $newId;
        } else {
            // Assign a pseudo id to keep mapping during dry-run
            $newId = 'dry-' . $created;
            $existingByName[strtolower($name)] = $newId;
            $existingBySlug[$uniqueSlug] = $newId;
        }
        $created++;
    }

    // Parent mapping to set on second pass
    if ($parentName !== '') {
        $toUpdateParent[] = ['name' => $name, 'parentName' => $parentName];
    }
}

// Second pass: set parent relationships
foreach ($toUpdateParent as $r) {
    $name = $r['name'];
    $parentName = $r['parentName'];
    $childId = $existingByName[strtolower($name)] ?? null;
    $parentId = $existingByName[strtolower($parentName)] ?? null;
    if (!$childId) {
        echo "Skipping parent update for '{$name}': child not found\n";
        $skipped++;
        continue;
    }
    if (!$parentId) {
        // Parent not found - try to create
        echo "Parent category '{$parentName}' not found - creating\n";
        if ($commit) {
            $parentSlug = slugify($parentName);
            $pInsert = ['name' => $parentName, 'slug' => $parentSlug, 'description' => null, 'parent_id' => null, 'display_order' => 0, 'is_active' => 1];
            $newParentId = $db->insert('categories', $pInsert);
            $existingByName[strtolower($parentName)] = $newParentId;
            $parentId = $newParentId;
        } else {
            $parentId = 'dry-parent-' . rand(1000, 9999);
            $existingByName[strtolower($parentName)] = $parentId;
        }
    }

    if ($childId && $parentId && $childId !== $parentId) {
        if ($commit) {
            // update parent_id
            $db->update('categories', ['parent_id' => $parentId], 'id = :id', ['id' => $childId]);
        }
        echo "Set parent of '{$name}' to '{$parentName}'\n";
    }
}

echo "Done. Created: $created, Updated: $updated, Skipped: $skipped\n";

if (!$commit) {
    echo "Dry-run completed. To apply changes, re-run with --commit\n";
}

return 0;

?>
