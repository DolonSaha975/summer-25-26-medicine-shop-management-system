<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders &mdash; MediShop</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">My Orders</h1>
            <p class="page-sub">Track all your purchase history</p>
        </div>
        <a href="index.php?page=home" class="btn btn-ghost">&larr; Continue Shopping</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card form-card" style="text-align:center;padding:60px 24px;">
            <h3 style="margin-bottom:8px;">No orders yet</h3>
            <p style="color:var(--text-muted);margin-bottom:20px;">Start shopping to see your orders here</p>
            <a href="index.php?page=home" class="btn btn-primary">Browse Medicines</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Delivery</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?= $order['id'] ?></strong></td>
                            <td><?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></td>
                            <td><strong>&#2547; <?= number_format($order['total_amount'], 2) ?></strong></td>
                            <td>
                                <?php if ($order['delivery_type'] === 'pickup'): ?>
                                    <span class="badge badge-info">Pickup</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Delivery</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($order['payment_method']) ?></td>
                            <td>
                                <span class="status-<?= esc($order['status']) ?>">
                                    <?= ucfirst(esc($order['status'])) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit"
                                   href="index.php?page=order_detail&id=<?= $order['id'] ?>">
                                   View Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop &mdash; Medicine Shop Management System</footer>
</body>
</html>
