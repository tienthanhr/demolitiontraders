# HTTPS & Mixed Content Fix

## Problem
Website loaded over HTTPS but making HTTP requests → Browser blocks insecure content

## Solutions Implemented

### 1. Force HTTPS Redirect (.htaccess)
```apache
RewriteCond %{HTTPS} off
RewriteCond %{HTTP_HOST} !^localhost [NC]
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```
- Redirects HTTP → HTTPS automatically (except localhost)
- Preserves original URL path

### 2. Content Security Policy Header
```apache
Header always set Content-Security-Policy "upgrade-insecure-requests"
```
- Browser automatically upgrades HTTP requests to HTTPS
- Fixes mixed content warnings
- Works for all fetch(), XMLHttpRequest, images, scripts, etc.

### 3. URL Helper Functions
Created `backend/config/url-helper.php` with:
- `get_protocol()` - Auto-detect HTTP/HTTPS
- `get_base_url()` - Get full base URL with correct protocol  
- `get_api_url()` - Get API endpoint URL
- `make_url_secure()` - Convert any URL to HTTPS if needed

## Testing

### Before Fix:
```
❌ Mixed Content: The page at 'https://...' was loaded over HTTPS, 
   but requested an insecure resource 'http://...'. 
   This request has been blocked.
```

### After Fix:
```
✅ All requests automatically upgraded to HTTPS
✅ No mixed content warnings
✅ API calls work correctly
```

## Usage in Code

### Option 1: Use Relative URLs (Recommended)
```javascript
// ✅ Always works - browser handles protocol
fetch('/demolitiontraders/backend/api/products/index.php');
```

### Option 2: Use Helper Functions (PHP)
```php
<?php
require_once 'backend/config/url-helper.php';

// Get base URL with correct protocol
$baseUrl = get_base_url(); // https://example.com/demolitiontraders

// Get API URL
$apiUrl = get_api_url('products/index.php');
?>

<script>
const BASE_URL = '<?php echo get_base_url(); ?>';
fetch(`${BASE_URL}/backend/api/products/index.php`);
</script>
```

### Option 3: Protocol-Relative URLs
```html
<!-- ✅ Inherits protocol from page -->
<img src="//example.com/image.jpg">
<script src="//cdn.example.com/script.js"></script>
```

## Browser Console Commands

Check if HTTPS is enforced:
```javascript
console.log(window.location.protocol); // Should be "https:"
```

Check if CSP header is present:
```javascript
fetch(window.location.href)
  .then(r => console.log(r.headers.get('content-security-policy')));
```

## Localhost vs Production

### Localhost (HTTP allowed):
- .htaccess checks for `localhost` hostname
- Skips HTTPS redirect for development
- Still adds CSP header for testing

### Production (HTTPS enforced):
- Automatic HTTP → HTTPS redirect
- CSP upgrades insecure requests
- All API calls use HTTPS

## Troubleshooting

### Problem: Still seeing mixed content warnings
**Solution:** Clear browser cache and hard refresh (Ctrl+Shift+R)

### Problem: Localhost redirecting to HTTPS
**Solution:** .htaccess already excludes localhost - check hostname

### Problem: HTTPS certificate error
**Solution:** 
- For production: Install valid SSL certificate
- For localhost: Use mkcert or accept self-signed cert

## Apache Configuration Required

Make sure these modules are enabled:
```bash
# Enable mod_headers
sudo a2enmod headers

# Enable mod_rewrite (already required)
sudo a2enmod rewrite

# Restart Apache
sudo systemctl restart apache2
```

For XAMPP (Windows): Usually enabled by default in `httpd.conf`

## Security Benefits

✅ Encrypted traffic (HTTPS)
✅ Prevents man-in-the-middle attacks
✅ SEO boost (Google prefers HTTPS)
✅ Required for modern browser features
✅ Required for PWA
✅ Browser trust indicators
