<?php
// controllers/admin_controller.php — request handling for the admin dashboard

function adminDashCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    $totalMedicines = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM medicines"))['c'] ?? 0);
    $totalCustomers = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='customer'"))['c'] ?? 0);
    $totalOrders    = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders"))['c'] ?? 0);
    $pendingOrders  = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE status='pending'"))['c'] ?? 0);
    $pendingReviews = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM reviews WHERE status='pending'"))['c'] ?? 0);
    $newFeedback    = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM feedback WHERE status='new'"))['c'] ?? 0);
    $expiringCount  = count(getExpiringMedicines($conn, 60));
    $damagedCount   = count(getDamagedMedicines($conn));
    $recentOrders   = array_slice(getAllOrders($conn), 0, 5);

    require 'views/admin/dashboard.php';
}

function adminMedicinesCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    $action     = $_GET['action'] ?? 'list';
    $error      = '';
    $editing    = null;
    $categories = getAllCategories($conn);

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $name     = trim($_POST['name']         ?? '');
        $catId    = intval($_POST['category_id'] ?? 0);
        $vendor   = trim($_POST['vendor_name']  ?? '');
        $price    = trim($_POST['price']        ?? '');
        $stock    = trim($_POST['availability'] ?? '');
        $desc     = trim($_POST['description']  ?? '');
        $expiry   = trim($_POST['expiry_date']  ?? '') ?: null;
        $lowStock = trim($_POST['low_stock_threshold'] ?? '') !== '' ? intval($_POST['low_stock_threshold']) : LOW_STOCK_DEFAULT;
        $reqRx    = isset($_POST['requires_prescription']) ? 1 : 0;

        if ($name === '' || $vendor === '' || $price === '' || $stock === '' || $catId === 0) {
            $error = 'All fields except image and description are required.';
        } elseif (!is_numeric($price) || floatval($price) < 0) {
            $error = 'Price must be a valid non-negative number.';
        } elseif (!ctype_digit($stock) || intval($stock) < 0) {
            $error = 'Stock must be a non-negative whole number.';
        } else {
            $imgPath = handleImageUpload('image') ?? '';
            if (addMedicine($conn, $name, $catId, $vendor, floatval($price), intval($stock), $desc, $imgPath,
                             $expiry, $lowStock, $reqRx)) {
                redirectTo('index.php?page=admin_medicines&msg=added');
            } else {
                $error = 'Failed to add medicine.';
            }
        }
    }

    if ($action === 'edit') {
        $editing = getMedicineById($conn, intval($_GET['id'] ?? 0));
        if (!$editing) redirectTo('index.php?page=admin_medicines');
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id       = intval($_GET['id']          ?? 0);
        $name     = trim($_POST['name']         ?? '');
        $catId    = intval($_POST['category_id'] ?? 0);
        $vendor   = trim($_POST['vendor_name']  ?? '');
        $price    = trim($_POST['price']        ?? '');
        $stock    = trim($_POST['availability'] ?? '');
        $desc     = trim($_POST['description']  ?? '');
        $expiry   = trim($_POST['expiry_date']  ?? '') ?: null;
        $lowStock = trim($_POST['low_stock_threshold'] ?? '') !== '' ? intval($_POST['low_stock_threshold']) : LOW_STOCK_DEFAULT;
        $reqRx    = isset($_POST['requires_prescription']) ? 1 : 0;

        if ($name === '' || $vendor === '' || $price === '' || $stock === '' || $catId === 0) {
            $error   = 'All required fields must be filled.';
            $editing = getMedicineById($conn, $id);
        } elseif (!is_numeric($price) || floatval($price) < 0) {
            $error   = 'Invalid price.';
            $editing = getMedicineById($conn, $id);
        } elseif (!ctype_digit($stock) || intval($stock) < 0) {
            $error   = 'Invalid stock.';
            $editing = getMedicineById($conn, $id);
        } else {
            $imgPath = handleImageUpload('image');
            if (updateMedicine($conn, $id, $name, $catId, $vendor, floatval($price), intval($stock), $desc, $imgPath,
                                $expiry, $lowStock, $reqRx)) {
                redirectTo('index.php?page=admin_medicines&msg=updated');
            } else {
                $error   = 'Update failed.';
                $editing = getMedicineById($conn, $id);
            }
        }
    }

    if ($action === 'delete') {
        csrf_verify();
        $id  = intval($_GET['id'] ?? 0);
        $med = getMedicineById($conn, $id);
        if ($med && $med['image_path'] && file_exists($med['image_path'])) unlink($med['image_path']);
        if ($id > 0) deleteMedicine($conn, $id);
        redirectTo('index.php?page=admin_medicines&msg=deleted');
    }

    $q = trim($_GET['q'] ?? '');
    $medicines = $q === '' ? getAllMedicines($conn) : searchMedicines($conn, $q);
    require 'views/admin/medicines.php';
}

function adminCategoriesCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    $action  = $_GET['action'] ?? 'list';
    $error   = '';
    $editing = null;

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['category_type'] ?? '');
        if ($name === '' || !in_array($type, ['liquid', 'solid'])) {
            $error = 'Category name and valid type (liquid/solid) are required.';
        } else {
            if (addCategory($conn, $name, $type)) redirectTo('index.php?page=admin_categories&msg=added');
            else $error = 'Failed to add category.';
        }
    }

    if ($action === 'edit') {
        $editing = getCategoryById($conn, intval($_GET['id'] ?? 0));
        if (!$editing) redirectTo('index.php?page=admin_categories');
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id   = intval($_GET['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['category_type'] ?? '');
        if ($name === '' || !in_array($type, ['liquid', 'solid'])) {
            $error   = 'All fields are required and type must be liquid or solid.';
            $editing = getCategoryById($conn, $id);
        } else {
            if (updateCategory($conn, $id, $name, $type)) redirectTo('index.php?page=admin_categories&msg=updated');
            else { $error = 'Update failed.'; $editing = getCategoryById($conn, $id); }
        }
    }

    if ($action === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteCategory($conn, $id);
        redirectTo('index.php?page=admin_categories&msg=deleted');
    }

    $categories = getAllCategories($conn);
    require 'views/admin/categories.php';
}

function adminCustomersCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    if (($_GET['action'] ?? '') === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteUser($conn, $id);
        redirectTo('index.php?page=admin_customers&msg=deleted');
    }

    $q = trim($_GET['q'] ?? '');
    $customers = $q === '' ? getAllCustomers($conn) : searchCustomers($conn, $q);
    require 'views/admin/customers.php';
}

function adminOrdersCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    $action = $_GET['action'] ?? '';
    if (in_array($action, ['accept', 'reject'])) {
        csrf_verify();
        $id     = intval($_GET['id'] ?? 0);
        $status = $action === 'accept' ? 'accepted' : 'rejected';
        if ($id > 0) updateOrderStatus($conn, $id, $status);
        redirectTo('index.php?page=admin_orders&msg=' . $status);
    }

    $q = trim($_GET['q'] ?? '');
    $orders = $q === '' ? getAllOrders($conn) : searchOrders($conn, $q);
    require 'views/admin/orders.php';
}

function adminReviewsCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    $action = $_GET['action'] ?? '';
    if (in_array($action, ['approve', 'reject'])) {
        csrf_verify();
        $id     = intval($_GET['id'] ?? 0);
        $status = $action === 'approve' ? 'approved' : 'rejected';
        if ($id > 0) updateReviewStatus($conn, $id, $status);
        redirectTo('index.php?page=admin_reviews&msg=' . $status);
    }
    if ($action === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteReview($conn, $id);
        redirectTo('index.php?page=admin_reviews&msg=deleted');
    }

    $q = trim($_GET['q'] ?? '');
    $reviews = $q === '' ? getAllReviews($conn) : searchReviews($conn, $q);
    require 'views/admin/reviews.php';
}

function adminFeedbackCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    $action   = $_GET['action'] ?? 'list';
    $error    = '';
    $replying = null;

    if ($action === 'reply') {
        $id = intval($_GET['id'] ?? 0);
        foreach (getAllFeedback($conn) as $f) { if ($f['id'] == $id) { $replying = $f; break; } }
        if (!$replying) redirectTo('index.php?page=admin_feedback');
    }

    if ($action === 'send_reply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id    = intval($_GET['id'] ?? 0);
        $reply = trim($_POST['admin_reply'] ?? '');
        if ($reply === '') {
            $error = 'Reply message cannot be empty.';
            foreach (getAllFeedback($conn) as $f) { if ($f['id'] == $id) { $replying = $f; break; } }
        } else {
            replyFeedback($conn, $id, $reply, 'resolved');
            redirectTo('index.php?page=admin_feedback&msg=replied');
        }
    }

    if ($action === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteFeedback($conn, $id);
        redirectTo('index.php?page=admin_feedback&msg=deleted');
    }

    $q = trim($_GET['q'] ?? '');
    $feedbackList = $q === '' ? getAllFeedback($conn) : searchFeedback($conn, $q);
    require 'views/admin/feedback.php';
}

function adminExpiryCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    $action = $_GET['action'] ?? '';

    if ($action === 'mark_damaged' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id    = intval($_GET['id'] ?? 0);
        $notes = trim($_POST['damage_notes'] ?? '');
        if ($id > 0) markMedicineDamaged($conn, $id, $notes);
        redirectTo('index.php?page=admin_expiry&msg=marked');
    }
    if ($action === 'clear_damaged') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) clearMedicineDamaged($conn, $id);
        redirectTo('index.php?page=admin_expiry&msg=cleared');
    }
    if ($action === 'delete') {
        csrf_verify();
        $id  = intval($_GET['id'] ?? 0);
        $med = getMedicineById($conn, $id);
        if ($med && $med['image_path'] && file_exists($med['image_path'])) unlink($med['image_path']);
        if ($id > 0) deleteMedicine($conn, $id);
        redirectTo('index.php?page=admin_expiry&msg=deleted');
    }

    $expiring = getExpiringMedicines($conn, 60);
    $damaged  = getDamagedMedicines($conn);
    require 'views/admin/expiry.php';
}

function adminPharmacistsCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    $action  = $_GET['action'] ?? 'list';
    $error   = '';
    $editing = null;

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $pass  = $_POST['password']   ?? '';

        if ($name === '' || $email === '' || $pass === '') {
            $error = 'Name, email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (getUserByEmail($conn, $email)) {
            $error = 'Email is already registered.';
        } else {
            addStaffUser($conn, $name, $email, $pass, 'pharmacist', $phone);
            redirectTo('index.php?page=admin_pharmacists&msg=added');
        }
    }

    if ($action === 'edit') {
        $editing = getStaffById($conn, intval($_GET['id'] ?? 0), 'pharmacist');
        if (!$editing) redirectTo('index.php?page=admin_pharmacists');
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id    = intval($_GET['id'] ?? 0);
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '') {
            $error   = 'Name is required.';
            $editing = getStaffById($conn, $id, 'pharmacist');
        } else {
            updateStaffUser($conn, $id, $name, $phone, 'pharmacist');
            redirectTo('index.php?page=admin_pharmacists&msg=updated');
        }
    }

    if ($action === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteStaffUser($conn, $id, 'pharmacist');
        redirectTo('index.php?page=admin_pharmacists&msg=deleted');
    }

    $q = trim($_GET['q'] ?? '');
    $pharmacists = $q === '' ? getAllStaffByRole($conn, 'pharmacist') : searchStaffByRole($conn, 'pharmacist', $q);
    require 'views/admin/pharmacists.php';
}

function adminSuppliersCtrl($conn) {
    if (!isAdmin()) redirectTo('index.php?page=login');

    $action  = $_GET['action'] ?? 'list';
    $error   = '';
    $editing = null;

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $pass  = $_POST['password']   ?? '';

        if ($name === '' || $email === '' || $pass === '') {
            $error = 'Name, email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (getUserByEmail($conn, $email)) {
            $error = 'Email is already registered.';
        } else {
            addStaffUser($conn, $name, $email, $pass, 'supplier', $phone);
            redirectTo('index.php?page=admin_suppliers&msg=added');
        }
    }

    if ($action === 'edit') {
        $editing = getStaffById($conn, intval($_GET['id'] ?? 0), 'supplier');
        if (!$editing) redirectTo('index.php?page=admin_suppliers');
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id    = intval($_GET['id'] ?? 0);
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '') {
            $error   = 'Name is required.';
            $editing = getStaffById($conn, $id, 'supplier');
        } else {
            updateStaffUser($conn, $id, $name, $phone, 'supplier');
            redirectTo('index.php?page=admin_suppliers&msg=updated');
        }
    }

    if ($action === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteStaffUser($conn, $id, 'supplier');
        redirectTo('index.php?page=admin_suppliers&msg=deleted');
    }

    $q = trim($_GET['q'] ?? '');
    $suppliers = $q === '' ? getAllStaffByRole($conn, 'supplier') : searchStaffByRole($conn, 'supplier', $q);
    require 'views/admin/suppliers.php';
}
?>
