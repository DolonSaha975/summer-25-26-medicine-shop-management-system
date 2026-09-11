<?php
// models/supplier_model.php — stock supply records, low-stock detection, sale history

function getAllStockSupplies($conn) {
    $r = mysqli_query($conn,
        "SELECT s.*, m.name AS medicine_name, m.availability, u.name AS supplier_name
         FROM stock_supplies s
         JOIN medicines m ON m.id = s.medicine_id
         JOIN users u     ON u.id = s.supplier_id
         ORDER BY s.supply_date DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchStockSupplies($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT s.*, m.name AS medicine_name, m.availability, u.name AS supplier_name
         FROM stock_supplies s
         JOIN medicines m ON m.id = s.medicine_id
         JOIN users u     ON u.id = s.supplier_id
         WHERE m.name LIKE ? OR u.name LIKE ? OR s.notes LIKE ?
         ORDER BY s.supply_date DESC");
    mysqli_stmt_bind_param($st, 'sss', $like, $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function getStockSupplyById($conn, $id) {
    $st = mysqli_prepare($conn, "SELECT * FROM stock_supplies WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $row;
}

function addStockSupply($conn, $medId, $supplierId, $qty, $notes) {
    $st = mysqli_prepare($conn,
        "INSERT INTO stock_supplies (medicine_id, supplier_id, quantity, notes) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($st, 'iiis', $medId, $supplierId, $qty, $notes);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if ($ok) {
        $up = mysqli_prepare($conn, "UPDATE medicines SET availability = availability + ? WHERE id = ?");
        mysqli_stmt_bind_param($up, 'ii', $qty, $medId);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }
    return $ok;
}

function updateStockSupply($conn, $id, $newQty, $notes) {
    $old = getStockSupplyById($conn, $id);
    if (!$old) return false;
    $delta = $newQty - (int)$old['quantity'];

    $st = mysqli_prepare($conn, "UPDATE stock_supplies SET quantity=?, notes=? WHERE id=?");
    mysqli_stmt_bind_param($st, 'isi', $newQty, $notes, $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if ($ok && $delta !== 0) {
        $up = mysqli_prepare($conn, "UPDATE medicines SET availability = availability + ? WHERE id = ?");
        mysqli_stmt_bind_param($up, 'ii', $delta, $old['medicine_id']);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }
    return $ok;
}

function deleteStockSupply($conn, $id) {
    $old = getStockSupplyById($conn, $id);
    if (!$old) return false;
    $st = mysqli_prepare($conn, "DELETE FROM stock_supplies WHERE id=?");
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if ($ok) {
        $up = mysqli_prepare($conn,
            "UPDATE medicines SET availability = GREATEST(availability - ?, 0) WHERE id = ?");
        mysqli_stmt_bind_param($up, 'ii', $old['quantity'], $old['medicine_id']);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }
    return $ok;
}

function getLowStockMedicines($conn) {
    $r = mysqli_query($conn,
        "SELECT * FROM medicines WHERE availability <= low_stock_threshold ORDER BY availability ASC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function getSaleHistorySummary($conn) {
    $r = mysqli_query($conn,
        "SELECT m.id, m.name AS medicine_name, m.vendor_name,
                SUM(oi.quantity) AS units_sold,
                SUM(oi.quantity * oi.unit_price) AS revenue
         FROM order_items oi
         JOIN orders o     ON o.id = oi.order_id
         JOIN medicines m  ON m.id = oi.medicine_id
         WHERE o.status = 'accepted'
         GROUP BY m.id, m.name, m.vendor_name
         ORDER BY units_sold DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function getSaleHistoryDetailed($conn) {
    $r = mysqli_query($conn,
        "SELECT oi.id, m.name AS medicine_name, u.name AS customer_name,
                oi.quantity, oi.unit_price, (oi.quantity * oi.unit_price) AS subtotal, o.order_date
         FROM order_items oi
         JOIN orders o    ON o.id = oi.order_id
         JOIN medicines m ON m.id = oi.medicine_id
         JOIN users u     ON u.id = o.user_id
         WHERE o.status = 'accepted'
         ORDER BY o.order_date DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchSaleHistoryDetailed($conn, $term) {
    $like = '%' . $term . '%';
    $st = mysqli_prepare($conn,
        "SELECT oi.id, m.name AS medicine_name, u.name AS customer_name,
                oi.quantity, oi.unit_price, (oi.quantity * oi.unit_price) AS subtotal, o.order_date
         FROM order_items oi
         JOIN orders o    ON o.id = oi.order_id
         JOIN medicines m ON m.id = oi.medicine_id
         JOIN users u     ON u.id = o.user_id
         WHERE o.status = 'accepted' AND (m.name LIKE ? OR u.name LIKE ?)
         ORDER BY o.order_date DESC");
    mysqli_stmt_bind_param($st, 'ss', $like, $like);
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}
?>
