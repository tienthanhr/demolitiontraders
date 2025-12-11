<?php
require_once '../frontend/config.php';

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    header('Location: ' . $protocol . $host . BASE_PATH . 'admin-login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>admin/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/api-helper.js"></script>
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
            <button class="btn btn-primary" onclick="window.location.href='<?php echo BASE_PATH; ?>'" style="padding: 15px;">
                <i class="fas fa-home"></i><br>View Website
            </button>
            <button class="btn btn-success" onclick="exportProducts()" style="padding: 15px;">
                <i class="fas fa-download"></i><br>Export Products
            </button>
            <button class="btn btn-info" onclick="openImportModal()" style="padding: 15px;">
                <i class="fas fa-upload"></i><br>Import Products
            </button>
            <button class="btn btn-warning" onclick="printReport()" style="padding: 15px;">
                <i class="fas fa-print"></i><br>Print Report
            </button>
            <button class="btn btn-danger" type="button" onclick="logoutConfirm(); return false;" style="padding: 15px;">
                <i class="fas fa-sign-out-alt"></i><br>Logout
            </button>
        </div>
    </div>
</div>

<!-- Import Products Modal -->
<div id="importModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Import Products from MySQL</h3>
            <span class="close" onclick="closeImportModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p style="color: #666; margin-bottom: 20px;">
                This will import all products from your MySQL database to the Render PostgreSQL database.
            </p>
            
            <div class="form-group">
                <label>Import Status:</label>
                <div id="importStatus" style="padding: 15px; background: #f5f5f5; border-radius: 5px; min-height: 100px; font-family: monospace; font-size: 13px; overflow-y: auto; max-height: 300px;">
                    Ready to import...
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn btn-success" onclick="startImport()" id="importBtn">
                    <i class="fas fa-upload"></i> Start Import
                </button>
                <button class="btn btn-secondary" onclick="closeImportModal()">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function logoutConfirm() {
    console.log('logoutConfirm called');
    const result = await showConfirm('Are you sure you want to logout?', 'Logout', true);
    console.log('confirm result:', result);
    if (result === true) {
        console.log('Redirecting to logout...');
        // Clear session via logout.php then redirect to admin-login
        await fetch('<?php echo BASE_PATH; ?>logout.php?admin=1');
        window.location.href = '<?php echo BASE_PATH; ?>admin-login';
    } else {
        console.log('Logout cancelled');
    }
    return false;
}

// Load opening hours from Google Places API
async function loadOpeningHours() {
    try {
        const response = await fetch(getApiUrl('/api/opening-hours.php'));
        const responseText = await response.text();
        const data = JSON.parse(responseText);
        
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
        const productsRes = await fetch(getApiUrl('/api/index.php?request=products&per_page=1'));
        const productsData = await productsRes.json();
        document.getElementById('stats-products').textContent = productsData.pagination?.total || 0;

        const categoriesRes = await fetch(getApiUrl('/api/index.php?request=categories'));
        const categoriesData = await categoriesRes.json();
        document.getElementById('stats-categories').textContent = (categoriesData.data || categoriesData).length;

        const ordersRes = await fetch(getApiUrl('/api/index.php?request=orders'));
        const ordersData = await ordersRes.json();
        document.getElementById('stats-orders').textContent = ordersData.length || 0;
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

function syncIdealPOS() {
    if (confirm('Sync products with IdealPOS? This may take a few minutes.')) {
        alert('Syncing... (Feature under development)');
    }
}

async function clearCache() {
    const confirmed = await showConfirm(
        'Are you sure you want to clear the website cache?',
        'Clear Cache',
        false
    );
    
    if (!confirmed) return;
    
    try {
        const response = await fetch(getApiUrl('/api/admin/clear-cache.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const responseText = await response.text();
        const result = JSON.parse(responseText);
        
        if (result.success) {
            showSuccess(`Cache cleared successfully!\n${result.files_deleted} files deleted (${result.size_freed})`);
        } else {
            showError('Failed to clear cache: ' + result.message);
        }
    } catch (error) {
        console.error('Error clearing cache:', error);
        showError('Server error while clearing cache');
    }
}

function exportProducts() {
    // Open export URL in new window to download CSV
    showInfo('Preparing products export...');
    window.location.href = getApiUrl('/api/admin/export-products.php');
}

function openImportModal() {
    document.getElementById('importModal').style.display = 'flex';
    document.getElementById('importStatus').innerHTML = 'Ready to import...';
    document.getElementById('importBtn').disabled = false;
}

function closeImportModal() {
    document.getElementById('importModal').style.display = 'none';
}

async function startImport() {
    const statusDiv = document.getElementById('importStatus');
    const importBtn = document.getElementById('importBtn');
    
    importBtn.disabled = true;
    statusDiv.innerHTML = '<span style="color: blue;">⏳ Starting import...</span><br>';
    
    try {
        // Call the import API with secret key
        const response = await fetch('https://demolitiontraders.onrender.com/database/import-via-api.php?secret=demo2024secure', {
            method: 'POST'
        });
        
        const text = await response.text();
        
        // Parse response
        let lines = text.split('\n');
        let html = '';
        
        for (let line of lines) {
            if (line.trim()) {
                if (line.includes('✓') || line.includes('SUCCESS')) {
                    html += '<span style="color: green;">' + line + '</span><br>';
                } else if (line.includes('✗') || line.includes('ERROR') || line.includes('Failed')) {
                    html += '<span style="color: red;">' + line + '</span><br>';
                } else if (line.includes('⏳') || line.includes('Starting')) {
                    html += '<span style="color: blue;">' + line + '</span><br>';
                } else {
                    html += line + '<br>';
                }
            }
        }
        
        statusDiv.innerHTML = html;
        
        if (response.ok) {
            showSuccess('Import completed! Check status above for details.');
        } else {
            showError('Import completed with some errors. Check status above.');
        }
        
    } catch (error) {
        statusDiv.innerHTML += '<br><span style="color: red;">✗ Error: ' + error.message + '</span>';
        showError('Import failed: ' + error.message);
    } finally {
        importBtn.disabled = false;
    }
}

function printReport() {
    // Open print report in new window
    window.open('/demolitiontraders/frontend/admin/print-report.php', '_blank');
}

// Initialize
loadOpeningHours();
loadSettingsStats();
</script>
        </main>
    </div>
    <?php include '../components/toast-notification.php'; ?>
</body>
</html>
