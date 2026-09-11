<?php
// controllers/ajax_controller.php — every endpoint that returns JSON instead of a view.
// Used by the search boxes (ajaxTable() in assets/js/app.js) and the cart page.

function ajaxCtrl($conn) {
    header('Content-Type: application/json');
    if (!isLoggedIn()) { http_response_code(403); echo json_encode(['error' => 'Unauthorized']); exit; }

    $type = $_GET['type'] ?? '';

    // ---------- Admin search tables ----------
    if ($type === 'medicines' && isAdmin()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllMedicines($conn) : searchMedicines($conn, $q));
        exit;
    }
    if ($type === 'customers' && isAdmin()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllCustomers($conn) : searchCustomers($conn, $q));
        exit;
    }
    if ($type === 'orders' && isAdmin()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllOrders($conn) : searchOrders($conn, $q));
        exit;
    }
    if ($type === 'reviews' && isAdmin()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllReviews($conn) : searchReviews($conn, $q));
        exit;
    }
    if ($type === 'feedback' && isAdmin()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllFeedback($conn) : searchFeedback($conn, $q));
        exit;
    }
    if ($type === 'pharmacist_staff' && isAdmin()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllStaffByRole($conn, 'pharmacist') : searchStaffByRole($conn, 'pharmacist', $q));
        exit;
    }
    if ($type === 'supplier_staff' && isAdmin()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllStaffByRole($conn, 'supplier') : searchStaffByRole($conn, 'supplier', $q));
        exit;
    }

    // ---------- Pharmacist search tables ----------
    if ($type === 'prescriptions' && isPharmacist()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllPrescriptions($conn) : searchPrescriptions($conn, $q));
        exit;
    }
    if ($type === 'medicine_limits' && isPharmacist()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllMedicineLimits($conn) : searchMedicineLimits($conn, $q));
        exit;
    }
    if ($type === 'payments' && isPharmacist()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllPaymentsForPharmacist($conn) : searchPayments($conn, $q));
        exit;
    }

    // ---------- Supplier search tables ----------
    if ($type === 'stock_supplies' && isSupplier()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllStockSupplies($conn) : searchStockSupplies($conn, $q));
        exit;
    }
    if ($type === 'sale_history' && isSupplier()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getSaleHistoryDetailed($conn) : searchSaleHistoryDetailed($conn, $q));
        exit;
    }
    if ($type === 'store_ratings' && isSupplier()) {
        $q = trim($_GET['q'] ?? '');
        echo json_encode($q === '' ? getAllStoreRatings($conn) : searchStoreRatings($conn, $q));
        exit;
    }

    // ---------- Customer: shop search ----------
    if ($type === 'search_medicines') {
        $q      = trim($_GET['q']      ?? '');
        $vendor = trim($_GET['vendor'] ?? '');
        $ftype  = trim($_GET['ftype']  ?? '');
        echo json_encode(searchMedicines($conn, $q, $vendor, $ftype));
        exit;
    }
    if ($type === 'my_reviews' && isCustomer()) {
        $q = trim($_GET['q'] ?? '');
        $all = getReviewsByCustomer($conn, $_SESSION['user']['id']);
        if ($q !== '') {
            $all = array_values(array_filter($all, function ($r) use ($q) {
                return stripos($r['medicine_name'], $q) !== false || stripos($r['comment'] ?? '', $q) !== false;
            }));
        }
        echo json_encode($all);
        exit;
    }
    if ($type === 'my_feedback' && isCustomer()) {
        $q = trim($_GET['q'] ?? '');
        $userId = $_SESSION['user']['id'];
        $all = array_values(array_filter(getAllFeedback($conn), function ($f) use ($userId) {
            return $f['customer_id'] == $userId;
        }));
        if ($q !== '') {
            $all = array_values(array_filter($all, function ($f) use ($q) {
                return stripos($f['subject'], $q) !== false || stripos($f['message'], $q) !== false;
            }));
        }
        echo json_encode($all);
        exit;
    }

    // ---------- Customer: cart ----------
    if ($type === 'add_to_cart' && isCustomer() && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data  = json_decode(file_get_contents('php://input'), true);
        $medId = intval($data['medicine_id'] ?? 0);
        $qty   = intval($data['quantity']    ?? 1);
        $med   = getMedicineById($conn, $medId);
        if (!$med)    { echo json_encode(['error' => 'Medicine not found']); exit; }
        if ($qty < 1) { echo json_encode(['error' => 'Invalid quantity']);   exit; }
        if ($qty > $med['availability']) {
            echo json_encode(['error' => 'Quantity exceeds stock (' . $med['availability'] . ')']); exit;
        }
        $limit = getMedicineLimitByMedicineId($conn, $medId);
        if ($limit) {
            $already = getCartQtyForMedicine($conn, $_SESSION['user']['id'], $medId);
            if (($already + $qty) > $limit['max_qty_per_customer']) {
                echo json_encode(['error' => 'Purchase limit: max ' . $limit['max_qty_per_customer'] . ' of this medicine per customer']); exit;
            }
        }
        addToCart($conn, $_SESSION['user']['id'], $medId, $qty);
        echo json_encode(['success' => true, 'cart_count' => getCartCount($conn, $_SESSION['user']['id'])]);
        exit;
    }

    if ($type === 'update_cart' && isCustomer() && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true);
        $cartId = intval($data['cart_id']  ?? 0);
        $qty    = intval($data['quantity'] ?? 0);
        $userId = $_SESSION['user']['id'];

        $cartItems = getCartItems($conn, $userId);
        $cartRow   = null;
        foreach ($cartItems as $ci) { if ($ci['id'] == $cartId) { $cartRow = $ci; break; } }
        if (!$cartRow) { echo json_encode(['error' => 'Cart item not found']); exit; }
        if ($qty < 1)  { echo json_encode(['error' => 'Quantity must be at least 1']); exit; }
        if ($qty > $cartRow['availability']) {
            echo json_encode(['error' => 'Only ' . $cartRow['availability'] . ' in stock']); exit;
        }
        $limit = getMedicineLimitByMedicineId($conn, $cartRow['medicine_id']);
        if ($limit && $qty > $limit['max_qty_per_customer']) {
            echo json_encode(['error' => 'Purchase limit: max ' . $limit['max_qty_per_customer'] . ' of this medicine per customer']); exit;
        }
        updateCartQuantity($conn, $cartId, $userId, $qty);

        $items = getCartItems($conn, $userId);
        $total = array_sum(array_map(function ($i) { return $i['price'] * $i['quantity']; }, $items));
        $count = getCartCount($conn, $userId);
        echo json_encode([
            'success'      => true,
            'new_subtotal' => number_format($cartRow['price'] * $qty, 2),
            'cart_total'   => number_format($total, 2),
            'cart_count'   => $count,
        ]);
        exit;
    }

    if ($type === 'remove_from_cart' && isCustomer() && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        parse_str(file_get_contents('php://input'), $data);
        $cartId = intval($data['cart_id'] ?? 0);
        $userId = $_SESSION['user']['id'];
        removeFromCart($conn, $cartId, $userId);
        $items = getCartItems($conn, $userId);
        $total = array_sum(array_map(function ($i) { return $i['price'] * $i['quantity']; }, $items));
        $count = getCartCount($conn, $userId);
        echo json_encode(['success' => true, 'cart_total' => number_format($total, 2), 'cart_count' => $count]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}
?>
