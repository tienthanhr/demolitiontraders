# ⚡ DAY 4: RATE LIMITING & SECURITY HARDENING CHECKLIST

**Priority:** P1 - HIGH SECURITY  
**Estimated Time:** 8 hours (1 day)  
**Status:** ⬜ Not Started

---

## 📋 OVERVIEW

Protect the application from:
- Brute force attacks (login, password reset)
- API abuse (spam requests)
- DDoS attempts
- Bot attacks
- Credential stuffing

---

## 🔧 PART 1: RATE LIMITING SYSTEM (4 hours)

### 1. Create RateLimiter Class (2 hours)

**File:** `/backend/security/RateLimiter.php`

#### Class Structure:
```php
<?php
class RateLimiter {
    private $db;
    private $cacheDir = __DIR__ . '/../../cache/rate-limits/';
    
    public function __construct() { }
    
    // Check if request is within limits
    public function check($identifier, $maxAttempts, $timeWindow) { }
    
    // Record an attempt
    private function recordAttempt($identifier) { }
    
    // Get attempt count
    private function getAttemptCount($identifier, $timeWindow) { }
    
    // Clear attempts (after successful action)
    public function clear($identifier) { }
    
    // Get remaining attempts
    public function getRemainingAttempts($identifier, $maxAttempts, $timeWindow) { }
    
    // Get retry after time
    public function getRetryAfter($identifier, $timeWindow) { }
    
    // Block IP permanently
    public function blockIP($ip, $reason = '') { }
    
    // Check if IP is blocked
    public function isIPBlocked($ip) { }
}
```

**Checklist:**

#### A) Constructor & Setup (15 mins)
```php
public function __construct() {
    $this->db = Database::getInstance();
    
    // Create cache directory if not exists
    if (!file_exists($this->cacheDir)) {
        mkdir($this->cacheDir, 0755, true);
    }
}
```

- [ ] Create constructor
- [ ] Initialize database connection
- [ ] Create cache directory
- [ ] Set permissions

#### B) Create Rate Limits Table (20 mins)
```sql
CREATE TABLE rate_limits (
  id INT PRIMARY KEY AUTO_INCREMENT,
  identifier VARCHAR(255) NOT NULL,
  attempt_count INT DEFAULT 1,
  first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_identifier (identifier),
  INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ip_blacklist (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ip_address VARCHAR(45) NOT NULL UNIQUE,
  reason VARCHAR(255),
  blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  blocked_until TIMESTAMP NULL,
  is_permanent BOOLEAN DEFAULT FALSE,
  INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] Create `rate_limits` table
- [ ] Create `ip_blacklist` table
- [ ] Run migrations
- [ ] Verify tables created

#### C) check() Method (45 mins)
```php
public function check($identifier, $maxAttempts, $timeWindow) {
    // Clean old attempts first
    $this->cleanOldAttempts($timeWindow);
    
    // Get current attempt count
    $attempts = $this->getAttemptCount($identifier, $timeWindow);
    
    // Check if exceeded
    if ($attempts >= $maxAttempts) {
        $retryAfter = $this->getRetryAfter($identifier, $timeWindow);
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => $retryAfter
        ];
    }
    
    // Record this attempt
    $this->recordAttempt($identifier);
    
    return [
        'allowed' => true,
        'remaining' => $maxAttempts - $attempts - 1
    ];
}
```

- [ ] Implement check logic
- [ ] Clean old attempts
- [ ] Count current attempts
- [ ] Compare with max
- [ ] Record attempt if allowed
- [ ] Return result array
- [ ] Test with different limits

#### D) recordAttempt() Method (20 mins)
```php
private function recordAttempt($identifier) {
    // Check if identifier exists
    $existing = $this->db->fetchOne(
        "SELECT * FROM rate_limits WHERE identifier = ?",
        [$identifier]
    );
    
    if ($existing) {
        // Increment count
        $this->db->query(
            "UPDATE rate_limits SET attempt_count = attempt_count + 1, last_attempt = NOW() WHERE identifier = ?",
            [$identifier]
        );
    } else {
        // Insert new record
        $this->db->query(
            "INSERT INTO rate_limits (identifier, attempt_count) VALUES (?, 1)",
            [$identifier]
        );
    }
}
```

- [ ] Check if identifier exists
- [ ] Update or insert
- [ ] Increment attempt count
- [ ] Update timestamp
- [ ] Test recording

#### E) getAttemptCount() Method (15 mins)
```php
private function getAttemptCount($identifier, $timeWindow) {
    $cutoff = date('Y-m-d H:i:s', time() - $timeWindow);
    
    $result = $this->db->fetchOne(
        "SELECT attempt_count FROM rate_limits 
         WHERE identifier = ? AND first_attempt > ?",
        [$identifier, $cutoff]
    );
    
    return $result ? (int)$result['attempt_count'] : 0;
}
```

- [ ] Calculate time cutoff
- [ ] Query attempts within window
- [ ] Return count
- [ ] Test with different windows

#### F) clear() Method (10 mins)
```php
public function clear($identifier) {
    $this->db->query(
        "DELETE FROM rate_limits WHERE identifier = ?",
        [$identifier]
    );
}
```

- [ ] Delete attempts for identifier
- [ ] Test clearing

#### G) IP Blocking Methods (25 mins)
```php
public function blockIP($ip, $reason = '', $duration = null) {
    $isPermanent = is_null($duration);
    $blockedUntil = $isPermanent ? null : date('Y-m-d H:i:s', time() + $duration);
    
    $this->db->query(
        "INSERT INTO ip_blacklist (ip_address, reason, blocked_until, is_permanent) 
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
         reason = VALUES(reason), 
         blocked_until = VALUES(blocked_until),
         is_permanent = VALUES(is_permanent)",
        [$ip, $reason, $blockedUntil, $isPermanent]
    );
}

