<?php
// Simple autoloader for mPDF if not using Composer

spl_autoload_register(function ($class) {
    if (strpos($class, 'Mpdf\\') === 0) {
        $path = __DIR__ . '/mpdf-8.1.0/src/' . str_replace('Mpdf\\', '', $class) . '.php';
        if (file_exists($path)) require_once $path;
    } elseif (strpos($class, 'setasign\\Fpdi\\') === 0) {
        $path = __DIR__ . '/mpdf-8.1.0/src/setasign/Fpdi/' . str_replace('setasign\\Fpdi\\', '', $class) . '.php';
        if (file_exists($path)) require_once $path;
    } elseif (strpos($class, 'Psr\\Log\\') === 0) {
        $path = __DIR__ . '/Psr/Log/' . str_replace('Psr\\Log\\', '', $class) . '.php';
        if (file_exists($path)) require_once $path;
    }
});
