<?php
/**
 * Auto-Update Script for Admin Pages
 * Upload this file to: /frontend/admin/update-admin-pages.php
 * Access: https://yourdomain.com/admin/update-admin-pages.php
 * 
 * ⚠️ DELETE THIS FILE AFTER USE!
 */

// Security: Simple password protection
define('UPDATE_PASSWORD', 'demo2024secure'); // Change this!

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] !== UPDATE_PASSWORD) {
        die('❌ Incorrect password!');
    }
} else {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Update Admin Pages</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                max-width: 500px; 
                margin: 100px auto; 
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            input[type="password"] {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            button {
                width: 100%;
                padding: 12px;
                background: #2f3192;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
            }
            button:hover { opacity: 0.9; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>🔧 Update Admin Pages</h2>
            <p>Enter password to proceed:</p>
            <form method="POST">
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Update Pages</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// Main Update Logic
// ============================================

$adminDir = __DIR__;
$results = [];
$errors = [];

// List of admin pages to update
$pages = [
    'admins.php',
    'categories.php',
    'contact-messages.php',
    'customers.php',
    'low-stock.php',
    'orders.php',
    'products.php',
    'sell-to-us.php',
    'settings.php',
    'wanted-listings.php'
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Update Results</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            background: #1e1e1e; 
            color: #d4d4d4; 
            padding: 20px;
            line-height: 1.6;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #569cd6; }
        h1 { color: #4ec9b0; }
        pre { 
            background: #2d2d2d; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto;
            border-left: 3px solid #4ec9b0;
        }
        .result-box {
            background: #2d2d2d;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<h1>🔧 Admin Pages Update Script</h1>
<p>Starting update process...</p>
<hr>
";

flush();

// Process each page
foreach ($pages as $page) {
    $filePath = $adminDir . '/' . $page;
    
    echo "<div class='result-box'>";
    echo "<strong class='info'>📝 Processing: $page</strong><br>";
    
    if (!file_exists($filePath)) {
        echo "<span class='error'>  ❌ File not found!</span><br>";
        $errors[] = "$page: File not found";
        echo "</div>";
        continue;
    }
    
    // Read file content
    $content = file_get_contents($filePath);
    
    if ($content === false) {
        echo "<span class='error'>  ❌ Could not read file!</span><br>";
        $errors[] = "$page: Could not read file";
        echo "</div>";
        continue;
    }
    
    // Create backup
    $backupPath = $filePath . '.backup-' . date('YmdHis');
    if (file_put_contents($backupPath, $content) === false) {
        echo "<span class='error'>  ❌ Could not create backup!</span><br>";
        $errors[] = "$page: Could not create backup";
        echo "</div>";
        continue;
    }
    echo "<span class='success'>  ✅ Backup created: " . basename($backupPath) . "</span><br>";
    
    $modified = false;
    
    // Check 1: Add auth-check.php if not present
    if (!preg_match("/require_once\s+['\"]auth-check\.php['\"]/", $content)) {
        // Find the line with require_once '../config.php';
        $pattern = "/(require_once\s+['\"]\.\.\/config\.php['\"]\s*;)/";
        if (preg_match($pattern, $content)) {
            $replacement = "$1\nrequire_once 'auth-check.php'; // Added by update script";
            $content = preg_replace($pattern, $replacement, $content, 1);
            echo "<span class='success'>  ✅ Added auth-check.php</span><br>";
            $modified = true;
        } else {
            echo "<span class='warning'>  ⚠️  Could not find config.php require statement</span><br>";
        }
    } else {
        echo "<span class='info'>  ℹ️  Already has auth-check.php</span><br>";
    }
    
    // Check 2: Remove old auth code
    $oldAuthPattern = '/\/\/\s*Check if user is admin.*?if\s*\(!isset\(\$_SESSION\[.*?\]\)\s*\|\|\s*!\$isAdmin\)\s*\{.*?exit;\s*\}/s';
    if (preg_match($oldAuthPattern, $content)) {
        $content = preg_replace($oldAuthPattern, '// Old auth code removed by update script', $content);
        echo "<span class='success'>  ✅ Removed old auth code</span><br>";
        $modified = true;
    }
    
    // Check 3: Update CSS path to use BASE_PATH with cache buster
    $cssPatterns = [
        '/href="admin\/admin-style\.css"/' => 'href="<?php echo BASE_PATH; ?>admin/admin-style.css?v=<?php echo time(); ?>"',
        '/href="<?php echo BASE_PATH; ?>admin\/admin-style\.css"(?!\?v)/' => 'href="<?php echo BASE_PATH; ?>admin/admin-style.css?v=<?php echo time(); ?>"'
    ];
    
    foreach ($cssPatterns as $pattern => $replacement) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            echo "<span class='success'>  ✅ Updated CSS path</span><br>";
            $modified = true;
            break;
        }
    }
    
    // Check 4: Update JS path
    if (preg_match('/src="assets\/js\/api-helper\.js"/', $content)) {
        $content = str_replace(
            'src="assets/js/api-helper.js"',
            'src="<?php echo BASE_PATH; ?>assets/js/api-helper.js"',
            $content
        );
        echo "<span class='success'>  ✅ Updated JS path</span><br>";
        $modified = true;
    }
    
    // Save modified content
    if ($modified) {
        if (file_put_contents($filePath, $content) !== false) {
            echo "<span class='success'>  ✅ File updated successfully!</span><br>";
            $results[] = "$page: Updated successfully";
        } else {
            echo "<span class='error'>  ❌ Failed to save file!</span><br>";
            $errors[] = "$page: Failed to save";
            
            // Restore backup
            file_put_contents($filePath, file_get_contents($backupPath));
            echo "<span class='warning'>  ⚠️  Restored from backup</span><br>";
        }
    } else {
        echo "<span class='info'>  ℹ️  No changes needed</span><br>";
        $results[] = "$page: No changes needed";
    }
    
    echo "</div>";
    flush();
}

// Summary
echo "<hr>";
echo "<h2>📊 Summary</h2>";
echo "<pre>";
echo "Total files processed: " . count($pages) . "\n";
echo "Successfully processed: " . count($results) . "\n";
echo "Errors: " . count($errors) . "\n\n";

if (count($errors) > 0) {
    echo "<span class='error'>Errors encountered:</span>\n";
    foreach ($errors as $error) {
        echo "  ❌ $error\n";
    }
}

echo "\n<span class='success'>✅ Update process completed!</span>\n";
echo "</pre>";

echo "<hr>";
echo "<h2>⚠️ Next Steps:</h2>";
echo "<ol>";
echo "<li><strong>Delete this file:</strong> update-admin-pages.php</li>";
echo "<li><strong>Upload auth-check.php</strong> to /frontend/admin/</li>";
echo "<li><strong>Update .htaccess</strong> in /frontend/admin/</li>";
echo "<li><strong>Test:</strong> Visit <a href='test-auth.php' style='color: #4ec9b0;'>test-auth.php</a></li>";
echo "<li><strong>Clear browser cache</strong> (Ctrl+Shift+R)</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php' style='color: #4ec9b0;'>← Back to Admin Dashboard</a></p>";

echo "</body></html>";
?>