public function isIPBlocked($ip) {
    $result = $this->db->fetchOne(
        "SELECT * FROM ip_blacklist 
         WHERE ip_address = ? 
         AND (is_permanent = 1 OR blocked_until > NOW())",
        [$ip]
    );
    
    return !empty($result);
}
```

- [ ] Implement blockIP()
- [ ] Support permanent and temporary blocks
- [ ] Implement isIPBlocked()
- [ ] Test blocking
- [ ] Test unblocking (expired)

#### H) Helper Methods (20 mins)
```php
private function cleanOldAttempts($timeWindow) {
    $cutoff = date('Y-m-d H:i:s', time() - $timeWindow);
    $this->db->query(
        "DELETE FROM rate_limits WHERE first_attempt < ?",
        [$cutoff]
    );
}

public function getRemainingAttempts($identifier, $maxAttempts, $timeWindow) {
    $attempts = $this->getAttemptCount($identifier, $timeWindow);
    return max(0, $maxAttempts - $attempts);
}

public function getRetryAfter($identifier, $timeWindow) {
    $result = $this->db->fetchOne(
        "SELECT first_attempt FROM rate_limits WHERE identifier = ?",
        [$identifier]
    );
    
    if (!$result) return 0;
    
    $firstAttempt = strtotime($result['first_attempt']);
    $expiresAt = $firstAttempt + $timeWindow;
    $retryAfter = max(0, $expiresAt - time());
    
    return $retryAfter;
}
```

- [ ] Implement cleanup
- [ ] Implement remaining attempts
- [ ] Implement retry after
- [ ] Test all helpers

---

### 2. Apply Rate Limiting to Endpoints (2 hours)

#### A) Login Endpoint (30 mins)

**File:** `/backend/api/user/login.php`

**Add at top:**
```php
require_once '../../security/RateLimiter.php';

// Rate limit by IP and email
$rateLimiter = new RateLimiter();
$ip = $_SERVER['REMOTE_ADDR'];
$email = $_POST['email'] ?? $_POST['email'] ?? '';

// Check IP-based rate limit
$ipCheck = $rateLimiter->check("login_ip_{$ip}", 10, 900); // 10 attempts per 15 mins
if (!$ipCheck['allowed']) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'Too many login attempts. Please try again in ' . ceil($ipCheck['retry_after'] / 60) . ' minutes.',
        'retry_after' => $ipCheck['retry_after']
    ]);
    exit;
}

