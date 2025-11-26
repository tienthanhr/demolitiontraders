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
    <title>Customers Management - Demolition Traders</title>
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

<!-- Customers Management Content -->
<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">All Customers</h2>
        <button class="btn btn-primary" onclick="loadCustomers()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>

    <div class="search-box">
        <input type="text" id="search-customers" placeholder="Search customers by name, email..." onkeyup="searchCustomers()">
    </div>

    <div class="table-container">
        <table id="customers-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Registered</th>
                    <th>Orders</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customers-tbody">
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;">
                        <p>Customer management coming soon...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Placeholder - will implement later
function loadCustomers() {
    console.log('Loading customers...');
}

function searchCustomers() {
    console.log('Searching customers...');
}
</script>
        </main>
    </div>
</body>
</html>
