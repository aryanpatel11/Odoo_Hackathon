<?php
// FleetFlow Header
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'dispatcher';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FleetFlow - <?= ucfirst(str_replace('_', ' ', $current_page)) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">🚚</div>
            <div>
                <div class="logo-text">FleetFlow</div>
                <div class="logo-sub">Logistics System</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>pages/dashboard.php" class="nav-item <?= $current_page==='dashboard'?'active':'' ?>">
                <i class="fas fa-tachometer-alt"></i> Command Center
            </a>
            <a href="<?= BASE_URL ?>pages/vehicles.php" class="nav-item <?= $current_page==='vehicles'?'active':'' ?>">
                <i class="fas fa-truck"></i> Vehicle Registry
            </a>
            <a href="<?= BASE_URL ?>pages/trips.php" class="nav-item <?= $current_page==='trips'?'active':'' ?>">
                <i class="fas fa-route"></i> Trip Dispatcher
            </a>
            <a href="<?= BASE_URL ?>pages/maintenance.php" class="nav-item <?= $current_page==='maintenance'?'active':'' ?>">
                <i class="fas fa-wrench"></i> Maintenance Logs
            </a>
            <a href="<?= BASE_URL ?>pages/fuel.php" class="nav-item <?= $current_page==='fuel'?'active':'' ?>">
                <i class="fas fa-gas-pump"></i> Fuel & Expenses
            </a>
            <a href="<?= BASE_URL ?>pages/drivers.php" class="nav-item <?= $current_page==='drivers'?'active':'' ?>">
                <i class="fas fa-id-card"></i> Driver Profiles
            </a>
            <a href="<?= BASE_URL ?>pages/analytics.php" class="nav-item <?= $current_page==='analytics'?'active':'' ?>">
                <i class="fas fa-chart-bar"></i> Analytics & Reports
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user_name,0,1)) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
                    <div class="user-role"><?= ucfirst(str_replace('_',' ',$user_role)) ?></div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>
    <!-- Main Content -->
    <main class="main-content">
