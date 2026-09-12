<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Customers &mdash; MediShop Admin</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/admin_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage Customers</h1>
            <p class="page-sub">View and manage all registered customers</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Customer deleted successfully.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input"
                       placeholder="Search by name, email or phone...">
            </div>
            <span class="badge" id="resultCount"><?= count($customers) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Joined</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="7" class="empty">No customers registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $i => $cust): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($cust['name']) ?></strong></td>
                            <td><?= esc($cust['email']) ?></td>
                            <td><?= esc($cust['phone'] ?? '—') ?></td>
                            <td><?= esc($cust['address'] ?? '—') ?></td>
                            <td><?= date('d M Y', strtotime($cust['created_at'])) ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-delete"
                                   href="index.php?page=admin_customers&action=delete&id=<?= $cust['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Delete customer <?= esc(addslashes($cust['name'])) ?>? This will also remove their cart and orders.')">
                                   Delete
                                </a>
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
            body.innerHTML = '<tr><td colspan="7" class="empty">No matching customers.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (c, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><strong>' + escapeHtml(c.name) + '</strong></td>' +
                '<td>' + escapeHtml(c.email) + '</td>' +
                '<td>' + (c.phone ? escapeHtml(c.phone) : '&#8212;') + '</td>' +
                '<td>' + (c.address ? escapeHtml(c.address) : '&#8212;') + '</td>' +
                '<td>' + escapeHtml(c.created_at ? c.created_at.substring(0,10) : '') + '</td>' +
                '<td class="text-right">' +
                    '<a class="btn-sm btn-delete" href="index.php?page=admin_customers&action=delete&id=' + c.id + '&csrf_token=' + CSRF_TOKEN +
                    '" onclick="return confirm(\'Delete this customer?\')">Delete</a>' +
                '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=customers&q=' + encodeURIComponent(input.value.trim()),
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
