<?php
/**
 * Clear Cache API
 */
require_once '../../core/bootstrap.php'; // Ensures session is started securely
require_once 'csrf_middleware.php';   // Handles admin auth and CSRF validation

header('Content-Type: application/json');
try {
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
