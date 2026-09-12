<?php $isEdit = !empty($editing); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Pharmacists &mdash; MediShop Admin</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/admin_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage Pharmacists</h1>
            <p class="page-sub">Create and manage pharmacist portal accounts</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['added' => 'Pharmacist account created.', 'updated' => 'Account updated.', 'deleted' => 'Account deleted.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <!-- ============ Add / Edit Form ============ -->
    <div class="card form-card" style="max-width:560px;">
        <h3 class="card-title">
            <?= $isEdit ? ' Edit Pharmacist (#' . intval($editing['id']) . ')' : '&#43; Add New Pharmacist' ?>
        </h3>
        <form method="POST"
              action="index.php?page=admin_pharmacists&action=<?= $isEdit ? 'update&id=' . intval($editing['id']) : 'add' ?>"
              class="form" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" value="<?= esc($editing['name'] ?? '') ?>" required>
            </div>
            <?php if (!$isEdit): ?>
            <div class="field-row">
                <div class="field">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" placeholder="pharmacist@example.com" required>
                </div>
                <div class="field">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                </div>
            </div>
            <?php else: ?>
            <div class="field">
                <label>Email</label>
                <input type="text" value="<?= esc($editing['email']) ?>" disabled style="background:var(--bg);">
            </div>
            <?php endif; ?>
            <div class="field">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= esc($editing['phone'] ?? '') ?>" placeholder="e.g. 017XXXXXXXX">
            </div>
            <div class="form-actions">
                <?php if ($isEdit): ?>
                    <a href="index.php?page=admin_pharmacists" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary">Create Account</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ============ Table ============ -->
    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name, email, phone...">
            </div>
            <span class="badge" id="resultCount"><?= count($pharmacists) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($pharmacists)): ?>
                        <tr><td colspan="6" class="empty">No pharmacist accounts yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pharmacists as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($p['name']) ?></strong></td>
                            <td><?= esc($p['email']) ?></td>
                            <td><?= esc($p['phone'] ?? '—') ?></td>
                            <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit" href="index.php?page=admin_pharmacists&action=edit&id=<?= $p['id'] ?>">Edit</a>
                                <a class="btn-sm btn-delete" href="index.php?page=admin_pharmacists&action=delete&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Delete this pharmacist account?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop Admin Panel</footer>

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
            body.innerHTML = '<tr><td colspan="6" class="empty">No matching pharmacists.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (p, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><strong>' + escapeHtml(p.name) + '</strong></td>' +
                '<td>' + escapeHtml(p.email) + '</td>' +
                '<td>' + (p.phone ? escapeHtml(p.phone) : '&#8212;') + '</td>' +
                '<td>' + escapeHtml(p.created_at ? p.created_at.substring(0,10) : '') + '</td>' +
                '<td class="text-right">' +
                    '<a class="btn-sm btn-edit" href="index.php?page=admin_pharmacists&action=edit&id=' + p.id + '">Edit</a>' +
                    '<a class="btn-sm btn-delete" href="index.php?page=admin_pharmacists&action=delete&id=' + p.id + '&csrf_token=' + CSRF_TOKEN + '" onclick="return confirm(\'Delete this pharmacist account?\')">Delete</a>' +
                '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=pharmacist_staff&q=' + encodeURIComponent(input.value.trim()),
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
