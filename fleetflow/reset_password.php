<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}

$error = '';
$success = '';
$tokenValid = false;
$userEmail = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $tokenValid = true;
        $userEmail = $res->fetch_assoc()['email'];
    } else {
        $error = "This password reset link is invalid or has expired.";
    }
} else {
    header("Location: index.php");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Hash it
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user
        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed, $userEmail);
        
        if ($update->execute()) {
            // Delete the consumed token
            $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $del->bind_param("s", $token);
            $del->execute();
            
            $success = "Password has been successfully reset! You can now log in.";
            $tokenValid = false; // Hide form
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FleetFlow - Reset Password</title>
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
            <h1 style="color: var(--primary);">Secure Recovery</h1>
            <p>Set a new password for your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
            <div style="text-align:center;margin-top:20px;">
                <a href="index.php" class="btn btn-primary" style="text-decoration:none;display:inline-block;">Return to Login</a>
            </div>
        <?php endif; ?>

        <?php if ($tokenValid): ?>
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-lock"></i> New Password</label>
                <input type="password" name="password" placeholder="Enter new password" required minlength="6" autofocus>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat the password" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary login-btn">
                <i class="fas fa-key"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>
        
        <?php if (!$success && !$tokenValid && $error): ?>
            <div style="text-align:center;margin-top:20px;">
                <a href="index.php" style="color:var(--primary);font-size:13px;">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
