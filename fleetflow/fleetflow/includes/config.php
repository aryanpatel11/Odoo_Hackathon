<?php
// FleetFlow - Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fleetflow');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;max-width:600px;margin:50px auto;">
        <h2 style="color:#856404;">⚠️ Database Connection Failed</h2>
        <p><strong>Error:</strong> ' . $conn->connect_error . '</p>
        <p>Please make sure:</p>
        <ul>
            <li>XAMPP is running (Apache + MySQL)</li>
            <li>You have imported <code>fleetflow.sql</code> into phpMyAdmin</li>
            <li>Database name is <code>fleetflow</code></li>
        </ul>
    </div>');
}

$conn->set_charset("utf8mb4");

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check function
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

function requireRole($roles) {
    if (!in_array($_SESSION['user_role'], (array)$roles)) {
        header('Location: ' . BASE_URL . 'pages/dashboard.php');
        exit();
    }
}

define('BASE_URL', '/fleetflow/');
?>
