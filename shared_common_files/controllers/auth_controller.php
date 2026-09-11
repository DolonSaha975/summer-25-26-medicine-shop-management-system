<?php
// controllers/auth_controller.php — login, register, logout

function loginCtrl($conn) {
    $error        = '';
    $selectedRole = $_POST['login_role'] ?? ($_GET['role'] ?? 'customer');
    if (!in_array($selectedRole, ['admin', 'pharmacist', 'supplier', 'customer'])) $selectedRole = 'customer';

    // Remember Me: only refills the email field, never the password.
    $rememberedEmail = $_COOKIE[REMEMBER_COOKIE] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password']   ?? '';
        $remember = isset($_POST['remember_me']);

        if ($email === '' || $password === '') {
            $error = 'Please fill in all fields.';
        } else {
            $user = getUserByEmail($conn, $email);
            // Same message either way so nobody can tell a valid email from an invalid one.
            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['role'] !== $selectedRole) {
                    $error = 'This account is not registered as ' . ucfirst($selectedRole) . '. Please choose the correct portal above.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id'    => $user['id'],
                        'name'  => $user['name'],
                        'email' => $user['email'],
                        'role'  => $user['role'],
                    ];
                    $_SESSION['last_active'] = time();

                    if ($remember) {
                        setcookie(REMEMBER_COOKIE, $email, time() + 60 * 60 * 24 * 30, '/', '', false, true);
                    } else {
                        setcookie(REMEMBER_COOKIE, '', time() - 3600, '/');
                    }
                    redirectTo('index.php?page=' . roleHomePage());
                }
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
    require 'views/auth/login.php';
}

function registerCtrl($conn) {
    $error   = '';
    $success = '';
    $initialRole = $_GET['role'] ?? 'customer';
    if (!in_array($initialRole, ['customer', 'pharmacist', 'supplier'])) $initialRole = 'customer';
    $old = ['name' => '', 'email' => '', 'phone' => '', 'address' => '', 'role' => $initialRole];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();

        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        $address  = trim($_POST['address']  ?? '');
        $role     = trim($_POST['role']     ?? 'customer');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['confirm']       ?? '';
        $old      = compact('name', 'email', 'phone', 'address', 'role');

        // Nobody can sign up as admin — checked again here even though the
        // dropdown only offers the other three roles.
        if (!in_array($role, ['customer', 'pharmacist', 'supplier'])) $role = 'customer';

        if ($name === '' || $email === '' || $password === '') {
            $error = 'Name, email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (getUserByEmail($conn, $email)) {
            $error = 'Email is already registered.';
        } else {
            if (registerUser($conn, $name, $email, $password, $role, $phone, $address)) {
                $success = 'Account created! You can now log in.';
                $old     = ['name' => '', 'email' => '', 'phone' => '', 'address' => '', 'role' => 'customer'];
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
    require 'views/auth/register.php';
}

function logoutCtrl() {
    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    redirectTo('index.php?page=login');
}
?>
