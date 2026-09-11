<?php
// config/config.php — database connection, session settings, app constants

// Settings you can change:
define('LOW_STOCK_DEFAULT', 10);     // a medicine at/below this shows the low-stock alert
define('SESSION_TIMEOUT',   1800);   // idle sign-out, in seconds (30 minutes)
define('REMEMBER_COOKIE',   'medishop_remember'); // cookie name for "remember me"

// Session cookie hardening — set BEFORE session_start() in index.php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

$conn = mysqli_connect('localhost', 'root', '', 'medicine_shop');
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

// The admin account is created automatically the first time a page loads.
// Everyone else (pharmacist, supplier, customer) signs up on the register page.
function seedAdminAccount($conn) {
    $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    mysqli_stmt_execute($chk);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0;
    mysqli_stmt_close($chk);

    if (!$exists) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $st = mysqli_prepare($conn,
            "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
        $name  = 'Administrator';
        $email = 'admin@medicine.com';
        mysqli_stmt_bind_param($st, 'sss', $name, $email, $hash);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    }
}
seedAdminAccount($conn);
?>
