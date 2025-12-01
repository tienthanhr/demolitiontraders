# 🔒 DAY 3: CSRF PROTECTION IMPLEMENTATION CHECKLIST

**Priority:** P0 - SECURITY CRITICAL  
**Estimated Time:** 8 hours (1 day)  
**Status:** ⬜ Not Started

---

## 📋 UNDERSTANDING CSRF

### What is CSRF?
Cross-Site Request Forgery - an attack that tricks a user into performing unwanted actions on a web application where they're authenticated.

### Why is it Critical?
Without CSRF protection:
- Attacker can change user passwords
- Attacker can make purchases
- Attacker can modify/delete data
- Attacker can perform admin actions

### How We'll Fix It:
- Generate unique token per session
- Include token in all forms
- Validate token on all POST/PUT/DELETE requests
- Reject requests with invalid/missing tokens

---

## 🔧 IMPLEMENTATION PLAN

### 1. Create CSRF Protection Class (2 hours)

**File:** `/backend/security/CsrfProtection.php`

#### Class Structure:
```php
<?php
class CsrfProtection {
    private static $tokenName = 'csrf_token';
    private static $tokenTime = 'csrf_token_time';
    
    // Generate and store token in session
    public static function generateToken() { }
    
    // Get current token from session
    public static function getToken() { }
    
    // Validate submitted token
    public static function validateToken($token) { }
    
    // Generate HTML hidden input field
    public static function getTokenField() { }
    
    // Get token for AJAX requests
    public static function getTokenForAjax() { }
    
    // Regenerate token (after use)
    public static function regenerateToken() { }
    
    // Check token age (expire after 1 hour)
    private static function isTokenExpired() { }
}
```

**Checklist:**

#### A) generateToken() Method (30 mins)
```php
public static function generateToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate cryptographically secure random token
    $token = bin2hex(random_bytes(32));
    
    // Store in session
    $_SESSION[self::$tokenName] = $token;
    $_SESSION[self::$tokenTime] = time();
    
    return $token;
}
```

- [ ] Create function
- [ ] Use `random_bytes()` for security
- [ ] Store token in session
- [ ] Store timestamp for expiry
- [ ] Return token
- [ ] Test token generation

#### B) getToken() Method (15 mins)
```php
public static function getToken() {
    if (!isset($_SESSION[self::$tokenName]) || self::isTokenExpired()) {
        return self::generateToken();
    }
    return $_SESSION[self::$tokenName];
}
```

- [ ] Check if token exists
- [ ] Check if expired
- [ ] Regenerate if needed
- [ ] Return token

#### C) validateToken() Method (45 mins)
```php
public static function validateToken($token) {
    // Check token exists in session
    if (!isset($_SESSION[self::$tokenName])) {
        error_log('CSRF: No token in session');
        return false;
    }
    
    // Check token not expired
    if (self::isTokenExpired()) {
        error_log('CSRF: Token expired');
        return false;
    }
    
    // Timing-safe comparison
    $sessionToken = $_SESSION[self::$tokenName];
    if (!hash_equals($sessionToken, $token)) {
        error_log('CSRF: Token mismatch');
        return false;
    }
    
    return true;
}
```

- [ ] Check token in session exists
- [ ] Check token not expired
- [ ] Use `hash_equals()` for timing-safe comparison
- [ ] Log validation failures
- [ ] Return boolean
- [ ] Test validation

#### D) getTokenField() Method (15 mins)
```php
public static function getTokenField() {
    $token = self::getToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
```

- [ ] Get current token
- [ ] Generate HTML input field
- [ ] Escape HTML
- [ ] Return HTML string

#### E) getTokenForAjax() Method (15 mins)
```php
public static function getTokenForAjax() {
    return [
        'token' => self::getToken(),
        'header' => 'X-CSRF-Token'
    ];
}
```

- [ ] Return token and header name for AJAX

