<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Staff - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Our Staff</h1>
            <nav class="breadcrumb">
                <a href="index.php">Home</a> / <span>Staff</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="staff-intro">
                <h2>Meet Our Friendly Team</h2>
                <p class="lead">Our experienced and knowledgeable staff are here to help you find exactly what you need for your project.</p>
            </div>

            <div class="staff-grid">
                <!-- Staff Member 1 -->
                <div class="staff-card">
                    <div class="staff-image">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div class="staff-info">
                        <h3>Karl</h3>
                        <p class="staff-role">Senior Salesperson</p>
                        <p class="staff-desc">Karl is one of our senior sales team members with extensive knowledge of our product range. He's here to help you find the perfect materials for your project.</p>
                        <div class="staff-contact">
                            <i class="fa-solid fa-phone"></i> 0800 DEMOLITION
                        </div>
                    </div>
                </div>

                <!-- Staff Member 2 -->
                <div class="staff-card">
                    <div class="staff-image">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div class="staff-info">
                        <h3>Bundy</h3>
                        <p class="staff-role">Senior Salesperson</p>
                        <p class="staff-desc">Bundy brings years of experience in the demolition and building materials industry. He's always ready to provide expert advice on your renovation needs.</p>
                        <div class="staff-contact">
                            <i class="fa-solid fa-phone"></i> 0800 DEMOLITION
                        </div>
                    </div>
                </div>

                <!-- Staff Member 3 -->
                <div class="staff-card">
                    <div class="staff-image">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div class="staff-info">
                        <h3>Wayne</h3>
                        <p class="staff-role">Yard Hand/Salesperson</p>
                        <p class="staff-desc">Wayne works both in the yard and with customers, ensuring smooth operations and helping you locate and select the materials you need.</p>
                        <div class="staff-contact">
                            <i class="fa-solid fa-phone"></i> 0800 DEMOLITION
                        </div>
                    </div>
                </div>

                <!-- Staff Member 4 -->
                <div class="staff-card">
                    <div class="staff-image">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div class="staff-info">
                        <h3>Alex</h3>
                        <p class="staff-role">Yard Hand/Salesperson</p>
                        <p class="staff-desc">Alex assists with yard operations and customer service, making sure your visit to Demolition Traders is efficient and productive.</p>
                        <div class="staff-contact">
                            <i class="fa-solid fa-phone"></i> 0800 DEMOLITION
                        </div>
                    </div>
                </div>
            </div>

            <div class="staff-cta">
                <h2>Need Expert Advice?</h2>
                <p>Our team is ready to help you with your project. Visit us at our yard or give us a call.</p>
                <div class="cta-buttons">
                    <a href="contact.php" class="btn btn-primary">Contact Us</a>
                    <a href="tel:0800336548466" class="btn btn-secondary">Call 0800 DEMOLITION</a>
                </div>
            </div>
        </div>
    </section>
    
    <?php include '../components/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
