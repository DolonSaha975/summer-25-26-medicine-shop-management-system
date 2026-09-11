<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed &mdash; MediShop</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/navbar.php'; ?>

<main class="main-content" style="max-width:760px;">

    <div class="success-banner">
        <h2>Order Placed Successfully!</h2>
        <p>Your order #<?= $order['id'] ?> has been submitted and is pending admin approval.</p>
    </div>

    <div class="card">
        <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="font-size:13px;color:var(--text-muted);">Order ID</div>
                <div style="font-weight:700;font-size:16px;">#<?= $order['id'] ?></div>
            </div>
            <div>
                <div style="font-size:13px;color:var(--text-muted);">Date</div>
                <div style="font-weight:600;"><?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></div>
            </div>
            <div>
                <div style="font-size:13px;color:var(--text-muted);">Payment</div>
                <div style="font-weight:600;"><?= esc($order['payment_method']) ?></div>
            </div>
            <div>
                <div style="font-size:13px;color:var(--text-muted);">Status</div>
                <span class="status-<?= esc($order['status']) ?>"><?= ucfirst(esc($order['status'])) ?></span>
            </div>
        </div>

        <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Shipping Address</div>
            <div style="font-weight:600;"><?= esc($order['shipping_address']) ?></div>
        </div>

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
                        <td colspan="5" class="text-right fw-bold">Total Amount</td>
                        <td class="text-right" style="font-size:16px;font-weight:700;color:var(--primary);">
                            &#2547; <?= number_format($order['total_amount'], 2) ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="index.php?page=my_orders" class="btn btn-primary">View All My Orders</a>
        <a href="index.php?page=home"      class="btn btn-ghost">Continue Shopping</a>
    </div>

</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop</footer>
</body>
</html>
