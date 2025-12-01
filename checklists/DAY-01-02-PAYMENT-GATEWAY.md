# 💳 DAY 1-2: PAYMENT GATEWAY INTEGRATION CHECKLIST

**Priority:** P0 - CRITICAL BLOCKER  
**Estimated Time:** 16 hours (2 days)  
**Status:** ⬜ Not Started

---

## 📋 PRE-REQUISITES

### Research & Setup (2 hours)
- [ ] Read Windcave API documentation thoroughly
- [ ] Sign up for Windcave developer account
- [ ] Get sandbox/test credentials (API Key, Username, Password)
- [ ] Get production credentials (for later)
- [ ] Understand payment flow:
  - [ ] Session creation
  - [ ] Payment redirect
  - [ ] Return URL handling
  - [ ] Webhook notifications
  - [ ] Signature verification
- [ ] Review Windcave security requirements
- [ ] Check Windcave PHP SDK availability (or use raw API)

### Alternative (if Windcave unavailable)
- [ ] Research Stripe API (easier alternative)
- [ ] Sign up for Stripe account
- [ ] Get test API keys
- [ ] Review Stripe Checkout documentation

---

## 🗄️ DATABASE CHANGES

### Create Payment Transactions Table (30 mins)
```sql
CREATE TABLE payment_transactions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  transaction_id VARCHAR(100) UNIQUE,
  payment_method VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'NZD',
  status ENUM('pending','processing','completed','failed','refunded','cancelled') DEFAULT 'pending',
  gateway VARCHAR(50) DEFAULT 'windcave',
  gateway_response TEXT,
  error_message TEXT,
  customer_ip VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  INDEX idx_transaction_id (transaction_id),
  INDEX idx_order_id (order_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] Run migration in development database
- [ ] Verify table created successfully
- [ ] Test foreign key constraints

### Update Orders Table (15 mins)
```sql
ALTER TABLE orders 
ADD COLUMN transaction_id VARCHAR(100) NULL AFTER payment_status,
ADD INDEX idx_transaction_id (transaction_id);
```

- [ ] Run migration
- [ ] Verify column added

---

## 🔧 BACKEND IMPLEMENTATION

### 1. Create PaymentService Class (3 hours)

**File:** `/backend/services/PaymentService.php`

#### Basic Structure
```php
<?php
class PaymentService {
    private $apiUrl;
    private $apiKey;
    private $username;
    private $password;
    private $db;
    
    public function __construct() { }
    private function getConfig() { }
    private function generateSignature($data) { }
    private function makeApiRequest($endpoint, $data, $method = 'POST') { }
    
