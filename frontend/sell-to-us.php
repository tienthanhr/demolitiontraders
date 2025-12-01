<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell to Us - Demolition Traders Hamilton</title>
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Sell to Us</h1>
            <nav class="breadcrumb">
                <a href="index.php">Home</a> / <span>Sell to Us</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="two-column-layout">
                <div class="content-main">
                    <h2>Turn Your Unwanted Materials Into Cash</h2>
                    <p>Demolition Traders is always looking to purchase quality building materials. Whether you're a demolition contractor, builder, or homeowner with materials from a renovation, we'd love to hear from you.</p>
                    
                    <h3>What We Buy</h3>
                    <div class="icon-grid">
                        <div class="icon-box">
                            <i class="fa-solid fa-door-open"></i>
                            <h4>Doors & Windows</h4>
                            <p>Interior, exterior, sliding doors, French doors, aluminium and wooden windows</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-tree"></i>
                            <h4>Native Timber</h4>
                            <p>Rimu, kauri, matai, and other native timber in any form</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-bath"></i>
                            <h4>Bathroom Items</h4>
                            <p>Baths, vanities, toilets, hand basins, and bathroom fittings</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-kitchen-set"></i>
                            <h4>Kitchen Cabinets</h4>
                            <p>Complete kitchens or individual cabinets in good condition</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-house"></i>
                            <h4>Roofing Iron</h4>
                            <p>Corrugated iron, long-run, and other roofing materials</p>
                        </div>
                        <div class="icon-box">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <h4>Building Materials</h4>
                            <p>Weatherboards, plywood, bricks, and more</p>
                        </div>
                    </div>
                    
                    <h3>How It Works</h3>
                    <ol class="numbered-list">
                        <li>Contact us with photos and description of your materials</li>
                        <li>We'll assess the items and provide a quote</li>
                        <li>If you accept, we can arrange pickup or you can deliver to our yard</li>
                        <li>Get paid for your materials!</li>
                    </ol>
                    
                    <div class="info-box-yellow">
                        <h4>What We Look For</h4>
                        <ul>
                            <li>Good quality materials in reusable condition</li>
                            <li>Complete items (e.g. doors with frames, windows with glass)</li>
                            <li>Character items from older buildings</li>
                            <li>Native timber in any form</li>
                            <li>Reasonable quantities</li>
                        </ul>
                    </div>
                    
                    <h3>Submit Your Items</h3>
                    <form class="contact-form" id="sell-form" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Your Name *</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" placeholder="City or region">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Item Description *</label>
                            <textarea name="description" rows="6" placeholder="Please describe the materials you have to sell, including quantity, condition, and approximate age..." required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload Photos</label>
                            <input type="file" name="photos[]" multiple accept="image/*">
                            <small>You can select multiple photos (max 5)</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Submit for Quote</button>
                    </form>
                </div>
                
                <aside class="sidebar">
                    <div class="sidebar-box">
                        <h3>Contact Us</h3>
                        <p><strong>Phone:</strong><br>07 847 4989</p>
                        <p><strong>Freephone:</strong><br>0800 DEMOLITION</p>
                        <p><strong>Email:</strong><br>info@demolitiontraders.co.nz</p>
                    </div>
                    
                    <div class="sidebar-box">
                        <h3>Our Location</h3>
                        <p>249 Kahikatea Drive, Greenlea Lane<br>Frankton, Hamilton<br>New Zealand</p>
                        <a href="contact.php" class="btn btn-secondary btn-sm">Get Directions</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
    
    <?php include 'components/footer.php'; ?>
    <?php include 'components/toast-notification.php'; ?>
    
    <script>
        document.getElementById('sell-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/demolitiontraders/backend/api/sell-to-us/submit.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    showToast('Thank you! Your submission has been received. We\'ll review your items and contact you shortly.', 'success');
                    this.reset();
                } else {
                    showToast(result.error || 'Failed to submit. Please try again.', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    </script>
</body>
</html>
