<?php
/**
 * Smart Script - Auto-detect admin folder location
 * Place ANYWHERE in your project
 * Access: http://localhost/demolitiontraders/add-auth-smart.php
 * Password: demo2024secure
 */

define('UPDATE_PASSWORD', 'demo2024secure');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] !== UPDATE_PASSWORD) {
        die('❌ Incorrect password!');
    }
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Smart Auth Check Installer</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 500px;
                width: 100%;
            }
            h2 { color: #2f3192; margin-bottom: 10px; }
            input[type="password"] {
                width: 100%;
                padding: 15px;
                margin: 10px 0;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
            }
            button {
                width: 100%;
                padding: 15px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>🔐 Smart Auth Installer</h2>
            <p style="color: #666; margin-bottom: 20px;">Auto-detects admin folder location</p>
            <form method="POST">
                <input type="password" name="password" placeholder="Password" required autofocus>
                <button type="submit">🚀 Install Auth Check</button>
            </form>
            <p style="margin-top: 15px; font-size: 12px; color: #999;">Password: demo2024secure</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// Auto-detect admin folder
// ============================================

function findAdminFolder($startDir) {
    $possiblePaths = [
        $startDir . '/frontend/admin',
        $startDir . '/admin',
        dirname($startDir) . '/frontend/admin',
        dirname($startDir) . '/admin',
    ];
    
    foreach ($possiblePaths as $path) {
        if (is_dir($path) && file_exists($path . '/index.php')) {
            return $path;
        }
    }
    
    return null;
}

$currentDir = __DIR__;
$adminDir = findAdminFolder($currentDir);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Auth Check Installation Results</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace; 
            background: #1e1e1e; 
            color: #d4d4d4; 
            padding: 20px;
            line-height: 1.8;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #569cd6; }
        h1 { color: #4ec9b0; margin-bottom: 20px; }
        .box {
            background: #2d2d2d;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #4ec9b0;
        }
        code { 
            background: #3e3e3e; 
            padding: 2px 6px; 
            border-radius: 3px; 
            color: #ce9178;
        }
        hr { border: none; border-top: 1px solid #404040; margin: 30px 0; }
    </style>
</head>
<body>
    <h1>🔐 Smart Auth Check Installer</h1>
    
<?php

echo "<div class='box'>";
echo "<strong class='info'>🔍 Detecting Admin Folder...</strong><br>";
echo "Current directory: <code>{$currentDir}</code><br>";
flush();

if (!$adminDir) {
    echo "<span class='error'>❌ Could not find admin folder!</span><br>";
    echo "<span class='warning'>Searched in:</span><br>";
    echo "- <code>{$currentDir}/frontend/admin</code><br>";
    echo "- <code>{$currentDir}/admin</code><br>";
    echo "- <code>" . dirname($currentDir) . "/frontend/admin</code><br>";
    echo "- <code>" . dirname($currentDir) . "/admin</code><br>";
    echo "</div>";
    echo "<div class='box'><span class='error'>Please place this script in the project root or provide correct path.</span></div>";
    echo "</body></html>";
    exit;
}

echo "<span class='success'>✅ Found admin folder: <code>{$adminDir}</code></span><br>";
echo "</div>";

// List of admin pages
$pages = [
    'admins.php',
    'categories.php',
    'contact-messages.php',
    'customers.php',
    'index.php',
    'low-stock.php',
    'orders.php',
    'products.php',
    'sell-to-us.php',
    'settings.php',
    'wanted-listings.php'
];

echo "<hr>";
echo "<h2>📝 Processing Files</h2>";

$successCount = 0;
$skipCount = 0;
$errorCount = 0;

foreach ($pages as $page) {
    $filePath = $adminDir . '/' . $page;
    
    echo "<div class='box'>";
    echo "<strong class='info'>{$page}</strong><br>";
    
    if (!file_exists($filePath)) {
        echo "<span class='error'>❌ Not found: <code>{$filePath}</code></span><br>";
        $errorCount++;
        echo "</div>";
        continue;
    }
    
    echo "<span class='success'>✅ Found file</span><br>";
    
    $content = file_get_contents($filePath);
    
    // Check if already has auth-check
    if (preg_match("/require_once\s+['\"]auth-check\.php['\"]/", $content)) {
        echo "<span class='info'>ℹ️ Already has auth-check.php</span><br>";
        $skipCount++;
        echo "</div>";
        continue;
    }
    
    // Create backup
    $backupPath = $filePath . '.backup-' . date('His');
    file_put_contents($backupPath, $content);
    echo "<span class='success'>💾 Backup created</span><br>";
    
    // Find insertion point - try multiple patterns
    $inserted = false;
    
    // Pattern 1: After require_once '../config.php';
    if (preg_match("/(require_once\s+['\"]\.\.\/config\.php['\"]\s*;)/", $content)) {
        $newContent = preg_replace(
            "/(require_once\s+['\"]\.\.\/config\.php['\"]\s*;)/",
            "$1\nrequire_once 'auth-check.php';",
            $content,
            1
        );
        
        if ($newContent !== $content && file_put_contents($filePath, $newContent)) {
            echo "<span class='success'>✅ Added auth-check.php</span><br>";
            $successCount++;
            $inserted = true;
        }
    }
    
    if (!$inserted) {
        echo "<span class='warning'>⚠️ Could not find insertion point</span><br>";
        echo "<span class='info'>💡 You may need to add manually after config.php</span><br>";
        $errorCount++;
    }
    
    echo "</div>";
    flush();
}

// Summary
echo "<hr>";
echo "<div class='box' style='border-color: #4ec9b0;'>";
echo "<h2>📊 Installation Summary</h2>";
echo "<p style='margin-top: 15px;'>";
echo "Admin folder: <code>{$adminDir}</code><br>";
echo "Total files: <strong>" . count($pages) . "</strong><br>";
echo "Successfully added: <strong class='success'>{$successCount}</strong><br>";
echo "Already had auth: <strong class='info'>{$skipCount}</strong><br>";
echo "Errors/Manual: <strong class='" . ($errorCount > 0 ? 'warning' : 'success') . "'>{$errorCount}</strong>";
echo "</p>";
echo "</div>";

// Next steps
echo "<div class='box' style='border-color: #dcdcaa;'>";
echo "<h2>⚠️ Next Steps</h2>";
echo "<ol style='margin-left: 20px; margin-top: 10px;'>";

// Check if auth-check.php exists
$authCheckPath = $adminDir . '/auth-check.php';
if (!file_exists($authCheckPath)) {
    echo "<li style='margin: 10px 0;'><strong class='error'>CRITICAL:</strong> Create <code>auth-check.php</code> in <code>{$adminDir}/</code></li>";
} else {
    echo "<li style='margin: 10px 0;'><strong class='success'>✅</strong> auth-check.php exists</li>";
}

echo "<li style='margin: 10px 0;'>Test: Logout and try accessing <code>/admin/index.php</code></li>";
echo "<li style='margin: 10px 0;'>Should redirect to login → Auth working!</li>";
echo "<li style='margin: 10px 0;'><strong class='error'>DELETE</strong> this script after testing</li>";

if ($errorCount > 0) {
    echo "<li style='margin: 10px 0;'><strong class='warning'>Manual Fix Needed:</strong> {$errorCount} files need manual addition of auth-check</li>";
}

echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center;'>";
echo "<a href='/demolitiontraders/admin/index.php' style='color: #4ec9b0; font-size: 18px; font-weight: bold;'>→ Test Admin Access</a>";
echo "</p>";

echo "</body></html>";
?>