// Check email-based rate limit (stricter)
if ($email) {
    $emailCheck = $rateLimiter->check("login_email_{$email}", 5, 900); // 5 attempts per 15 mins
    if (!$emailCheck['allowed']) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Too many failed attempts for this account. Please try again later.',
            'retry_after' => $emailCheck['retry_after']
        ]);
        exit;
    }
}

// ... existing login logic ...

// On successful login, clear rate limits
if ($loginSuccessful) {
    $rateLimiter->clear("login_ip_{$ip}");
    $rateLimiter->clear("login_email_{$email}");
}

// On failed login after 5 attempts, consider blocking
$attempts = $rateLimiter->getAttemptCount("login_ip_{$ip}", 900);
if ($attempts >= 8) {
    error_log("Suspicious login activity from IP: {$ip}");
    // Optionally send alert email to admin
}
```

**Checklist:**
- [ ] Add rate limiting check
- [ ] Limit by IP (10 per 15 mins)
- [ ] Limit by email (5 per 15 mins)
- [ ] Return 429 status code
- [ ] Return retry_after time
- [ ] Clear limits on successful login
- [ ] Log suspicious activity
- [ ] Test legitimate login
- [ ] Test brute force attempt
- [ ] Verify lockout works
- [ ] Verify unlock after time

#### B) Password Reset Endpoint (20 mins)

**File:** `/backend/api/user/reset-password.php`

```php
$rateLimiter = new RateLimiter();
$ip = $_SERVER['REMOTE_ADDR'];
$email = $_POST['email'] ?? '';

// Limit password reset requests
$check = $rateLimiter->check("password_reset_{$email}_{$ip}", 3, 3600); // 3 per hour
if (!$check['allowed']) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'Too many password reset requests. Please try again later.'
    ]);
    exit;
}
```

- [ ] Add rate limiting
- [ ] Limit to 3 per hour per email+IP combo
- [ ] Test legitimate reset
- [ ] Test abuse scenario

#### C) Register Endpoint (20 mins)

**File:** `/backend/api/user/register.php`

```php
$rateLimiter = new RateLimiter();
$ip = $_SERVER['REMOTE_ADDR'];

// Prevent mass registration
$check = $rateLimiter->check("register_{$ip}", 3, 3600); // 3 per hour per IP
if (!$check['allowed']) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'Too many registration attempts. Please try again later.'
    ]);
    exit;
}
```

- [ ] Add rate limiting
- [ ] Limit to 3 per hour per IP
- [ ] Test legitimate registration
- [ ] Test bot prevention

#### D) API Endpoints - General (30 mins)

**Create middleware:** `/backend/api/rate-limit-middleware.php`

```php
<?php
require_once '../security/RateLimiter.php';

