<?php
// models/medicine_model.php — categories, the medicine catalogue, reviews, expiry/damage

// ---------- Categories ----------

function getAllCategories($conn) {
    $r = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function getCategoryById($conn, $id) {
    $st = mysqli_prepare($conn, "SELECT * FROM categories WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function addCategory($conn, $name, $type) {
    $st = mysqli_prepare($conn, "INSERT INTO categories (name, category_type) VALUES (?, ?)");
    mysqli_stmt_bind_param($st, 'ss', $name, $type);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function updateCategory($conn, $id, $name, $type) {
    $st = mysqli_prepare($conn, "UPDATE categories SET name=?, category_type=? WHERE id=?");
    mysqli_stmt_bind_param($st, 'ssi', $name, $type, $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function deleteCategory($conn, $id) {
    $st = mysqli_prepare($conn, "DELETE FROM categories WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// ---------- Medicines ----------

function getAllMedicines($conn) {
    $r = mysqli_query($conn,
        "SELECT m.*, c.name AS category_name, c.category_type
         FROM medicines m
         JOIN categories c ON c.id = m.category_id
         ORDER BY m.id DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function getMedicineById($conn, $id) {
    $st = mysqli_prepare($conn,
        "SELECT m.*, c.name AS category_name, c.category_type
         FROM medicines m
         JOIN categories c ON c.id = m.category_id
         WHERE m.id = ?");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function searchMedicines($conn, $term, $vendorFilter = '', $typeFilter = '') {
    $like       = '%' . $term . '%';
    $vendorLike = '%' . $vendorFilter . '%';

    $sql = "SELECT m.*, c.name AS category_name, c.category_type
            FROM medicines m
            JOIN categories c ON c.id = m.category_id
            WHERE (m.name LIKE ? OR m.description LIKE ?)";
    $types = 'ss';
    $binds = [$like, $like];

    if ($vendorFilter !== '') { $sql .= " AND m.vendor_name LIKE ?"; $types .= 's'; $binds[] = $vendorLike; }
    if ($typeFilter   !== '') { $sql .= " AND c.category_type = ?";  $types .= 's'; $binds[] = $typeFilter; }
    $sql .= " ORDER BY m.id DESC";

    $st = mysqli_prepare($conn, $sql);
    $refs = [$st, $types];
    foreach ($binds as $k => $v) { $refs[] = &$binds[$k]; }
    call_user_func_array('mysqli_stmt_bind_param', $refs);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function addMedicine($conn, $name, $catId, $vendor, $price, $stock, $desc, $imagePath,
                      $expiryDate = null, $lowStockThreshold = 10, $requiresPrescription = 0) {
    $st = mysqli_prepare($conn,
        "INSERT INTO medicines (name, category_id, vendor_name, price, availability, description, image_path, expiry_date, low_stock_threshold, requires_prescription)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($st, 'sisdisssii', $name, $catId, $vendor, $price, $stock, $desc, $imagePath, $expiryDate, $lowStockThreshold, $requiresPrescription);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function updateMedicine($conn, $id, $name, $catId, $vendor, $price, $stock, $desc, $imagePath = null,
                         $expiryDate = null, $lowStockThreshold = 10, $requiresPrescription = 0) {
    if ($imagePath !== null) {
        $st = mysqli_prepare($conn,
            "UPDATE medicines SET name=?, category_id=?, vendor_name=?, price=?, availability=?, description=?, image_path=?, expiry_date=?, low_stock_threshold=?, requires_prescription=? WHERE id=?");
        mysqli_stmt_bind_param($st, 'sisdisssiii', $name, $catId, $vendor, $price, $stock, $desc, $imagePath, $expiryDate, $lowStockThreshold, $requiresPrescription, $id);
    } else {
        $st = mysqli_prepare($conn,
            "UPDATE medicines SET name=?, category_id=?, vendor_name=?, price=?, availability=?, description=?, expiry_date=?, low_stock_threshold=?, requires_prescription=? WHERE id=?");
        mysqli_stmt_bind_param($st, 'sisdissiii', $name, $catId, $vendor, $price, $stock, $desc, $expiryDate, $lowStockThreshold, $requiresPrescription, $id);
    }
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function deleteMedicine($conn, $id) {
    $st = mysqli_prepare($conn, "DELETE FROM medicines WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// ---------- Reviews (customer writes, admin moderates) ----------

function getAllReviews($conn) {
    $r = mysqli_query($conn,
        "SELECT r.*, m.name AS medicine_name, u.name AS customer_name
         FROM reviews r
         JOIN medicines m ON m.id = r.medicine_id
         JOIN users u     ON u.id = r.customer_id
         ORDER BY r.created_at DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchReviews($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT r.*, m.name AS medicine_name, u.name AS customer_name
         FROM reviews r
         JOIN medicines m ON m.id = r.medicine_id
         JOIN users u     ON u.id = r.customer_id
         WHERE m.name LIKE ? OR u.name LIKE ? OR r.status LIKE ?
         ORDER BY r.created_at DESC");
    mysqli_stmt_bind_param($st, 'sss', $like, $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getReviewsForMedicine($conn, $medId, $onlyApproved = true) {
    if ($onlyApproved) {
        $st = mysqli_prepare($conn,
            "SELECT r.*, u.name AS customer_name FROM reviews r
             JOIN users u ON u.id = r.customer_id
             WHERE r.medicine_id = ? AND r.status = 'approved'
             ORDER BY r.created_at DESC");
    } else {
        $st = mysqli_prepare($conn,
            "SELECT r.*, u.name AS customer_name FROM reviews r
             JOIN users u ON u.id = r.customer_id
             WHERE r.medicine_id = ?
             ORDER BY r.created_at DESC");
    }
    mysqli_stmt_bind_param($st, 'i', $medId);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getMedicineRatingSummary($conn, $medId) {
    $st = mysqli_prepare($conn,
        "SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rating FROM reviews WHERE medicine_id = ? AND status = 'approved'");
    mysqli_stmt_bind_param($st, 'i', $medId);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return [
        'count'   => (int)($row['cnt'] ?? 0),
        'average' => $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : 0,
    ];
}

function addReview($conn, $medId, $customerId, $rating, $comment) {
    $st = mysqli_prepare($conn,
        "INSERT INTO reviews (medicine_id, customer_id, rating, comment) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($st, 'iiis', $medId, $customerId, $rating, $comment);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function getReviewsByCustomer($conn, $customerId) {
    $st = mysqli_prepare($conn,
        "SELECT r.*, m.name AS medicine_name
         FROM reviews r
         JOIN medicines m ON m.id = r.medicine_id
         WHERE r.customer_id = ?
         ORDER BY r.created_at DESC");
    mysqli_stmt_bind_param($st, 'i', $customerId);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getReviewById($conn, $id) {
    $st = mysqli_prepare($conn,
        "SELECT r.*, m.name AS medicine_name
         FROM reviews r
         JOIN medicines m ON m.id = r.medicine_id
         WHERE r.id = ?");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function updateReview($conn, $id, $rating, $comment) {
    // editing a review sends it back for approval
    $st = mysqli_prepare($conn, "UPDATE reviews SET rating=?, comment=?, status='pending' WHERE id=?");
    mysqli_stmt_bind_param($st, 'isi', $rating, $comment, $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function updateReviewStatus($conn, $id, $status) {
    $st = mysqli_prepare($conn, "UPDATE reviews SET status=? WHERE id=?");
    mysqli_stmt_bind_param($st, 'si', $status, $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function deleteReview($conn, $id) {
    $st = mysqli_prepare($conn, "DELETE FROM reviews WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// ---------- Expiry & damage tracking (admin) ----------

function getExpiringMedicines($conn, $days = 60) {
    $st = mysqli_prepare($conn,
        "SELECT * FROM medicines
         WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
         ORDER BY expiry_date ASC");
    mysqli_stmt_bind_param($st, 'i', $days);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getDamagedMedicines($conn) {
    $r = mysqli_query($conn, "SELECT * FROM medicines WHERE is_damaged = 1 ORDER BY name ASC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function markMedicineDamaged($conn, $id, $notes) {
    $st = mysqli_prepare($conn, "UPDATE medicines SET is_damaged=1, damage_notes=? WHERE id=?");
    mysqli_stmt_bind_param($st, 'si', $notes, $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function clearMedicineDamaged($conn, $id) {
    $st = mysqli_prepare($conn, "UPDATE medicines SET is_damaged=0, damage_notes=NULL WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}
?>
