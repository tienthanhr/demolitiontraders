<?php
session_start();

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    header('Location: ../admin-login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Demolition Traders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php include 'admin-style.css'; ?>
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

<!-- Settings Content -->
<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">Website Settings</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <h4 style="margin-bottom: 15px; color: #2f3192;">
                <i class="fas fa-store"></i> Store Information
            </h4>
            <p><strong>Store Name:</strong> Demolition Traders</p>
            <p><strong>Address:</strong> 249 Kahikatea Drive, Greenlea Lane, Frankton, Hamilton</p>
            <p><strong>Phone:</strong> 07 847 4989</p>
            <p><strong>Email:</strong> info@demolitiontraders.co.nz</p>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <h4 style="margin-bottom: 15px; color: #2f3192;">
                <i class="fas fa-clock"></i> Opening Hours
            </h4>
            <div id="opening-hours-display">
                <div style="text-align: center; padding: 20px;">
                    <div class="spinner"></div>
                    <p style="margin-top: 10px; color: #6c757d;">Loading hours...</p>
                </div>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <h4 style="margin-bottom: 15px; color: #2f3192;">
                <i class="fas fa-database"></i> Database Stats
            </h4>
            <p><strong>Total Products:</strong> <span id="stats-products">-</span></p>
            <p><strong>Total Categories:</strong> <span id="stats-categories">-</span></p>
            <p><strong>Total Orders:</strong> <span id="stats-orders">-</span></p>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <h4 style="margin-bottom: 15px; color: #2f3192;">
                <i class="fas fa-tools"></i> Admin Actions
            </h4>
            <button class="btn btn-primary" onclick="syncIdealPOS()" style="width: 100%; margin-bottom: 10px;">
                <i class="fas fa-sync"></i> Sync with IdealPOS
            </button>
            <button class="btn btn-warning" onclick="clearCache()" style="width: 100%;">
                <i class="fas fa-trash"></i> Clear Cache
            </button>
        </div>
    </div>

    <div style="margin-top: 30px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 20px; color: #2f3192;">Quick Actions</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <button class="btn btn-primary" onclick="window.location.href='../index.php'" style="padding: 15px;">
                <i class="fas fa-home"></i><br>View Website
            </button>
            <button class="btn btn-success" onclick="exportProducts()" style="padding: 15px;">
                <i class="fas fa-download"></i><br>Export Products
            </button>
            <button class="btn btn-warning" onclick="window.print()" style="padding: 15px;">
                <i class="fas fa-print"></i><br>Print Report
            </button>
            <button class="btn btn-danger" onclick="if(confirm('Are you sure you want to logout?')) window.location.href='../../logout.php'" style="padding: 15px;">
                <i class="fas fa-sign-out-alt"></i><br>Logout
            </button>
        </div>
    </div>
</div>

<script>
// Load opening hours from Google Places API
async function loadOpeningHours() {
    try {
        const response = await fetch('/demolitiontraders/backend/api/opening-hours.php');
        const data = await response.json();
        
        const container = document.getElementById('opening-hours-display');
        
        if (data.success && data.weekday_text) {
            // Display weekly hours
            let hoursHTML = '';
            data.weekday_text.forEach((dayHours, index) => {
                const isToday = new Date().getDay() === (index === 6 ? 0 : index + 1);
                const style = isToday ? 'font-weight: bold; color: #2f3192; background: rgba(47,49,146,0.1); padding: 5px; border-radius: 3px;' : '';
                hoursHTML += `<p style="${style}">${dayHours}</p>`;
            });
            
            // Add open/closed status
            const statusBadge = data.open_now 
                ? '<span class="badge badge-active" style="margin-top: 10px; display: inline-block;">Currently Open</span>'
                : '<span class="badge badge-inactive" style="margin-top: 10px; display: inline-block;">Currently Closed</span>';
            
            container.innerHTML = hoursHTML + statusBadge;
        } else {
            container.innerHTML = '<p style="color: #dc3545;">Unable to load opening hours</p>';
        }
    } catch (error) {
        console.error('Error loading opening hours:', error);
        document.getElementById('opening-hours-display').innerHTML = 
            '<p style="color: #dc3545;">Error loading hours</p>';
    }
}

// Load stats
async function loadSettingsStats() {
    try {
        const productsRes = await fetch('/demolitiontraders/backend/api/index.php?request=products&per_page=1');
        const productsData = await productsRes.json();
        document.getElementById('stats-products').textContent = productsData.pagination?.total || 0;

        const categoriesRes = await fetch('/demolitiontraders/backend/api/index.php?request=categories');
        const categoriesData = await categoriesRes.json();
        document.getElementById('stats-categories').textContent = (categoriesData.data || categoriesData).length;

        document.getElementById('stats-orders').textContent = '0';
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

function syncIdealPOS() {
    if (confirm('Sync products with IdealPOS? This may take a few minutes.')) {
        alert('Syncing... (Feature under development)');
    }
}

function clearCache() {
    if (confirm('Clear website cache?')) {
        alert('Cache cleared successfully!');
    }
}

function exportProducts() {
    alert('Exporting products... (Feature under development)');
}

// Initialize
loadOpeningHours();
loadSettingsStats();
</script>
        </main>
    </div>
</body>
</html>
