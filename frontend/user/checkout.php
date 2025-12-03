<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    
    <!-- Load API Helper -->
    <script src="assets/js/api-helper.js?v=1"></script>
    <script>const BASE_PATH = '<?php echo BASE_PATH; ?>';</script>
    
    <link rel="stylesheet" href="assets/css/new-style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin: 40px 0;
        }
        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-section {
            margin-bottom: 30px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }
        .form-section h2 {
            margin-bottom: 0;
            padding: 15px 20px;
            border-bottom: 2px solid #2f3192;
            background: #f9f9f9;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
            font-size: 16px;
        }
        .form-section h2:hover {
            background: #f0f0f0;
        }
        .form-section h2 i.toggle-icon {
            transition: transform 0.3s;
            font-size: 14px;
            color: #2f3192;
        }
        .form-section h2.collapsed i.toggle-icon {
            transform: rotate(-90deg);
        }
        .form-section-content {
            padding: 20px;
            max-height: 2000px;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        .form-section-content.collapsed {
            max-height: 0;
            padding: 0 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13px;
        }
        .form-group label .required {
            color: #ff0000;
            margin-left: 2px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2f3192;
        }
        .order-summary {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .order-summary h2 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            color: #2f3192;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid #2f3192;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }
        .payment-method {
            border: 2px solid #ddd;
            padding: 15px 10px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .payment-method:hover {
            border-color: #2f3192;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .payment-method.selected {
            border-color: #2f3192;
            background: #e8e8f5;
        }
        .payment-method input {
            display: none;
        }
        .payment-method i {
            font-size: 24px;
            margin-bottom: 8px;
            color: #666;
        }
        .payment-method .method-name {
            font-size: 12px;
            font-weight: 600;
            color: #333;
        }
        .payment-method .method-desc {
            font-size: 10px;
            color: #999;
            margin-top: 4px;
        }
        .payment-details {
            margin-top: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #2f3192;
            display: none;
        }
        .payment-details.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .payment-details h4 {
            margin-top: 0;
            color: #2f3192;
            font-size: 16px;
        }
        .bank-details {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .bank-details p {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
        }
        .bank-details strong {
            color: #333;
        }
        .qr-code {
            text-align: center;
            margin: 15px 0;
        }
        .qr-code img {
            width: 200px;
            height: 200px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }
        .payment-logo {
            max-width: 50px;
            margin-bottom: 5px;
        }
        .delivery-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .delivery-option {
            border: 2px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .delivery-option:hover {
            border-color: #2f3192;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .delivery-option.selected {
            border-color: #2f3192;
            background: #e8e8f5;
        }
        .delivery-option input {
            display: none;
        }
        .delivery-option i {
            font-size: 32px;
            margin-bottom: 10px;
            color: #2f3192;
        }
        .delivery-option .option-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .delivery-option .option-desc {
            font-size: 11px;
            color: #666;
        }
        .guest-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.25);
            position: relative;
            overflow: hidden;
        }
        .guest-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        .guest-banner::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }
        .guest-banner-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }
        .guest-banner-icon i {
            font-size: 28px;
            color: white;
        }
        .guest-banner-content {
            flex: 1;
            position: relative;
            z-index: 1;
        }
        .guest-banner-content h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .guest-banner-content p {
            margin: 0;
            opacity: 0.95;
            font-size: 14px;
            line-height: 1.5;
        }
        .guest-banner .btn-login {
            background: white;
            color: #667eea;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            white-space: nowrap;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .guest-banner .btn-login i {
            font-size: 16px;
        }
        .guest-banner .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            background: #f8f9ff;
        }
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2f3192;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-content h3 {
            margin: 0 0 10px 0;
            color: #2f3192;
        }
        .loading-content p {
            margin: 0;
            color: #666;
        }
        
        /* Success Modal */
        .success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .success-modal.active {
            display: flex;
        }
        .success-modal-content {
            background: white;
            padding: 0;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: modalSlideIn 0.4s ease;
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success-modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .success-modal-header i {
            font-size: 64px;
            margin-bottom: 15px;
            animation: successPulse 1s ease;
        }
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .success-modal-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .success-modal-body {
            padding: 30px;
        }
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .order-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .order-info-item:last-child {
            margin-bottom: 0;
            padding-top: 12px;
            border-top: 2px solid #dee2e6;
            font-weight: 700;
            font-size: 18px;
            color: #2f3192;
        }
        .account-offer {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .account-offer h3 {
            margin: 0 0 12px 0;
            font-size: 20px;
        }
        .account-offer ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        .account-offer li {
            margin-bottom: 8px;
        }
        .modal-actions {
            display: flex;
            gap: 12px;
        }
        .modal-actions button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-create-account {
            background: #2f3192;
            color: white;
        }
        .btn-create-account:hover {
            background: #1f2170;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(47, 49, 146, 0.3);
        }
        .btn-skip {
            background: #e9ecef;
            color: #495057;
        }
        .btn-skip:hover {
            background: #dee2e6;
        }
        
        /* Email exists alert */
        .email-exists-badge {
            display: none;
            margin-top: 8px;
            padding: 12px 15px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 5px;
            font-size: 13px;
            color: #856404;
            animation: slideDown 0.3s ease;
        }
        .email-exists-badge.show {
            display: block;
        }
        .email-exists-badge i {
            margin-right: 8px;
        }
        .email-exists-badge a {
            color: #856404;
            font-weight: 600;
            text-decoration: underline;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Checkout</h1>
            <nav class="breadcrumb">
                <a href="<?php echo userUrl('index.php'); ?>">Home</a> / <a href="<?php echo userUrl('cart.php'); ?>">Cart</a> / <span>Checkout</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="checkout-container">
            <div class="checkout-form">
                <!-- Guest Checkout Banner (shown only if not logged in) -->
                <div class="guest-banner" id="guest-banner" style="display: none;">
                    <div class="guest-banner-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="guest-banner-content">
                        <h3>Continue as Guest</h3>
                        <p>You can checkout without an account. We'll ask if you'd like to create one after your order.</p>
                    </div>
                    <a href="<?php echo userUrl('login.php?redirect=checkout.php'); ?>" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </div>

                <form id="checkout-form">
                    <!-- Contact Information -->
                    <div class="form-section">
                        <h2 onclick="toggleSection(this)">
                            <span><i class="fas fa-user"></i> Contact Information</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h2>
                        <div class="form-section-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name <span class="required">*</span></label>
                                    <input type="text" name="billing_first_name" required>
                                </div>
                                <div class="form-group">
                                    <label>Last Name <span class="required">*</span></label>
                                    <input type="text" name="billing_last_name" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email <span class="required">*</span></label>
                                    <input type="email" name="billing_email" id="billing_email" required>
                                    <div class="email-exists-badge" id="email-exists-badge">
                                        <i class="fas fa-info-circle"></i>
                                        This email already has an account. 
                                        <a href="<?php echo userUrl('login.php?redirect=checkout.php'); ?>">Login here</a> to link your order.
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Phone <span class="required">*</span></label>
                                    <input type="tel" name="billing_phone" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing Address -->
                    <div class="form-section">
                        <h2 onclick="toggleSection(this)">
                            <span><i class="fas fa-map-marker-alt"></i> Billing Address</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h2>
                        <div class="form-section-content">
                        <!-- Saved Addresses Dropdown (only for logged-in users) -->
                        <div class="form-group" id="saved-addresses-container" style="display: none;">
                            <label>Saved Addresses</label>
                            <select id="saved-addresses-select" onchange="fillAddressFromSaved(this.value)" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                                <option value="">-- Select a saved address --</option>
                                <option value="new">+ Enter new address</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Street Address <span class="required">*</span></label>
                            <input type="text" name="billing_address" placeholder="House number and street name" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>City <span class="required">*</span></label>
                                <input type="text" name="billing_city" required>
                            </div>
                            <div class="form-group">
                                <label>Postcode <span class="required">*</span></label>
                                <input type="text" name="billing_postcode" required>
                            </div>
                        </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Method -->
                    <div class="form-section">
                        <h2 onclick="toggleSection(this)">
                            <span><i class="fas fa-shipping-fast"></i> Delivery Method</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h2>
                        <div class="form-section-content">
                            <div class="delivery-options">
                                <label class="delivery-option selected" id="option-pickup">
                                    <input type="radio" name="delivery_method" value="pickup" checked>
                                    <i class="fas fa-store"></i>
                                    <div class="option-title">Pickup</div>
                                    <div class="option-desc">Collect from warehouse</div>
                                    <div class="option-desc" style="margin-top: 5px; color: #2f3192; font-weight: 600;">FREE</div>
                                </label>
                                
                                <label class="delivery-option" id="option-delivery">
                                    <input type="radio" name="delivery_method" value="delivery">
                                    <i class="fas fa-truck"></i>
                                    <div class="option-title">Delivery</div>
                                    <div class="option-desc">Deliver to your address</div>
                                    <div class="option-desc" style="margin-top: 5px; color: #2f3192; font-weight: 600;">To be quoted</div>
                                </label>
                            </div>
                            
                            <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 5px;" id="pickup-info">
                                <p style="margin: 0; font-weight: 600;"><i class="fas fa-map-marker-alt"></i> Pickup Location:</p>
                                <p style="margin: 5px 0 0 24px; color: #666; font-size: 13px;">
                                    <strong>Demolition Traders</strong><br>
                                    249 Kahikatea Drive, Greenlea Lane, Frankton, Hamilton<br><br>
                                                                    </p>
                            </div>
                            
                            <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 5px; display: none;" id="delivery-info">
                                <p style="margin: 0; font-weight: 600;"><i class="fas fa-info-circle"></i> Delivery Information:</p>
                                <p style="margin: 5px 0 0 24px; color: #666; font-size: 13px;">
                                    Delivery fee calculated based on location and order size.<br>
                                    Estimated delivery: 2-5 business days.
                                </p>
                                <div id="delivery-method-details" style="margin-top: 12px; padding: 12px; background: #f8f9fa; border-left: 3px solid #2f3192; border-radius: 5px; display: none;">
                                    <p style="margin: 0; font-weight: 600; color: #2f3192; font-size: 13px;">
                                        <i class="fas fa-check-circle"></i> <span id="selected-method-name"></span>
                                    </p>
                                    <p style="margin: 8px 0 0 0; color: #555; font-size: 12px; line-height: 1.6;" id="selected-method-description"></p>
                                    <p style="margin: 8px 0 0 0; color: #666; font-size: 12px; font-style: italic;">
                                        Our staff will contact you with a shipping quote.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Address -->
                    <div class="form-section" id="shipping-section" style="display: none;">
                        <h2 onclick="toggleSection(this)">
                            <span><i class="fas fa-truck"></i> Shipping Address</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h2>
                        <div class="form-section-content">
                            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                            <input type="checkbox" id="same-as-billing" checked onchange="toggleShipping()">
                            Same as billing address
                        </label>
                        <div id="shipping-fields">
                            <div class="form-group">
                                <label>Street Address <span class="required">*</span></label>
                                <input type="text" name="shipping_address" id="shipping_address">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>City <span class="required">*</span></label>
                                    <input type="text" name="shipping_city" id="shipping_city">
                                </div>
                                <div class="form-group">
                                    <label>Postcode <span class="required">*</span></label>
                                    <input type="text" name="shipping_postcode" id="shipping_postcode">
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="form-section">
                        <h2 onclick="toggleSection(this)">
                            <span><i class="fas fa-credit-card"></i> Payment Method</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h2>
                        <div class="form-section-content">
                            <div class="payment-methods">
                                <!-- Credit/Debit Card -->
                                <label class="payment-method selected" data-method="card">
                                    <input type="radio" name="payment_method" value="card" checked>
                                    <i class="fas fa-credit-card"></i>
                                    <div class="method-name">Card Payment</div>
                                    <div class="method-desc">Visa, Mastercard</div>
                                </label>
                                
                                <!-- Bank Transfer -->
                                <label class="payment-method" data-method="banktransfer">
                                    <input type="radio" name="payment_method" value="banktransfer">
                                    <i class="fas fa-money-check-alt"></i>
                                    <div class="method-name">Bank Transfer</div>
                                    <div class="method-desc">Manual transfer</div>
                                </label>
                                
                                <!-- Cash on Pickup -->
                                <label class="payment-method" data-method="cash">
                                    <input type="radio" name="payment_method" value="cash">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div class="method-name">Cash</div>
                                    <div class="method-desc">Pay on pickup</div>
                                </label>
                            </div>
                            
                            <!-- Payment Details Sections -->
                            <div id="payment-details-container">
                                <!-- Card Details -->
                                <div class="payment-details active" id="details-card">
                                    <h4><i class="fas fa-credit-card"></i> Card Payment</h4>
                                    <p><i class="fas fa-info-circle"></i> You will be redirected to secure payment gateway (Windcave)</p>
                                    <p style="font-size: 12px; color: #666; margin-top: 10px;">
                                        <i class="fas fa-lock"></i> Secure payment powered by Windcave • PCI DSS Compliant
                                    </p>
                                </div>
                                
                                <!-- Bank Transfer Details -->
                                <div class="payment-details" id="details-banktransfer">
                                    <h4><i class="fas fa-money-check-alt"></i> Bank Transfer Details</h4>
                                    <div class="bank-details">
                                        <p><strong>Bank Name:</strong> <span>BNZ Bank of New Zealand</span></p>
                                        <p><strong>Account Name:</strong> <span>Demolition Traders Ltd</span></p>
                                        <p><strong>Account Number:</strong> <span>01-0123-0456789-00</span></p>
                                        <p><strong>Reference:</strong> <span id="order-reference">DT-{ORDER_ID}</span></p>
                                        <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                            <strong>Amount to Pay:</strong> 
                                            <span style="color: #2f3192; font-size: 18px; font-weight: 700;">$<span id="transfer-amount">0.00</span></span>
                                        </p>
                                    </div>
                                    <div class="qr-code">
                                        <p><strong>Or scan QR code with your banking app:</strong></p>
                                        <div style="background: white; display: inline-block; padding: 20px; border-radius: 10px; margin-top: 10px;">
                                            <div style="width: 200px; height: 200px; background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%), linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%); background-size: 20px 20px; background-position: 0 0, 10px 10px; display: flex; align-items: center; justify-content: center; border: 2px solid #ddd;">
                                                <i class="fas fa-qrcode" style="font-size: 100px; color: #333;"></i>
                                            </div>
                                        </div>
                                        <p style="font-size: 11px; color: #999; margin-top: 10px;">VietQR compatible</p>
                                    </div>
                                    <p style="font-size: 12px; color: #ff6b00; margin-top: 15px;">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Your order will be processed once payment is received (usually 1-2 business days)
                                    </p>
                                </div>
                                
                                <!-- Cash on Pickup Details -->
                                <div class="payment-details" id="details-cash">
                                    <h4><i class="fas fa-money-bill-wave"></i> Cash on Pickup</h4>
                                    <p><i class="fas fa-check-circle" style="color: green;"></i> Pay cash when you collect your order</p>
                                    <div style="background: white; padding: 15px; border-radius: 5px; margin-top: 15px;">
                                        <p><strong>📍 Pickup Location:</strong></p>
                                        <p style="margin-left: 20px; color: #666;">
                                            Demolition Traders<br>
                                            249 Kahikatea Drive, Greenlea Lane<br>
                                            Frankton<br>
                                            Hamilton, New Zealand
                                        </p>
                                        <p style="margin-top: 10px;"><strong>🕒 Opening Hours:</strong></p>
                                        <div id="opening-hours-checkout" style="margin-left: 20px; color: #666; font-size: 14px;">
                                            <div style="text-align: center; padding: 5px;">
                                                <div class="spinner" style="width: 15px; height: 15px; border-width: 2px;"></div>
                                            </div>
                                        </div>
                                        <p style="margin-top: 10px;"><strong>📞 Contact:</strong></p>
                                        <p style="margin-left: 20px; color: #666;">
                                            Freephone: 0800 DEMOLITION<br>
                                            Phone: 07 847 4989
                                        </p>
                                    </div>
                                    <p style="font-size: 12px; color: #666; margin-top: 15px;">
                                        <i class="fas fa-info-circle"></i> Please bring your order confirmation email and valid ID
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Notes -->
                    <div class="form-section">
                        <h2 onclick="toggleSection(this)">
                            <span><i class="fas fa-sticky-note"></i> Order Notes (Optional)</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </h2>
                        <div class="form-section-content">
                            <div class="form-group">
                            <label>Notes about your order, e.g. special delivery instructions</label>
                            <textarea name="notes" rows="4"></textarea>
                        </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 18px;">
                        <i class="fas fa-lock"></i> Place Order
                    </button>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div id="order-items"></div>
                <div class="summary-item">
                    <span>Subtotal:</span>
                    <span id="summary-subtotal">$0.00</span>
                </div>
                <div class="summary-item">
                    <span>Shipping:</span>
                    <span>To Be Quoted</span>
                </div>
                <div class="summary-item" style="font-size: 11px; color: #666; border: none; padding-top: 5px;">
                    <span><i class="fas fa-info-circle"></i> All prices include GST</span>
                    <span></span>
                </div>
                <div class="summary-total">
                    <span>Total:</span>
                    <span id="summary-total">$0.00</span>
                </div>
            </div>
        </div>
    </section>
    
    <?php include '../components/footer.php'; ?>
<?php include '../components/toast-notification.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Load cart summary
        async function loadCartSummary() {
            try {
                const response = await fetch(getApiUrl('/api/cart/get.php'));
                
                if (!response.ok) {
                    throw new Error('Failed to load cart');
                }
                
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid response from server');
                }
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load cart');
                }
                
                if (!data.items || data.items.length === 0) {
                    alert('Your cart is empty!');
                    window.location.href = BASE_PATH + 'shop.php';
                    return;
                }
                
                // Display items
                document.getElementById('order-items').innerHTML = data.items.map(item => {
                    const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                    return `
                    <div class="summary-item">
                        <span>${item.name} × ${item.quantity}</span>
                        <span>$${itemTotal.toFixed(2)}</span>
                    </div>
                `}).join('');
                
                // Display totals
                document.getElementById('summary-subtotal').textContent = '$' + data.summary.subtotal;
                document.getElementById('summary-total').textContent = '$' + data.summary.total;
                
            } catch (error) {
                console.error('Error loading cart:', error);
                alert('Error loading cart. Please try again.');
                window.location.href = BASE_PATH + 'shop.php';
            }
        }
        
        // Delivery method selection
        document.querySelectorAll('.delivery-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.delivery-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input').checked = true;
                
                const method = this.querySelector('input').value;
                const shippingSection = document.getElementById('shipping-section');
                const pickupInfo = document.getElementById('pickup-info');
                const deliveryInfo = document.getElementById('delivery-info');
                const cashOption = document.querySelector('.payment-method[data-method="cash"]');
                
                if (method === 'delivery') {
                    shippingSection.style.display = 'block';
                    pickupInfo.style.display = 'none';
                    deliveryInfo.style.display = 'block';
                    
                    // Hide Cash payment option
                    if (cashOption) {
                        cashOption.style.display = 'none';
                        // If cash was selected, switch to card
                        if (cashOption.classList.contains('selected')) {
                            cashOption.classList.remove('selected');
                            const cardOption = document.querySelector('.payment-method[data-method="card"]');
                            cardOption.classList.add('selected');
                            cardOption.querySelector('input').checked = true;
                            // Show card details
                            document.querySelectorAll('.payment-details').forEach(detail => detail.classList.remove('active'));
                            document.getElementById('details-card').classList.add('active');
                        }
                    }
                    
                    // Make shipping fields required
                    document.querySelectorAll('#shipping-section input').forEach(input => {
                        if (input.type !== 'checkbox') {
                            input.required = true;
                        }
                    });
                } else {
                    shippingSection.style.display = 'none';
                    pickupInfo.style.display = 'block';
                    deliveryInfo.style.display = 'none';
                    
                    // Show Cash payment option
                    if (cashOption) {
                        cashOption.style.display = 'block';
                    }
                    
                    // Remove required from shipping fields
                    document.querySelectorAll('#shipping-section input').forEach(input => {
                        input.required = false;
                    });
                }
            });
        });
        
        // Set initial delivery method on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set Pickup as default
            const pickupOption = document.querySelector('input[name="delivery_method"][value="pickup"]');
            if (pickupOption && pickupOption.checked) {
                document.getElementById('shipping-section').style.display = 'none';
                document.getElementById('pickup-info').style.display = 'block';
                document.getElementById('delivery-info').style.display = 'none';
                document.getElementById('option-pickup').classList.add('selected');
                document.getElementById('option-delivery').classList.remove('selected');
                
                // Make sure Cash option is visible for pickup
                const cashOption = document.querySelector('.payment-method[data-method="cash"]');
                if (cashOption) {
                    cashOption.style.display = 'block';
                }
            }
        });
        
        // Toggle shipping fields (same as billing)
        function toggleShipping() {
            const checked = document.getElementById('same-as-billing').checked;
            const shippingFields = document.querySelectorAll('#shipping-fields input');
            
            if (checked) {
                // Copy billing address to shipping
                document.getElementById('shipping_address').value = document.querySelector('input[name="billing_address"]').value;
                document.getElementById('shipping_city').value = document.querySelector('input[name="billing_city"]').value;
                document.getElementById('shipping_postcode').value = document.querySelector('input[name="billing_postcode"]').value;
                
                // Disable fields
                shippingFields.forEach(field => field.disabled = true);
            } else {
                // Enable fields
                shippingFields.forEach(field => field.disabled = false);
            }
        }
        
        // Toggle section collapse/expand
        function toggleSection(header) {
            header.classList.toggle('collapsed');
            const content = header.nextElementSibling;
            content.classList.toggle('collapsed');
        }
        
        // Payment method selection and show details
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Update selected state
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input').checked = true;
                
                // Show corresponding payment details
                const methodType = this.dataset.method;
                document.querySelectorAll('.payment-details').forEach(detail => {
                    detail.classList.remove('active');
                });
                const detailSection = document.getElementById('details-' + methodType);
                if (detailSection) {
                    detailSection.classList.add('active');
                }
                
                // Update bank transfer amount if bank transfer selected
                if (methodType === 'banktransfer') {
                    const totalAmount = document.getElementById('summary-total').textContent.replace('$', '');
                    document.getElementById('transfer-amount').textContent = totalAmount;
                }
            });
        });
        
        // Handle form submission
        document.getElementById('checkout-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Show loading overlay
            showLoading('Processing your order...');
            
            const formData = new FormData(this);
            const sameAsBilling = document.getElementById('same-as-billing').checked;
            
            // Prepare billing address
            const billingAddress = {
                first_name: formData.get('billing_first_name'),
                last_name: formData.get('billing_last_name'),
                email: formData.get('billing_email'),
                phone: formData.get('billing_phone'),
                address: formData.get('billing_address'),
                city: formData.get('billing_city'),
                postcode: formData.get('billing_postcode')
            };
            
            // Prepare shipping address
            const shippingAddress = sameAsBilling ? billingAddress : {
                first_name: formData.get('shipping_first_name'),
                last_name: formData.get('shipping_last_name'),
                address: formData.get('shipping_address'),
                city: formData.get('shipping_city'),
                postcode: formData.get('shipping_postcode')
            };
            
            const orderData = {
                email: billingAddress.email,
                billing_address: billingAddress,
                shipping_address: shippingAddress,
                payment_method: formData.get('payment_method'),
                notes: formData.get('notes'),
                shipping_amount: 0,
                discount_amount: 0
            };
            
            // Check if logged in user wants to save new address
            if (window.currentUser && window.currentUser.id) {
                const isNewAddress = await checkIfNewAddress(billingAddress);
                console.log('Is new address:', isNewAddress);
                if (isNewAddress) {
                    const shouldSave = confirm('Would you like to save this address to your account for future use?');
                    console.log('User chose to save:', shouldSave);
                    if (shouldSave) {
                        orderData.save_address = true;
                    }
                }
            }
            
            try {
                console.log('Sending order data:', orderData);
                
                const response = await fetch(getApiUrl('/api/index.php?request=orders'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(orderData)
                });
                
                console.log('Response status:', response.status);
                
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse response:', e);
                    hideLoading();
                    alert('Server returned invalid response. Check console for details.');
                    return;
                }
                
                if (response.ok) {
                    hideLoading();
                    
                    // Check if user is logged in
                    const isLoggedIn = window.currentUser && window.currentUser.id;
                    
                    if (!isLoggedIn && !window.emailExists) {
                        // Guest user who hasn't registered - offer account creation
                        window.pendingOrderId = result.id;
                        showSuccessModal(result, billingAddress, true);
                    } else {
                        // Logged in user or email exists - just show success without account offer
                        showSuccessModal(result, null, false);
                    }
                } else {
                    console.error('Order error:', result);
                    hideLoading();
                    alert('Error: ' + (result.error || 'Failed to place order'));
                }
            } catch (error) {
                console.error('Checkout error:', error);
                hideLoading();
                alert('An error occurred while processing your order. Please try again.');
            }
        });
        
        // Loading overlay functions
        function showLoading(message = 'Processing...') {
            let overlay = document.getElementById('loading-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'loading-overlay';
                overlay.className = 'loading-overlay';
                overlay.innerHTML = `
                    <div class="loading-content">
                        <div class="loading-spinner"></div>
                        <h3 id="loading-message">${message}</h3>
                        <p>Please wait...</p>
                    </div>
                `;
                document.body.appendChild(overlay);
            }
            document.getElementById('loading-message').textContent = message;
            overlay.classList.add('active');
        }
        
        function hideLoading() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
        
        // Show success modal
        function showSuccessModal(orderResult, billingAddress, showAccountOffer) {
            const modal = document.createElement('div');
            modal.className = 'success-modal active';
            modal.id = 'success-modal';
            
            modal.innerHTML = `
                <div class="success-modal-content">
                    <div class="success-modal-header">
                        <i class="fas fa-check-circle"></i>
                        <h2>Order Placed Successfully!</h2>
                    </div>
                    <div class="success-modal-body">
                        <div class="order-info">
                            <div class="order-info-item">
                                <span>Order Number:</span>
                                <strong>${orderResult.order_number}</strong>
                            </div>
                            <div class="order-info-item">
                                <span>Total Amount:</span>
                                <strong>$${parseFloat(orderResult.total_amount).toFixed(2)}</strong>
                            </div>
                        </div>
                        
                        ${showAccountOffer ? `
                        <div class="account-offer">
                            <h3>🎉 Create Your Account Now!</h3>
                            <p>Save time on your next order with these benefits:</p>
                            <ul>
                                <li>✓ Track orders easily</li>
                                <li>✓ Save addresses for faster checkout</li>
                                <li>✓ View complete order history</li>
                                <li>✓ Manage your wishlist</li>
                                <li>✓ Get exclusive member deals</li>
                            </ul>
                        </div>
                        
                        <div class="modal-actions">
                            <button class="btn-create-account" onclick="createAccountFromModal()">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                            <button class="btn-skip" onclick="closeSuccessModal()">
                                Skip for Now
                            </button>
                        </div>
                        ` : `
                        <div class="modal-actions" style="display: flex; gap: 10px;">
                            <button class="btn-primary" onclick="window.location.href = BASE_PATH + 'order-detail.php?id=' + ${orderResult.id}" style="flex: 1; padding: 15px; font-size: 16px;">
                                <i class="fas fa-receipt"></i> View Order
                            </button>
                            <button class="btn-secondary" onclick="closeSuccessModal()" style="flex: 1; padding: 15px; font-size: 16px;">
                                Continue Shopping
                            </button>
                        </div>
                        `}
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Store billing address for account creation (only if offering account)
            if (showAccountOffer && billingAddress) {
                window.pendingAccountData = billingAddress;
            }
        }
        
        function closeSuccessModal() {
            const modal = document.getElementById('success-modal');
            if (modal) modal.remove();
            window.location.href = BASE_PATH + 'shop.php';
        }
        
        async function createAccountFromModal() {
            const billingAddress = window.pendingAccountData;
            if (!billingAddress) {
                window.location.href = BASE_PATH + 'shop.php';
                return;
            }
            
            const modal = document.getElementById('success-modal');
            if (modal) modal.remove();
            
            await offerAccountCreation(billingAddress);
        }
        
        // Offer account creation for guest users
        async function offerAccountCreation(billingAddress) {
            // Create registration modal
            const modal = document.createElement('div');
            modal.id = 'register-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10001;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 16px; padding: 40px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #2f3192, #4a4eb8); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <i class="fas fa-user-plus" style="color: white; font-size: 36px;"></i>
                        </div>
                        <h2 style="color: #2f3192; margin: 0 0 10px 0; font-size: 28px;">Create Your Account</h2>
                        <p style="color: #666; margin: 0;">Track your order and enjoy member benefits!</p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                        <div style="margin-bottom: 10px;">
                            <strong style="color: #2f3192;">Email:</strong>
                            <div style="color: #333;">${billingAddress.email}</div>
                        </div>
                        <div>
                            <strong style="color: #2f3192;">Name:</strong>
                            <div style="color: #333;">${billingAddress.first_name} ${billingAddress.last_name}</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600;">Create Password</label>
                        <input type="password" id="register-password" placeholder="Minimum 8 characters" 
                            style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box;" />
                        <div style="color: #666; font-size: 12px; margin-top: 5px;">Must be at least 8 characters</div>
                    </div>
                    
                    <div style="display: flex; gap: 12px;">
                        <button onclick="submitRegistration()" style="flex: 1; padding: 14px; background: linear-gradient(135deg, #2f3192, #4a4eb8); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-check"></i> Create Account
                        </button>
                        <button onclick="dismissRegistration()" style="flex: 1; padding: 14px; background: #6c757d; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-times"></i> Dismiss
                        </button>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
                        Already have an account? <a href="<?php echo userUrl('login.php'); ?>" style="color: #2f3192; text-decoration: none; font-weight: 600;">Login here</a>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Focus password field
            setTimeout(() => {
                document.getElementById('register-password').focus();
            }, 100);
            
            // Store billing data for submission
            window.pendingRegistration = billingAddress;
        }
        
        function dismissRegistration() {
            const modal = document.getElementById('register-modal');
            if (modal) modal.remove();
            window.location.href = BASE_PATH + 'shop.php';
        }
        
        async function submitRegistration() {
            const password = document.getElementById('register-password').value;
            const billingAddress = window.pendingRegistration;
            
            if (!password) {
                alert('Please enter a password');
                return;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return;
            }
            
            showLoading('Creating your account...');
            
            try {
                const response = await fetch(getApiUrl('/api/user/register.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        first_name: billingAddress.first_name,
                        last_name: billingAddress.last_name,
                        email: billingAddress.email,
                        phone: billingAddress.phone,
                        password: password,
                        order_id: window.pendingOrderId || null
                    })
                });
                
                const result = await response.json();
                hideLoading();
                
                if (result.success) {
                    const modal = document.getElementById('register-modal');
                    if (modal) modal.remove();
                    
                    alert(
                        `✓ Account created successfully!\n\n` +
                        `You are now logged in.\n` +
                        `You can view your order in your profile.`
                    );
                    window.location.href = BASE_PATH + 'profile.php';
                } else {
                    alert('Account creation failed: ' + result.message);
                }
            } catch (error) {
                console.error('Account creation error:', error);
                hideLoading();
                alert('Failed to create account. Please try again later.');
            }
        }
        
        // Check email when user finishes typing
        let emailCheckTimeout;
        document.getElementById('billing_email').addEventListener('input', function() {
            clearTimeout(emailCheckTimeout);
            const email = this.value.trim();
            const badge = document.getElementById('email-exists-badge');
            
            if (!email || !email.includes('@')) {
                badge.classList.remove('show');
                window.emailExists = false;
                return;
            }
            
            // Skip check if user is logged in and using their own email
            if (window.currentUser && window.currentUser.email === email) {
                badge.classList.remove('show');
                window.emailExists = false;
                return;
            }
            
            emailCheckTimeout = setTimeout(async () => {
                try {
                    console.log('Checking email:', email);
                    const response = await fetch(getApiUrl('/api/user/check-email.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email })
                    });
                    
                    const result = await response.json();
                    console.log('Email check response:', result);
                    
                    if (result.exists) {
                        badge.classList.add('show');
                        window.emailExists = true;
                        console.log('Email exists in database');
                    } else {
                        badge.classList.remove('show');
                        window.emailExists = false;
                        console.log('Email is available');
                    }
                } catch (error) {
                    console.error('Error checking email:', error);
                    // On error, assume email doesn't exist to allow checkout
                    badge.classList.remove('show');
                    window.emailExists = false;
                }
            }, 500); // Wait 500ms after user stops typing
        });
        
        // Check if user is logged in and auto-fill form
        async function checkUserAndAutoFill() {
            try {
                const response = await fetch(getApiUrl('/api/user/me.php'));
                
                if (!response.ok) {
                    if (response.status === 401) {
                        // Not authenticated - guest mode
                        console.log('Guest checkout mode');
                        window.currentUser = null;
                        document.getElementById('guest-banner').style.display = 'flex';
                        return;
                    }
                    throw new Error('Failed to check user status');
                }
                
                const result = await response.json();
                
                if (result.success && result.user) {
                    // User is logged in
                    window.currentUser = result.user;
                    
                    // Auto-fill form
                    document.querySelector('[name="billing_first_name"]').value = result.user.first_name || '';
                    document.querySelector('[name="billing_last_name"]').value = result.user.last_name || '';
                    document.querySelector('[name="billing_email"]').value = result.user.email || '';
                    document.querySelector('[name="billing_phone"]').value = result.user.phone || '';
                    
                    // Load and show saved addresses
                    const addressResponse = await fetch(getApiUrl('/api/user/addresses.php'));
                    if (addressResponse.ok) {
                        const addressResult = await addressResponse.json();
                        
                        if (addressResult.success && addressResult.addresses && addressResult.addresses.length > 0) {
                            // Store addresses globally
                            window.savedAddresses = addressResult.addresses;
                            
                            // Show saved addresses dropdown
                            document.getElementById('saved-addresses-container').style.display = 'block';
                            
                            // Populate dropdown
                            const select = document.getElementById('saved-addresses-select');
                            addressResult.addresses.forEach((addr, index) => {
                                const option = document.createElement('option');
                                option.value = index;
                                option.textContent = `${addr.street_address}, ${addr.city} ${addr.postcode}${addr.is_default ? ' (Default)' : ''}`;
                                select.appendChild(option);
                            });
                            
                            // Auto-fill with default address
                            const defaultAddress = addressResult.addresses.find(a => a.is_default) || addressResult.addresses[0];
                            if (defaultAddress) {
                                fillAddressFields(defaultAddress);
                                // Select it in dropdown
                                const defaultIndex = addressResult.addresses.indexOf(defaultAddress);
                                select.value = defaultIndex;
                            }
                        }
                    }
                } else {
                    // User is guest - show guest banner
                    window.currentUser = null;
                    document.getElementById('guest-banner').style.display = 'flex';
                }
            } catch (error) {
                console.log('Not logged in, continuing as guest:', error.message);
                window.currentUser = null;
                document.getElementById('guest-banner').style.display = 'flex';
            }
        }
        
        // Helper function to fill address fields
        function fillAddressFields(address) {
            document.querySelector('[name="billing_address"]').value = address.street_address || '';
            document.querySelector('[name="billing_city"]').value = address.city || '';
            document.querySelector('[name="billing_postcode"]').value = address.postcode || '';
            
            // Also update shipping if "same as billing" is checked
            const sameAsBilling = document.getElementById('same-as-billing');
            if (sameAsBilling && sameAsBilling.checked) {
                document.getElementById('shipping_address').value = address.street_address || '';
                document.getElementById('shipping_city').value = address.city || '';
                document.getElementById('shipping_postcode').value = address.postcode || '';
            }
        }
        
        // Function to fill address from saved addresses dropdown
        function fillAddressFromSaved(index) {
            if (index === '') return;
            
            if (index === 'new') {
                // Clear all address fields for manual input
                document.querySelector('[name="billing_address"]').value = '';
                document.querySelector('[name="billing_city"]').value = '';
                document.querySelector('[name="billing_postcode"]').value = '';
                
                // Also clear shipping if same as billing
                const sameAsBilling = document.getElementById('same-as-billing');
                if (sameAsBilling && sameAsBilling.checked) {
                    document.getElementById('shipping_address').value = '';
                    document.getElementById('shipping_city').value = '';
                    document.getElementById('shipping_postcode').value = '';
                }
                
                // Focus on address field
                document.querySelector('[name="billing_address"]').focus();
                return;
            }
            
            if (!window.savedAddresses) return;
            
            const address = window.savedAddresses[index];
            if (address) {
                fillAddressFields(address);
            }
        }
        
        // Function to check if address is new (not in saved addresses)
        async function checkIfNewAddress(billingAddress) {
            if (!window.savedAddresses || window.savedAddresses.length === 0) {
                return true; // No saved addresses, definitely new
            }
            
            // Normalize address for comparison
            const normalize = (str) => (str || '').toLowerCase().trim();
            
            // Check if this address matches any saved address
            for (const saved of window.savedAddresses) {
                if (normalize(saved.street_address) === normalize(billingAddress.address) &&
                    normalize(saved.city) === normalize(billingAddress.city) &&
                    normalize(saved.postcode) === normalize(billingAddress.postcode)) {
                    return false; // Address already exists
                }
            }
            
            return true; // This is a new address
        }
        
        // Initialize
        checkUserAndAutoFill();
        loadCartSummary();
    </script>

    <!-- Delivery Method Selection Modal -->
    <div class="modal-backdrop" id="deliveryMethodBackdrop" style="display: none;">
        <div class="delivery-method-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3>Choose Delivery Method</h3>
                <button class="modal-close" onclick="closeDeliveryMethodModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px;">We deliver nationwide! Please select the appropriate delivery method based on your items:</p>
                
                <div class="delivery-method-option" onclick="selectDeliveryMethod('courier')">
                    <div class="option-header">
                        <i class="fas fa-box"></i>
                        <span class="option-name">Courier</span>
                        <button class="help-icon" onclick="event.stopPropagation(); toggleTooltip('courier-tooltip')" title="Learn more">
                            <i class="fas fa-question-circle"></i>
                        </button>
                    </div>
                    <p class="option-description">Best for small to medium items</p>
                    <div class="tooltip-content" id="courier-tooltip" style="display: none;">
                        <strong>Nationwide Courier Delivery</strong><br>
                        Best suited for:<br>
                        • Standard packages and parcels<br>
                        • Small to medium-sized items<br>
                        • Residential & commercial addresses<br>
                        • Items under 25kg<br>
                        • Faster delivery times
                    </div>
                </div>

                <div class="delivery-method-option" onclick="selectDeliveryMethod('general-freighter')">
                    <div class="option-header">
                        <i class="fas fa-truck-loading"></i>
                        <span class="option-name">General Freighter</span>
                        <button class="help-icon" onclick="event.stopPropagation(); toggleTooltip('general-tooltip')" title="Learn more">
                            <i class="fas fa-question-circle"></i>
                        </button>
                    </div>
                    <p class="option-description">Best for large or bulk orders</p>
                    <div class="tooltip-content" id="general-tooltip" style="display: none;">
                        <strong>Nationwide General Freighter</strong><br>
                        Best suited for:<br>
                        • Large or heavy items<br>
                        • Bulk orders<br>
                        • Palletized goods<br>
                        • Commercial deliveries<br>
                        • Items over 25kg<br>
                        • Cost-effective for heavy loads<br><br>
                        <em style="color: #2f3192;">We will provide a shipping quote</em>
                    </div>
                </div>

                <div class="delivery-method-option" onclick="selectDeliveryMethod('fragile-freighter')">
                    <div class="option-header">
                        <i class="fas fa-fragile"></i>
                        <span class="option-name">Fragile Freighter</span>
                        <button class="help-icon" onclick="event.stopPropagation(); toggleTooltip('fragile-tooltip')" title="Learn more">
                            <i class="fas fa-question-circle"></i>
                        </button>
                    </div>
                    <p class="option-description">For delicate items requiring special care</p>
                    <div class="tooltip-content" id="fragile-tooltip" style="display: none;">
                        <strong>Nationwide Fragile Freighter</strong><br>
                        Best suited for:<br>
                        • Windows and glass doors<br>
                        • Doors with glass panels<br>
                        • Mirrors and glass items<br>
                        • Delicate architectural pieces<br>
                        • Items requiring special handling<br><br>
                        <em style="color: #d9534f;">We will provide contact details of specialized freight companies for you to arrange directly based on your location and requirements.</em>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle delivery method radio button click
        document.getElementById('option-delivery').addEventListener('click', function(e) {
            if (e.target.type === 'radio' || e.target.closest('label')) {
                e.preventDefault();
                // Uncheck the radio button
                const deliveryRadio = this.querySelector('input[type="radio"]');
                deliveryRadio.checked = false;
                
                // Keep pickup selected visually
                document.getElementById('option-pickup').classList.add('selected');
                this.classList.remove('selected');
                
                // Show the modal
                openDeliveryMethodModal();
            }
        });

        function openDeliveryMethodModal() {
            const backdrop = document.getElementById('deliveryMethodBackdrop');
            backdrop.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Close on ESC key
            document.addEventListener('keydown', handleEscapeKey);
        }

        function closeDeliveryMethodModal() {
            const backdrop = document.getElementById('deliveryMethodBackdrop');
            backdrop.style.display = 'none';
            document.body.style.overflow = '';
            
            document.removeEventListener('keydown', handleEscapeKey);
        }

        function handleEscapeKey(e) {
            if (e.key === 'Escape') {
                closeDeliveryMethodModal();
            }
        }

        function selectDeliveryMethod(method) {
            // Set the delivery radio button
            const deliveryRadio = document.querySelector('input[name="delivery_method"][value="delivery"]');
            deliveryRadio.checked = true;
            
            // Update visual selection
            document.getElementById('option-pickup').classList.remove('selected');
            document.getElementById('option-delivery').classList.add('selected');
            
            // Store the selected delivery method (you can add a hidden input if needed)
            console.log('Selected delivery method:', method);
            
            // Show delivery info, hide pickup info
            document.getElementById('pickup-info').style.display = 'none';
            document.getElementById('delivery-info').style.display = 'block';
            document.getElementById('shipping-section').style.display = 'block';
            
            // Update delivery method details
            const detailsDiv = document.getElementById('delivery-method-details');
            const methodNameEl = document.getElementById('selected-method-name');
            const methodDescEl = document.getElementById('selected-method-description');
            
            if (method === 'courier') {
                methodNameEl.textContent = 'Courier Delivery Selected';
                methodDescEl.innerHTML = '<strong>Suitable for:</strong><br>' +
                    '• Standard packages and parcels<br>' +
                    '• Small to medium-sized items<br>' +
                    '• Residential & commercial addresses<br>' +
                    '• Items under 25kg<br>' +
                    '• Faster delivery times';
            } else if (method === 'general-freighter') {
                methodNameEl.textContent = 'General Freighter Delivery Selected';
                methodDescEl.innerHTML = '<strong>Suitable for:</strong><br>' +
                    '• Large or heavy items<br>' +
                    '• Bulk orders<br>' +
                    '• Palletized goods<br>' +
                    '• Commercial deliveries<br>' +
                    '• Items over 25kg<br><br>' +
                    '<strong style="color: #2f3192;">Note:</strong> Please contact our office <a href="mailto:info@demolitontraders.co.nz" style="color: #2f3192; text-decoration: underline;">info@demolitontraders.co.nz</a> for a shipping quote.';
            } else if (method === 'fragile-freighter') {
                methodNameEl.textContent = 'Fragile Freighter Delivery Selected';
                methodDescEl.innerHTML = '<strong>Suitable for:</strong><br>' +
                    '• Windows and glass doors<br>' +
                    '• Doors with glass panels<br>' +
                    '• Mirrors and glass items<br>' +
                    '• Delicate architectural pieces<br><br>' +
                    '<strong style="color: #d9534f;">Note:</strong> Staff will provide contact details of specialized freight companies for you to arrange delivery directly based on your location and requirements.';
            }
            
            detailsDiv.style.display = 'block';
            
            // Close the modal
            closeDeliveryMethodModal();
            
            // Show confirmation
            let methodName = '';
            let notificationMsg = '';
            if (method === 'courier') {
                methodName = 'Courier';
                notificationMsg = `${methodName} delivery selected. Staff will contact you with shipping quote.`;
            } else if (method === 'general-freighter') {
                methodName = 'General Freighter';
                notificationMsg = `${methodName} delivery selected. Staff will contact you with shipping quote.`;
            } else if (method === 'fragile-freighter') {
                methodName = 'Fragile Freighter';
                notificationMsg = `${methodName} selected. Staff will provide freight company contact details.`;
            }
            showNotification(notificationMsg);
        }

        function toggleTooltip(tooltipId) {
            const tooltip = document.getElementById(tooltipId);
            
            // Just toggle current tooltip without hiding others
            tooltip.style.display = tooltip.style.display === 'none' ? 'block' : 'none';
        }

        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #2f3192;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 10001;
                max-width: 300px;
                font-size: 14px;
                line-height: 1.4;
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // Close modal when clicking backdrop
        document.getElementById('deliveryMethodBackdrop').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeliveryMethodModal();
            }
        });
    </script>

    <style>
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }

        .delivery-method-modal {
            position: relative;
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s ease;
            z-index: 10001;
        }

        @keyframes modalFadeIn {
            from { 
                transform: scale(0.9);
                opacity: 0;
            }
            to { 
                transform: scale(1);
                opacity: 1;
            }
        }

        .delivery-method-modal .modal-header {
            background: linear-gradient(135deg, #2f3192 0%, #1f2182 100%);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .delivery-method-modal .modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .delivery-method-modal .modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .delivery-method-modal .modal-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }

        .delivery-method-modal .modal-body {
            padding: 25px;
        }

        .delivery-method-option {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .delivery-method-option:hover {
            background: #fff;
            border-color: #2f3192;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(47,49,146,0.15);
        }

        .delivery-method-option .option-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .delivery-method-option .option-header i.fa-box,
        .delivery-method-option .option-header i.fa-truck-loading {
            font-size: 24px;
            color: #2f3192;
        }

        .delivery-method-option .option-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            flex: 1;
        }

        .delivery-method-option .help-icon {
            background: transparent;
            border: none;
            color: #999;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s;
        }

        .delivery-method-option .help-icon:hover {
            color: #2f3192;
        }

        .delivery-method-option .option-description {
            margin: 0 0 0 36px;
            color: #666;
            font-size: 14px;
        }

        .delivery-method-option .tooltip-content {
            margin-top: 12px;
            padding: 12px;
            background: white;
            border-left: 3px solid #ffca0d;
            border-radius: 5px;
            font-size: 13px;
            line-height: 1.6;
            color: #555;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .delivery-method-option .tooltip-content strong {
            color: #2f3192;
        }

        @media (max-width: 768px) {
            .delivery-method-modal {
                max-width: 95%;
            }

            .delivery-method-modal .modal-header h3 {
                font-size: 18px;
            }

            .delivery-method-modal .modal-body {
                padding: 20px;
            }

            .delivery-method-option {
                padding: 15px;
            }

            .delivery-method-option .option-name {
                font-size: 16px;
            }
        }
    </style>
    
    <script>
    // Load opening hours from Google Places API
    async function loadOpeningHours() {
        try {
            const response = await fetch(getApiUrl('/api/opening-hours.php'));
            const data = await response.json();
            const element = document.getElementById('opening-hours-checkout');
            
            if (data.weekday_text && data.weekday_text.length > 0) {
                element.innerHTML = data.weekday_text.map(day => {
                    const dayName = day.split(':')[0];
                    const hours = day.split(':').slice(1).join(':').trim();
                    return `${dayName}: ${hours}`;
                }).join('<br>');
            } else {
                element.innerHTML = 'Opening hours not available';
            }
        } catch (error) {
            element.innerHTML = 'Hours information unavailable';
        }
    }
    
    if (document.getElementById('opening-hours-checkout')) {
        loadOpeningHours();
    }
    </script>
</body>
</html>