function checkApiRateLimit($endpoint, $maxRequests = 100, $window = 60) {
    $rateLimiter = new RateLimiter();
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Check if IP is blacklisted
    if ($rateLimiter->isIPBlocked($ip)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Your IP has been blocked due to abuse.'
        ]);
        exit;
    }
    
    // Check rate limit
    $check = $rateLimiter->check("api_{$endpoint}_{$ip}", $maxRequests, $window);
    if (!$check['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . $check['retry_after']);
        echo json_encode([
            'success' => false,
            'error' => 'Rate limit exceeded. Please slow down.',
            'retry_after' => $check['retry_after']
        ]);
        exit;
    }
    
    // Add rate limit headers
    header('X-RateLimit-Limit: ' . $maxRequests);
    header('X-RateLimit-Remaining: ' . $check['remaining']);
}
```

**Apply to high-traffic endpoints:**
- [ ] `/backend/api/products/list.php` - 100/min
- [ ] `/backend/api/cart/add.php` - 30/min
- [ ] `/backend/api/wishlist/add.php` - 30/min
- [ ] `/backend/api/search/products.php` - 60/min

- [ ] Create middleware
- [ ] Apply to endpoints
- [ ] Test rate limits
- [ ] Verify headers returned

#### E) Contact Form (15 mins)

**File:** `/backend/api/contact/submit.php`

```php
$check = $rateLimiter->check("contact_{$ip}", 5, 3600); // 5 per hour
```

- [ ] Add rate limiting
- [ ] Prevent spam
- [ ] Test submission

#### F) Review Submission (15 mins)

**File:** `/backend/api/reviews/submit.php`

```php
$check = $rateLimiter->check("review_{$userId}", 10, 3600); // 10 per hour
```

- [ ] Add rate limiting
- [ ] Prevent review spam
- [ ] Test submission

---

## 🔒 PART 2: reCAPTCHA INTEGRATION (2 hours)

### 3. Setup Google reCAPTCHA v3 (1 hour)

#### A) Get reCAPTCHA Keys (15 mins)
- [ ] Go to https://www.google.com/recaptcha/admin
- [ ] Register site
- [ ] Select reCAPTCHA v3
- [ ] Add domain (localhost for dev, prod domain for production)
- [ ] Get Site Key (public)
- [ ] Get Secret Key (private)
- [ ] Save keys to `.env` file

#### B) Add to Config (10 mins)

**File:** `/backend/config/config.php`

```php
'recaptcha' => [
    'enabled' => getenv('RECAPTCHA_ENABLED') === 'true',
    'site_key' => getenv('RECAPTCHA_SITE_KEY'),
    'secret_key' => getenv('RECAPTCHA_SECRET_KEY'),
    'min_score' => 0.5, // 0.0 to 1.0 (higher = more likely human)
],
```

**.env:**
```
RECAPTCHA_ENABLED=true
RECAPTCHA_SITE_KEY=your_site_key_here
RECAPTCHA_SECRET_KEY=your_secret_key_here
```

- [ ] Add config
- [ ] Add env variables
- [ ] Update .env.example

#### C) Create reCAPTCHA Helper (20 mins)

**File:** `/backend/security/RecaptchaHelper.php`

```php
<?php
class RecaptchaHelper {
    private $secretKey;
    private $minScore;
    private $enabled;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $this->secretKey = $config['recaptcha']['secret_key'];
        $this->minScore = $config['recaptcha']['min_score'];
        $this->enabled = $config['recaptcha']['enabled'];
    }
    
    public function verify($token, $action) {
        if (!$this->enabled) {
            return ['success' => true]; // Skip if disabled
        }
        
        if (empty($token)) {
            return [
                'success' => false,
                'error' => 'No reCAPTCHA token provided'
            ];
        }
        
        // Verify with Google
        $response = file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ])
        );
        
        $result = json_decode($response, true);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'error' => 'reCAPTCHA verification failed',
                'details' => $result['error-codes'] ?? []
            ];
        }
        
        // Check score
        if ($result['score'] < $this->minScore) {
            return [
                'success' => false,
                'error' => 'Bot detected',
                'score' => $result['score']
            ];
        }
        
        // Check action matches
        if ($result['action'] !== $action) {
            return [
                'success' => false,
                'error' => 'Action mismatch'
            ];
        }
        
        return [
            'success' => true,
            'score' => $result['score']
        ];
    }
}
```

- [ ] Create helper class
- [ ] Implement verify method
- [ ] Check score threshold
- [ ] Check action matches
- [ ] Handle errors
- [ ] Test with valid token
- [ ] Test with invalid token

#### D) Add to Frontend (15 mins)

**File:** `/frontend/components/header.php`

```php
<?php if ($recaptchaEnabled): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>
<script>
const RECAPTCHA_SITE_KEY = '<?php echo RECAPTCHA_SITE_KEY; ?>';

async function getRecaptchaToken(action) {
    try {
        return await grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: action });
    } catch (error) {
        console.error('reCAPTCHA error:', error);
        return null;
    }
}
</script>
<?php endif; ?>
```

- [ ] Add reCAPTCHA script
- [ ] Add helper function
- [ ] Test script loads

---

### 4. Apply reCAPTCHA to Forms (1 hour)

#### A) Login Form (15 mins)

**Frontend (`login.php`):**
```javascript
async function handleLogin(e) {
    e.preventDefault();
    
    // Get reCAPTCHA token
    const token = await getRecaptchaToken('login');
    
    const formData = new FormData(e.target);
    formData.append('recaptcha_token', token);
    
    // ... rest of login code
}
```

**Backend (`/backend/api/user/login.php`):**
```php
require_once '../../security/RecaptchaHelper.php';

