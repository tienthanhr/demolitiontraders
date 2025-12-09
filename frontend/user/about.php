<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=4">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Subtle animations và improvements */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .page-header {
            animation: fadeIn 0.6s ease-out;
        }

        .content-main {
            animation: slideInLeft 0.8s ease-out;
        }

        .content-main p {
            animation: fadeInUp 0.8s ease-out;
            animation-fill-mode: both;
        }

        .content-main p:nth-child(1) { animation-delay: 0.1s; }
        .content-main p:nth-child(2) { animation-delay: 0.2s; }
        .content-main p:nth-child(3) { animation-delay: 0.3s; }
        .content-main p:nth-child(4) { animation-delay: 0.4s; }
        .content-main p:nth-child(5) { animation-delay: 0.5s; }
        .content-main p:nth-child(6) { animation-delay: 0.6s; }

        .sidebar {
            animation: slideInRight 0.8s ease-out 0.2s both;
        }

        .sidebar-box {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .sidebar-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .content-main img {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .content-main img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2) !important;
        }

        .btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }

        .btn:hover:before {
            width: 300px;
            height: 300px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .service-list li {
            transition: all 0.3s ease;
        }

        .service-list li:hover {
            transform: translateX(8px);
            background: rgba(0, 123, 255, 0.05);
            padding-left: 10px;
        }

        .service-list a {
            transition: color 0.3s ease;
        }

        #opening-hours-about > div {
            animation: fadeInUp 0.5s ease-out;
            animation-fill-mode: both;
        }

        #opening-hours-about > div:nth-child(1) { animation-delay: 0.1s; }
        #opening-hours-about > div:nth-child(2) { animation-delay: 0.2s; }
        #opening-hours-about > div:nth-child(3) { animation-delay: 0.3s; }
        #opening-hours-about > div:nth-child(4) { animation-delay: 0.4s; }
        #opening-hours-about > div:nth-child(5) { animation-delay: 0.5s; }
        #opening-hours-about > div:nth-child(6) { animation-delay: 0.6s; }
        #opening-hours-about > div:nth-child(7) { animation-delay: 0.7s; }

        /* Subtle gradient text effect */
        .page-header h1 {
            background: linear-gradient(135deg, #1a1a1a 0%, #4a4a4a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Smooth spinner rotation */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        /* Enhanced shadow for boxes */
        .sidebar-box {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        /* Subtle hover effect for contact info */
        .sidebar-box p {
            transition: color 0.3s ease;
        }

        .sidebar-box p:hover {
            color: #007bff;
        }

        /* Add smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .content-main, .sidebar {
                animation: fadeIn 0.8s ease-out;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>About Demolition Traders</h1>
            <nav class="breadcrumb">
                <a href="<?php echo userUrl('index.php'); ?>">Home</a> / <span>About</span>
            </nav>
        </div>
    </div>
    
    <section class="content-section">
        <div class="container">
            <div class="two-column-layout">
                <div class="content-main">
                    <div style="margin-bottom: 30px;"></div>
                    <p>Established in 1984, Demolition Traders is one of the Waikato's iconic, privately owned businesses. The premises covers 3.5 acres allowing for easy access, parking & loading.</p>
                    <p>Demolition Traders offer a comprehensive range of both new and recycled building materials at discounted prices. The stock is sourced from manufacturers' surplus stock and cancelled orders, or end of line items & downgraded products, or direct from importers & national suppliers, or demolition projects. They also have new products made specifically for Demolition Traders.</p>
                    <p>The company has expanded to supply value to customers nationwide. This is achieved by offering delivery solutions by courier, carrier & fragile freight contacts.</p>
                    <p>The new Aluminium Joinery is supplied by a leading NZ based manufacturer with comprehensive guarantees. This ensures NZ manufacturing standards are upheld as the joinery is built by professionals, not staff members.</p>
                    <p>Over the years Demolition Traders has expanded to become the largest supplier of materials to tiny home builders & granny flats. Their tiny home product range includes, new timber, new roofing & flashings, pack lots of treated & untreated plywood, double glazed Aluminium Joinery, & much more. New Zealand's largest range of Double Glazed Ready-Made Aluminium Joinery.</p>
                    <p>Their employees are very friendly, helpful & knowledgeable. Mike & Wayne have been with Demolition Traders for 20 plus years, Karl & Bundy for 10 plus years, while other team members have several years' experience with Demolition Traders & other building related positions.</p>
                    <div style="text-align:center; margin: 18px 0 28px 0;">
                        <img src="assets/images/DemoTeam.jpg" alt="Demolition Traders Team" style="max-width:320px;width:100%;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.10);margin-bottom:8px;">
                        <div style="font-size:13px;color:#666;">The Demolition Traders Team</div>
                    </div>
                    <p>For a unique shopping experience, come in & meet the team!</p>
                </div>
                <aside class="sidebar">
                    <div class="sidebar-box">
                        <h3>Visit Our Yard</h3>
                        <p><strong>Address:</strong><br>249 Kahikatea Drive, Greenlea Lane<br>Frankton, Hamilton<br>New Zealand</p>
                        <a href="https://www.google.com/maps/place/Demolition+Traders/@-37.8072281,175.2449009,6771m/data=!3m1!1e3!4m6!3m5!1s0x6d6d21fa970b5073:0x229ec1a4d67e239a!8m2!3d-37.8072319!4d175.2624104!16s%2Fg%2F1hm6cqmtt?entry=ttu&g_ep=EgoyMDI1MTEyMy4xIKXMDSoASAFQAw%3D%3D" target="_blank" class="btn btn-primary" style="display: block; text-align: center;">
                            <i class="fa-solid fa-map-location-dot"></i> Get Directions
                        </a>
                        <br></br>
                        <p><strong>Opening Hours:</strong></p>
                        <div id="opening-hours-about" style="margin-left: 0; line-height: 1.8;">
                            <div style="text-align: center; padding: 10px;">
                                <div class="spinner" style="width: 20px; height: 20px; border-width: 2px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="sidebar-box">
                        <h3>Contact Us</h3>
                        <p><strong>Phone:</strong><br>07 847 4989</p>
                        <p><strong>Freephone:</strong><br>0800 DEMOLITION</p>
                        <p><strong>Email:</strong><br>info@demolitiontraders.co.nz</p>
                    </div>
                    <div class="sidebar-box">
                        <h3>Services</h3>
                        <ul class="service-list">
                            <li><a href="<?php echo userUrl('shop.php'); ?>">Browse Products</a></li>
                            <li><a href="<?php echo userUrl('wanted-listing.php'); ?>">Wanted Listing</a></li>
                            <li><a href="<?php echo userUrl('sell-to-us.php'); ?>">Sell to Us</a></li>
                            <li><a href="<?php echo userUrl('cabins.php'); ?>">Cabins</a></li>
                            <li><a href="<?php echo userUrl('contact.php'); ?>">Delivery Services</a></li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </section>
    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/toast-notification.php'; ?>
    
    <script>
    // Load opening hours from Google Places API
    async function loadOpeningHours(elementId) {
        try {
            const response = await fetch(getApiUrl('/api/opening-hours.php'));
            const responseText = await response.text();
            const data = JSON.parse(responseText);
            const element = document.getElementById(elementId);
            
            if (data.weekday_text && data.weekday_text.length > 0) {
                element.innerHTML = data.weekday_text.map(day => {
                    return `<div style="padding: 3px 0;">${day}</div>`;
                }).join('');
            } else {
                element.innerHTML = 'Opening hours not available';
            }
        } catch (error) {
            document.getElementById(elementId).innerHTML = 'Hours information unavailable';
        }
    }
    
    loadOpeningHours('opening-hours-about');
    </script>
</body>
</html>
