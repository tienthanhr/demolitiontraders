<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Use - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Terms of Use</h1>
            <nav class="breadcrumb">
                <a href="index.php">Home</a> / <span>Terms of Use</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="terms-content" style="max-width: 900px; margin: 0 auto;">
                
                <div class="terms-section">
                    <h2>1. Acceptance of Terms</h2>
                    <p>By accessing and using the Demolition Traders website and services, you accept and agree to be bound by these Terms of Use. If you do not agree to these terms, please do not use our website or services.</p>
                </div>

                <div class="terms-section">
                    <h2>2. Product Information and Pricing</h2>
                    <p>We strive to provide accurate product descriptions, images, and pricing. However:</p>
                    <ul>
                        <li>Recycled and demolition materials may vary in condition and appearance from photos</li>
                        <li>Prices are subject to change without notice</li>
                        <li>Stock availability is not guaranteed and changes regularly</li>
                        <li>We reserve the right to correct any errors in pricing or product information</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>3. Returns and Refunds</h2>
                    <p><strong>30-Day Return Policy:</strong> We offer a full refund for items returned within 30 days of purchase, subject to the following conditions:</p>
                    <ul>
                        <li>Items must be unused and in original condition</li>
                        <li>Original receipt or proof of purchase is required</li>
                        <li>Some items may be subject to restocking fees</li>
                        <li>Special orders and custom items are non-refundable</li>
                    </ul>
                    <p><strong>Recycled and Demolition Materials:</strong> Due to the unique nature of these items, different return conditions may apply. Please inspect carefully and ask our staff about specific return policies at the time of purchase.</p>
                    <p>To arrange a return, please contact us within 30 days of purchase with your receipt.</p>
                </div>

                <div class="terms-section">
                    <h2>4. Payment Terms</h2>
                    <ul>
                        <li>We accept cash, EFTPOS, credit cards, and approved account payments</li>
                        <li>Payment is required before goods can be removed from our premises</li>
                        <li>Trade accounts are available for approved businesses - documentation required</li>
                        <li>All prices include GST unless otherwise stated</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>5. Delivery and Pickup</h2>
                    <ul>
                        <li>Delivery services are available at an additional cost - please inquire for rates</li>
                        <li>Self-pickup is welcome during business hours with appropriate vehicle and equipment</li>
                        <li>Customer is responsible for securing and transporting purchased items safely</li>
                        <li>Loading assistance may be provided at staff discretion and availability</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>6. Safety and Liability</h2>
                    <ul>
                        <li>Customers visiting our yard must follow all safety instructions and signage</li>
                        <li>Appropriate footwear and clothing are recommended</li>
                        <li>Children must be supervised at all times</li>
                        <li>Demolition Traders is not liable for injuries resulting from customer negligence</li>
                        <li>Items are sold "as is" - customer assumes all risk upon purchase</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>7. Website Use</h2>
                    <ul>
                        <li>You must be 18 years or older to make purchases through our website</li>
                        <li>You are responsible for maintaining the confidentiality of your account</li>
                        <li>You agree not to misuse our website or interfere with its operation</li>
                        <li>We reserve the right to refuse service or cancel orders at our discretion</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>8. Privacy and Data</h2>
                    <p>We collect and use personal information in accordance with New Zealand privacy laws. Your information is used to:</p>
                    <ul>
                        <li>Process orders and provide services</li>
                        <li>Communicate with you about your inquiries and purchases</li>
                        <li>Notify you of items on your wanted list (if opted in)</li>
                        <li>Improve our services and customer experience</li>
                    </ul>
                    <p>We do not sell or share your personal information with third parties for marketing purposes.</p>
                </div>

                <div class="terms-section">
                    <h2>9. Intellectual Property</h2>
                    <p>All content on this website, including text, images, logos, and design, is the property of Demolition Traders and protected by copyright. You may not reproduce, distribute, or use any content without written permission.</p>
                </div>

                <div class="terms-section">
                    <h2>10. Changes to Terms</h2>
                    <p>We reserve the right to modify these Terms of Use at any time. Changes will be effective immediately upon posting to the website. Your continued use of our services constitutes acceptance of any changes.</p>
                </div>

                <div class="terms-section">
                    <h2>11. Contact Information</h2>
                    <p>If you have questions about these Terms of Use, please contact us:</p>
                    <p>
                        <strong>Demolition Traders</strong><br>
                        249 Kahikatea Drive, Greenlea Lane<br>
                        Frankton, Hamilton<br>
                        Phone: <a href="tel:+6478474989">07 847 4989</a><br>
                        Freephone: <a href="tel:08003366548466">0800 DEMOLITION</a><br>
                        Email: <a href="mailto:info@demolitiontraders.co.nz">info@demolitiontraders.co.nz</a>
                    </p>
                </div>

                <div class="terms-section">
                    <p style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #666; font-size: 14px;">
                        <strong>Last Updated:</strong> <?php echo date('F j, Y'); ?>
                    </p>
                </div>

            </div>
        </div>
    </section>
    
    <?php include '../components/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
