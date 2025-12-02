<!-- Footer Component -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-logo-column">
                <img src="assets/images/footer-logo.png" alt="Demolition Traders" class="footer-logo">
                <img src="assets/images/footer-tagline.png" alt="Take a look... you'll be surprised" class="footer-tagline">
            </div>
            
            <div class="footer-nav">
                <h4>Browse</h4>
                <a href="index.php">Home</a>
                <a href="shop.php">Shop Now</a>
                <a href="wanted-listing.php">Wanted Listing</a>
                <a href="cabins.php">Cabins</a>
                <a href="faqs.php">FAQs</a>
                <a href="about.php">About Us</a>
                <a href="staff.php">Staff</a>
                <a href="contact.php">Contact Us</a>
                <a href="terms.php">Terms of Use</a>
            </div>
            
            <div class="footer-contact">
                <h4>Opening Hours</h4>
                <p>Mon - Fri 8am to 5pm<br>
                Sat&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;8am to 4pm<br>
                Sun&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Closed<br>
                <strong>Closed Public Holidays</strong></p>
                
                <h4>Contact Us</h4>
                <p>Freephone: <a href="tel:08003366548466">0800 DEMOLITION</a><br>
                Phone: <a href="tel:+6478474989">07 847 4989</a><br>
                Email: <a href="mailto:info@demolitiontraders.co.nz">info@demolitiontraders.co.nz</a></p>
            </div>
            
            <div class="footer-location">
                <h4>Find Us</h4>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3152.807807988!2d175.2598355!3d-37.8072319!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6d6d21fa970b5073%3A0x229ec1a4d67e239a!2sDemolition%20Traders!5e0!3m2!1sen!2snz!4v1234567890" 
                        width="100%" 
                        height="200" 
                        style="border:0; border-radius: 8px;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                <p style="margin-top: 10px;">
                249 Kahikatea Drive, Greenlea Lane<br>
                Frankton, Hamilton<br>
                <a href="https://www.google.com/maps/place/Demolition+Traders/@-37.8072281,175.2449009,6771m/data=!3m1!1e3!4m6!3m5!1s0x6d6d21fa970b5073:0x229ec1a4d67e239a!8m2!3d-37.8072319!4d175.2624104!16s%2Fg%2F1hm6cqmtt?entry=ttu&g_ep=EgoyMDI1MTEyMy4xIKXMDSoASAFQAw%3D%3D" target="_blank">Get Directions »</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Footer Base -->
<div class="footer-base">
    <div class="container">
        <div class="social-links">
            <a href="https://www.facebook.com/profile.php?id=100063449630280#" target="_blank" title="Facebook">
                <i class="fa-brands fa-facebook"></i>
            </a>
            <a href="https://www.instagram.com/demolition_traders/" target="_blank" title="Instagram">
                <i class="fa-brands fa-instagram"></i>
            </a>
        </div>
        <p>© <?php echo date('Y'); ?> Demolition Traders | 
        <a href="cabins.php">Cabins</a> | 
        Powered by Demolition Traders team</p>
    </div>
</div>

<!-- Back to Top Button -->
<button id="backToTop" class="back-to-top" title="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: #ff6b35;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    display: none;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(255,107,53,0.4);
    z-index: 999;
    transition: all 0.3s ease;
}

.back-to-top:hover {
    background: #e55a2b;
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(255,107,53,0.6);
}

.back-to-top.show {
    display: flex;
}
</style>

<script>
// Back to top button functionality
(function() {
    const backToTopBtn = document.getElementById('backToTop');
    
    // Show/hide button on scroll
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });
    
    // Smooth scroll to top
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
})();
</script>
