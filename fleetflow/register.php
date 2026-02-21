<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $msg = ['danger', 'Passwords do not match.'];
    } elseif (strlen($password) < 6) {
        $msg = ['danger', 'Password must be at least 6 characters.'];
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $msg = ['danger', 'An account with this email already exists.'];
        } else {
            // New users get the 'pending' role. An Admin must assign a real role later.
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role = 'pending';
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed, $role);
            
            if ($stmt->execute()) {
                $msg = ['success', 'Registration successful! Your account is pending admin approval. You cannot log in yet.'];
            } else {
                $msg = ['danger', 'Database error: ' . $stmt->error];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FleetFlow - Register</title>
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
            <div class="icon">🚚</div>
            <h1>FleetFlow Registration</h1>
            <p>Create a staff account to access the platform</p>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg[0] ?>">
                <i class="fas fa-<?= $msg[0] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $msg[1] ?>
            </div>
            <?php if ($msg[0] === 'success'): ?>
                <div style="text-align:center;margin-top:20px;">
                    <a href="index.php" class="btn btn-primary" style="text-decoration:none;display:inline-block;">Return to Login</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$msg || $msg[0] !== 'success'): ?>
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="John Doe" required autofocus>
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" placeholder="john@company.com" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Create a strong password" required minlength="6">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat your password" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary login-btn">
                <i class="fas fa-user-plus"></i> Register
            </button>
            <div style="text-align:center;margin-top:15px;font-size:13px;">
                Already have an account? <a href="index.php" style="color:var(--primary);text-decoration:none;">Sign In Here</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
