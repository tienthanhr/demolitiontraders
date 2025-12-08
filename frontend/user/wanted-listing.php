<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wanted Listing - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Wanted Listing</h1>
            <nav class="breadcrumb">
                <a href="<?php echo userUrl('index.php'); ?>">Home</a> / <span>Wanted Listing</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="two-column-layout">
                <div class="content-main">
                    <h2>Can't Find What You're Looking For?</h2>
                    <p>At Demolition Traders, we understand that sometimes the perfect item isn't available in our current stock. That's why we offer a Wanted Listing service to help you find those hard-to-get pieces for your renovation or restoration projects.</p>                   
                    <h3>How It Works</h3>
                    <ol class="numbered-list">
                        <li>Fill out the form with details of what you're looking for</li>
                        <li>We'll check our incoming stock and contact you when we have a match</li>
                        <li>Come visit our yard to view and purchase the items</li>
                    </ol>
                                        
                    <h3>Submit Your Wanted Item</h3>
                    <form class="contact-form" id="wanted-form">
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
                                <label>Category</label>
                                <select name="category">
                                    <option value="">Select Category</option>
                                    <option value="timber">Timber</option>
                                    <option value="doors">Doors</option>
                                    <option value="windows">Windows</option>
                                    <option value="bathroom">Bathroom</option>
                                    <option value="kitchen">Kitchen</option>
                                    <option value="flooring">Flooring</option>
                                    <option value="roofing">Roofing</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Item Description *</label>
                            <textarea name="description" rows="6" placeholder="Please provide as much detail as possible about the item you're looking for..." required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Quantity Needed</label>
                            <input type="text" name="quantity" placeholder="e.g. 50 square meters, 10 units, etc.">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="notify" checked>
                                Send me email notifications when similar items become available
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Submit Wanted Item</button>
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
                        <h3>Opening Hours</h3>
                        <div id="opening-hours-wanted" style="font-size: 14px;">
                            <div style="text-align: center; padding: 10px;">
                                <div class="spinner" style="width: 20px; height: 20px; border-width: 2px;"></div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/toast-notification.php'; ?>
    
    <script>
        document.getElementById('wanted-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch(getApiUrl('/api/wanted-listing/submit.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                // Log the raw response for debugging
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error('Invalid JSON response from server');
                }
                
                if (response.ok && result.success) {
                    let message = result.message;
                    if (result.matched_products && result.matched_products > 0) {
                        message += ` We found ${result.matched_products} similar items and added them to your wishlist!`;
                    }
                    if (typeof showToast === 'function') {
                        showToast(message, 'success');
                    } else {
                        alert(message);
                    }
                    this.reset();
                } else {
                    const errorMsg = result.error || 'Failed to submit. Please try again.';
                    if (typeof showToast === 'function') {
                        showToast(errorMsg, 'error');
                    } else {
                        alert('Error: ' + errorMsg);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                const errorMsg = 'An error occurred. Please try again.';
                if (typeof showToast === 'function') {
                    showToast(errorMsg, 'error');
                } else {
                    alert(errorMsg);
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
        
        // Load opening hours from Google Places API
        async function loadOpeningHours() {
            try {
                const response = await fetch(getApiUrl('/api/opening-hours.php'));
                const data = await response.json();
                const element = document.getElementById('opening-hours-wanted');
                
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
        
        loadOpeningHours();
    </script>
</body>
</html>
