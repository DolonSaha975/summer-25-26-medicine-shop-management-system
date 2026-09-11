<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supplier Dashboard &mdash; Medicine Shop Management</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/supplier_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Supplier Dashboard</h1>
            <p class="page-sub">Welcome back, <?= esc($_SESSION['user']['name']) ?>!</p>
        </div>
    </div>

    <?php if (!empty($lowStock)): ?>
        <div class="banner-card banner-warning">
            <div>
                <strong><?= count($lowStock) ?> medicine<?= count($lowStock) > 1 ? 's are' : ' is' ?> running low on stock.</strong>
                <div style="margin-top:2px;">
                    <?php foreach (array_slice($lowStock, 0, 5) as $m): ?>
                        <span class="badge badge-warning" style="margin-right:6px;"><?= esc($m['name']) ?>: <?= $m['availability'] ?> left</span>
                    <?php endforeach; ?>
                    <?php if (count($lowStock) > 5): ?><span>+<?= count($lowStock) - 5 ?> more</span><?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= count($lowStock) ?></div>
                <div class="stat-label">Low Stock Medicines</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= count($saleSummary) ?></div>
                <div class="stat-label">Medicines With Sales</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $ratingSummary['average'] ?> / 5</div>
                <div class="stat-label">Store Rating (<?= $ratingSummary['count'] ?> reviews)</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-toolbar" style="justify-content:space-between;">
            <span style="font-weight:700;font-size:16px;">Recent Stock Supplies</span>
            <a href="index.php?page=supplier_stock" class="btn-sm btn-edit">View All</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th class="text-right">Qty Supplied</th>
                        <th>Date</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentSupplies)): ?>
                        <tr><td colspan="4" class="empty">No stock supplies recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentSupplies as $s): ?>
                        <tr>
                            <td><strong><?= esc($s['medicine_name']) ?></strong></td>
                            <td class="text-right">+<?= $s['quantity'] ?></td>
                            <td><?= date('d M Y', strtotime($s['supply_date'])) ?></td>
                            <td><?= esc($s['notes'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="index.php?page=supplier_stock&action=add" class="btn btn-primary">&#43; Record Stock Supply</a>
        <a href="index.php?page=supplier_sales" class="btn btn-ghost">View Sale History</a>
        <a href="index.php?page=supplier_ratings" class="btn btn-ghost">View Store Ratings</a>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> Medicine Shop Management &mdash; Supplier Portal</footer>
</body>
</html>
