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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- Sidebar Logo -->
        <div class="sidebar-logo">
            <div class="logo-icon">
                <img src="../img/logo.png" alt="Logo" style="height: 30px; width: auto; filter: drop-shadow(0 4px 6px rgba(13, 162, 146, 0.2));" onerror="this.onerror=null; this.outerHTML='<i class=\'fas fa-water\'></i>';">
            </div>
            <div>
                <div class="logo-text" style="color: var(--primary);">FleetFlow</div>
                <div class="logo-sub">Command Center</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>pages/dashboard.php" class="nav-item <?= $current_page==='dashboard'?'active':'' ?>">
                <i class="fas fa-tachometer-alt"></i> Command Center
            </a>
            <?php if (in_array($user_role, ['manager', 'dispatcher', 'safety_officer', 'admin'])): ?>
            <a href="<?= BASE_URL ?>pages/vehicles.php" class="nav-item <?= $current_page==='vehicles'?'active':'' ?>">
                <i class="fas fa-truck"></i> Vehicle Registry
            </a>
            <?php endif; ?>
            
            <?php if (in_array($user_role, ['manager', 'dispatcher', 'admin'])): ?>
            <a href="<?= BASE_URL ?>pages/trips.php" class="nav-item <?= $current_page==='trips'?'active':'' ?>">
                <i class="fas fa-route"></i> Trip Dispatcher
            </a>
            <?php endif; ?>
            
            <?php if (in_array($user_role, ['manager', 'safety_officer', 'financial_analyst', 'admin'])): ?>
            <a href="<?= BASE_URL ?>pages/maintenance.php" class="nav-item <?= $current_page==='maintenance'?'active':'' ?>">
                <i class="fas fa-wrench"></i> Maintenance Logs
            </a>
            <?php endif; ?>
            
            <?php if (in_array($user_role, ['manager', 'financial_analyst', 'admin'])): ?>
            <a href="<?= BASE_URL ?>pages/fuel.php" class="nav-item <?= $current_page==='fuel'?'active':'' ?>">
                <i class="fas fa-gas-pump"></i> Fuel & Expenses
            </a>
            <?php endif; ?>
            
            <?php if (in_array($user_role, ['manager', 'dispatcher', 'safety_officer', 'admin'])): ?>
            <a href="<?= BASE_URL ?>pages/drivers.php" class="nav-item <?= $current_page==='drivers'?'active':'' ?>">
                <i class="fas fa-id-card"></i> Driver Profiles
            </a>
            <?php endif; ?>
            
            <?php if (in_array($user_role, ['manager', 'financial_analyst', 'admin'])): ?>
            <a href="<?= BASE_URL ?>pages/analytics.php" class="nav-item <?= $current_page==='analytics'?'active':'' ?>">
                <i class="fas fa-chart-bar"></i> Analytics & Reports
            </a>
            <?php endif; ?>

            <?php if ($user_role === 'admin'): ?>
            <div style="border-top:1px solid rgba(255,255,255,0.1); margin: 10px 0;"></div>
            <a href="<?= BASE_URL ?>pages/users.php" class="nav-item <?= $current_page==='users'?'active':'' ?>" style="color:#fbbf24;">
                <i class="fas fa-users-cog"></i> Admin Control Panel
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <i class="fas fa-user-circle fa-2x"></i>
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
