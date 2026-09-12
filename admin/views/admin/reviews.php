<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Reviews &mdash; MediShop Admin</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/admin_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage Reviews</h1>
            <p class="page-sub">Approve or reject customer medicine reviews</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['approved' => 'Review approved.', 'rejected' => 'Review rejected.', 'deleted' => 'Review deleted.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by medicine, customer, status...">
            </div>
            <span class="badge" id="resultCount"><?= count($reviews) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="7" class="empty">No reviews submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($r['medicine_name']) ?></strong></td>
                            <td><?= esc($r['customer_name']) ?></td>
                            <td><span class="stars"><?= str_repeat('&#9733;', (int)$r['rating']) ?><span class="stars-muted"><?= str_repeat('&#9733;', 5 - (int)$r['rating']) ?></span></span></td>
                            <td><?= esc($r['comment'] ?? '—') ?></td>
                            <td><span class="status-<?= esc($r['status']) ?>"><?= ucfirst(esc($r['status'])) ?></span></td>
                            <td class="text-right">
                                <?php if ($r['status'] !== 'approved'): ?>
                                <a class="btn-sm btn-accept" href="index.php?page=admin_reviews&action=approve&id=<?= $r['id'] ?>&csrf_token=<?= csrf_token() ?>">Approve</a>
                                <?php endif; ?>
                                <?php if ($r['status'] !== 'rejected'): ?>
                                <a class="btn-sm btn-reject" href="index.php?page=admin_reviews&action=reject&id=<?= $r['id'] ?>&csrf_token=<?= csrf_token() ?>">Reject</a>
                                <?php endif; ?>
                                <a class="btn-sm btn-delete" href="index.php?page=admin_reviews&action=delete&id=<?= $r['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Delete this review?')">Delete</a>
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

    function starsHtml(n) {
        var full = ''; for (var i = 0; i < n; i++) full += '&#9733;';
        var empty = ''; for (var i = n; i < 5; i++) empty += '&#9733;';
        return '<span class="stars">' + full + '<span class="stars-muted">' + empty + '</span></span>';
    }

    function actionsCell(r) {
        var html = '';
        if (r.status !== 'approved') html += '<a class="btn-sm btn-accept" href="index.php?page=admin_reviews&action=approve&id=' + r.id + '&csrf_token=' + CSRF_TOKEN + '">Approve</a>';
        if (r.status !== 'rejected') html += '<a class="btn-sm btn-reject" href="index.php?page=admin_reviews&action=reject&id=' + r.id + '&csrf_token=' + CSRF_TOKEN + '">Reject</a>';
        html += '<a class="btn-sm btn-delete" href="index.php?page=admin_reviews&action=delete&id=' + r.id + '&csrf_token=' + CSRF_TOKEN + '" onclick="return confirm(\'Delete this review?\')">Delete</a>';
        return html;
    }

    function render(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="7" class="empty">No matching reviews.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (r, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><strong>' + escapeHtml(r.medicine_name) + '</strong></td>' +
                '<td>' + escapeHtml(r.customer_name) + '</td>' +
                '<td>' + starsHtml(parseInt(r.rating)) + '</td>' +
                '<td>' + (r.comment ? escapeHtml(r.comment) : '&#8212;') + '</td>' +
                '<td><span class="status-' + escapeHtml(r.status) + '">' + escapeHtml(r.status.charAt(0).toUpperCase() + r.status.slice(1)) + '</span></td>' +
                '<td class="text-right">' + actionsCell(r) + '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=reviews&q=' + encodeURIComponent(input.value.trim()),
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
