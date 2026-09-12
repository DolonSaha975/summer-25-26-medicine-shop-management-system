<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Expiry &amp; Damage &mdash; MediShop Admin</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/admin_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Expiry &amp; Damage Tracking</h1>
            <p class="page-sub">Medicines expiring within 60 days, and items marked as damaged</p>
        </div>
        <a href="index.php?page=admin_medicines" class="btn btn-ghost">&larr; Manage Medicines</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['marked' => 'Medicine marked as damaged.', 'cleared' => 'Damage flag cleared.', 'deleted' => 'Medicine removed.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ============ Expiring Soon ============ -->
    <div class="card">
        <div class="card-toolbar" style="justify-content:space-between;">
            <span style="font-weight:700;font-size:16px;">Expiring Within 60 Days</span>
            <span class="badge badge-warning"><?= count($expiring) ?> medicine(s)</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Vendor</th>
                        <th>Expiry Date</th>
                        <th class="text-right">Stock</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expiring)): ?>
                        <tr><td colspan="6" class="empty">Nothing expiring soon.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expiring as $i => $m): ?>
                        <?php
                            $daysLeft = (int)((strtotime($m['expiry_date']) - strtotime(date('Y-m-d'))) / 86400);
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($m['name']) ?></strong></td>
                            <td><?= esc($m['vendor_name']) ?></td>
                            <td>
                                <?= date('d M Y', strtotime($m['expiry_date'])) ?>
                                <?php if ($daysLeft < 0): ?>
                                    <span class="badge badge-danger">Expired</span>
                                <?php elseif ($daysLeft <= 14): ?>
                                    <span class="badge badge-danger"><?= $daysLeft ?> days left</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= $daysLeft ?> days left</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?= $m['availability'] ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit" href="index.php?page=admin_medicines&action=edit&id=<?= $m['id'] ?>">Edit</a>
                                <a class="btn-sm btn-delete" href="index.php?page=admin_expiry&action=delete&id=<?= $m['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Remove this expired medicine from inventory?')">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============ Damaged Stock ============ -->
    <div class="card">
        <div class="card-toolbar" style="justify-content:space-between;">
            <span style="font-weight:700;font-size:16px;">Marked as Damaged</span>
            <span class="badge badge-danger"><?= count($damaged) ?> medicine(s)</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Vendor</th>
                        <th>Damage Notes</th>
                        <th class="text-right">Stock</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($damaged)): ?>
                        <tr><td colspan="6" class="empty">No damaged stock reported.</td></tr>
                    <?php else: ?>
                        <?php foreach ($damaged as $i => $m): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($m['name']) ?></strong></td>
                            <td><?= esc($m['vendor_name']) ?></td>
                            <td><?= esc($m['damage_notes'] ?? '—') ?></td>
                            <td class="text-right"><?= $m['availability'] ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-accept" href="index.php?page=admin_expiry&action=clear_damaged&id=<?= $m['id'] ?>&csrf_token=<?= csrf_token() ?>">Clear Flag</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============ Mark a medicine as damaged ============ -->
    <div class="card form-card" style="max-width:600px;">
        <h3 class="card-title">&#43; Report Damaged Stock</h3>
        <form method="POST" action="index.php?page=admin_expiry&action=mark_damaged" class="form" novalidate
              onsubmit="return setDamagedId(this);">
    <?= csrf_field() ?>
            <input type="hidden" name="_id_placeholder">
            <div class="field">
                <label for="damage_medicine_id">Medicine *</label>
                <select id="damage_medicine_id" name="damage_medicine_id" required onchange="this.form.action='index.php?page=admin_expiry&action=mark_damaged&id='+this.value;">
                    <option value="">-- Select Medicine --</option>
                    <?php
                    $allMeds = getAllMedicines($conn);
                    foreach ($allMeds as $m):
                        if ((int)$m['is_damaged'] === 1) continue;
                    ?>
                        <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?> (<?= esc($m['vendor_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="damage_notes">Damage Notes *</label>
                <input type="text" id="damage_notes" name="damage_notes" placeholder="e.g. Packaging torn during transit" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Mark as Damaged</button>
            </div>
        </form>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop Admin Panel</footer>

<script src="assets/js/app.js"></script>
<script>
var CSRF_TOKEN = '<?php echo csrf_token(); ?>';
function setDamagedId(form) {
    var sel = document.getElementById('damage_medicine_id');
    if (!sel.value) { alert('Please select a medicine.'); return false; }
    form.action = 'index.php?page=admin_expiry&action=mark_damaged&id=' + sel.value + '&csrf_token=' + CSRF_TOKEN;
    return true;
}
</script>

</body>
</html>