$recaptcha = new RecaptchaHelper();
$token = $_POST['recaptcha_token'] ?? '';
$verification = $recaptcha->verify($token, 'login');

if (!$verification['success']) {
    echo json_encode([
        'success' => false,
        'error' => 'Bot verification failed. Please try again.'
    ]);
    exit;
}
```

- [ ] Update frontend to get token
- [ ] Update backend to verify
- [ ] Test login works
- [ ] Test bot detection

#### B) Register Form (15 mins)
- [ ] Add reCAPTCHA to frontend
- [ ] Verify on backend
- [ ] Test registration

#### C) Contact Form (10 mins)
- [ ] Add reCAPTCHA
- [ ] Verify on backend
- [ ] Test submission

#### D) Password Reset (10 mins)
- [ ] Add reCAPTCHA
- [ ] Verify on backend
- [ ] Test reset

#### E) Checkout (Optional) (10 mins)
- [ ] Add reCAPTCHA to checkout
- [ ] Verify before order creation
- [ ] Test checkout flow

---

## 🛡️ PART 3: ADDITIONAL SECURITY HARDENING (2 hours)

### 5. Session Security Improvements (45 mins)

**File:** `/backend/config/session.php` (create new)

```php
<?php
// Secure session configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Requires HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', 0); // Until browser closes
ini_set('session.gc_maxlifetime', 1800); // 30 minutes

session_start();

// Regenerate session ID after login
function regenerateSessionAfterLogin() {
    session_regenerate_id(true);
}

