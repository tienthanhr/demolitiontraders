<?php
/**
 * CSP Headers Configuration
 * Sets Content Security Policy headers for CSP compliance
 */

// Only set CSP headers if running in browser context
if (php_sapi_name() !== 'cli') {
    // For now, use permissive CSP that allows cdn resources
    // But with nonce support for future inline scripts
    $nonce = base64_encode(random_bytes(16));
    
    // CSP Header - permissive but structured
    // Note: Currently allows cdnjs for Font Awesome and other libraries
    // Migration path: Download libraries locally and switch to 'self' only
    $csp = "
        default-src 'self';
        script-src 'self' https://cdnjs.cloudflare.com;
        style-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline';
        img-src 'self' data: https:;
        font-src 'self' data: https://cdnjs.cloudflare.com;
        connect-src 'self' https:;
        frame-src 'self' https://www.google.com;
        object-src 'none';
        base-uri 'self';
        form-action 'self';
    ";
    
    // Clean up whitespace
    $csp = preg_replace('/\s+/', ' ', trim($csp));
    
    // Note: Currently using permissive policy to allow development
    // To enforce strict CSP:
    // header("Content-Security-Policy: $csp", false);
    
    // Using report-only mode for testing
    header("Content-Security-Policy-Report-Only: $csp", false);
    
    // Store nonce for use in inline scripts
    $_SERVER['CSP_NONCE'] = $nonce;
}
