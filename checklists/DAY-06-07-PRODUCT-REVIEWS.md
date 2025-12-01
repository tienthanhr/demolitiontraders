# ⭐ DAY 6-7: PRODUCT REVIEWS SYSTEM CHECKLIST

**Priority:** P1 - IMPORTANT FEATURE  
**Estimated Time:** 16 hours (2 days)  
**Status:** ⬜ Not Started

---

## 📋 OVERVIEW

Build a complete product review and rating system:
- Customers can leave reviews
- Star ratings (1-5 stars)
- Only verified purchasers can review
- Admin moderation
- Display on product pages
- Helpful/unhelpful voting
- Review statistics

---

## 🗄️ DATABASE VERIFICATION & UPDATES (30 mins)

### 1. Verify Reviews Table Exists

**Check:** `/database/schema.sql` should have:
```sql
CREATE TABLE product_reviews (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  rating INT NOT NULL,
  title VARCHAR(255),
  comment TEXT,
  is_verified_purchase BOOLEAN DEFAULT FALSE,
  is_approved BOOLEAN DEFAULT FALSE,
  helpful_count INT DEFAULT 0,
  unhelpful_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_product_id (product_id),
  INDEX idx_user_id (user_id),
  INDEX idx_approved (is_approved),
  INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Checklist:**
- [ ] Verify table exists in schema.sql
- [ ] If not, create migration file
- [ ] Run migration on dev database
- [ ] Verify table created successfully

### 2. Add Review Voting Table

**Create:** `database/reviews_votes.sql`
```sql
CREATE TABLE review_votes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  review_id INT NOT NULL,
  user_id INT NOT NULL,
  vote_type ENUM('helpful', 'unhelpful') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_vote (review_id, user_id),
  INDEX idx_review_id (review_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Checklist:**
- [ ] Create SQL file
- [ ] Run migration
- [ ] Verify table created
- [ ] Test unique constraint

### 3. Update Products Table for Review Stats

```sql
ALTER TABLE products 
ADD COLUMN review_count INT DEFAULT 0,
ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00,
ADD INDEX idx_rating (average_rating);
```

**Checklist:**
- [ ] Run migration
- [ ] Verify columns added
- [ ] Test default values

---

## 🔧 BACKEND IMPLEMENTATION (8 hours)

### 4. Create ReviewController (3 hours)

**File:** `/backend/controllers/ReviewController.php`

#### Class Structure:
```php
<?php
class ReviewController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Submit a review
    public function submitReview($data) { }
    
    // Get reviews for a product
    public function getProductReviews($productId, $page = 1, $limit = 10, $sortBy = 'recent') { }
    
    // Get user's review for a product
    public function getUserReview($productId, $userId) { }
    
    // Update review
    public function updateReview($reviewId, $userId, $data) { }
    
    // Delete review
    public function deleteReview($reviewId, $userId) { }
    
    // Check if user can review product (verified purchase)
    public function canUserReview($productId, $userId) { }
    
    // Vote on review (helpful/unhelpful)
    public function voteReview($reviewId, $userId, $voteType) { }
    
    // Admin: Moderate review
    public function moderateReview($reviewId, $approved) { }
    
    // Admin: Delete review
    public function adminDeleteReview($reviewId) { }
    
    // Get review statistics
    public function getReviewStats($productId) { }
    
    // Update product rating cache
    private function updateProductRatingCache($productId) { }
}
```

#### A) submitReview() Method (45 mins)
```php
public function submitReview($data) {
    $productId = (int)$data['product_id'];
    $userId = (int)$data['user_id'];
    $rating = (int)$data['rating'];
    $title = trim($data['title'] ?? '');
    $comment = trim($data['comment']);
    
    // Validate rating (1-5)
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5');
    }
    
    // Validate comment
    if (empty($comment) || strlen($comment) < 10) {
        throw new Exception('Review must be at least 10 characters');
    }
    
    if (strlen($comment) > 1000) {
        throw new Exception('Review must not exceed 1000 characters');
    }
    
    // Check if user already reviewed this product
    $existing = $this->db->fetchOne(
        "SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?",
        [$productId, $userId]
    );
    
    if ($existing) {
        throw new Exception('You have already reviewed this product');
    }
    
    // Check if verified purchase
    $isVerified = $this->canUserReview($productId, $userId);
    
    // Insert review (requires approval by default)
    $reviewId = $this->db->insert(
        "INSERT INTO product_reviews (product_id, user_id, rating, title, comment, is_verified_purchase, is_approved) 
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$productId, $userId, $rating, $title, $comment, $isVerified, false]
    );
    
    // Send notification to admin
    // TODO: Implement email notification
    
    return [
        'success' => true,
        'review_id' => $reviewId,
        'message' => 'Review submitted successfully. It will appear after moderation.'
    ];
}
```

**Checklist:**
- [ ] Validate rating (1-5)
- [ ] Validate comment (10-1000 chars)
- [ ] Check for duplicate review
- [ ] Check verified purchase status
- [ ] Insert review (not approved by default)
- [ ] Return success response
- [ ] Test submission
- [ ] Test validation errors
- [ ] Test duplicate prevention

#### B) canUserReview() Method (20 mins)
```php
public function canUserReview($productId, $userId) {
    // Check if user has purchased and received this product
    $result = $this->db->fetchOne(
        "SELECT COUNT(*) as count 
         FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         WHERE o.user_id = ? 
         AND oi.product_id = ? 
         AND o.status = 'delivered'",
        [$userId, $productId]
    );
    
    return $result['count'] > 0;
}
```

- [ ] Query order history
- [ ] Check for delivered orders
- [ ] Return boolean
- [ ] Test with verified buyer
- [ ] Test with non-buyer

#### C) getProductReviews() Method (1 hour)
```php
public function getProductReviews($productId, $page = 1, $limit = 10, $sortBy = 'recent') {
    $offset = ($page - 1) * $limit;
    
    // Sort options
    $orderBy = match($sortBy) {
        'helpful' => 'r.helpful_count DESC',
        'rating_high' => 'r.rating DESC',
        'rating_low' => 'r.rating ASC',
        default => 'r.created_at DESC' // recent
    };
    
    // Get reviews
    $reviews = $this->db->fetchAll(
        "SELECT r.*, 
                u.first_name, u.last_name,
                (SELECT vote_type FROM review_votes WHERE review_id = r.id AND user_id = ?) as user_vote
         FROM product_reviews r
         JOIN users u ON r.user_id = u.id
         WHERE r.product_id = ? AND r.is_approved = 1
         ORDER BY {$orderBy}
         LIMIT ? OFFSET ?",
        [$_SESSION['user_id'] ?? 0, $productId, $limit, $offset]
    );
    
    // Get total count
    $total = $this->db->fetchOne(
        "SELECT COUNT(*) as count FROM product_reviews WHERE product_id = ? AND is_approved = 1",
        [$productId]
    );
    
    // Format reviews
    foreach ($reviews as &$review) {
        $review['reviewer_name'] = $review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.';
        $review['time_ago'] = $this->timeAgo($review['created_at']);
        unset($review['first_name'], $review['last_name']);
    }
    
    return [
        'reviews' => $reviews,
        'total' => $total['count'],
        'page' => $page,
        'pages' => ceil($total['count'] / $limit),
        'limit' => $limit
    ];
}

private function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}
```

**Checklist:**
- [ ] Implement pagination
- [ ] Implement sorting options
- [ ] Join with users table
- [ ] Hide reviewer last name (privacy)
- [ ] Check user's vote status
- [ ] Format time as "X ago"
- [ ] Return formatted array
- [ ] Test with different sort options
- [ ] Test pagination

#### D) getReviewStats() Method (30 mins)
```php
public function getReviewStats($productId) {
    // Get rating distribution
    $distribution = $this->db->fetchAll(
        "SELECT rating, COUNT(*) as count 
         FROM product_reviews 
         WHERE product_id = ? AND is_approved = 1 
         GROUP BY rating 
         ORDER BY rating DESC",
        [$productId]
    );
    
    // Initialize all ratings (1-5)
    $stats = [
        'total_reviews' => 0,
        'average_rating' => 0,
        'distribution' => [
            5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0
        ]
    ];
    
    // Fill distribution
    foreach ($distribution as $row) {
        $stats['distribution'][$row['rating']] = (int)$row['count'];
        $stats['total_reviews'] += (int)$row['count'];
    }
    
    // Calculate average
    if ($stats['total_reviews'] > 0) {
        $sum = 0;
        foreach ($stats['distribution'] as $rating => $count) {
            $sum += $rating * $count;
        }
        $stats['average_rating'] = round($sum / $stats['total_reviews'], 2);
    }
    
    // Calculate percentages
    foreach ($stats['distribution'] as $rating => $count) {
        $percentage = $stats['total_reviews'] > 0 
            ? round(($count / $stats['total_reviews']) * 100) 
            : 0;
        $stats['distribution'][$rating] = [
            'count' => $count,
            'percentage' => $percentage
        ];
    }
    
    return $stats;
}
```

- [ ] Query rating distribution
- [ ] Calculate total reviews
- [ ] Calculate average rating
- [ ] Calculate percentages
- [ ] Return formatted stats
- [ ] Test with various products

#### E) voteReview() Method (30 mins)
```php
public function voteReview($reviewId, $userId, $voteType) {
    if (!in_array($voteType, ['helpful', 'unhelpful'])) {
        throw new Exception('Invalid vote type');
    }
    
    // Check if already voted
    $existing = $this->db->fetchOne(
        "SELECT vote_type FROM review_votes WHERE review_id = ? AND user_id = ?",
        [$reviewId, $userId]
    );
    
    if ($existing) {
        // Update vote if different
        if ($existing['vote_type'] !== $voteType) {
            // Decrement old count
            $oldColumn = $existing['vote_type'] === 'helpful' ? 'helpful_count' : 'unhelpful_count';
            $this->db->query(
                "UPDATE product_reviews SET {$oldColumn} = {$oldColumn} - 1 WHERE id = ?",
                [$reviewId]
            );
            
            // Increment new count
            $newColumn = $voteType === 'helpful' ? 'helpful_count' : 'unhelpful_count';
            $this->db->query(
                "UPDATE product_reviews SET {$newColumn} = {$newColumn} + 1 WHERE id = ?",
                [$reviewId]
            );
            
            // Update vote record
            $this->db->query(
                "UPDATE review_votes SET vote_type = ? WHERE review_id = ? AND user_id = ?",
                [$voteType, $reviewId, $userId]
            );
        }
    } else {
        // New vote
        $column = $voteType === 'helpful' ? 'helpful_count' : 'unhelpful_count';
        
        $this->db->query(
            "UPDATE product_reviews SET {$column} = {$column} + 1 WHERE id = ?",
            [$reviewId]
        );
        
        $this->db->query(
            "INSERT INTO review_votes (review_id, user_id, vote_type) VALUES (?, ?, ?)",
            [$reviewId, $userId, $voteType]
        );
    }
    
    // Get updated counts
    $review = $this->db->fetchOne(
        "SELECT helpful_count, unhelpful_count FROM product_reviews WHERE id = ?",
        [$reviewId]
    );
    
    return [
        'success' => true,
        'helpful_count' => $review['helpful_count'],
        'unhelpful_count' => $review['unhelpful_count']
    ];
}
```

- [ ] Validate vote type
- [ ] Check existing vote
- [ ] Handle vote change
- [ ] Update counts
- [ ] Return updated counts
- [ ] Test voting
- [ ] Test vote change

#### F) moderateReview() Method (15 mins)
```php
public function moderateReview($reviewId, $approved) {
    $this->db->query(
        "UPDATE product_reviews SET is_approved = ? WHERE id = ?",
        [$approved, $reviewId]
    );
    
    // Get review details for notification
    $review = $this->db->fetchOne(
        "SELECT r.*, p.name as product_name, u.email, u.first_name
         FROM product_reviews r
         JOIN products p ON r.product_id = p.id
         JOIN users u ON r.user_id = u.id
         WHERE r.id = ?",
        [$reviewId]
    );
    
    // Update product rating cache
    $this->updateProductRatingCache($review['product_id']);
    
    // Send email notification if approved
    if ($approved) {
        // TODO: Send approval email to user
    }
    
    return ['success' => true];
}
```

- [ ] Update approval status
- [ ] Get review details
- [ ] Update product cache
- [ ] Send notification
- [ ] Test approval
- [ ] Test rejection

#### G) updateProductRatingCache() Method (20 mins)
```php
private function updateProductRatingCache($productId) {
    $stats = $this->db->fetchOne(
        "SELECT COUNT(*) as count, AVG(rating) as average
         FROM product_reviews 
         WHERE product_id = ? AND is_approved = 1",
        [$productId]
    );
    
    $this->db->query(
        "UPDATE products SET review_count = ?, average_rating = ? WHERE id = ?",
        [$stats['count'], round($stats['average'], 2), $productId]
    );
}
```

- [ ] Calculate stats
- [ ] Update products table
- [ ] Test cache update

---

### 5. Create Review API Endpoints (2 hours)

#### A) Submit Review (30 mins)

**File:** `/backend/api/reviews/submit.php`

```php
<?php
session_start();
require_once '../../controllers/ReviewController.php';
require_once '../../security/CsrfMiddleware.php';
require_once '../../security/RateLimiter.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please login to submit a review']);
    exit;
}

// Validate CSRF
if (!CsrfMiddleware::validate()) {
    exit;
}

// Rate limit
$rateLimiter = new RateLimiter();
$check = $rateLimiter->check("review_{$_SESSION['user_id']}", 10, 3600);
if (!$check['allowed']) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many reviews. Please try again later.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $data = [
        'product_id' => $input['product_id'],
        'user_id' => $_SESSION['user_id'],
        'rating' => $input['rating'],
        'title' => $input['title'] ?? '',
        'comment' => $input['comment']
    ];
    
    $controller = new ReviewController();
    $result = $controller->submitReview($data);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

**Checklist:**
- [ ] Create file
- [ ] Check authentication
- [ ] Validate CSRF
- [ ] Add rate limiting
- [ ] Call controller
- [ ] Handle errors
- [ ] Test submission
- [ ] Test without login
- [ ] Test rate limiting

#### B) Get Product Reviews (20 mins)

**File:** `/backend/api/reviews/list.php`

```php
<?php
require_once '../../controllers/ReviewController.php';

header('Content-Type: application/json');

try {
    $productId = (int)($_GET['product_id'] ?? 0);
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 10);
    $sortBy = $_GET['sort'] ?? 'recent';
    
    if (!$productId) {
        throw new Exception('Product ID required');
    }
    
    $controller = new ReviewController();
    $result = $controller->getProductReviews($productId, $page, $limit, $sortBy);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

- [ ] Create file
- [ ] Get parameters
- [ ] Call controller
- [ ] Return reviews
- [ ] Test fetching

#### C) Get Review Stats (15 mins)

**File:** `/backend/api/reviews/stats.php`

```php
<?php
require_once '../../controllers/ReviewController.php';

header('Content-Type: application/json');

try {
    $productId = (int)($_GET['product_id'] ?? 0);
    
    if (!$productId) {
        throw new Exception('Product ID required');
    }
    
    $controller = new ReviewController();
    $stats = $controller->getReviewStats($productId);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

- [ ] Create file
- [ ] Get product ID
- [ ] Call controller
- [ ] Return stats
- [ ] Test stats

#### D) Vote on Review (20 mins)

**File:** `/backend/api/reviews/vote.php`

```php
<?php
session_start();
require_once '../../controllers/ReviewController.php';
require_once '../../security/CsrfMiddleware.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please login to vote']);
    exit;
}

