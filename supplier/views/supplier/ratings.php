<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Store Ratings &mdash; Supplier</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/supplier_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Store Ratings</h1>
            <p class="page-sub">What customers are saying about the store</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Rating removed.</div>
    <?php endif; ?>

    <div class="stats-grid" style="grid-template-columns:repeat(2,1fr);max-width:500px;">
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $summary['average'] ?> / 5</div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value"><?= $summary['count'] ?></div>
                <div class="stat-label">Total Ratings</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by customer or comment...">
            </div>
            <span class="badge" id="resultCount"><?= count($ratings) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($ratings)): ?>
                        <tr><td colspan="6" class="empty">No ratings submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ratings as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($r['customer_name']) ?></strong></td>
                            <td><span class="stars"><?= str_repeat('&#9733;', (int)$r['rating']) ?><span class="stars-muted"><?= str_repeat('&#9733;', 5 - (int)$r['rating']) ?></span></span></td>
                            <td><?= esc($r['comment'] ?? '—') ?></td>
                            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-delete"
                                   href="index.php?page=supplier_ratings&action=delete&id=<?= $r['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Remove this rating?')">Remove</a>
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

    function starsHtml(n) {
        var full = '';
        for (var i = 0; i < n; i++) full += '&#9733;';
        var empty = '';
        for (var i = n; i < 5; i++) empty += '&#9733;';
        return '<span class="stars">' + full + '<span class="stars-muted">' + empty + '</span></span>';
    }

    function render(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="6" class="empty">No matching ratings.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (r, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><strong>' + escapeHtml(r.customer_name) + '</strong></td>' +
                '<td>' + starsHtml(parseInt(r.rating)) + '</td>' +
                '<td>' + (r.comment ? escapeHtml(r.comment) : '&#8212;') + '</td>' +
                '<td>' + escapeHtml(r.created_at ? r.created_at.substring(0,10) : '') + '</td>' +
                '<td class="text-right">' +
                    '<a class="btn-sm btn-delete" href="index.php?page=supplier_ratings&action=delete&id=' + r.id + '&csrf_token=' + CSRF_TOKEN + '" onclick="return confirm(\'Remove this rating?\')">Remove</a>' +
                '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=store_ratings&q=' + encodeURIComponent(input.value.trim()),
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