    // Main methods to implement below
}
```

- [ ] Create file structure
- [ ] Add constructor with dependency injection
- [ ] Load config from `backend/config/config.php`

#### Method: createPaymentSession() (1 hour)
```php
public function createPaymentSession($orderId, $amount, $currency = 'NZD') {
    // 1. Validate order exists
    // 2. Create transaction record (status='pending')
    // 3. Call Windcave API to create session
    // 4. Generate return URLs
    // 5. Store session ID
    // 6. Return session URL for redirect
}
```

**Checklist:**
- [ ] Validate `$orderId` exists in database
- [ ] Validate `$amount` is positive number
- [ ] Create entry in `payment_transactions` table
- [ ] Generate unique transaction reference
- [ ] Prepare API request payload:
  - [ ] Amount (in cents)
  - [ ] Currency code
  - [ ] Order reference
  - [ ] Return URL (success)
  - [ ] Cancel URL
  - [ ] Callback URL (webhook)
  - [ ] Customer details (from order)
- [ ] Call Windcave API endpoint
- [ ] Handle API errors gracefully
- [ ] Parse API response
- [ ] Store session ID in transaction record
- [ ] Return payment URL
- [ ] Log all steps for debugging

#### Method: verifyPaymentSignature() (45 mins)
```php
private function verifyPaymentSignature($data, $signature) {
    // 1. Get shared secret from config
    // 2. Rebuild signature from data
    // 3. Compare signatures (timing-safe)
    // 4. Return boolean
}
```

**Checklist:**
- [ ] Implement HMAC-SHA256 signature generation
- [ ] Use `hash_equals()` for timing-safe comparison
- [ ] Handle missing signature gracefully
- [ ] Log verification failures

#### Method: processPaymentReturn() (1 hour)
```php
public function processPaymentReturn($transactionId, $params) {
    // 1. Verify signature
    // 2. Get transaction from database
    // 3. Check if already processed
    // 4. Query Windcave API for payment status
    // 5. Update transaction status
    // 6. Update order payment_status
    // 7. Trigger post-payment actions
    // 8. Return result
}
```

**Checklist:**
- [ ] Validate incoming parameters
- [ ] Verify request signature
- [ ] Fetch transaction from database
- [ ] Prevent duplicate processing (idempotency)
- [ ] Query payment status from gateway
- [ ] Update `payment_transactions` table
- [ ] Update `orders.payment_status`:
  - [ ] `completed` → `paid`
  - [ ] `failed` → `failed`
  - [ ] `cancelled` → `pending`
- [ ] If successful, trigger:
  - [ ] Email invoice to customer
  - [ ] Update inventory
  - [ ] Sync to IdealPOS
- [ ] Log all actions
- [ ] Return structured response

#### Method: handleWebhook() (1 hour)
```php
public function handleWebhook($payload, $signature) {
    // 1. Verify webhook signature
    // 2. Parse payload
    // 3. Find transaction
    // 4. Update status
    // 5. Trigger events
    // 6. Return 200 OK
}
```

**Checklist:**
- [ ] Verify webhook signature first (security!)
- [ ] Parse JSON payload
- [ ] Extract transaction ID
- [ ] Fetch transaction from database
- [ ] Check if status update needed
- [ ] Update transaction status
- [ ] Handle different payment events:
  - [ ] `payment.completed`
  - [ ] `payment.failed`
  - [ ] `payment.refunded`
  - [ ] `payment.cancelled`
- [ ] Update order accordingly
- [ ] Send notifications if needed
- [ ] Log webhook data
- [ ] Return HTTP 200 (important!)
- [ ] Handle errors gracefully (still return 200)

#### Method: refundPayment() (45 mins)
```php
public function refundPayment($transactionId, $amount = null, $reason = '') {
    // 1. Get transaction
    // 2. Validate can be refunded
    // 3. Call Windcave refund API
    // 4. Update transaction
    // 5. Update order
    // 6. Notify customer
}
```

**Checklist:**
- [ ] Validate transaction exists and is completed
- [ ] Check if already refunded
- [ ] Call Windcave refund endpoint
- [ ] Handle partial vs full refund
- [ ] Update transaction status to 'refunded'
- [ ] Update order payment_status
- [ ] Send refund confirmation email
- [ ] Log refund action

#### Method: getTransactionDetails() (15 mins)
```php
public function getTransactionDetails($transactionId) {
    // Query database and return transaction info
}
```

- [ ] Fetch from `payment_transactions` table
- [ ] Join with `orders` table
- [ ] Return formatted array

#### Method: checkPaymentStatus() (30 mins)
```php
public function checkPaymentStatus($transactionId) {
    // Query Windcave API for current status
}
```

- [ ] Call Windcave status endpoint
- [ ] Parse response
- [ ] Return status info

---

### 2. Create API Endpoints (2 hours)

#### A) Create Payment Session Endpoint (30 mins)
**File:** `/backend/api/payment/create-session.php`

```php
<?php
require_once '../../services/PaymentService.php';

