<?php $isReplying = !empty($replying); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Feedback &mdash; MediShop Admin</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/admin_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Feedback Section</h1>
            <p class="page-sub">Read and respond to customer feedback</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['replied' => 'Reply sent.', 'deleted' => 'Feedback deleted.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <?php if ($isReplying): ?>
    <div class="card form-card" style="max-width:640px;">
        <h3 class="card-title">Reply to Feedback #<?= $replying['id'] ?></h3>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">
            From: <strong><?= esc($replying['customer_name']) ?></strong> (<?= esc($replying['customer_email']) ?>)
        </p>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px;">
            <strong><?= esc($replying['subject']) ?></strong>
            <p style="margin-top:6px;font-size:13.5px;"><?= nl2br(esc($replying['message'])) ?></p>
        </div>
        <form method="POST" action="index.php?page=admin_feedback&action=send_reply&id=<?= $replying['id'] ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="admin_reply">Your Reply</label>
                <textarea id="admin_reply" name="admin_reply" rows="4" placeholder="Type your reply..."><?= esc($replying['admin_reply'] ?? '') ?></textarea>
            </div>
            <div class="form-actions">
                <a href="index.php?page=admin_feedback" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Send Reply &amp; Mark Resolved</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by subject, customer, status...">
            </div>
            <span class="badge" id="resultCount"><?= count($feedbackList) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($feedbackList)): ?>
                        <tr><td colspan="6" class="empty">No feedback submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($feedbackList as $i => $f): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= esc($f['customer_name']) ?></td>
                            <td><?= esc($f['subject']) ?></td>
                            <td><span class="status-<?= esc($f['status']) ?>"><?= ucfirst(esc($f['status'])) ?></span></td>
                            <td><?= date('d M Y', strtotime($f['created_at'])) ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit" href="index.php?page=admin_feedback&action=reply&id=<?= $f['id'] ?>">Reply</a>
                                <a class="btn-sm btn-delete" href="index.php?page=admin_feedback&action=delete&id=<?= $f['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Delete this feedback?')">Delete</a>
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
            body.innerHTML = '<tr><td colspan="6" class="empty">No matching feedback.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (f, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + escapeHtml(f.customer_name) + '</td>' +
                '<td>' + escapeHtml(f.subject) + '</td>' +
                '<td><span class="status-' + escapeHtml(f.status) + '">' + escapeHtml(f.status.charAt(0).toUpperCase() + f.status.slice(1)) + '</span></td>' +
                '<td>' + escapeHtml(f.created_at ? f.created_at.substring(0,10) : '') + '</td>' +
                '<td class="text-right">' +
                    '<a class="btn-sm btn-edit" href="index.php?page=admin_feedback&action=reply&id=' + f.id + '">Reply</a>' +
                    '<a class="btn-sm btn-delete" href="index.php?page=admin_feedback&action=delete&id=' + f.id + '&csrf_token=' + CSRF_TOKEN + '" onclick="return confirm(\'Delete this feedback?\')">Delete</a>' +
                '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=feedback&q=' + encodeURIComponent(input.value.trim()),
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
