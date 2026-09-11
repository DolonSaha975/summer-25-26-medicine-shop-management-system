<?php
// models/order_model.php — cart, orders, payments, prescriptions, purchase
// limits, feedback and store ratings. Kept together because almost every
// one of these tables is joined against orders somewhere.

// ---------- Cart ----------

function getCartItems($conn, $userId) {
    $st = mysqli_prepare($conn,
        "SELECT c.id, c.quantity, m.id AS medicine_id, m.name, m.vendor_name, m.price, m.availability, m.image_path
         FROM cart c
         JOIN medicines m ON m.id = c.medicine_id
         WHERE c.user_id = ?
         ORDER BY c.added_at DESC");
    mysqli_stmt_bind_param($st, 'i', $userId);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getCartCount($conn, $userId) {
    $st = mysqli_prepare($conn, "SELECT SUM(quantity) AS cnt FROM cart WHERE user_id=?");
    mysqli_stmt_bind_param($st, 'i', $userId);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return (int)($row['cnt'] ?? 0);
}

function getCartQtyForMedicine($conn, $userId, $medicineId) {
    $st = mysqli_prepare($conn, "SELECT quantity FROM cart WHERE user_id=? AND medicine_id=?");
    mysqli_stmt_bind_param($st, 'ii', $userId, $medicineId);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row ? (int)$row['quantity'] : 0;
}

function addToCart($conn, $userId, $medicineId, $quantity) {
    $st = mysqli_prepare($conn,
        "INSERT INTO cart (user_id, medicine_id, quantity) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
    mysqli_stmt_bind_param($st, 'iii', $userId, $medicineId, $quantity);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function updateCartQuantity($conn, $cartId, $userId, $quantity) {
    $st = mysqli_prepare($conn, "UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($st, 'iii', $quantity, $cartId, $userId);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function removeFromCart($conn, $cartId, $userId) {
    $st = mysqli_prepare($conn, "DELETE FROM cart WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($st, 'ii', $cartId, $userId);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function clearCart($conn, $userId) {
    $st = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id=?");
    mysqli_stmt_bind_param($st, 'i', $userId);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// ---------- Orders ----------

function createOrder($conn, $userId, $totalAmount, $shippingAddress, $paymentMethod, $deliveryType = 'delivery', $pickupTime = null) {
    $st = mysqli_prepare($conn,
        "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, delivery_type, pickup_time, status)
         VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($st, 'idssss', $userId, $totalAmount, $shippingAddress, $paymentMethod, $deliveryType, $pickupTime);
    $ok = mysqli_stmt_execute($st);
    $orderId = mysqli_stmt_insert_id($st);
    mysqli_stmt_close($st);
    return $ok ? $orderId : false;
}

function addOrderItem($conn, $orderId, $medicineId, $quantity, $unitPrice) {
    $st = mysqli_prepare($conn,
        "INSERT INTO order_items (order_id, medicine_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($st, 'iiid', $orderId, $medicineId, $quantity, $unitPrice);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function reduceStock($conn, $medicineId, $quantity) {
    $st = mysqli_prepare($conn,
        "UPDATE medicines SET availability = availability - ? WHERE id = ? AND availability >= ?");
    mysqli_stmt_bind_param($st, 'iii', $quantity, $medicineId, $quantity);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function getOrderById($conn, $orderId) {
    $st = mysqli_prepare($conn, "SELECT * FROM orders WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $orderId);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function getOrderItems($conn, $orderId) {
    $st = mysqli_prepare($conn,
        "SELECT oi.*, m.name AS medicine_name, m.vendor_name
         FROM order_items oi
         JOIN medicines m ON m.id = oi.medicine_id
         WHERE oi.order_id=?");
    mysqli_stmt_bind_param($st, 'i', $orderId);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getAllOrders($conn) {
    $r = mysqli_query($conn,
        "SELECT o.*, u.name AS customer_name, u.email AS customer_email
         FROM orders o
         JOIN users u ON u.id = o.user_id
         ORDER BY o.order_date DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchOrders($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT o.*, u.name AS customer_name, u.email AS customer_email
         FROM orders o
         JOIN users u ON u.id = o.user_id
         WHERE u.name LIKE ? OR u.email LIKE ? OR o.status LIKE ? OR CAST(o.id AS CHAR) LIKE ?
         ORDER BY o.order_date DESC");
    mysqli_stmt_bind_param($st, 'ssss', $like, $like, $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function updateOrderStatus($conn, $orderId, $status) {
    $st = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE id=?");
    mysqli_stmt_bind_param($st, 'si', $status, $orderId);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function getOrdersByUser($conn, $userId) {
    $st = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id=? ORDER BY order_date DESC");
    mysqli_stmt_bind_param($st, 'i', $userId);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

// ---------- Payments (pharmacist confirms) ----------

function createPayment($conn, $orderId, $amount, $paymentMethod) {
    $txn = strtoupper(bin2hex(random_bytes(6)));
    $st = mysqli_prepare($conn,
        "INSERT INTO payments (order_id, amount, payment_method, transaction_id) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($st, 'idss', $orderId, $amount, $paymentMethod, $txn);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $txn;
}

function getAllPaymentsForPharmacist($conn) {
    $r = mysqli_query($conn,
        "SELECT pay.*, o.id AS order_ref, o.total_amount, u.name AS customer_name, v.name AS verified_by_name
         FROM payments pay
         JOIN orders o ON o.id = pay.order_id
         JOIN users u  ON u.id = o.user_id
         LEFT JOIN users v ON v.id = pay.verified_by
         ORDER BY pay.payment_date DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchPayments($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT pay.*, o.id AS order_ref, o.total_amount, u.name AS customer_name, v.name AS verified_by_name
         FROM payments pay
         JOIN orders o ON o.id = pay.order_id
         JOIN users u  ON u.id = o.user_id
         LEFT JOIN users v ON v.id = pay.verified_by
         WHERE u.name LIKE ? OR pay.payment_method LIKE ? OR pay.status LIKE ? OR pay.transaction_id LIKE ?
         ORDER BY pay.payment_date DESC");
    mysqli_stmt_bind_param($st, 'ssss', $like, $like, $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function updatePaymentStatus($conn, $paymentId, $status, $pharmacistId) {
    $st = mysqli_prepare($conn, "UPDATE payments SET status=?, verified_by=?, verified_at=NOW() WHERE id=?");
    mysqli_stmt_bind_param($st, 'sii', $status, $pharmacistId, $paymentId);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// ---------- Prescriptions (pharmacist verifies) ----------

function autoCreatePrescriptionsForOrder($conn, $orderId, $customerId, $items) {
    foreach ($items as $item) {
        $med = getMedicineById($conn, $item['medicine_id']);
        if ($med && (int)$med['requires_prescription'] === 1) {
            $st = mysqli_prepare($conn,
                "INSERT INTO prescriptions (order_id, medicine_id, customer_id, status) VALUES (?, ?, ?, 'pending')");
            mysqli_stmt_bind_param($st, 'iii', $orderId, $item['medicine_id'], $customerId);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
    }
}

function getPrescriptionsForOrder($conn, $orderId) {
    $st = mysqli_prepare($conn,
        "SELECT p.*, m.name AS medicine_name
         FROM prescriptions p
         JOIN medicines m ON m.id = p.medicine_id
         WHERE p.order_id = ?");
    mysqli_stmt_bind_param($st, 'i', $orderId);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getAllPrescriptions($conn) {
    $r = mysqli_query($conn,
        "SELECT p.*, m.name AS medicine_name, c.name AS customer_name, c.email AS customer_email,
                ph.name AS pharmacist_name
         FROM prescriptions p
         JOIN medicines m ON m.id = p.medicine_id
         JOIN users c     ON c.id = p.customer_id
         LEFT JOIN users ph ON ph.id = p.pharmacist_id
         ORDER BY p.created_at DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchPrescriptions($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT p.*, m.name AS medicine_name, c.name AS customer_name, c.email AS customer_email,
                ph.name AS pharmacist_name
         FROM prescriptions p
         JOIN medicines m ON m.id = p.medicine_id
         JOIN users c     ON c.id = p.customer_id
         LEFT JOIN users ph ON ph.id = p.pharmacist_id
         WHERE m.name LIKE ? OR c.name LIKE ? OR p.status LIKE ? OR CAST(p.order_id AS CHAR) LIKE ?
         ORDER BY p.created_at DESC");
    mysqli_stmt_bind_param($st, 'ssss', $like, $like, $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getPrescriptionById($conn, $id) {
    $st = mysqli_prepare($conn,
        "SELECT p.*, m.name AS medicine_name, c.name AS customer_name
         FROM prescriptions p
         JOIN medicines m ON m.id = p.medicine_id
         JOIN users c     ON c.id = p.customer_id
         WHERE p.id = ?");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function addPrescription($conn, $orderId, $medicineId, $customerId, $note) {
    $st = mysqli_prepare($conn,
        "INSERT INTO prescriptions (order_id, medicine_id, customer_id, note, status) VALUES (?, ?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($st, 'iiis', $orderId, $medicineId, $customerId, $note);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function updatePrescriptionStatus($conn, $id, $status, $note, $pharmacistId) {
    $st = mysqli_prepare($conn,
        "UPDATE prescriptions SET status=?, note=?, pharmacist_id=?, verified_at=NOW() WHERE id=?");
    mysqli_stmt_bind_param($st, 'ssii', $status, $note, $pharmacistId, $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function deletePrescription($conn, $id) {
    $st = mysqli_prepare($conn, "DELETE FROM prescriptions WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function getPendingPrescriptionCount($conn) {
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM prescriptions WHERE status='pending'");
    return (int)(mysqli_fetch_assoc($r)['c'] ?? 0);
}

// ---------- Purchase limits (pharmacist sets, enforced at cart time) ----------

function getMedicineLimitByMedicineId($conn, $medId) {
    $st = mysqli_prepare($conn, "SELECT * FROM medicine_limits WHERE medicine_id=?");
    mysqli_stmt_bind_param($st, 'i', $medId);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function getAllMedicineLimits($conn) {
    $r = mysqli_query($conn,
        "SELECT l.*, m.name AS medicine_name, m.price, u.name AS set_by_name
         FROM medicine_limits l
         JOIN medicines m ON m.id = l.medicine_id
         LEFT JOIN users u ON u.id = l.set_by
         ORDER BY l.updated_at DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchMedicineLimits($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT l.*, m.name AS medicine_name, m.price, u.name AS set_by_name
         FROM medicine_limits l
         JOIN medicines m ON m.id = l.medicine_id
         LEFT JOIN users u ON u.id = l.set_by
         WHERE m.name LIKE ?
         ORDER BY l.updated_at DESC");
    mysqli_stmt_bind_param($st, 's', $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getMedicineLimitById($conn, $id) {
    $st = mysqli_prepare($conn, "SELECT * FROM medicine_limits WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function setMedicineLimit($conn, $medId, $maxQty, $pharmacistId) {
    $st = mysqli_prepare($conn,
        "INSERT INTO medicine_limits (medicine_id, max_qty_per_customer, set_by)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE max_qty_per_customer = VALUES(max_qty_per_customer), set_by = VALUES(set_by)");
    mysqli_stmt_bind_param($st, 'iii', $medId, $maxQty, $pharmacistId);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function deleteMedicineLimit($conn, $id) {
    $st = mysqli_prepare($conn, "DELETE FROM medicine_limits WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// ---------- Feedback (customer writes, admin replies) ----------

function getAllFeedback($conn) {
    $r = mysqli_query($conn,
        "SELECT f.*, u.name AS customer_name, u.email AS customer_email
         FROM feedback f
         JOIN users u ON u.id = f.customer_id
         ORDER BY f.created_at DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchFeedback($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT f.*, u.name AS customer_name, u.email AS customer_email
         FROM feedback f
         JOIN users u ON u.id = f.customer_id
         WHERE f.subject LIKE ? OR f.message LIKE ? OR u.name LIKE ? OR f.status LIKE ?
         ORDER BY f.created_at DESC");
    mysqli_stmt_bind_param($st, 'ssss', $like, $like, $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function addFeedback($conn, $customerId, $subject, $message) {
    $st = mysqli_prepare($conn, "INSERT INTO feedback (customer_id, subject, message) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($st, 'iss', $customerId, $subject, $message);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function getFeedbackById($conn, $id) {
    $st = mysqli_prepare($conn,
        "SELECT f.*, u.name AS customer_name, u.email AS customer_email
         FROM feedback f
         JOIN users u ON u.id = f.customer_id
         WHERE f.id = ?");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function updateFeedback($conn, $id, $subject, $message) {
    // editing sends it back to "new" so admin re-reviews it, same idea as review edits
    $st = mysqli_prepare($conn, "UPDATE feedback SET subject=?, message=?, status='new' WHERE id=?");
    mysqli_stmt_bind_param($st, 'ssi', $subject, $message, $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function replyFeedback($conn, $id, $reply, $status) {
    $st = mysqli_prepare($conn, "UPDATE feedback SET admin_reply=?, status=? WHERE id=?");
    mysqli_stmt_bind_param($st, 'ssi', $reply, $status, $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function deleteFeedback($conn, $id) {
    $st = mysqli_prepare($conn, "DELETE FROM feedback WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// ---------- Store ratings (customer writes, supplier reads) ----------

function getStoreRatingSummary($conn) {
    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rating FROM store_ratings");
    $row = mysqli_fetch_assoc($r);
    return [
        'count'   => (int)($row['cnt'] ?? 0),
        'average' => $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : 0,
    ];
}

function getAllStoreRatings($conn) {
    $r = mysqli_query($conn,
        "SELECT r.*, u.name AS customer_name
         FROM store_ratings r
         JOIN users u ON u.id = r.customer_id
         ORDER BY r.created_at DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchStoreRatings($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT r.*, u.name AS customer_name
         FROM store_ratings r
         JOIN users u ON u.id = r.customer_id
         WHERE u.name LIKE ? OR r.comment LIKE ?
         ORDER BY r.created_at DESC");
    mysqli_stmt_bind_param($st, 'ss', $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function addStoreRating($conn, $customerId, $rating, $comment) {
    $st = mysqli_prepare($conn, "INSERT INTO store_ratings (customer_id, rating, comment) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($st, 'iis', $customerId, $rating, $comment);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function deleteStoreRating($conn, $id) {
    $st = mysqli_prepare($conn, "DELETE FROM store_ratings WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}
?>
