<?php
/**
 * Configuration Loader
 * Loads environment variables from .env file
 */

class Config {
    private static $loaded = false;
    
    /**
     * Load environment variables from .env file
     */
    public static function load() {
        if (self::$loaded) {
            return;
        }
        
        $envFile = dirname(__DIR__, 2) . '/.env';
        
        if (!file_exists($envFile)) {
            // Try .env.example if .env doesn't exist
            $envFile = dirname(__DIR__, 2) . '/.env.example';
            if (!file_exists($envFile)) {
                error_log("Warning: .env file not found");
                return;
            }
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse line
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes
                $value = trim($value, '"\'');
                
                // Set environment variable
                if (!getenv($key)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        self::load();
        
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        
        // Convert boolean strings
        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;
        
        return $value;
    }
    
    /**
     * Get all configuration
     */
    public static function all() {
        self::load();
        return $_ENV;
    }
    
    /**
     * Check if in development mode
     */
    public static function isDevelopment() {
        return self::get('APP_ENV', 'production') === 'development';
    }
    
    /**
     * Check if in debug mode
     */
    public static function isDebug() {
        return self::get('APP_DEBUG', false) === true || self::get('APP_DEBUG') === 'true';
    }
}

// Load config on include
Config::load();
