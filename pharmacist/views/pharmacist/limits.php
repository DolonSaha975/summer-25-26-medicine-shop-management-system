<?php $isEdit = !empty($editing); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase Limits &mdash; Pharmacist</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/pharmacist_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Purchase Limit Per Person</h1>
            <p class="page-sub">Cap how many units of a medicine one customer may buy</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['saved' => 'Limit saved.', 'deleted' => 'Limit removed.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <!-- ============ Add / Edit Form ============ -->
    <div class="card form-card" style="max-width:560px;">
        <h3 class="card-title"><?= $isEdit ? 'Edit Limit' : 'Set a New Limit' ?></h3>
        <form method="POST"
              action="index.php?page=pharmacist_limits&action=<?= $isEdit ? 'update&id=' . intval($editing['id']) : 'add' ?>"
              class="form" novalidate>
            <?= csrf_field() ?>
            <div class="field-row">
                <div class="field">
                    <label for="medicine_id">Medicine *</label>
                    <?php if ($isEdit): ?>
                        <input type="text" value="<?php
                            foreach ($medicines as $m) { if ($m['id'] == $editing['medicine_id']) { echo esc($m['name']); break; } }
                        ?>" disabled style="background:var(--bg);">
                    <?php else: ?>
                        <select id="medicine_id" name="medicine_id" required>
                            <option value="">-- Select Medicine --</option>
                            <?php foreach ($medicines as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?> (<?= esc($m['vendor_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="max_qty_per_customer">Max Qty Per Customer *</label>
                    <input type="number" id="max_qty_per_customer" name="max_qty_per_customer" min="1"
                           value="<?= esc($editing['max_qty_per_customer'] ?? '') ?>" placeholder="e.g. 5" required>
                </div>
            </div>
            <div class="form-actions">
                <?php if ($isEdit): ?>
                    <a href="index.php?page=pharmacist_limits" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Limit</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary">Save Limit</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ============ Table ============ -->
    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by medicine name...">
            </div>
            <span class="badge" id="resultCount"><?= count($limits) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th class="text-right">Max Qty / Customer</th>
                        <th>Set By</th>
                        <th>Updated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($limits)): ?>
                        <tr><td colspan="6" class="empty">No purchase limits set yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($limits as $i => $l): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($l['medicine_name']) ?></strong></td>
                            <td class="text-right"><span class="limit-tag"><?= esc($l['max_qty_per_customer']) ?> units</span></td>
                            <td><?= esc($l['set_by_name'] ?? '—') ?></td>
                            <td><?= date('d M Y', strtotime($l['updated_at'])) ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit"
                                   href="index.php?page=pharmacist_limits&action=edit&id=<?= $l['id'] ?>">Edit</a>
                                <a class="btn-sm btn-delete"
                                   href="index.php?page=pharmacist_limits&action=delete&id=<?= $l['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Remove this purchase limit?')">Remove</a>
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
            body.innerHTML = '<tr><td colspan="6" class="empty">No matching limits.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (l, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><strong>' + escapeHtml(l.medicine_name) + '</strong></td>' +
                '<td class="text-right"><span class="limit-tag">' + escapeHtml(l.max_qty_per_customer) + ' units</span></td>' +
                '<td>' + (l.set_by_name ? escapeHtml(l.set_by_name) : '&#8212;') + '</td>' +
                '<td>' + escapeHtml(l.updated_at ? l.updated_at.substring(0,10) : '') + '</td>' +
                '<td class="text-right">' +
                    '<a class="btn-sm btn-edit" href="index.php?page=pharmacist_limits&action=edit&id=' + l.id + '">Edit</a>' +
                    '<a class="btn-sm btn-delete" href="index.php?page=pharmacist_limits&action=delete&id=' + l.id + '&csrf_token=' + CSRF_TOKEN + '" onclick="return confirm(\'Remove this purchase limit?\')">Remove</a>' +
                '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=medicine_limits&q=' + encodeURIComponent(input.value.trim()),
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