if (!CsrfMiddleware::validate()) {
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $reviewId = (int)$input['review_id'];
    $voteType = $input['vote_type'];
    
    $controller = new ReviewController();
    $result = $controller->voteReview($reviewId, $_SESSION['user_id'], $voteType);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

- [ ] Create file
- [ ] Check authentication
- [ ] Validate CSRF
- [ ] Call controller
- [ ] Return result
- [ ] Test voting

#### E) Check Can Review (15 mins)

**File:** `/backend/api/reviews/can-review.php`

```php
<?php
session_start();
require_once '../../controllers/ReviewController.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['can_review' => false, 'reason' => 'not_logged_in']);
    exit;
}

try {
    $productId = (int)($_GET['product_id'] ?? 0);
    
    $controller = new ReviewController();
    $canReview = $controller->canUserReview($productId, $_SESSION['user_id']);
    
    // Check if already reviewed
    $existing = $controller->getUserReview($productId, $_SESSION['user_id']);
    
    echo json_encode([
        'can_review' => $canReview && !$existing,
        'is_verified_buyer' => $canReview,
        'has_reviewed' => (bool)$existing,
        'review' => $existing ?: null
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'can_review' => false,
        'error' => $e->getMessage()
    ]);
}
```

- [ ] Create file
- [ ] Check login
- [ ] Check verified purchase
- [ ] Check existing review
- [ ] Return status
- [ ] Test various scenarios

---

### 6. Admin Review Management (1 hour)

#### A) Get Pending Reviews API (20 mins)

**File:** `/backend/api/admin/reviews/pending.php`

```php
<?php
session_start();
require_once '../../../controllers/ReviewController.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    $reviews = $db->fetchAll(
        "SELECT r.*, 
                p.name as product_name, 
                u.first_name, u.last_name, u.email
         FROM product_reviews r
         JOIN products p ON r.product_id = p.id
         JOIN users u ON r.user_id = u.id
         WHERE r.is_approved = 0
         ORDER BY r.created_at DESC"
    );
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

- [ ] Create file
- [ ] Check admin auth
- [ ] Query pending reviews
- [ ] Return list
- [ ] Test fetching

#### B) Moderate Review API (20 mins)

**File:** `/backend/api/admin/reviews/moderate.php`

```php
<?php
session_start();
require_once '../../../controllers/ReviewController.php';
require_once '../../../security/CsrfMiddleware.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!CsrfMiddleware::validate()) {
    exit;
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $reviewId = (int)$input['review_id'];
    $approved = (bool)$input['approved'];
    
    $controller = new ReviewController();
    $result = $controller->moderateReview($reviewId, $approved);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

- [ ] Create file
- [ ] Check admin auth
- [ ] Validate CSRF
- [ ] Call controller
- [ ] Return result
- [ ] Test approval
- [ ] Test rejection

#### C) Delete Review API (20 mins)

**File:** `/backend/api/admin/reviews/delete.php`

```php
<?php
session_start();
require_once '../../../controllers/ReviewController.php';
require_once '../../../security/CsrfMiddleware.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!CsrfMiddleware::validate()) {
    exit;
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $reviewId = (int)$input['review_id'];
    
    $controller = new ReviewController();
    $result = $controller->adminDeleteReview($reviewId);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

- [ ] Create file
- [ ] Check admin auth
- [ ] Validate CSRF
- [ ] Delete review
- [ ] Update product cache
- [ ] Return result
- [ ] Test deletion

---

## 🎨 FRONTEND IMPLEMENTATION (5 hours)

### 7. Product Detail Page Reviews Section (2 hours)

**File:** `/frontend/product-detail.php`

#### Add HTML (45 mins):
```html
<!-- Reviews Section -->
<section class="product-reviews">
    <div class="reviews-summary">
        <div class="rating-overview">
            <div class="average-rating">
                <span class="rating-number" id="avgRating">0.0</span>
                <div class="stars" id="avgStars">
                    <!-- Stars rendered by JS -->
                </div>
                <span class="review-count" id="reviewCount">0 reviews</span>
            </div>
            
            <div class="rating-distribution" id="ratingDistribution">
                <!-- Distribution bars rendered by JS -->
            </div>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <button class="btn btn-primary" id="writeReviewBtn">
            <i class="fas fa-star"></i> Write a Review
        </button>
        <?php else: ?>
        <a href="/frontend/login.php" class="btn btn-primary">
            Login to Write a Review
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Review Filters & Sort -->
    <div class="reviews-controls">
        <select id="reviewSort">
            <option value="recent">Most Recent</option>
            <option value="helpful">Most Helpful</option>
            <option value="rating_high">Highest Rating</option>
            <option value="rating_low">Lowest Rating</option>
        </select>
    </div>
    
    <!-- Reviews List -->
    <div class="reviews-list" id="reviewsList">
        <!-- Reviews rendered by JS -->
    </div>
    
    <!-- Pagination -->
    <div class="reviews-pagination" id="reviewsPagination">
        <!-- Pagination rendered by JS -->
    </div>
</section>

<!-- Write Review Modal -->
<div class="modal" id="writeReviewModal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Write a Review</h2>
        
        <form id="reviewForm">
            <div class="form-group">
                <label>Rating *</label>
                <div class="star-rating" id="starRating">
                    <span data-rating="1">☆</span>
                    <span data-rating="2">☆</span>
                    <span data-rating="3">☆</span>
                    <span data-rating="4">☆</span>
                    <span data-rating="5">☆</span>
                </div>
                <input type="hidden" name="rating" id="ratingValue" required>
            </div>
            
            <div class="form-group">
                <label>Title (optional)</label>
                <input type="text" name="title" maxlength="255" placeholder="Brief summary">
            </div>
            
            <div class="form-group">
                <label>Review *</label>
                <textarea name="comment" required minlength="10" maxlength="1000" rows="5" placeholder="Share your experience with this product (minimum 10 characters)"></textarea>
                <span class="char-count"><span id="charCount">0</span>/1000</span>
            </div>
            
            <div class="verified-purchase-note" id="verifiedNote" style="display:none;">
                <i class="fas fa-check-circle"></i> You purchased this product
            </div>
            
            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
    </div>
</div>
```

**Checklist:**
- [ ] Add HTML structure
- [ ] Add review summary section
- [ ] Add write review button
- [ ] Add reviews list container
- [ ] Add review modal form
- [ ] Add star rating input
- [ ] Style with CSS

#### Add JavaScript (1 hour 15 mins):
```javascript
// Reviews functionality
const ProductReviews = {
    productId: <?php echo $product['id']; ?>,
    currentPage: 1,
    currentSort: 'recent',
    
    init: function() {
        this.loadStats();
        this.loadReviews();
        this.setupEventListeners();
        this.checkCanReview();
    },
    
    setupEventListeners: function() {
        // Star rating click
        document.querySelectorAll('#starRating span').forEach(star => {
            star.addEventListener('click', (e) => {
                const rating = e.target.dataset.rating;
                this.setRating(rating);
            });
        });
        
        // Review form submit
        document.getElementById('reviewForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitReview();
        });
        
        // Sort change
        document.getElementById('reviewSort').addEventListener('change', (e) => {
            this.currentSort = e.target.value;
            this.loadReviews();
        });
        
        // Write review button
        document.getElementById('writeReviewBtn')?.addEventListener('click', () => {
            document.getElementById('writeReviewModal').style.display = 'block';
        });
        
        // Modal close
        document.querySelector('.modal .close').addEventListener('click', () => {
            document.getElementById('writeReviewModal').style.display = 'none';
        });
        
        // Character count
        document.querySelector('textarea[name="comment"]').addEventListener('input', (e) => {
            document.getElementById('charCount').textContent = e.target.value.length;
        });
    },
    
    setRating: function(rating) {
        document.getElementById('ratingValue').value = rating;
        const stars = document.querySelectorAll('#starRating span');
        stars.forEach((star, index) => {
            star.textContent = index < rating ? '★' : '☆';
            star.classList.toggle('selected', index < rating);
        });
    },
    
    async loadStats: async function() {
        try {
            const response = await fetch(`/demolitiontraders/backend/api/reviews/stats.php?product_id=${this.productId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderStats(data.stats);
            }
        } catch (error) {
            console.error('Error loading review stats:', error);
        }
    },
    
    renderStats: function(stats) {
        document.getElementById('avgRating').textContent = stats.average_rating.toFixed(1);
        document.getElementById('reviewCount').textContent = `${stats.total_reviews} review${stats.total_reviews !== 1 ? 's' : ''}`;
        
        // Render star average
        this.renderStars('avgStars', stats.average_rating);
        
        // Render distribution
        let html = '';
        for (let i = 5; i >= 1; i--) {
            const data = stats.distribution[i];
            html += `
                <div class="rating-bar">
                    <span class="rating-label">${i} ★</span>
                    <div class="bar">
                        <div class="bar-fill" style="width: ${data.percentage}%"></div>
                    </div>
                    <span class="rating-count">${data.count}</span>
                </div>
            `;
        }
        document.getElementById('ratingDistribution').innerHTML = html;
    },
    
    renderStars: function(elementId, rating) {
        const container = document.getElementById(elementId);
        let html = '';
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        
        for (let i = 0; i < fullStars; i++) {
            html += '<i class="fas fa-star"></i>';
        }
        if (hasHalfStar) {
            html += '<i class="fas fa-star-half-alt"></i>';
        }
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        for (let i = 0; i < emptyStars; i++) {
            html += '<i class="far fa-star"></i>';
        }
        
        container.innerHTML = html;
    },
    
    async loadReviews: async function() {
        try {
            const response = await fetch(
                `/demolitiontraders/backend/api/reviews/list.php?product_id=${this.productId}&page=${this.currentPage}&sort=${this.currentSort}`
            );
            const data = await response.json();
            
            if (data.success) {
                this.renderReviews(data.data.reviews);
                this.renderPagination(data.data);
            }
        } catch (error) {
            console.error('Error loading reviews:', error);
        }
    },
    
    renderReviews: function(reviews) {
        const container = document.getElementById('reviewsList');
        
        if (reviews.length === 0) {
            container.innerHTML = '<p class="no-reviews">No reviews yet. Be the first to review!</p>';
            return;
        }
        
        let html = reviews.map(review => `
            <div class="review-item">
                <div class="review-header">
                    <div class="reviewer-info">
                        <strong>${review.reviewer_name}</strong>
                        ${review.is_verified_purchase ? '<span class="verified-badge"><i class="fas fa-check-circle"></i> Verified Purchase</span>' : ''}
                    </div>
                    <div class="review-rating">
                        ${this.renderStarsHTML(review.rating)}
                        <span class="review-date">${review.time_ago}</span>
                    </div>
                </div>
                
                ${review.title ? `<h4 class="review-title">${review.title}</h4>` : ''}
                
                <p class="review-comment">${review.comment}</p>
                
                <div class="review-actions">
                    <button class="btn-helpful ${review.user_vote === 'helpful' ? 'active' : ''}" onclick="ProductReviews.voteReview(${review.id}, 'helpful')">
                        <i class="fas fa-thumbs-up"></i> Helpful (${review.helpful_count})
                    </button>
                    <button class="btn-helpful ${review.user_vote === 'unhelpful' ? 'active' : ''}" onclick="ProductReviews.voteReview(${review.id}, 'unhelpful')">
                        <i class="fas fa-thumbs-down"></i> Not Helpful (${review.unhelpful_count})
                    </button>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
    },
    
    renderStarsHTML: function(rating) {
        let html = '';
        for (let i = 0; i < rating; i++) {
            html += '<i class="fas fa-star"></i>';
        }
        for (let i = rating; i < 5; i++) {
            html += '<i class="far fa-star"></i>';
        }
        return html;
    },
    
    renderPagination: function(data) {
        const container = document.getElementById('reviewsPagination');
        
        if (data.pages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '';
        for (let i = 1; i <= data.pages; i++) {
            html += `<button class="page-btn ${i === data.page ? 'active' : ''}" onclick="ProductReviews.goToPage(${i})">${i}</button>`;
        }
        
        container.innerHTML = html;
    },
    
    goToPage: function(page) {
        this.currentPage = page;
        this.loadReviews();
        document.getElementById('reviewsList').scrollIntoView({ behavior: 'smooth' });
    },
    
    async checkCanReview: async function() {
        try {
            const response = await fetch(`/demolitiontraders/backend/api/reviews/can-review.php?product_id=${this.productId}`);
            const data = await response.json();
            
            if (!data.can_review) {
                const btn = document.getElementById('writeReviewBtn');
                if (btn) {
                    if (data.has_reviewed) {
                        btn.textContent = 'You already reviewed this product';
                        btn.disabled = true;
                    } else if (!data.is_verified_buyer) {
                        btn.title = 'Purchase this product to leave a review';
                    }
                }
            } else if (data.is_verified_buyer) {
                document.getElementById('verifiedNote').style.display = 'block';
            }
        } catch (error) {
            console.error('Error checking review status:', error);
        }
    },
    
    async submitReview: async function() {
        const form = document.getElementById('reviewForm');
        const formData = new FormData(form);
        
        const data = {
            product_id: this.productId,
            rating: parseInt(formData.get('rating')),
            title: formData.get('title'),
            comment: formData.get('comment'),
            csrf_token: CsrfHelper.getToken()
        };
        
        try {
            const response = await CsrfHelper.fetch('/demolitiontraders/backend/api/reviews/submit.php', {
                method: 'POST',
                body: data
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                document.getElementById('writeReviewModal').style.display = 'none';
                form.reset();
                this.loadStats();
                this.loadReviews();
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            alert('Error submitting review. Please try again.');
            console.error(error);
        }
    },
    
    async voteReview: async function(reviewId, voteType) {
        try {
            const response = await CsrfHelper.fetch('/demolitiontraders/backend/api/reviews/vote.php', {
                method: 'POST',
                body: {
                    review_id: reviewId,
                    vote_type: voteType
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.loadReviews(); // Reload to show updated counts
            } else {
                if (result.error.includes('login')) {
                    alert('Please login to vote on reviews');
                } else {
                    alert('Error: ' + result.error);
                }
            }
        } catch (error) {
            console.error('Error voting:', error);
        }
    }
};

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    ProductReviews.init();
});
```

**Checklist:**
- [ ] Add JavaScript code
- [ ] Test loading stats
- [ ] Test loading reviews
- [ ] Test pagination
- [ ] Test sorting
- [ ] Test star rating input
- [ ] Test form submission
- [ ] Test voting
- [ ] Test all edge cases

---

### 8. Admin Reviews Page (2 hours)

**File:** `/frontend/admin/reviews.php`

[Create admin page with pending reviews list, approve/reject buttons, statistics]

**Checklist:**
- [ ] Create admin page
- [ ] List pending reviews
- [ ] Add approve/reject buttons
- [ ] Add delete button
- [ ] Show review statistics
- [ ] Test moderation
- [ ] Test deletion

---

### 9. CSS Styling (1 hour)

[Style reviews section, modal, star ratings, etc.]

**Checklist:**
- [ ] Style reviews section
- [ ] Style star ratings
- [ ] Style review modal
- [ ] Style review items
- [ ] Style voting buttons
- [ ] Style rating distribution bars
- [ ] Mobile responsive
- [ ] Test across browsers

---

## ✅ COMPLETION CHECKLIST

- [ ] Database tables created
- [ ] ReviewController complete and tested
- [ ] All API endpoints working
- [ ] Frontend reviews section implemented
- [ ] Review modal working
- [ ] Star ratings working
- [ ] Voting system working
- [ ] Admin moderation working
- [ ] Verified purchase check working
- [ ] Email notifications working
- [ ] All tests passing
- [ ] Mobile responsive
- [ ] Documentation updated
- [ ] Code committed

---

**Total Estimated Time:** 16 hours  
**Actual Time Spent:** _____ hours  
**Completion Date:** ___________  
**Notes:**

---

**Next:** Day 8 - Complete Backend APIs (Wanted Listing, Sell-to-Us, Contact)
