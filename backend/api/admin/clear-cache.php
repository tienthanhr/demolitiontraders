<?php
/**
 * Clear Cache API
 * Clears website cache files
 */
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if user is admin
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;
    
    if (!isset($_SESSION['user_id']) || !$isAdmin) {
        throw new Exception('Unauthorized access');
    }
    
    // Get cache directory path
    $cacheDir = dirname(__DIR__, 3) . '/cache';
    $deletedFiles = 0;
    $totalSize = 0;
    
    // Check if cache directory exists
    if (!is_dir($cacheDir)) {
        throw new Exception('Cache directory not found');
    }
    
    // Clear all files in cache directory
    $files = glob($cacheDir . '/*');
    
    if ($files === false) {
        throw new Exception('Unable to read cache directory');
    }
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileSize = filesize($file);
            if (unlink($file)) {
                $deletedFiles++;
                $totalSize += $fileSize;
            }
        }
    }
    
    // Format size
    $sizeFormatted = $totalSize < 1024 
        ? $totalSize . ' B' 
        : ($totalSize < 1048576 
            ? round($totalSize / 1024, 2) . ' KB' 
            : round($totalSize / 1048576, 2) . ' MB');
    
    // Log the action
    error_log("Admin " . $_SESSION['user_id'] . " cleared cache: $deletedFiles files, $sizeFormatted");
    
    echo json_encode([
        'success' => true,
        'message' => "Cache cleared successfully!",
        'files_deleted' => $deletedFiles,
        'size_freed' => $sizeFormatted
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