#### F) isTokenExpired() Method (15 mins)
```php
private static function isTokenExpired() {
    if (!isset($_SESSION[self::$tokenTime])) {
        return true;
    }
    
    $tokenAge = time() - $_SESSION[self::$tokenTime];
    $maxAge = 3600; // 1 hour
    
    return $tokenAge > $maxAge;
}
```

- [ ] Check timestamp exists
- [ ] Calculate age
- [ ] Compare with max age (1 hour)
- [ ] Return boolean

#### G) regenerateToken() Method (15 mins)
```php
public static function regenerateToken() {
    unset($_SESSION[self::$tokenName]);
    unset($_SESSION[self::$tokenTime]);
    return self::generateToken();
}
```

- [ ] Clear old token
- [ ] Generate new token
- [ ] Return new token

---

### 2. Create CSRF Validation Middleware (1 hour)

**File:** `/backend/security/CsrfMiddleware.php`

```php
<?php
require_once __DIR__ . '/CsrfProtection.php';

class CsrfMiddleware {
    public static function validate() {
        // Only validate POST, PUT, DELETE requests
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return true; // GET requests don't need CSRF
        }
        
        // Get token from request
        $token = self::getTokenFromRequest();
        
        if (!$token) {
            self::handleInvalidToken('Missing CSRF token');
            return false;
        }
        
        // Validate token
        if (!CsrfProtection::validateToken($token)) {
            self::handleInvalidToken('Invalid CSRF token');
            return false;
        }
        
        return true;
    }
    
    private static function getTokenFromRequest() {
        // Check POST data
        if (isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }
        
        // Check JSON body
        $json = file_get_contents('php://input');
        if ($json) {
            $data = json_decode($json, true);
            if (isset($data['csrf_token'])) {
                return $data['csrf_token'];
            }
        }
        
        // Check header (for AJAX)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        return null;
    }
    
    private static function handleInvalidToken($message) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Security validation failed',
            'message' => $message
        ]);
        exit;
    }
}
```

**Checklist:**
- [ ] Create file
- [ ] Implement `validate()` method
- [ ] Check request method
- [ ] Get token from various sources:
  - [ ] POST form data
  - [ ] JSON body
  - [ ] HTTP header
- [ ] Validate token
- [ ] Handle invalid tokens (403 error)
- [ ] Return appropriate JSON response
- [ ] Log security violations

---

### 3. Add CSRF to All Forms (3 hours)

Need to update EVERY form in the application:

#### Frontend Forms:

**A) Authentication Forms (30 mins)**
- [ ] `/frontend/login.php` - Login form
  ```php
  <form method="POST" action="/backend/api/user/login.php">
      <?php require_once '../backend/security/CsrfProtection.php'; echo CsrfProtection::getTokenField(); ?>
      <!-- rest of form -->
  </form>
  ```
- [ ] `/frontend/login.php` - Register form
- [ ] `/frontend/reset-password.php` - Reset form
- [ ] Test each form submits token

**B) Checkout & Cart Forms (30 mins)**
- [ ] `/frontend/checkout.php` - Checkout form
- [ ] `/frontend/cart.php` - Update quantity forms
- [ ] Test forms

**C) Profile Forms (20 mins)**
- [ ] `/frontend/profile.php` - Profile update form
- [ ] `/frontend/profile.php` - Password change form
- [ ] `/frontend/profile.php` - Address forms

**D) Customer Interaction Forms (30 mins)**
- [ ] `/frontend/wanted-listing.php` - Request form
- [ ] `/frontend/sell-to-us.php` - Sell form
- [ ] `/frontend/contact.php` - Contact form
- [ ] `/frontend/product-detail.php` - Review form (if implemented)

**E) Wishlist Actions (15 mins)**
- [ ] `/frontend/wishlist.php` - Remove from wishlist forms

#### Admin Forms:

**F) Product Management (30 mins)**
- [ ] `/frontend/admin/products.php` - Create product form
- [ ] `/frontend/admin/products.php` - Edit product form
- [ ] `/frontend/admin/products.php` - Delete product forms
- [ ] `/frontend/admin/products.php` - Bulk action forms

