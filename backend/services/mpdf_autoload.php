<?php
// Simple autoloader for mPDF if not using Composer

spl_autoload_register(function ($class) {
    // Replace backslashes with forward slashes for Linux compatibility
    $classPath = str_replace('\\', '/', $class);

    if (strpos($class, 'Mpdf\\') === 0) {
        $relativeClass = str_replace('Mpdf/', '', $classPath);
        $path = __DIR__ . '/mpdf-8.1.0/src/' . $relativeClass . '.php';
        if (file_exists($path)) require_once $path;
    } elseif (strpos($class, 'setasign\\Fpdi\\') === 0) {
        $relativeClass = str_replace('setasign/Fpdi/', '', $classPath);
        $path = __DIR__ . '/mpdf-8.1.0/src/setasign/Fpdi/' . $relativeClass . '.php';
        if (file_exists($path)) require_once $path;
    } elseif (strpos($class, 'Psr\\Log\\') === 0) {
        $relativeClass = str_replace('Psr/Log/', '', $classPath);
        $path = __DIR__ . '/Psr/Log/' . $relativeClass . '.php';
        if (file_exists($path)) require_once $path;
    }
});
