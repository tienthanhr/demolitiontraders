<?php
/**
 * Export MySQL Data to Render PostgreSQL
 * Demolition Traders
 * 
 * Usage: php export-mysql-to-render.php
 */

// MySQL Connection
$mysql_host = 'localhost';
$mysql_user = 'root';
$mysql_pass = '';
$mysql_db = 'demolitiontraders';

// PostgreSQL Connection (Render)
$render_db_url = getenv('DATABASE_URL');
if (!$render_db_url) {
    die("ERROR: DATABASE_URL not set. Add it to .env or environment variables.\n");
}

echo "=== Demolition Traders Database Migration ===\n";
echo "Exporting MySQL → PostgreSQL\n\n";

// Connect to MySQL
try {
    $mysql = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
    if ($mysql->connect_error) {
        die("MySQL Connection Error: " . $mysql->connect_error . "\n");
    }
    echo "✓ Connected to MySQL: $mysql_db\n";
} catch (Exception $e) {
    die("MySQL Error: " . $e->getMessage() . "\n");
}

// Connect to PostgreSQL
try {
    $pdo = new PDO($render_db_url);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to Render PostgreSQL\n";
} catch (Exception $e) {
    die("PostgreSQL Error: " . $e->getMessage() . "\n");
}

// Get all tables from MySQL
$tables_result = $mysql->query("SHOW TABLES");
$tables = [];
while ($row = $tables_result->fetch_row()) {
    $tables[] = $row[0];
}

echo "\nFound " . count($tables) . " tables\n";
echo "Migrating data...\n\n";

$total_rows = 0;
$errors = [];

// Disable foreign key checks
try {
    $pdo->exec("SET session_replication_role = 'replica'");
} catch (Exception $e) {
    // Ignore if not supported
}

// Migrate each table
foreach ($tables as $table) {
    try {
        // Get column info from MySQL
        $columns_result = $mysql->query("DESCRIBE $table");
        $columns = [];
        $column_types = [];
        
        while ($col = $columns_result->fetch_assoc()) {
            $columns[] = $col['Field'];
            $column_types[$col['Field']] = $col['Type'];
        }
        
        // Get data from MySQL
        $data_result = $mysql->query("SELECT * FROM $table");
        $row_count = 0;
        
        if ($data_result->num_rows > 0) {
            // Prepare insert statement
            $col_list = implode(', ', array_map(fn($c) => '"' . $c . '"', $columns));
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $insert_sql = "INSERT INTO \"$table\" ($col_list) VALUES ($placeholders)";
            $stmt = $pdo->prepare($insert_sql);
            
            // Batch insert (faster)
            while ($row = $data_result->fetch_assoc()) {
                $values = [];
                foreach ($columns as $col) {
                    $value = $row[$col];
                    // Convert MySQL boolean to PostgreSQL
                    if (in_array($col, ['is_active', 'is_featured', 'is_deleted']) && 
                        ($value === '0' || $value === '1' || $value === 0 || $value === 1)) {
                        $values[] = (bool)$value;
                    } else {
                        $values[] = $value;
                    }
                }
                
                try {
                    $stmt->execute($values);
                    $row_count++;
                    $total_rows++;
                } catch (Exception $e) {
                    $errors[] = "Table $table, Row $row_count: " . $e->getMessage();
                }
            }
            
            echo "✓ $table: $row_count rows\n";
        } else {
            echo "○ $table: 0 rows\n";
        }
        
    } catch (Exception $e) {
        $errors[] = "Table $table: " . $e->getMessage();
        echo "✗ $table: ERROR\n";
    }
}

// Re-enable foreign key checks
try {
    $pdo->exec("SET session_replication_role = 'origin'");
} catch (Exception $e) {
    // Ignore if not supported
}

echo "\n=== Migration Summary ===\n";
echo "Total rows migrated: $total_rows\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// Close connections
$mysql->close();
$pdo = null;

echo "\n✓ Migration complete!\n";
?>
