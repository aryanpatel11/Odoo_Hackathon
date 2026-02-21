<?php
require_once 'includes/config.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}

$error = '';
$forgot = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['forgot'])) {
        $email = trim($_POST['email'] ?? '');
        $forgot = true;
        $error = '<span style="color:var(--success)">✓ If this email exists, a reset link has been sent.</span>';
        
        if ($email) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store in DB
                $insert = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                $insert->bind_param("sss", $email, $token, $expires);
                $insert->execute();
                
                // Simulate Email Delivery
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $base_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $resetLink = $protocol . $host . $base_dir . "/reset_password.php?token=" . $token;
                
                $error .= "<br><br><div style='padding:12px; background:rgba(13, 162, 146, 0.05); border-radius:8px; border:1px solid var(--primary); font-size:13px;'><strong style='color:var(--primary)'><i class='fas fa-info-circle'></i> Local Env Simulation:</strong><br>Normally this link is emailed. Since we have no SMTP server, click here to reset:<br><a href='{$resetLink}' style='color:var(--primary); font-weight:600; word-break:break-all;'>{$resetLink}</a></div>";
            }
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: pages/dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FleetFlow - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <div class="icon" style="margin-bottom: 20px;">
                <img src="img/logo.png" alt="FleetFlow Logo" style="height: 60px; width: auto; display: block; margin: 0 auto; filter: drop-shadow(0 10px 15px rgba(13, 162, 146, 0.2));" onerror="this.onerror=null; this.outerHTML='<i class=\'fas fa-water\' style=\'font-size: 50px; color: var(--primary); filter: drop-shadow(0 0 10px rgba(13, 162, 146, 0.3));\'></i>';">
            </div>
            <h1 style="color: var(--primary);">FleetFlow</h1>
            <p>Modular Fleet & Logistics Management</p>
        </div>

        <?php if ($error): ?>
            <div class="alert <?= str_contains($error,'✓')?'alert-success':'alert-danger' ?>">
                <i class="fas fa-<?= str_contains($error,'✓')?'check-circle':'exclamation-circle' ?>"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (!$forgot): ?>
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required autofocus>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
            <div style="display:flex; justify-content:space-between; margin-top:10px; font-size:13px;">
                <a href="register.php" style="color:var(--primary);text-decoration:none;">Create an Account</a>
                <button type="submit" name="forgot" value="1" style="background:none;border:none;color:var(--primary);font-size:13px;cursor:pointer;">
                    Forgot Password?
                </button>
            </div>
        </form>
        <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" placeholder="Enter your registered email" required>
            </div>
            <button type="submit" name="forgot" value="1" class="btn btn-primary login-btn">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
            <div style="text-align:center;margin-top:12px;">
                <a href="index.php" style="color:var(--primary);font-size:13px;">← Back to Login</a>
            </div>
        </form>
        <?php endif; ?>

        <div class="login-hint">
            <strong>Demo Credentials:</strong><br>
            Manager: <code>manager@fleetflow.com</code> / <code>password</code><br>
            Dispatcher: <code>dispatcher@fleetflow.com</code> / <code>password</code>
        </div>
    </div>
</div>
<script src="js/app.js"></script>
</body>
</html>
