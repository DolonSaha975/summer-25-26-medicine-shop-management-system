<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pharmacist Dashboard &mdash; Medicine Shop Management</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/pharmacist_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Pharmacist Dashboard</h1>
            <p class="page-sub">Welcome back, <?= esc($_SESSION['user']['name']) ?>!</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $pendingPrescriptions ?></div>
                <div class="stat-label">Pending Prescriptions</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $totalLimits ?></div>
                <div class="stat-label">Purchase Limits Set</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $pendingPayments ?></div>
                <div class="stat-label">Payments Awaiting Confirmation</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-toolbar" style="justify-content:space-between;">
            <span style="font-weight:700;font-size:16px;">Recent Prescription Records</span>
            <a href="index.php?page=pharmacist_prescriptions" class="btn-sm btn-edit">View All</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Medicine</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentPrescriptions)): ?>
                        <tr><td colspan="5" class="empty">No prescription records yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentPrescriptions as $p): ?>
                        <tr>
                            <td>#<?= $p['order_id'] ?></td>
                            <td><?= esc($p['medicine_name']) ?></td>
                            <td><?= esc($p['customer_name']) ?></td>
                            <td><span class="status-<?= esc($p['status']) ?>"><?= ucfirst(esc($p['status'])) ?></span></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit" href="index.php?page=pharmacist_prescriptions&action=verify&id=<?= $p['id'] ?>">Review</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="index.php?page=pharmacist_prescriptions" class="btn btn-primary">Verify Prescriptions</a>
        <a href="index.php?page=pharmacist_limits" class="btn btn-ghost">Set Purchase Limits</a>
        <a href="index.php?page=pharmacist_payments" class="btn btn-ghost">Confirm Payments</a>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> Medicine Shop Management &mdash; Pharmacist Portal</footer>
</body>
</html>