**G) Order Management (20 mins)**
- [ ] `/frontend/admin/orders.php` - Update status forms
- [ ] `/frontend/admin/orders.php` - Delete order forms

**H) Customer Management (20 mins)**
- [ ] `/frontend/admin/customers.php` - Update customer forms
- [ ] `/frontend/admin/customers.php` - Delete customer forms

**I) Admin Management (15 mins)**
- [ ] `/frontend/admin/admins.php` - Promote to admin form
- [ ] `/frontend/admin/admins.php` - Demote admin form

**J) Settings Forms (15 mins)**
- [ ] `/frontend/admin/settings.php` - Update settings form
- [ ] `/frontend/admin/settings.php` - Opening hours form

---

### 4. Add CSRF to All API Endpoints (2 hours)

Need to add validation to EVERY endpoint that modifies data:

#### Add to Top of Each API File:
```php
<?php
require_once '../../security/CsrfMiddleware.php';

// Validate CSRF token
if (!CsrfMiddleware::validate()) {
    exit; // Middleware handles response
}

// Rest of API code...
```

#### User API Endpoints (30 mins):
- [ ] `/backend/api/user/register.php`
- [ ] `/backend/api/user/login.php`
- [ ] `/backend/api/user/logout.php`
- [ ] `/backend/api/user/update-profile.php`
- [ ] `/backend/api/user/change-password.php`
- [ ] `/backend/api/user/reset-password.php`
- [ ] Test each endpoint

#### Cart API Endpoints (20 mins):
- [ ] `/backend/api/cart/add.php`
- [ ] `/backend/api/cart/update.php`
- [ ] `/backend/api/cart/remove.php`
- [ ] `/backend/api/cart/sync.php`

#### Wishlist API Endpoints (15 mins):
- [ ] `/backend/api/wishlist/add.php`
- [ ] `/backend/api/wishlist/remove.php`

#### Order API Endpoints (20 mins):
- [ ] `/backend/api/order/create.php`
- [ ] `/backend/api/order/cancel.php`

#### Product API Endpoints (20 mins):
- [ ] `/backend/api/products/create.php`
- [ ] `/backend/api/products/update.php`
- [ ] `/backend/api/products/delete.php`

#### Admin API Endpoints (30 mins):
- [ ] `/backend/api/admin/update-user-role.php`
- [ ] `/backend/api/admin/promote-to-admin.php`
- [ ] `/backend/api/admin/delete-user.php`
- [ ] `/backend/api/admin/update-order-status.php`
- [ ] All other admin endpoints

#### Other API Endpoints (15 mins):
- [ ] `/backend/api/wanted-listing/submit.php`
- [ ] `/backend/api/sell-items/submit.php`
- [ ] `/backend/api/contact/submit.php`
- [ ] `/backend/api/payment/create-session.php`

#### Exceptions (Don't Add CSRF):
- [ ] GET requests (read-only)
- [ ] `/backend/api/payment/webhook.php` (external service)
- [ ] `/backend/api/payment/process.php` (return from gateway)
- [ ] Public read endpoints

---

### 5. Add CSRF to AJAX Requests (1.5 hours)

#### Create Global JavaScript Helper (30 mins)

**File:** `/frontend/assets/js/csrf-helper.js`

```javascript
// CSRF Helper for AJAX Requests
const CsrfHelper = {
    // Get token from meta tag or cookie
    getToken: function() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.getAttribute('content');
        }
        return null;
    },
    
    // Get headers object with CSRF token
    getHeaders: function() {
        return {
            'X-CSRF-Token': this.getToken(),
            'Content-Type': 'application/json'
        };
    },
    
    // Fetch wrapper with CSRF
    fetch: async function(url, options = {}) {
        options.headers = {
            ...options.headers,
            'X-CSRF-Token': this.getToken()
        };
        
        // If body is object, stringify it
        if (options.body && typeof options.body === 'object') {
            options.body = JSON.stringify(options.body);
            options.headers['Content-Type'] = 'application/json';
        }
        
        return fetch(url, options);
    },
    
    // Add token to FormData
    addToFormData: function(formData) {
        formData.append('csrf_token', this.getToken());
        return formData;
    }
};
```

