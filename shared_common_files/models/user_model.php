<?php
// models/user_model.php — everything that touches the users table
// (all 4 roles live in one table, same as the reference project)

function getUserByEmail($conn, $email) {
    $st = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $email);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function getUserById($conn, $id) {
    $st = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

// Self-registration for the three non-admin roles. $role must already be
// validated by the controller against ['customer','pharmacist','supplier'].
function registerUser($conn, $name, $email, $password, $role, $phone, $address) {
    if (getUserByEmail($conn, $email)) return false; // duplicate email
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = mysqli_prepare($conn,
        "INSERT INTO users (name, email, password_hash, role, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($st, 'ssssss', $name, $email, $hash, $role, $phone, $address);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function updateUserProfile($conn, $id, $name, $phone, $address, $picturePath = null) {
    if ($picturePath !== null) {
        $st = mysqli_prepare($conn,
            "UPDATE users SET name=?, phone=?, address=?, profile_picture=? WHERE id=?");
        mysqli_stmt_bind_param($st, 'ssssi', $name, $phone, $address, $picturePath, $id);
    } else {
        $st = mysqli_prepare($conn,
            "UPDATE users SET name=?, phone=?, address=? WHERE id=?");
        mysqli_stmt_bind_param($st, 'sssi', $name, $phone, $address, $id);
    }
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// ---------- Admin: customer accounts ----------

function getAllCustomers($conn) {
    $r = mysqli_query($conn, "SELECT id, name, email, phone, address, created_at FROM users WHERE role='customer' ORDER BY id DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchCustomers($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT id, name, email, phone, address, created_at FROM users
         WHERE role='customer' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)
         ORDER BY id DESC");
    mysqli_stmt_bind_param($st, 'sss', $like, $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function deleteUser($conn, $id) {
    $st = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role='customer'");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// ---------- Admin: pharmacist / supplier staff accounts ----------
// Kept for cases where an admin wants to add a staff account directly,
// in addition to pharmacists/suppliers being able to self-register.

function getAllStaffByRole($conn, $role) {
    $st = mysqli_prepare($conn,
        "SELECT id, name, email, phone, created_at FROM users WHERE role = ? ORDER BY id DESC");
    mysqli_stmt_bind_param($st, 's', $role);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function searchStaffByRole($conn, $role, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT id, name, email, phone, created_at FROM users
         WHERE role = ? AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)
         ORDER BY id DESC");
    mysqli_stmt_bind_param($st, 'ssss', $role, $like, $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function addStaffUser($conn, $name, $email, $password, $role, $phone) {
    if (getUserByEmail($conn, $email)) return false;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = mysqli_prepare($conn,
        "INSERT INTO users (name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($st, 'sssss', $name, $email, $hash, $role, $phone);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function updateStaffUser($conn, $id, $name, $phone, $role) {
    $st = mysqli_prepare($conn, "UPDATE users SET name=?, phone=? WHERE id=? AND role=?");
    mysqli_stmt_bind_param($st, 'ssis', $name, $phone, $id, $role);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function deleteStaffUser($conn, $id, $role) {
    $st = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role=?");
    mysqli_stmt_bind_param($st, 'is', $id, $role);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function getStaffById($conn, $id, $role) {
    $st = mysqli_prepare($conn, "SELECT * FROM users WHERE id=? AND role=?");
    mysqli_stmt_bind_param($st, 'is', $id, $role);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}
?>
