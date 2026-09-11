<?php $isEdit = !empty($editing); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Supply &mdash; Supplier</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/supplier_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Stock Supply</h1>
            <p class="page-sub">Record new stock deliveries and manage supply history</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['added' => 'Stock supply recorded.', 'updated' => 'Supply record updated.', 'deleted' => 'Supply record deleted.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($lowStock)): ?>
        <div class="banner-card banner-warning">
            <div>
                <strong>Low stock alert:</strong>
                <?php foreach (array_slice($lowStock, 0, 6) as $m): ?>
                    <span class="badge badge-warning" style="margin-left:6px;"><?= esc($m['name']) ?>: <?= $m['availability'] ?> left</span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============ Add / Edit Form ============ -->
    <div class="card form-card" style="max-width:600px;">
        <h3 class="card-title">
            <?= $isEdit ? ' Edit Supply Record (#' . intval($editing['id']) . ')' : '&#43; Record New Stock Supply' ?>
        </h3>
        <form method="POST"
              action="index.php?page=supplier_stock&action=<?= $isEdit ? 'update&id=' . intval($editing['id']) : 'add' ?>"
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
                                <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?> (current: <?= $m['availability'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="quantity">Quantity Supplied *</label>
                    <input type="number" id="quantity" name="quantity" min="1"
                           value="<?= esc($editing['quantity'] ?? '') ?>" placeholder="e.g. 100" required>
                </div>
            </div>
            <div class="field">
                <label for="notes">Notes</label>
                <input type="text" id="notes" name="notes"
                       value="<?= esc($editing['notes'] ?? '') ?>" placeholder="e.g. Batch #, delivery reference...">
            </div>
            <div class="form-actions">
                <?php if ($isEdit): ?>
                    <a href="index.php?page=supplier_stock" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Record</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary">Save Supply</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ============ Table ============ -->
    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by medicine, notes...">
            </div>
            <span class="badge" id="resultCount"><?= count($supplies) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th class="text-right">Qty Supplied</th>
                        <th>Current Stock</th>
                        <th>Supplied By</th>
                        <th>Date</th>
                        <th>Notes</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($supplies)): ?>
                        <tr><td colspan="8" class="empty">No supply records yet. Add one above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($supplies as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($s['medicine_name']) ?></strong></td>
                            <td class="text-right">+<?= $s['quantity'] ?></td>
                            <td><?= $s['availability'] ?></td>
                            <td><?= esc($s['supplier_name']) ?></td>
                            <td><?= date('d M Y', strtotime($s['supply_date'])) ?></td>
                            <td><?= esc($s['notes'] ?? '—') ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit"
                                   href="index.php?page=supplier_stock&action=edit&id=<?= $s['id'] ?>">Edit</a>
                                <a class="btn-sm btn-delete"
                                   href="index.php?page=supplier_stock&action=delete&id=<?= $s['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Delete this supply record? Stock will be reduced back.')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> Medicine Shop Management &mdash; Supplier Portal</footer>

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
            body.innerHTML = '<tr><td colspan="8" class="empty">No matching records.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (s, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><strong>' + escapeHtml(s.medicine_name) + '</strong></td>' +
                '<td class="text-right">+' + s.quantity + '</td>' +
                '<td>' + s.availability + '</td>' +
                '<td>' + escapeHtml(s.supplier_name) + '</td>' +
                '<td>' + escapeHtml(s.supply_date ? s.supply_date.substring(0,10) : '') + '</td>' +
                '<td>' + (s.notes ? escapeHtml(s.notes) : '&#8212;') + '</td>' +
                '<td class="text-right">' +
                    '<a class="btn-sm btn-edit" href="index.php?page=supplier_stock&action=edit&id=' + s.id + '">Edit</a>' +
                    '<a class="btn-sm btn-delete" href="index.php?page=supplier_stock&action=delete&id=' + s.id + '&csrf_token=' + CSRF_TOKEN + '" onclick="return confirm(\'Delete this supply record? Stock will be reduced back.\')">Delete</a>' +
                '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=stock_supplies&q=' + encodeURIComponent(input.value.trim()),
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