**Checklist:**
- [ ] Create file
- [ ] Implement helper methods
- [ ] Test in browser console

#### Add CSRF Meta Tag to Header (15 mins)

**File:** `/frontend/components/header.php`

```php
<?php
require_once '../backend/security/CsrfProtection.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="<?php echo CsrfProtection::getToken(); ?>">
    <!-- rest of head -->
</head>
```

- [ ] Add meta tag to header
- [ ] Verify tag appears in HTML
- [ ] Test JavaScript can read it

#### Update All AJAX Calls (45 mins)

Need to update EVERY AJAX call in the application:

**A) Cart AJAX (`/frontend/assets/js/cart.js` or inline):**
```javascript
// OLD:
fetch('/demolitiontraders/backend/api/cart/add.php', {
    method: 'POST',
    body: JSON.stringify({ product_id: 123 })
});

// NEW:
CsrfHelper.fetch('/demolitiontraders/backend/api/cart/add.php', {
    method: 'POST',
    body: { product_id: 123 }
});
```

- [ ] Find all cart AJAX calls
- [ ] Update to use CsrfHelper
- [ ] Test add to cart
- [ ] Test update quantity
- [ ] Test remove from cart

**B) Wishlist AJAX:**
- [ ] Update add to wishlist
- [ ] Update remove from wishlist
- [ ] Test functionality

**C) Admin Panel AJAX:**
- [ ] Update product create/edit/delete
- [ ] Update order status changes
- [ ] Update user management actions
- [ ] Update settings save
- [ ] Test all admin actions

**D) Checkout AJAX:**
- [ ] Update checkout submission
- [ ] Test checkout flow

**E) Other AJAX:**
- [ ] Search suggestions (if POST)
- [ ] Review submission
- [ ] Contact form
- [ ] Wanted listing
- [ ] Sell-to-us

---

### 6. Add Error Handling (30 mins)

#### Global AJAX Error Handler

**Add to main JavaScript file:**
```javascript
// Global error handler for CSRF failures
document.addEventListener('DOMContentLoaded', function() {
    // Intercept fetch errors
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        const response = await originalFetch(...args);
        
        if (response.status === 403) {
            const data = await response.json();
            if (data.message && data.message.includes('CSRF')) {
                alert('Security token expired. Please refresh the page.');
                // Optionally auto-refresh
                // location.reload();
            }
        }
        
        return response;
    };
});
```

**Checklist:**
- [ ] Add error handler
- [ ] Show user-friendly message
- [ ] Optionally auto-refresh page
- [ ] Log errors to console
- [ ] Test expired token scenario

#### Form Error Messages

**Update forms to show CSRF errors:**
```php
<?php
if (isset($_GET['csrf_error'])) {
    echo '<div class="alert alert-danger">
        Security validation failed. Please try again.
    </div>';
}
?>
```

- [ ] Add error display to forms
- [ ] Test invalid token submission
- [ ] Verify error message shows

---

### 7. Testing (1 hour)

#### Manual Testing (45 mins)

**Test Each Form:**
- [ ] Login form
  - [ ] Submit with valid token → Success
  - [ ] Remove token from HTML → Error
  - [ ] Modify token → Error
  - [ ] Expired token (wait 1 hour or modify code) → Error
- [ ] Register form - same tests
- [ ] Checkout form - same tests
- [ ] Admin product create - same tests
- [ ] Admin order update - same tests

**Test AJAX Calls:**
- [ ] Add to cart
  - [ ] Normal request → Success
  - [ ] Remove token from request → 403 error
  - [ ] Invalid token → 403 error
- [ ] Wishlist add - same tests
- [ ] Admin AJAX actions - same tests

**Test Edge Cases:**
- [ ] Multiple tabs (token should work in all)
- [ ] Back button after form submission
- [ ] Session timeout → Token regenerates
- [ ] Concurrent requests