// 1. Verify user is logged in
// 2. Get order_id from request
// 3. Validate order belongs to user
// 4. Check order not already paid
// 5. Call PaymentService->createPaymentSession()
// 6. Return payment URL
```

**Checklist:**
- [ ] Create file
- [ ] Check user authentication
- [ ] Validate input (order_id)
- [ ] Verify order ownership
- [ ] Check order status (must be pending)
- [ ] Instantiate PaymentService
- [ ] Call `createPaymentSession()`
- [ ] Handle errors
- [ ] Return JSON:
  ```json
  {
    "success": true,
    "payment_url": "https://...",
    "transaction_id": "TXN123",
    "amount": 150.00
  }
  ```
- [ ] Add error handling
- [ ] Log request

#### B) Process Payment Return Endpoint (45 mins)
**File:** `/backend/api/payment/process.php`

```php
<?php
// This is where Windcave redirects user after payment
// 1. Get params from URL (transaction_id, result, etc.)
// 2. Call PaymentService->processPaymentReturn()
// 3. Redirect to success/failure page
```

**Checklist:**
- [ ] Create file
- [ ] Get query parameters
- [ ] Call `processPaymentReturn()`
- [ ] Handle success:
  - [ ] Show success message
  - [ ] Redirect to order confirmation page
  - [ ] Display order details
- [ ] Handle failure:
  - [ ] Show error message
  - [ ] Offer retry option
  - [ ] Redirect to checkout with error
- [ ] Handle cancellation:
  - [ ] Show cancelled message
  - [ ] Redirect to cart
- [ ] Log all returns

#### C) Webhook Endpoint (30 mins)
**File:** `/backend/api/payment/webhook.php`

```php
<?php
// Receives async notifications from Windcave
// 1. Get raw POST body
// 2. Get signature header
// 3. Call PaymentService->handleWebhook()
// 4. Return 200 OK
```

**Checklist:**
- [ ] Create file
- [ ] Get raw POST body: `file_get_contents('php://input')`
- [ ] Get signature from header
- [ ] Call `handleWebhook()`
- [ ] Always return HTTP 200
- [ ] Log all webhooks to file
- [ ] Don't echo/print anything (breaks webhook)

#### D) Payment Status Check Endpoint (15 mins)
**File:** `/backend/api/payment/status.php`

```php
<?php
// Admin/Customer checks payment status
// GET /api/payment/status.php?transaction_id=XXX
```

**Checklist:**
- [ ] Create file
- [ ] Verify authentication
- [ ] Get transaction_id from query
- [ ] Call `checkPaymentStatus()`
- [ ] Return JSON status

---

### 3. Update OrderController (30 mins)

**File:** `/backend/controllers/OrderController.php`

**Changes needed:**
- [ ] After order creation, don't mark as paid immediately
- [ ] Keep order in 'pending' status
- [ ] Return order_id to frontend
- [ ] Remove any auto-payment logic
- [ ] Add method to check if order requires payment
- [ ] Add method to update order after payment

```php
public function createOrder($data) {
    // ... existing code ...
    
    // Don't mark as paid here anymore
    // Instead return order_id for payment processing
    
    return [
        'success' => true,
        'order_id' => $orderId,
        'total' => $total,
        'requires_payment' => $data['payment_method'] === 'card',
        'message' => 'Order created. Please complete payment.'
    ];
}
```

- [ ] Update `createOrder()` method
- [ ] Add `requiresPayment()` method
- [ ] Add `markAsPaid()` method
- [ ] Test order creation flow

---

## 🎨 FRONTEND IMPLEMENTATION

### 4. Update Checkout Page (2 hours)

**File:** `/frontend/checkout.php`

#### Changes (1 hour):
- [ ] After successful order creation, check `requires_payment`
- [ ] If yes, call `/api/payment/create-session.php`
- [ ] Show loading spinner
- [ ] Redirect to payment gateway
- [ ] Don't show "order complete" until payment confirmed

```javascript
async function processCheckout() {
    // ... existing validation ...
    
    // Create order
    const orderResponse = await fetch('/demolitiontraders/backend/api/order/create.php', {
        method: 'POST',
        body: JSON.stringify(orderData)
    });
    
    const orderResult = await orderResponse.json();
    
    if (orderResult.success) {
        if (orderResult.requires_payment) {
            // Initialize payment
            showLoadingMessage('Redirecting to payment gateway...');
            
            const paymentResponse = await fetch('/demolitiontraders/backend/api/payment/create-session.php', {
                method: 'POST',
                body: JSON.stringify({ order_id: orderResult.order_id })
            });
            
            const paymentResult = await paymentResponse.json();
            
            if (paymentResult.success) {
                // Redirect to payment gateway
                window.location.href = paymentResult.payment_url;
            } else {
                showError('Payment initialization failed');
            }
        } else {
            // Non-card payment (bank transfer, cash)
            window.location.href = 'order-confirmation.php?order_id=' + orderResult.order_id;
        }
    }
}
```

**Checklist:**
- [ ] Update form submission handler
- [ ] Add payment initialization call
- [ ] Add loading states
- [ ] Handle payment redirect
- [ ] Handle errors gracefully
- [ ] Test with different payment methods

#### UI Improvements (30 mins):
- [ ] Add payment method icons (Visa, Mastercard, etc.)
- [ ] Add "Secure Payment" badge
- [ ] Show encryption icon
- [ ] Add payment steps indicator
- [ ] Show what happens next

#### Payment Method Radio Buttons (30 mins):
- [ ] Card Payment (triggers gateway redirect)
- [ ] Bank Transfer (shows bank details)
- [ ] Cash on Pickup (shows pickup instructions)

```html
<div class="payment-methods">
    <label class="payment-option">
        <input type="radio" name="payment_method" value="card" checked>
        <div class="option-content">
            <i class="fas fa-credit-card"></i>
            <span>Credit/Debit Card</span>
            <div class="card-logos">
                <img src="/assets/visa.png" alt="Visa">
                <img src="/assets/mastercard.png" alt="Mastercard">
            </div>
        </div>
    </label>
    
    <label class="payment-option">
        <input type="radio" name="payment_method" value="bank">
        <div class="option-content">
            <i class="fas fa-university"></i>
            <span>Bank Transfer</span>
        </div>
    </label>
    
    <label class="payment-option">
        <input type="radio" name="payment_method" value="cash">
        <div class="option-content">
            <i class="fas fa-money-bill"></i>
            <span>Cash on Pickup</span>
        </div>
    </label>
