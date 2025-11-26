<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabins - Demolition Traders Hamilton</title>
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Cabins & Portable Buildings</h1>
            <nav class="breadcrumb">
                <a href="index.php">Home</a> / <span>Cabins</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <h2 class="center">Quality Portable Cabins</h2>
            <p class="center">Perfect for site offices, storage, sleepouts, or additional accommodation</p>
            
            <div class="cabins-grid">
                <div class="cabin-card">
                    <img src="assets/images/cabin-office.jpg" alt="Office Cabin" onerror="this.src='assets/images/placeholder.jpg'">
                    <h3>Site Office Cabins</h3>
                    <p>Ideal for construction sites and temporary workspaces. Fully insulated with power and lighting.</p>
                    <ul>
                        <li>Multiple sizes available</li>
                        <li>Pre-wired for power</li>
                        <li>Insulated walls and ceiling</li>
                        <li>Windows and door included</li>
                    </ul>
                    <a href="contact.php" class="btn btn-primary">Enquire Now</a>
                </div>
                
                <div class="cabin-card">
                    <img src="assets/images/cabin-storage.jpg" alt="Storage Cabin" onerror="this.src='assets/images/placeholder.jpg'">
                    <h3>Storage Cabins</h3>
                    <p>Secure storage solutions for tools, equipment, and materials. Weather-proof and durable.</p>
                    <ul>
                        <li>Various sizes from 10m² to 40m²</li>
                        <li>Lockable security doors</li>
                        <li>Weather-proof construction</li>
                        <li>Optional shelving</li>
                    </ul>
                    <a href="contact.php" class="btn btn-primary">Enquire Now</a>
                </div>
                
                <div class="cabin-card">
                    <img src="assets/images/cabin-sleepout.jpg" alt="Sleepout Cabin" onerror="this.src='assets/images/placeholder.jpg'">
                    <h3>Sleepout Cabins</h3>
                    <p>Extra living space for guests, teenagers, or home offices. Comfortable and well-insulated.</p>
                    <ul>
                        <li>Fully lined and insulated</li>
                        <li>Power points and lighting</li>
                        <li>Quality windows</li>
                        <li>Can include bathroom option</li>
                    </ul>
                    <a href="contact.php" class="btn btn-primary">Enquire Now</a>
                </div>
                
                <div class="cabin-card">
                    <img src="assets/images/cabin-custom.jpg" alt="Custom Cabin" onerror="this.src='assets/images/placeholder.jpg'">
                    <h3>Custom Cabins</h3>
                    <p>Need something specific? We can customize cabins to meet your exact requirements.</p>
                    <ul>
                        <li>Custom sizes and layouts</li>
                        <li>Choose your finishes</li>
                        <li>Additional features available</li>
                        <li>Bathroom and kitchen options</li>
                    </ul>
                    <a href="contact.php" class="btn btn-primary">Enquire Now</a>
                </div>
            </div>
            
            <div class="info-section">
                <h2>Why Choose Our Cabins?</h2>
                <div class="features-grid">
                    <div class="feature-box">
                        <i class="fa-solid fa-dollar-sign"></i>
                        <h4>Affordable Prices</h4>
                        <p>Quality cabins at competitive prices that won't break the bank</p>
                    </div>
                    <div class="feature-box">
                        <i class="fa-solid fa-truck"></i>
                        <h4>Delivery Available</h4>
                        <p>We can deliver and install your cabin anywhere in the Waikato region</p>
                    </div>
                    <div class="feature-box">
                        <i class="fa-solid fa-hammer"></i>
                        <h4>Quality Construction</h4>
                        <p>Built to last with quality materials and expert craftsmanship</p>
                    </div>
                    <div class="feature-box">
                        <i class="fa-solid fa-clock"></i>
                        <h4>Quick Setup</h4>
                        <p>Fast delivery and installation, ready to use in no time</p>
                    </div>
                </div>
            </div>
            
            <div class="cta-section center">
                <h2>Interested in a Cabin?</h2>
                <p>Contact us today for a quote or to discuss your requirements</p>
                <div class="cta-buttons">
                    <a href="tel:078474989" class="btn btn-primary">Call 07 847 4989</a>
                    <a href="contact.php" class="btn btn-secondary">Contact Form</a>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'components/footer.php'; ?>
</body>
</html>