**Test Different Browsers:**
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

#### Security Testing (15 mins)

**Try to Attack:**
- [ ] Create malicious HTML form on different site
- [ ] Try to trigger action without valid token
- [ ] Verify attack fails with 403
- [ ] Try to guess token (should be impossible)
- [ ] Try replay attack (reuse old token)
- [ ] Verify all attacks blocked

---

### 8. Documentation (30 mins)

#### Developer Documentation

**Create:** `/docs/CSRF-PROTECTION.md`

**Content:**
```markdown
# CSRF Protection

## Overview
All state-changing requests require CSRF token validation.

## For Forms
```php
<?php echo CsrfProtection::getTokenField(); ?>
```

## For AJAX
```javascript
CsrfHelper.fetch('/api/endpoint', {
    method: 'POST',
    body: { data: 'value' }
});
```

## Adding to New API Endpoints
```php
require_once '../../security/CsrfMiddleware.php';
if (!CsrfMiddleware::validate()) {
    exit;
}
```

## Testing
[Testing instructions]

## Troubleshooting
[Common issues and solutions]
```

**Checklist:**
- [ ] Write overview
- [ ] Document usage for forms
- [ ] Document usage for AJAX
- [ ] Document how to add to new endpoints
- [ ] Add testing section
- [ ] Add troubleshooting section
- [ ] Add examples

---

## ✅ COMPLETION CHECKLIST

### Code Implementation:
- [ ] CsrfProtection class created and tested
- [ ] CsrfMiddleware created and tested
- [ ] CSRF added to ALL forms
- [ ] CSRF added to ALL API endpoints
- [ ] CSRF added to ALL AJAX calls
- [ ] JavaScript helper created
- [ ] Meta tag added to header
- [ ] Error handling implemented

### Testing:
- [ ] All forms tested (10+ forms)
- [ ] All AJAX calls tested (20+ calls)
- [ ] Edge cases tested
- [ ] Security testing completed
- [ ] Cross-browser testing done
- [ ] No legitimate requests blocked
- [ ] All attack attempts blocked

### Documentation:
- [ ] Developer documentation written
- [ ] Code comments added
- [ ] Examples provided
- [ ] Troubleshooting guide created

### Quality:
- [ ] No console errors
- [ ] No PHP errors/warnings
- [ ] Clean code, consistent style
- [ ] Logging in place
- [ ] Git committed

---

## 🚨 KNOWN ISSUES & GOTCHAS

### Issue: Token Expires During Long Forms
**Solution:** Implement JavaScript to refresh token every 30 minutes
```javascript
setInterval(async function() {
    const response = await fetch('/backend/api/csrf/refresh.php');
    const data = await response.json();
    document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
}, 1800000); // 30 minutes
```

### Issue: Multiple Tabs
**Solution:** Token is per-session, works across tabs automatically

### Issue: File Uploads
**Solution:** Include token in FormData
```javascript
const formData = new FormData();
formData.append('file', file);
CsrfHelper.addToFormData(formData);
```

### Issue: External Webhooks
**Solution:** Don't add CSRF to webhook endpoints (they're verified differently)

---

## 📊 SUCCESS METRICS

- [ ] 0 security vulnerabilities related to CSRF
- [ ] 100% of forms protected
- [ ] 100% of API endpoints protected
- [ ] 0 false positives (legitimate requests blocked)
- [ ] All tests passing

---

## 🎯 COMPLETION CRITERIA

✅ **Day 3 is COMPLETE when:**
- [ ] All checkboxes above are checked
- [ ] No form can be submitted without valid token
- [ ] No API can be called without valid token
- [ ] All tests passing
- [ ] Security audit passed
- [ ] Documentation complete
- [ ] Code committed to git

---

**Total Estimated Time:** 8 hours  
**Actual Time Spent:** _____ hours  
**Completion Date:** ___________  
**Security Audit Passed:** ⬜ Yes / ⬜ No  
**Notes/Issues:**

---

**Next:** Day 4 - Rate Limiting & Security Hardening
