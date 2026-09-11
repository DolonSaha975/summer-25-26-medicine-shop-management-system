<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order #<?= $order['id'] ?> &mdash; MediShop</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php
if (isAdmin()) {
    require 'views/partials/admin_navbar.php';
} elseif (isPharmacist()) {
    require 'views/partials/pharmacist_navbar.php';
} elseif (isSupplier()) {
    require 'views/partials/supplier_navbar.php';
} else {
    require 'views/partials/navbar.php';
}
?>

<main class="main-content" style="max-width:800px;">
    <p style="margin-bottom:16px;">
        <?php if (isAdmin()): ?>
            <a href="index.php?page=admin_orders">&larr; Back to All Orders</a>
        <?php elseif (isPharmacist()): ?>
            <a href="index.php?page=pharmacist_prescriptions">&larr; Back to Prescriptions</a>
        <?php elseif (isSupplier()): ?>
            <a href="index.php?page=supplier">&larr; Back to Dashboard</a>
        <?php else: ?>
            <a href="index.php?page=my_orders">&larr; Back to My Orders</a>
        <?php endif; ?>
    </p>

    <div class="page-header">
        <div>
            <h1 class="page-title">Order #<?= $order['id'] ?></h1>
            <p class="page-sub">Placed on <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></p>
        </div>
        <span class="status-<?= esc($order['status']) ?>" style="font-size:14px;padding:8px 16px;">
            <?= ucfirst(esc($order['status'])) ?>
        </span>
    </div>

    <!-- Meta info -->
    <div class="card form-card" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
        <div>
            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Customer</div>
            <div style="font-weight:600;"><?= esc($customer['name']) ?></div>
            <div style="font-size:13px;color:var(--text-muted);"><?= esc($customer['email']) ?></div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Payment Method</div>
            <div style="font-weight:600;"><?= esc($order['payment_method']) ?></div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Delivery Method</div>
            <div style="font-weight:600;">
                <?php if ($order['delivery_type'] === 'pickup'): ?>
                    Store Pickup
                    <?php if (!empty($order['pickup_time'])): ?>
                        <div style="font-size:12.5px;color:var(--text-muted);font-weight:400;">
                            <?= date('d M Y, h:i A', strtotime($order['pickup_time'])) ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    Home Delivery
                <?php endif; ?>
            </div>
        </div>
        <div style="grid-column:1/-1;">
            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px;">
                <?= $order['delivery_type'] === 'pickup' ? 'Pickup Location' : 'Shipping Address' ?>
            </div>
            <div style="font-weight:600;"><?= esc($order['shipping_address']) ?></div>
        </div>
    </div>

    <?php if (!empty($orderPrescriptions)): ?>
    <div class="card form-card" style="margin-bottom:20px;">
        <h3 class="card-title">Prescription Verification</h3>
        <?php foreach ($orderPrescriptions as $p): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
                <span><?= esc($p['medicine_name']) ?></span>
                <span class="status-<?= esc($p['status']) ?>"><?= ucfirst(esc($p['status'])) ?></span>
            </div>
        <?php endforeach; ?>
        <p style="font-size:12.5px;color:var(--text-muted);margin-top:8px;">
            Some items in this order require a pharmacist to verify a prescription before dispensing.
        </p>
    </div>
    <?php endif; ?>

    <!-- Order items table -->
    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Vendor</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $i => $item): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= esc($item['medicine_name']) ?></td>
                        <td><?= esc($item['vendor_name']) ?></td>
                        <td class="text-right">&#2547; <?= number_format($item['unit_price'], 2) ?></td>
                        <td class="text-right"><?= $item['quantity'] ?></td>
                        <td class="text-right fw-bold">&#2547; <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="5" class="text-right fw-bold" style="font-size:15px;">Grand Total</td>
                        <td class="text-right" style="font-size:17px;font-weight:700;color:var(--primary);">
                            &#2547; <?= number_format($order['total_amount'], 2) ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (isAdmin() && $order['status'] === 'pending'): ?>
    <div style="display:flex;gap:12px;margin-top:8px;">
        <a href="index.php?page=admin_orders&action=accept&id=<?= $order['id'] ?>&csrf_token=<?= csrf_token() ?>"
           class="btn btn-primary"
           onclick="return confirm('Accept this order?')">
            Accept Order
        </a>
        <a href="index.php?page=admin_orders&action=reject&id=<?= $order['id'] ?>&csrf_token=<?= csrf_token() ?>"
           class="btn btn-danger"
           onclick="return confirm('Reject this order?')">
            Reject Order
        </a>
    </div>
    <?php endif; ?>

</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop &mdash; Medicine Shop Management System</footer>
</body>
</html>
