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
        $forgot = true;
        $error = '<span style="color:#4ade80">✓ If this email exists, a reset link has been sent.</span>';
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <div class="icon">🚚</div>
            <h1>FleetFlow</h1>
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
                <input type="email" name="email" placeholder="Enter your email" required value="manager@fleetflow.com">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Enter your password" required value="password">
            </div>
            <button type="submit" class="btn btn-primary login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
            <div style="text-align:right;margin-top:10px;">
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
