<?php $isVerifying = !empty($editing); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prescription Verification &mdash; Pharmacist</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/pharmacist_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Prescription Verification</h1>
            <p class="page-sub">Review and verify prescription-required orders</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['added' => 'Prescription record added.', 'updated' => 'Prescription updated.', 'deleted' => 'Record deleted.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <?php if (!$isVerifying): ?>
    <!-- ============ Add Record ============ -->
    <div class="card form-card" style="max-width:600px;">
        <h3 class="card-title">Add Prescription Record</h3>
        <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:14px;">
            Records are created automatically for orders that need one, but you can log a manual check here too (e.g. for a phone order).
        </p>
        <form method="POST" action="index.php?page=pharmacist_prescriptions&action=add" class="form" novalidate>
    <?= csrf_field() ?>
            <div class="field-row">
                <div class="field">
                    <label for="order_id">Order *</label>
                    <select id="order_id" name="order_id" required>
                        <option value="">-- Select Order --</option>
                        <?php foreach ($orders as $o): ?>
                            <option value="<?= $o['id'] ?>">#<?= $o['id'] ?> &mdash; <?= esc($o['customer_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="medicine_id">Medicine *</label>
                    <select id="medicine_id" name="medicine_id" required>
                        <option value="">-- Select Medicine --</option>
                        <?php foreach ($medicines as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="note">Note</label>
                <input type="text" id="note" name="note" placeholder="Optional note">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Record</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($isVerifying): ?>
    <!-- ============ Verify Form ============ -->
    <div class="card form-card" style="max-width:600px;">
        <h3 class="card-title">Review Record &mdash; Order #<?= $editing['order_id'] ?></h3>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">
            Medicine: <strong><?= esc($editing['medicine_name']) ?></strong> &nbsp;|&nbsp;
            Customer: <strong><?= esc($editing['customer_name']) ?></strong>
        </p>
        <form method="POST" action="index.php?page=pharmacist_prescriptions&action=save&id=<?= $editing['id'] ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="status">Verification Status</label>
                <select id="status" name="status" required>
                    <option value="pending"  <?= $editing['status'] === 'pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="verified" <?= $editing['status'] === 'verified' ? 'selected' : '' ?>>Verified &mdash; Approve</option>
                    <option value="rejected" <?= $editing['status'] === 'rejected' ? 'selected' : '' ?>>Rejected &mdash; Deny</option>
                </select>
            </div>
            <div class="field">
                <label for="note">Pharmacist Note</label>
                <textarea id="note" name="note" placeholder="Verification notes, prescription reference, etc."><?= esc($editing['note'] ?? '') ?></textarea>
            </div>
            <div class="form-actions">
                <a href="index.php?page=pharmacist_prescriptions" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Verification</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ============ Table ============ -->
    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input"
                       placeholder="Search by medicine, customer, order#...">
            </div>
            <span class="badge" id="resultCount"><?= count($prescriptions) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order</th>
                        <th>Medicine</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Verified By</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($prescriptions)): ?>
                        <tr><td colspan="7" class="empty">No prescription records yet. They are created automatically when a customer orders a medicine that requires one.</td></tr>
                    <?php else: ?>
                        <?php foreach ($prescriptions as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>#<?= $p['order_id'] ?></td>
                            <td><strong><?= esc($p['medicine_name']) ?></strong></td>
                            <td><?= esc($p['customer_name']) ?></td>
                            <td><span class="status-<?= esc($p['status']) ?>"><?= ucfirst(esc($p['status'])) ?></span></td>
                            <td><?= esc($p['pharmacist_name'] ?? '—') ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit"
                                   href="index.php?page=pharmacist_prescriptions&action=verify&id=<?= $p['id'] ?>">Review</a>
                                <a class="btn-sm btn-delete"
                                   href="index.php?page=pharmacist_prescriptions&action=delete&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Delete this prescription record?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> Medicine Shop Management &mdash; Pharmacist Portal</footer>

<script src="assets/js/app.js"></script>
<script>
var CSRF_TOKEN = '<?php echo csrf_token(); ?>';
(function () {
    var input   = document.getElementById('searchInput');
    var body    = document.getElementById('tableBody');
    var counter = document.getElementById('resultCount');
    var timer;

    function render(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="7" class="empty">No matching records.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (p, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>#' + p.order_id + '</td>' +
                '<td><strong>' + escapeHtml(p.medicine_name) + '</strong></td>' +
                '<td>' + escapeHtml(p.customer_name) + '</td>' +
                '<td><span class="status-' + escapeHtml(p.status) + '">' + escapeHtml(p.status.charAt(0).toUpperCase() + p.status.slice(1)) + '</span></td>' +
                '<td>' + (p.pharmacist_name ? escapeHtml(p.pharmacist_name) : '&#8212;') + '</td>' +
                '<td class="text-right">' +
                    '<a class="btn-sm btn-edit" href="index.php?page=pharmacist_prescriptions&action=verify&id=' + p.id + '">Review</a>' +
                    '<a class="btn-sm btn-delete" href="index.php?page=pharmacist_prescriptions&action=delete&id=' + p.id + '&csrf_token=' + CSRF_TOKEN + '" onclick="return confirm(\'Delete this prescription record?\')">Delete</a>' +
                '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=prescriptions&q=' + encodeURIComponent(input.value.trim()),
                  { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(render)
                .catch(function (e) { console.error(e); });
        }, 220);
    });
})();
</script>

</body>
</html>