// Session timeout (30 minutes of inactivity)
function checkSessionTimeout() {
    $timeout = 1800; // 30 minutes
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// Validate session
if (!checkSessionTimeout()) {
    header('Location: /frontend/login.php?timeout=1');
    exit;
}
```

**Checklist:**
- [ ] Create session config file
- [ ] Set secure cookie flags
- [ ] Implement session timeout
- [ ] Add session regeneration on login
- [ ] Update login.php to call regenerateSessionAfterLogin()
- [ ] Test session timeout
- [ ] Test session regeneration

**Update login:**
```php
// In /backend/api/user/login.php after successful login
require_once '../config/session.php';
regenerateSessionAfterLogin();
```

- [ ] Add regeneration call
- [ ] Test login

---

### 6. Input Validation & Sanitization (30 mins)

**File:** `/backend/security/InputValidator.php`

```php
<?php
class InputValidator {
    public static function sanitizeEmail($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function sanitizeString($string) {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }
    
    public static function sanitizeInt($value) {
        return (int) $value;
    }
    
    public static function sanitizeFloat($value) {
        return (float) $value;
    }
    
    public static function validatePhone($phone) {
        // New Zealand phone format
        return preg_match('/^(\+64|0)[0-9]{8,10}$/', $phone);
    }
    
    public static function validatePassword($password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }
    
    public static function sanitizeFileName($filename) {
        // Remove path traversal attempts
        $filename = basename($filename);
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return $filename;
    }
}
```

**Apply to key endpoints:**
- [ ] User registration
- [ ] Profile update
- [ ] Product creation
- [ ] All file uploads

- [ ] Create validator class
- [ ] Apply to registration
- [ ] Apply to profile update
- [ ] Apply to file uploads
- [ ] Test validation

---

### 7. Security Headers (15 mins)

**File:** `/backend/security/SecurityHeaders.php`

```php
<?php
class SecurityHeaders {
    public static function set() {
        // Prevent XSS
        header("X-XSS-Protection: 1; mode=block");
        
        // Prevent clickjacking
        header("X-Frame-Options: SAMEORIGIN");
        
        // Prevent MIME sniffing
        header("X-Content-Type-Options: nosniff");
        
        // Referrer policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;");
        
        // Force HTTPS (if on HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
    }
}
```

**Add to all pages:**
```php
require_once '../backend/security/SecurityHeaders.php';
SecurityHeaders::set();
```

- [ ] Create headers class
- [ ] Add to frontend pages
- [ ] Add to API endpoints
- [ ] Test headers set (browser dev tools)
- [ ] Adjust CSP as needed

---

### 8. File Upload Security (30 mins)

**Update Product Image Upload:**

```php
// In product image upload handling
require_once '../../security/InputValidator.php';

// Validate file
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

$fileType = mime_content_type($_FILES['image']['tmp_name']);
$fileSize = $_FILES['image']['size'];

if (!in_array($fileType, $allowedTypes)) {
    throw new Exception('Invalid file type. Only JPG, PNG, GIF, WebP allowed.');
}

if ($fileSize > $maxSize) {
    throw new Exception('File too large. Maximum 5MB.');
}

// Sanitize filename
$originalName = InputValidator::sanitizeFileName($_FILES['image']['name']);

// Generate unique filename
$extension = pathinfo($originalName, PATHINFO_EXTENSION);
$newFilename = uniqid() . '_' . time() . '.' . $extension;

// Move to safe location
$uploadDir = __DIR__ . '/../../uploads/products/';
$uploadPath = $uploadDir . $newFilename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
    throw new Exception('Failed to upload file.');
}

// Set permissions
chmod($uploadPath, 0644);
```

**Checklist:**
- [ ] Validate MIME type (not just extension)
- [ ] Check file size
- [ ] Sanitize filename
- [ ] Generate unique filename
- [ ] Store outside web root if possible
- [ ] Set correct permissions
- [ ] Test upload with image
- [ ] Test upload with PHP file (should fail)
- [ ] Test upload with large file (should fail)

---

## ✅ TESTING (Throughout Implementation)

### Test Rate Limiting:
- [ ] Login brute force (should block after 5)
- [ ] Password reset spam (should block after 3)
- [ ] Registration spam (should block after 3)
- [ ] API spam (should block after limit)
- [ ] Verify retry-after header
- [ ] Verify unlock after time expires

### Test reCAPTCHA:
- [ ] Forms work with valid reCAPTCHA
- [ ] Forms reject without reCAPTCHA
- [ ] Check score threshold works
- [ ] Test with reCAPTCHA disabled (dev mode)

### Test Security:
- [ ] Session timeout works
- [ ] Session regeneration works
- [ ] Security headers present
- [ ] File upload validation works
- [ ] Input validation works

---

## 📚 DOCUMENTATION (30 mins)

**Create:** `/docs/SECURITY-FEATURES.md`

```markdown
# Security Features

## Rate Limiting
- Login: 5 attempts per 15 minutes per email
- Password Reset: 3 per hour
- Registration: 3 per hour per IP
- API: 100 requests per minute per endpoint

## reCAPTCHA
Enabled on:
- Login
- Registration
- Contact Form
- Password Reset

## Session Security
- 30-minute timeout
- Regeneration after login
- Secure cookies (HTTP-only, Secure, SameSite)

## File Upload Security
- MIME type validation
- Size limits (5MB)
- Filename sanitization
- Unique filenames

## Security Headers
[List all headers and their purpose]

## IP Blacklist
Admins can permanently block abusive IPs.
```

- [ ] Write documentation
- [ ] Document all limits
- [ ] Document configuration
- [ ] Add troubleshooting section

---

## ✅ COMPLETION CHECKLIST

- [ ] RateLimiter class created and working
- [ ] Rate limits applied to all sensitive endpoints
- [ ] reCAPTCHA integrated and working
- [ ] Session security implemented
- [ ] Input validation applied
- [ ] Security headers set
- [ ] File upload security enhanced
- [ ] All tests passing
- [ ] Documentation complete
- [ ] Code committed

---

**Total Estimated Time:** 8 hours  
**Actual Time Spent:** _____ hours  
**Completion Date:** ___________  
**Notes:**

---

**Next:** Day 5 - File Upload Security & Image Optimization
