<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Frequently Asked Questions</h1>
            <nav class="breadcrumb">
                <a href="<?php echo userUrl('index.php'); ?>">Home</a> / <span>FAQs</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What are your opening hours?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We are open Monday to Friday from 8am to 5pm, and Saturday from 8am to 4pm. We are closed on Sundays and public holidays.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Where are you located?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We are located at 249 Kahikatea Drive, Greenlea Lane, Frankton, Hamilton. We have ample parking and easy access for all vehicles including trailers.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Do you offer delivery?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, we offer delivery services throughout the Waikato region and surrounding areas. Delivery costs vary depending on the size of your order and delivery location. Contact us for a delivery quote.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What payment methods do you accept?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We accept cash, EFTPOS, credit cards (Visa and Mastercard), and bank transfers. For larger orders, we can arrange account terms for approved customers.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Do you buy building materials?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! We're always interested in purchasing quality building materials, especially native timber, doors, windows, and character items. Visit our <a href="<?php echo userUrl('sell-to-us.php'); ?>">Sell to Us</a> page for more information.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Are your recycled products safe to use?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, all our recycled materials are carefully inspected and cleaned before sale. We only sell items that are in good, reusable condition. However, we recommend checking items for suitability for your specific project.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Can I return or exchange items?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We offer a <strong>30-day return policy with full refund</strong> for items in original condition. Please keep your receipt and contact us within 30 days of purchase to arrange a return. Conditions apply:</p>
                        <ul>
                            <li>Items must be unused and in original condition</li>
                            <li>Original receipt or proof of purchase required</li>
                            <li>Some items may be subject to restocking fees</li>
                            <li>Special orders and custom items are non-refundable</li>
                        </ul>
                        <p>For recycled and demolition materials, please inspect carefully before purchase as these may have different return conditions.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Do you have specific items in stock?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Our stock changes regularly as we receive new materials from demolition sites. If you're looking for something specific, use our <a href="<?php echo userUrl('wanted-listing.php'); ?>">Wanted Listing</a> service and we'll contact you when we have a match.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Can I visit your yard to browse?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Absolutely! We encourage customers to visit our yard during opening hours to browse our extensive range of materials. Our friendly staff are always available to help you find what you need.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>How do I know if an item will fit my project?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We provide dimensions for all products where possible. Feel free to bring measurements and photos of your project when you visit, and our experienced staff can help you find suitable materials.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Do you offer installation services?</h3>
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We focus on supplying materials rather than installation. However, we can recommend experienced tradespeople if you need installation services.</p>
                    </div>
                </div>
            </div>
            
            <div class="cta-section center">
                <h2>Still Have Questions?</h2>
                <p>Contact us and our friendly team will be happy to help</p>
                <div class="cta-buttons">
                    <a href="tel:078474989" class="btn btn-primary">Call 07 847 4989</a>
                    <a href="<?php echo userUrl('contact.php'); ?>" class="btn btn-secondary">Contact Us</a>
                </div>
            </div>
        </div>
    </section>
    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/toast-notification.php'; ?>
    
    <script>
        // FAQ accordion functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', function() {
                const faqItem = this.parentElement;
                const isActive = faqItem.classList.contains('active');
                
                // Close all FAQ items
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Open clicked item if it wasn't active
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
