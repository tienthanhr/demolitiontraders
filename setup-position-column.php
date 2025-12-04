<?php
/**
 * Add position column to categories
 */
require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance();
    
    // Check if position column already exists by trying to query it
    try {
        $result = $db->fetchOne("SELECT position FROM categories LIMIT 1");
        echo "✓ Position column already exists\n";
    } catch (Exception $e) {
        // Column doesn't exist, need to add it
        echo "Adding position column...\n";
        
        $pdo = new PDO(
            'mysql:host=localhost;dbname=demolitiontraders',
            'root',
            ''
        );
        
        $pdo->exec("ALTER TABLE categories ADD COLUMN position INT DEFAULT 0 AFTER name");
        echo "✓ Added position column\n";
        
        $pdo->exec("ALTER TABLE categories ADD KEY idx_position (position)");
        echo "✓ Added position index\n";
        
        // Set sequential positions
        $categories = $pdo->query("SELECT id FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $pos = 1;
        $stmt = $pdo->prepare("UPDATE categories SET position = ? WHERE id = ?");
        foreach ($categories as $cat) {
            $stmt->execute([$pos++, $cat['id']]);
        }
        echo "✓ Set sequential positions for " . count($categories) . " categories\n";
    }
    
    echo "\nDone!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
