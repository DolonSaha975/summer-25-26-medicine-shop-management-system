<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard &mdash; MediShop</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/admin_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-sub">Welcome back, <?= esc($_SESSION['user']['name']) ?>!</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $totalMedicines ?></div>
                <div class="stat-label">Total Medicines</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $totalCustomers ?></div>
                <div class="stat-label">Registered Customers</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $totalOrders ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $pendingOrders ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <a href="index.php?page=admin_reviews" class="stat-card" style="text-decoration:none;color:inherit;">
            <div>
                <div class="stat-value"><?= $pendingReviews ?></div>
                <div class="stat-label">Reviews Awaiting Approval</div>
            </div>
        </a>
        <a href="index.php?page=admin_feedback" class="stat-card" style="text-decoration:none;color:inherit;">
            <div>
                <div class="stat-value"><?= $newFeedback ?></div>
                <div class="stat-label">New Feedback</div>
            </div>
        </a>
        <a href="index.php?page=admin_expiry" class="stat-card" style="text-decoration:none;color:inherit;">
            <div>
                <div class="stat-value"><?= $expiringCount ?></div>
                <div class="stat-label">Expiring Soon</div>
            </div>
        </a>
        <a href="index.php?page=admin_expiry" class="stat-card" style="text-decoration:none;color:inherit;">
            <div>
                <div class="stat-value"><?= $damagedCount ?></div>
                <div class="stat-label">Damaged Stock</div>
            </div>
        </a>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-toolbar" style="justify-content:space-between;">
            <span style="font-weight:700;font-size:16px;">Recent Orders</span>
            <a href="index.php?page=admin_orders" class="btn-sm btn-edit">View All</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="7" class="empty">No orders yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= esc($o['customer_name']) ?></td>
                            <td><strong>&#2547; <?= number_format($o['total_amount'], 2) ?></strong></td>
                            <td><?= esc($o['payment_method']) ?></td>
                            <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
                            <td><span class="status-<?= esc($o['status']) ?>"><?= ucfirst(esc($o['status'])) ?></span></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit" href="index.php?page=order_detail&id=<?= $o['id'] ?>">View</a>
                                <?php if ($o['status'] === 'pending'): ?>
                                <a class="btn-sm btn-accept"
                                   href="index.php?page=admin_orders&action=accept&id=<?= $o['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Accept order #<?= $o['id'] ?>?')">Accept</a>
                                <a class="btn-sm btn-reject"
                                   href="index.php?page=admin_orders&action=reject&id=<?= $o['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Reject order #<?= $o['id'] ?>?')">Reject</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick links -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="index.php?page=admin_medicines&action=add"  class="btn btn-primary">&#43; Add Medicine</a>
        <a href="index.php?page=admin_categories&action=add" class="btn btn-ghost">&#43; Add Category</a>
        <a href="index.php?page=admin_orders" class="btn btn-ghost">Manage Orders</a>
        <a href="index.php?page=admin_pharmacists" class="btn btn-ghost">Manage Pharmacists</a>
        <a href="index.php?page=admin_suppliers" class="btn btn-ghost">Manage Suppliers</a>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop Admin Panel</footer>
</body>
</html>
