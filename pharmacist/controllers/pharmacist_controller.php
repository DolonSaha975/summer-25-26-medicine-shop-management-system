<?php
// controllers/pharmacist_controller.php — request handling for the pharmacist dashboard

function pharmacistDashCtrl($conn) {
    if (!isPharmacist()) redirectTo('index.php?page=login');

    $pendingPrescriptions = getPendingPrescriptionCount($conn);
    $totalLimits          = count(getAllMedicineLimits($conn));
    $payments             = getAllPaymentsForPharmacist($conn);
    $pendingPayments      = count(array_filter($payments, function ($p) { return $p['status'] === 'pending'; }));
    $recentPrescriptions  = array_slice(getAllPrescriptions($conn), 0, 5);

    require 'views/pharmacist/dashboard.php';
}

function pharmacistPrescriptionsCtrl($conn) {
    if (!isPharmacist()) redirectTo('index.php?page=login');

    $action  = $_GET['action'] ?? 'list';
    $error   = '';
    $editing = null;

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $orderId    = intval($_POST['order_id'] ?? 0);
        $medicineId = intval($_POST['medicine_id'] ?? 0);
        $note       = trim($_POST['note'] ?? '');
        $order      = getOrderById($conn, $orderId);

        if (!$order || $medicineId === 0) {
            $error = 'Please pick a valid order and medicine.';
        } else {
            addPrescription($conn, $orderId, $medicineId, $order['user_id'], $note);
            redirectTo('index.php?page=pharmacist_prescriptions&msg=added');
        }
    }

    if ($action === 'verify') {
        $editing = getPrescriptionById($conn, intval($_GET['id'] ?? 0));
        if (!$editing) redirectTo('index.php?page=pharmacist_prescriptions');
    }

    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id     = intval($_GET['id'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['pending', 'verified', 'rejected']) ? $_POST['status'] : 'pending';
        $note   = trim($_POST['note'] ?? '');
        updatePrescriptionStatus($conn, $id, $status, $note, $_SESSION['user']['id']);
        redirectTo('index.php?page=pharmacist_prescriptions&msg=updated');
    }

    if ($action === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deletePrescription($conn, $id);
        redirectTo('index.php?page=pharmacist_prescriptions&msg=deleted');
    }

    $q = trim($_GET['q'] ?? '');
    $prescriptions = $q === '' ? getAllPrescriptions($conn) : searchPrescriptions($conn, $q);
    $orders        = getAllOrders($conn);
    $medicines     = getAllMedicines($conn);
    require 'views/pharmacist/prescriptions.php';
}

function pharmacistLimitsCtrl($conn) {
    if (!isPharmacist()) redirectTo('index.php?page=login');

    $action    = $_GET['action'] ?? 'list';
    $error     = '';
    $editing   = null;
    $medicines = getAllMedicines($conn);

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $medId  = intval($_POST['medicine_id']         ?? 0);
        $maxQty = intval($_POST['max_qty_per_customer'] ?? 0);
        if ($medId === 0 || $maxQty < 1) {
            $error = 'Please select a medicine and enter a valid limit (at least 1).';
        } else {
            setMedicineLimit($conn, $medId, $maxQty, $_SESSION['user']['id']);
            redirectTo('index.php?page=pharmacist_limits&msg=saved');
        }
    }

    if ($action === 'edit') {
        $editing = getMedicineLimitById($conn, intval($_GET['id'] ?? 0));
        if (!$editing) redirectTo('index.php?page=pharmacist_limits');
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id     = intval($_GET['id'] ?? 0);
        $maxQty = intval($_POST['max_qty_per_customer'] ?? 0);
        $row    = getMedicineLimitById($conn, $id);
        if (!$row || $maxQty < 1) {
            $error   = 'Enter a valid limit (at least 1).';
            $editing = $row;
        } else {
            setMedicineLimit($conn, $row['medicine_id'], $maxQty, $_SESSION['user']['id']);
            redirectTo('index.php?page=pharmacist_limits&msg=updated');
        }
    }

    if ($action === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteMedicineLimit($conn, $id);
        redirectTo('index.php?page=pharmacist_limits&msg=deleted');
    }

    $q = trim($_GET['q'] ?? '');
    $limits = $q === '' ? getAllMedicineLimits($conn) : searchMedicineLimits($conn, $q);
    require 'views/pharmacist/limits.php';
}

function pharmacistPaymentsCtrl($conn) {
    if (!isPharmacist()) redirectTo('index.php?page=login');

    $action = $_GET['action'] ?? '';
    if (in_array($action, ['mark_paid', 'mark_refunded'])) {
        csrf_verify();
        $id     = intval($_GET['id'] ?? 0);
        $status = $action === 'mark_paid' ? 'paid' : 'refunded';
        if ($id > 0) updatePaymentStatus($conn, $id, $status, $_SESSION['user']['id']);
        redirectTo('index.php?page=pharmacist_payments&msg=' . $status);
    }

    $q = trim($_GET['q'] ?? '');
    $payments = $q === '' ? getAllPaymentsForPharmacist($conn) : searchPayments($conn, $q);
    require 'views/pharmacist/payments.php';
}
?>
