<?php
// helpers/helpers.php — small utility functions used everywhere:
// esc(), CSRF tokens, login/role guards, flash messages, session timeout.

function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function redirectTo($url) {
    header('Location: ' . $url);
    exit;
}

// ---------- Login guards ----------

function isLoggedIn()   { return isset($_SESSION['user']); }
function isAdmin()      { return isLoggedIn() && $_SESSION['user']['role'] === 'admin'; }
function isPharmacist() { return isLoggedIn() && $_SESSION['user']['role'] === 'pharmacist'; }
function isSupplier()   { return isLoggedIn() && $_SESSION['user']['role'] === 'supplier'; }
function isCustomer()   { return isLoggedIn() && $_SESSION['user']['role'] === 'customer'; }

// Used by index.php to send a role to the wrong dashboard before the
// controller even runs.
function require_role($role) {
    if (!isLoggedIn() || $_SESSION['user']['role'] !== $role) {
        redirectTo('index.php?page=login');
    }
}

function roleHomePage() {
    if (!isLoggedIn()) return 'login';
    switch ($_SESSION['user']['role']) {
        case 'admin':      return 'admin';
        case 'pharmacist': return 'pharmacist';
        case 'supplier':   return 'supplier';
        default:           return 'home';
    }
}

// ---------- CSRF ----------
// A token is stored in the session once, printed into every form as a
// hidden field, and appended to every delete/approve/reject link. Every
// POST handler (and every GET action that changes data) checks it before
// doing anything.

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

// Call this at the top of any action that changes data. It reads the
// token from POST first, then GET, so it works for both forms and links.
function csrf_verify() {
    $sent = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(400);
        die('Security check failed (invalid or expired form token). Go back and try again.');
    }
}

// ---------- Session timeout ----------
// Signs the user out automatically after SESSION_TIMEOUT seconds of
// inactivity. Called once from index.php before routing.

function check_session_timeout() {
    if (!isLoggedIn()) return;
    if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        redirectTo('index.php?page=login&msg=timeout');
    }
    $_SESSION['last_active'] = time();
}

// ---------- Flash messages ----------
// One-time messages that survive a single redirect (e.g. "Item added").

function flash_set($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function flash_get($key) {
    if (empty($_SESSION['flash'][$key])) return null;
    $msg = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ---------- Image upload ----------

function handleImageUpload($fieldName, $uploadDir = 'uploads/medicines/') {
    if (empty($_FILES[$fieldName]['name'])) return null;
    $file = $_FILES[$fieldName];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 2 * 1024 * 1024) return null; // 2MB max
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('med_', true) . '.' . $ext;
    $dest     = $uploadDir . $filename;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dest)) return $dest;
    return null;
}
?>
