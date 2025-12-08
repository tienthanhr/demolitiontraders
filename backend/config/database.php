<?php
/**
 * Database Configuration and Connection
 * Demolition Traders E-commerce Platform
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Connect to database
     */
    private function connect() {
        try {
            // Check if using PostgreSQL (Render) or MySQL (localhost)
            $databaseUrl = getenv('DATABASE_URL');
            
            if ($databaseUrl) {
                // Parse PostgreSQL URL from Render
                // Format: postgresql://user:password@host:port/dbname
                $parts = parse_url($databaseUrl);
                
                if (!$parts) {
                    throw new Exception("Invalid DATABASE_URL format");
                }
                
                $host = $parts['host'] ?? null;
                $port = $parts['port'] ?? 5432;
                $dbname = ltrim($parts['path'] ?? '', '/');
                $username = $parts['user'] ?? null;
                $password = $parts['pass'] ?? '';
                
                // Validate parsed values
                if (!$host || !$dbname || !$username) {
                    error_log("DATABASE_URL parsing failed: host=$host, dbname=$dbname, user=$username");
                    throw new Exception("Invalid DATABASE_URL: missing required components");
                }
                
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
            } else {
                // Use MySQL for localhost
                $host = getenv('DB_HOST') ?: 'localhost';
                $dbname = getenv('DB_NAME') ?: 'demolitiontraders';
                $username = getenv('DB_USER') ?: 'root';
                $password = getenv('DB_PASS') ?: '';
                $port = getenv('DB_PORT') ?: '3306';
                
                error_log("Connecting to MySQL: host=$host, port=$port, db=$dbname, user=$username");

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            }
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $username, $password, $options);
            
        } catch (PDOException $e) {
            $errorMsg = "Database connection error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")";
            error_log($errorMsg);
            // Throw as regular Exception so it can be caught by the API router
            throw new Exception($errorMsg);
        } catch (Exception $e) {
            $errorMsg = "Database configuration error: " . $e->getMessage();
            error_log($errorMsg);
            throw new Exception($errorMsg);
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute query with parameters
     * Automatically converts integer 1/0 to boolean TRUE/FALSE for PostgreSQL
     */
    public function query($sql, $params = []) {
        try {
            // Fix PostgreSQL boolean comparison: replace = 1 with = TRUE, = 0 with = FALSE
            if ($this->isPostgreSQL()) {
                $sql = $this->fixBooleanSQL($sql);
            }
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log to file only, never echo
            $this->log("[SQL ERROR][QUERY] " . $e->getMessage() . "\nSQL: $sql\nPARAMS: " . var_export($params, true));
            error_log("Query error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if using PostgreSQL
     */
    private function isPostgreSQL() {
        return $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
    }
    
    /**
     * Fix boolean comparisons for PostgreSQL
     * Converts = 1 to = TRUE, = 0 to = FALSE for known boolean columns
     * Also fixes MySQL-specific syntax like UNSIGNED, SUBSTRING_INDEX
     */
    private function fixBooleanSQL($sql) {
        $booleanColumns = [
            'is_active', 'is_featured', 'is_primary', 'is_default',
            'show_collection_options', 'is_verified_purchase', 'is_approved',
            'synced_to_pos', 'matched_by_admin'
        ];
        
        foreach ($booleanColumns as $col) {
            // Replace = 1 with = TRUE
            $sql = preg_replace("/\b$col\s*=\s*1\b/i", "$col = TRUE", $sql);
            // Replace = 0 with = FALSE  
            $sql = preg_replace("/\b$col\s*=\s*0\b/i", "$col = FALSE", $sql);
        }
        
        // Fix MySQL UNSIGNED -> PostgreSQL INTEGER
        $sql = preg_replace("/\bAS\s+UNSIGNED\b/i", "AS INTEGER", $sql);
        
        // Fix MySQL SUBSTRING_INDEX -> PostgreSQL SPLIT_PART
        // SUBSTRING_INDEX(str, delim, count) -> SPLIT_PART(str, delim, abs(count))
        $sql = preg_replace_callback(
            "/SUBSTRING_INDEX\s*\(\s*([^,]+),\s*'([^']+)'\s*,\s*(-?\d+)\s*\)/i",
            function($matches) {
                $str = trim($matches[1]);
                $delim = $matches[2];
                $count = intval($matches[3]);
                if ($count < 0) {
                    // Negative index means from right - not directly supported, use array index
                    return "SPLIT_PART($str, '$delim', ARRAY_LENGTH(STRING_TO_ARRAY($str, '$delim'), 1) + $count + 1)";
                }
                return "SPLIT_PART($str, '$delim', $count)";
            },
            $sql
        );
        
        return $sql;
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert record and return last insert ID
     */
    public function insert($table, $data) {
        if (empty($data)) {
            $this->log("[INSERT][ERROR] Empty data for table $table");
            return false;
        }

        $columns = array_keys($data);
        $placeholders = array_map(function ($col) {
            return ':' . $col;
        }, $columns);

        $sql = "INSERT INTO $table (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ") RETURNING id";

        // Prepare params - convert to string except null
        $params = [];
        foreach ($data as $k => $v) {
            $params[$k] = is_null($v) ? null : (string)$v;
        }

        // Validate counts match
        if (count($columns) !== count($params) || count($columns) !== count($placeholders)) {
            $this->log("[INSERT][ERROR] MISMATCH: columns=" . count($columns) . ", placeholders=" . count($placeholders) . ", params=" . count($params));
            return false;
        }

        try {
            $result = $this->query($sql, $params);
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $insertId = $row['id'] ?? null;
            
            // If RETURNING didn't work, try lastInsertId with sequence name
            if (!$insertId) {
                try {
                    $insertId = $this->connection->lastInsertId("{$table}_id_seq");
                    if (!$insertId) {
                        $this->log("[INSERT][WARNING] Could not get ID from RETURNING or lastInsertId");
                    }
                } catch (Exception $e) {
                    $this->log("[INSERT][WARNING] Could not get lastInsertId: " . $e->getMessage());
                }
            }
            
            if (!$insertId) {
                throw new Exception("Failed to get insert ID for table $table");
            }
            
            $this->log("[INSERT][SUCCESS] Table: $table, ID: $insertId");
            return $insertId;
        } catch (PDOException $e) {
            $this->log("[INSERT][ERROR] " . $e->getMessage() . "\nSQL: $sql\nParams: " . json_encode($params));
            throw $e; // Re-throw exception instead of returning false
        }
    }
    
    /**
     * Update record
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = :{$column}";
        }
        $set = implode(', ', $set);
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        try {
            $result = $this->query($sql, $params);
            $this->log("[UPDATE][SUCCESS] Table: $table");
            return $result;
        } catch (PDOException $e) {
            $this->log("[UPDATE][ERROR] " . $e->getMessage() . "\nSQL: $sql");
            throw $e;
        }
    }
    
    /**
     * Delete record
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        try {
            $result = $this->query($sql, $params);
            $this->log("[DELETE][SUCCESS] Table: $table");
            return $result;
        } catch (PDOException $e) {
            $this->log("[DELETE][ERROR] " . $e->getMessage() . "\nSQL: $sql");
            throw $e;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($table) {
        try {
            $result = $this->connection->query("SELECT 1 FROM {$table} LIMIT 1");
            return $result !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Internal logging method - writes to file only, NEVER echo
     */
    private function log($message) {
        // Only log to file, never output to stdout
        $logDir = __DIR__ . '/../../logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . 'database_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        
        @file_put_contents(
            $logFile,
            "[{$timestamp}] {$message}\n",
            FILE_APPEND
        );
        
        // Also use error_log for system logs
        error_log($message);
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Alias for convenience
class_alias('Database', 'DB');