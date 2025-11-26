<?php
/**
 * Quick Diagnostic Test
 * Check MySQL connection and configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>MySQL Diagnostic Test</h1>";

// Test 1: Check if .env file exists
echo "<h2>1. Configuration Files:</h2>";
$envFile = __DIR__ . '/.env';
$envExampleFile = __DIR__ . '/.env.example';

if (file_exists($envFile)) {
    echo "<p style='color: green;'>✓ .env file exists</p>";
} else {
    echo "<p style='color: red;'>✗ .env file NOT found</p>";
    if (file_exists($envExampleFile)) {
        echo "<p style='color: orange;'>⚠ Copying .env.example to .env...</p>";
        copy($envExampleFile, $envFile);
        echo "<p style='color: green;'>✓ .env file created</p>";
    }
}

// Test 2: Load config
echo "<h2>2. Loading Configuration:</h2>";
try {
    require_once __DIR__ . '/backend/config/config.php';
    echo "<p style='color: green;'>✓ Config loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Config error: " . $e->getMessage() . "</p>";
}

// Test 3: Check MySQL connection directly
echo "<h2>3. Direct MySQL Connection Test:</h2>";

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'demolitiontraders';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>Host</td><td>{$host}</td></tr>";
echo "<tr><td>Port</td><td>{$port}</td></tr>";
echo "<tr><td>Database</td><td>{$dbname}</td></tr>";
echo "<tr><td>Username</td><td>{$username}</td></tr>";
echo "<tr><td>Password</td><td>" . (empty($password) ? '(empty)' : '***') . "</td></tr>";
echo "</table>";

// Test connection
try {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<p style='color: green;'>✓ MySQL Server Connection: <strong>SUCCESS</strong></p>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$dbname}'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "<p style='color: green;'>✓ Database '{$dbname}' exists</p>";
        
        // Connect to database
        $pdo->exec("USE {$dbname}");
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p style='color: green;'>✓ Found " . count($tables) . " tables</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>{$table}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠ Database is empty. Need to import schema.sql</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Database '{$dbname}' does NOT exist</p>";
        echo "<p><strong>Creating database...</strong></p>";
        
        try {
            $pdo->exec("CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<p style='color: green;'>✓ Database '{$dbname}' created successfully!</p>";
            echo "<p><strong>Now import:</strong> database/schema.sql</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Failed to create database: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ MySQL Connection Failed!</p>";
    echo "<pre style='background: #ffe6e6; padding: 15px; border-radius: 5px;'>";
    echo "Error: " . $e->getMessage();
    echo "</pre>";
    
    echo "<h3>Possible Solutions:</h3>";
    echo "<ol>";
    echo "<li><strong>Check if MySQL is running in XAMPP Control Panel</strong></li>";
    echo "<li>Make sure port 3306 is not blocked</li>";
    echo "<li>Check username/password in .env file</li>";
    echo "<li>Try restarting XAMPP</li>";
    echo "</ol>";
}

// Test 4: Check if PDO MySQL driver is installed
echo "<h2>4. PHP PDO MySQL Driver:</h2>";
if (extension_loaded('pdo_mysql')) {
    echo "<p style='color: green;'>✓ PDO MySQL driver is installed</p>";
} else {
    echo "<p style='color: red;'>✗ PDO MySQL driver is NOT installed</p>";
    echo "<p>Enable it in php.ini: extension=pdo_mysql</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Make sure MySQL is running in XAMPP</li>";
echo "<li>If database doesn't exist or is empty, import schema.sql</li>";
echo "<li>Go to: <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
echo "<li>After import, reload this page</li>";
echo "</ol>";
?>
