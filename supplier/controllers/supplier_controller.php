<?php
// controllers/supplier_controller.php — request handling for the supplier dashboard

function supplierDashCtrl($conn) {
    if (!isSupplier()) redirectTo('index.php?page=login');

    $lowStock       = getLowStockMedicines($conn);
    $saleSummary    = getSaleHistorySummary($conn);
    $ratingSummary  = getStoreRatingSummary($conn);
    $recentSupplies = array_slice(getAllStockSupplies($conn), 0, 5);

    require 'views/supplier/dashboard.php';
}

function supplierStockCtrl($conn) {
    if (!isSupplier()) redirectTo('index.php?page=login');

    $action    = $_GET['action'] ?? 'list';
    $error     = '';
    $editing   = null;
    $medicines = getAllMedicines($conn);

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $medId = intval($_POST['medicine_id'] ?? 0);
        $qty   = intval($_POST['quantity']    ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($medId === 0 || $qty < 1) {
            $error = 'Please select a medicine and enter a valid quantity.';
        } else {
            addStockSupply($conn, $medId, $_SESSION['user']['id'], $qty, $notes);
            redirectTo('index.php?page=supplier_stock&msg=added');
        }
    }

    if ($action === 'edit') {
        $editing = getStockSupplyById($conn, intval($_GET['id'] ?? 0));
        if (!$editing) redirectTo('index.php?page=supplier_stock');
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id    = intval($_GET['id'] ?? 0);
        $qty   = intval($_POST['quantity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($qty < 1) {
            $error   = 'Quantity must be at least 1.';
            $editing = getStockSupplyById($conn, $id);
        } else {
            updateStockSupply($conn, $id, $qty, $notes);
            redirectTo('index.php?page=supplier_stock&msg=updated');
        }
    }

    if ($action === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteStockSupply($conn, $id);
        redirectTo('index.php?page=supplier_stock&msg=deleted');
    }

    $q = trim($_GET['q'] ?? '');
    $supplies = $q === '' ? getAllStockSupplies($conn) : searchStockSupplies($conn, $q);
    $lowStock = getLowStockMedicines($conn);
    require 'views/supplier/stock.php';
}

function supplierSalesCtrl($conn) {
    if (!isSupplier()) redirectTo('index.php?page=login');

    $q        = trim($_GET['q'] ?? '');
    $summary  = getSaleHistorySummary($conn);
    $detailed = $q === '' ? getSaleHistoryDetailed($conn) : searchSaleHistoryDetailed($conn, $q);
    require 'views/supplier/sales.php';
}

function supplierRatingsCtrl($conn) {
    if (!isSupplier()) redirectTo('index.php?page=login');

    if (($_GET['action'] ?? '') === 'delete') {
        csrf_verify();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) deleteStoreRating($conn, $id);
        redirectTo('index.php?page=supplier_ratings&msg=deleted');
    }

    $q       = trim($_GET['q'] ?? '');
    $ratings = $q === '' ? getAllStoreRatings($conn) : searchStoreRatings($conn, $q);
    $summary = getStoreRatingSummary($conn);
    require 'views/supplier/ratings.php';
}
?>