</div>
```

- [ ] Add radio button styling
- [ ] Add selected state styling
- [ ] Show/hide relevant info based on selection

---

### 5. Create Payment Success/Failure Pages (1 hour)

#### A) Payment Success Page (30 mins)
**File:** `/frontend/payment-success.php`

```php
<?php
session_start();
require_once '../backend/services/PaymentService.php';

// Get transaction_id from URL
// Verify payment completed
// Show order details
// Clear cart
```

**Checklist:**
- [ ] Create file
- [ ] Get transaction_id from URL
- [ ] Verify payment is completed
- [ ] Fetch order details
- [ ] Display success message
- [ ] Show order summary
- [ ] Show payment details
- [ ] Download invoice button
- [ ] Track order button
- [ ] Continue shopping button
- [ ] Clear cart items
- [ ] Add Google Analytics event

**UI Elements:**
- [ ] Large success checkmark icon
- [ ] "Payment Successful!" heading
- [ ] Order number prominently displayed
- [ ] Order summary table
- [ ] Payment receipt info
- [ ] Next steps (delivery, tracking)
- [ ] Print receipt button

#### B) Payment Failure Page (30 mins)
**File:** `/frontend/payment-failure.php`

```php
<?php
// Show error message
// Offer to retry
// Show alternative payment methods
```

**Checklist:**
- [ ] Create file
- [ ] Get error details from URL
- [ ] Display user-friendly error message
- [ ] Show what went wrong
- [ ] Retry payment button
- [ ] Try different card button
- [ ] Alternative payment methods
- [ ] Contact support link
- [ ] Return to cart button

**UI Elements:**
- [ ] Error icon (not too scary)
- [ ] Friendly error message
- [ ] "What happened" explanation
- [ ] Retry button (prominent)
- [ ] Alternative options
- [ ] Support contact info

---

### 6. Create Order Confirmation Page (45 mins)

**File:** `/frontend/order-confirmation.php`

For non-card payments (bank transfer, cash)

**Checklist:**
- [ ] Create file
- [ ] Get order_id from URL
- [ ] Verify order belongs to logged-in user
- [ ] Display order details
- [ ] Show payment instructions:
  - [ ] Bank transfer: bank details, reference number
  - [ ] Cash: pickup location, hours
- [ ] Download invoice option
- [ ] Email confirmation sent message
- [ ] What happens next section

---

## 👨‍💼 ADMIN IMPLEMENTATION

### 7. Admin Payment Management (2 hours)

#### A) Payment Transactions List Page (1 hour)
**File:** `/frontend/admin/payments.php`

**Features:**
- [ ] Create file with admin template
- [ ] List all payment transactions
- [ ] Columns:
  - [ ] Transaction ID
  - [ ] Order Number (link)
  - [ ] Customer Name
  - [ ] Amount
  - [ ] Payment Method
  - [ ] Status (badge)
  - [ ] Date
  - [ ] Actions
- [ ] Filters:
  - [ ] By status
  - [ ] By date range
  - [ ] By payment method
  - [ ] Search by transaction ID
- [ ] Pagination (50 per page)
- [ ] Export to CSV button
- [ ] Statistics cards:
  - [ ] Total processed
  - [ ] Total amount
  - [ ] Success rate
  - [ ] Failed payments count

#### B) Payment Detail Modal (30 mins)
- [ ] View transaction details
- [ ] Gateway response
- [ ] Customer IP
- [ ] Timestamps
- [ ] Refund button (if eligible)
- [ ] Retry button (if failed)

#### C) Refund Functionality (30 mins)
- [ ] Refund button in payment detail
- [ ] Confirmation modal
- [ ] Partial/full refund option
- [ ] Reason field
- [ ] Call refund API
- [ ] Show refund success/failure
- [ ] Send email to customer
- [ ] Log refund action

---

### 8. Update Admin Orders Page (30 mins)

**File:** `/frontend/admin/orders.php`

**Changes:**
- [ ] Add payment status column
- [ ] Show transaction ID if exists
- [ ] Link to payment detail
- [ ] Payment status badges:
  - [ ] Pending (yellow)
  - [ ] Paid (green)
  - [ ] Failed (red)
  - [ ] Refunded (gray)
- [ ] Filter by payment status
- [ ] Manual "Mark as Paid" button (for bank transfer)

---

## 🔒 SECURITY IMPLEMENTATION

### 9. Security Measures (1 hour)

#### Signature Verification (30 mins)
- [ ] All webhooks verified
- [ ] All return URLs verified
- [ ] Use HMAC-SHA256
- [ ] Secret key stored securely in config
- [ ] Timing-safe comparison

#### Input Validation (15 mins)
- [ ] Validate all amounts (positive, 2 decimals)
- [ ] Validate order IDs (exist in DB)
- [ ] Validate transaction IDs (format)
- [ ] Sanitize all inputs

#### Logging (15 mins)
- [ ] Log all payment attempts
- [ ] Log all API calls to gateway
- [ ] Log all webhooks received
- [ ] Log all errors
- [ ] Don't log sensitive data (card numbers)
- [ ] Create `/logs/payment.log`

---

## ⚙️ CONFIGURATION

### 10. Update Config Files (30 mins)

#### Add to `/backend/config/config.php`:
```php
// Payment Gateway Configuration
return [
    // ... existing config ...
    
    'payment' => [
        'gateway' => 'windcave', // or 'stripe'
        'mode' => 'sandbox', // 'sandbox' or 'live'
        'windcave' => [
            'api_url' => getenv('WINDCAVE_API_URL') ?: 'https://sec.windcave.com/api/v1',
            'api_key' => getenv('WINDCAVE_API_KEY'),
            'username' => getenv('WINDCAVE_USERNAME'),
            'password' => getenv('WINDCAVE_PASSWORD'),
        ],
        'stripe' => [
            'public_key' => getenv('STRIPE_PUBLIC_KEY'),
            'secret_key' => getenv('STRIPE_SECRET_KEY'),
        ],
        'currency' => 'NZD',
        'return_url' => getenv('BASE_URL') . '/frontend/api/payment/process.php',
        'webhook_url' => getenv('BASE_URL') . '/backend/api/payment/webhook.php',
    ],
];
```

**Checklist:**
- [ ] Add payment config section
- [ ] Add environment variables
- [ ] Add URLs for callbacks
- [ ] Add currency setting
- [ ] Document all config options

#### Update `.env.example`:
```
# Payment Gateway
WINDCAVE_API_URL=https://sec.windcave.com/api/v1
WINDCAVE_API_KEY=your_api_key_here
WINDCAVE_USERNAME=your_username
WINDCAVE_PASSWORD=your_password
PAYMENT_MODE=sandbox
```

- [ ] Add payment variables
- [ ] Update documentation

---

## 🧪 TESTING

### 11. Test Payment Flow (3 hours)

#### Unit Tests (if time permits)
- [ ] Test PaymentService methods
- [ ] Test signature verification
- [ ] Test amount calculations
- [ ] Test status updates

#### Integration Tests (1 hour)
- [ ] Test full payment flow:
  - [ ] Create order
  - [ ] Create payment session
  - [ ] Redirect to gateway (sandbox)
  - [ ] Complete payment (test card)
  - [ ] Process return
  - [ ] Verify order updated
  - [ ] Check email sent

#### Test Cases (2 hours):

**Successful Payment:**
- [ ] Create test order
- [ ] Select card payment
- [ ] Use Windcave test card: `4111111111111111`
- [ ] Complete payment
- [ ] Verify redirected to success page
- [ ] Check order status = paid
- [ ] Check transaction record created
- [ ] Check email received
- [ ] Check inventory updated

**Failed Payment:**
- [ ] Use test card that fails
- [ ] Verify redirected to failure page
- [ ] Check order still pending
- [ ] Check transaction status = failed
- [ ] Check can retry

**Cancelled Payment:**
- [ ] Click cancel on payment page
- [ ] Verify redirected to cancellation page
- [ ] Check order still pending
- [ ] Check can retry

**Webhook Test:**
- [ ] Trigger webhook manually (Postman)
- [ ] Verify signature check
- [ ] Verify status updated
- [ ] Verify email sent
- [ ] Check logs

**Duplicate Processing:**
- [ ] Process same transaction twice
- [ ] Verify idempotency (no duplicate updates)
- [ ] Check logs

**Refund Test:**
- [ ] Create paid order
- [ ] Initiate refund from admin
- [ ] Verify API call successful
- [ ] Check transaction status = refunded
- [ ] Check order updated
- [ ] Check email sent

**Bank Transfer:**
- [ ] Select bank transfer
- [ ] Verify no gateway redirect
- [ ] Check order pending
- [ ] Admin marks as paid manually
- [ ] Verify order updated

**Error Handling:**
- [ ] Test with invalid order_id
- [ ] Test with invalid transaction_id
- [ ] Test with invalid signature
- [ ] Test with expired session
- [ ] Test with insufficient funds
- [ ] Verify friendly error messages

---

## 📚 DOCUMENTATION

### 12. Write Documentation (1 hour)

#### Developer Docs (30 mins):
- [ ] Create `/docs/PAYMENT-INTEGRATION.md`
- [ ] Document payment flow diagram
- [ ] Document API endpoints
- [ ] Document PaymentService methods
- [ ] Document configuration
- [ ] Document testing procedures
- [ ] Document troubleshooting

#### Admin Guide (15 mins):
- [ ] How to view payments
- [ ] How to process refunds
- [ ] How to handle failed payments
- [ ] How to manually mark as paid

#### User Guide (15 mins):
- [ ] How to pay with card
- [ ] What to do if payment fails
- [ ] How to get receipt
- [ ] Refund policy

---

## ✅ FINAL CHECKLIST

### Pre-Deployment:
- [ ] All code committed to git
- [ ] All tests passing
- [ ] Documentation complete
- [ ] Config files ready
- [ ] Environment variables documented
- [ ] Database migrations ready
- [ ] Sandbox testing successful

### Production Deployment:
- [ ] Switch from sandbox to live mode
- [ ] Update API credentials to production
- [ ] Update webhook URL to production
- [ ] Test with real card (small amount)
- [ ] Monitor payment logs
- [ ] Have rollback plan ready

### Monitoring:
- [ ] Set up payment success/failure alerts
- [ ] Monitor payment logs daily
- [ ] Check webhook delivery
- [ ] Monitor refund requests
- [ ] Track payment completion rate

---

## 🚨 TROUBLESHOOTING

### Common Issues:

**Payment redirect not working:**
- [ ] Check return URL is correct
- [ ] Check SSL certificate
- [ ] Check API credentials
- [ ] Check signature generation

**Webhook not received:**
- [ ] Check webhook URL is publicly accessible
- [ ] Check no firewall blocking
- [ ] Check signature verification
- [ ] Check server logs

**Payment stuck in pending:**
- [ ] Check webhook processed
- [ ] Manually query payment status
- [ ] Check gateway dashboard
- [ ] Contact gateway support

**Refund failing:**
- [ ] Check transaction is completed
- [ ] Check not already refunded
- [ ] Check API credentials
- [ ] Check refund amount valid

---

## 📊 SUCCESS METRICS

After implementation, track:
- [ ] Payment success rate (target: >95%)
- [ ] Average payment time (target: <2 minutes)
- [ ] Failed payment rate (target: <5%)
- [ ] Refund request rate
- [ ] Customer support tickets related to payments

---

## 🎯 COMPLETION CRITERIA

✅ **Day 1-2 is COMPLETE when:**
- [ ] All checkboxes above are checked
- [ ] Full payment flow working end-to-end
- [ ] Card payments process successfully
- [ ] Webhooks received and processed
- [ ] Orders updated correctly
- [ ] Emails sent on payment completion
- [ ] Admin can view and manage payments
- [ ] Refunds working
- [ ] All test cases passing
- [ ] Documentation written
- [ ] Code reviewed and tested

---

**Total Estimated Time:** 16 hours  
**Actual Time Spent:** _____ hours  
**Completion Date:** ___________  
**Notes/Issues:** 

---

**Next:** Day 3 - CSRF Protection Implementation
