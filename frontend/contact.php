<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Demolition Traders Hamilton</title>
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Contact Us</h1>
            <nav class="breadcrumb">
                <a href="index.php">Home</a> / <span>Contact</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="two-column-layout">
                <div class="content-main">
                    <h2>Send Us a Message</h2>
                    <form class="contact-form" id="contact-form">
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
                                <label>Phone Number</label>
                                <input type="tel" name="phone">
                            </div>
                            <div class="form-group">
                                <label>Subject *</label>
                                <select name="subject" required>
                                    <option value="">Select Subject</option>
                                    <option value="General Enquiry">General Enquiry</option>
                                    <option value="Product Enquiry">Product Enquiry</option>
                                    <option value="Delivery Quote">Delivery Quote</option>
                                    <option value="Sell to Us">Sell to Us</option>
                                    <option value="Cabins">Cabins Enquiry</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Message *</label>
                            <textarea name="message" rows="8" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
                
                <aside class="sidebar">
                    <div class="sidebar-box">
                        <h3>Contact Details</h3>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fa-solid fa-phone"></i>
                                <div>
                                    <strong>Phone</strong>
                                    <p>07 847 4989</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fa-solid fa-phone-volume"></i>
                                <div>
                                    <strong>Freephone</strong>
                                    <p>0800 DEMOLITION<br>(0800 336 654 8466)</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fa-solid fa-envelope"></i>
                                <div>
                                    <strong>Email</strong>
                                    <p>info@demolitiontraders.co.nz</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fa-solid fa-location-dot"></i>
                                <div>
                                    <strong>Address</strong>
                                    <p>249 Kahikatea Drive, Greenlea Lane<br>Frankton, Hamilton<br>New Zealand</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-box">
                        <h3>Opening Hours</h3>
                        <div id="opening-hours-contact">
                            <div style="text-align: center; padding: 20px;">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-box">
                        <h3>Follow Us</h3>
                        <div class="social-links-large">
                            <a href="https://www.facebook.com/DemolitionTraders" target="_blank">
                                <i class="fa-brands fa-facebook"></i> Facebook
                            </a>
                            <a href="https://www.instagram.com/demolition_traders/" target="_blank">
                                <i class="fa-brands fa-instagram"></i> Instagram
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
            
            <div class="map-section">
                <h2 class="center">Find Us</h2>
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3152.9376674724795!2d175.2602217!3d-37.8072319!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6d6d21fa970b5073%3A0x229ec1a4d67e239a!2sDemolition%20Traders!5e0!3m2!1sen!2snz!4v1234567890" 
                            width="100%" 
                            height="450" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                <div class="directions-info">
                    <p><strong>Easy to find:</strong> We're located on Kahikatea Drive in Hamilton, with plenty of parking and easy access for all vehicles including trailers.</p>
                    <a href="https://www.google.com/maps/place/Demolition+Traders/@-37.8072281,175.2449009,14z" target="_blank" class="btn btn-secondary">Get Directions</a>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'components/footer.php'; ?>
    
    <script>
        document.getElementById('contact-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('/demolitiontraders/backend/api/contact/submit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    alert('Thank you for your message! We\'ll get back to you soon.');
                    this.reset();
                } else {
                    alert('Failed to send message. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again or contact us directly.');
            }
        });
        
        // Load opening hours from Google Places API
        async function loadOpeningHours() {
            try {
                const response = await fetch('/demolitiontraders/backend/api/opening-hours.php');
                const data = await response.json();
                const element = document.getElementById('opening-hours-contact');
                
                if (data.weekday_text && data.weekday_text.length > 0) {
                    const table = document.createElement('table');
                    table.className = 'hours-table';
                    
                    data.weekday_text.forEach(day => {
                        const parts = day.split(':');
                        const dayName = parts[0].trim();
                        const hours = parts.slice(1).join(':').trim();
                        
                        const row = document.createElement('tr');
                        row.innerHTML = `<td>${dayName}</td><td>${hours}</td>`;
                        table.appendChild(row);
                    });
                    
                    element.innerHTML = '';
                    element.appendChild(table);
                } else {
                    element.innerHTML = '<p>Opening hours not available</p>';
                }
            } catch (error) {
                document.getElementById('opening-hours-contact').innerHTML = '<p>Hours information unavailable</p>';
            }
        }
        
        loadOpeningHours();
    </script>
</body>
</html>
