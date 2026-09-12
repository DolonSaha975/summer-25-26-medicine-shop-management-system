<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Orders &mdash; MediShop Admin</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/admin_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage Orders</h1>
            <p class="page-sub">View all purchase requests, accept or reject pending orders</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['accepted' => 'Order accepted successfully.', 'rejected' => 'Order rejected.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input"
                       placeholder="Search by customer name, email, order ID or status...">
            </div>
            <span class="badge" id="resultCount"><?= count($orders) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th class="text-right">Total</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="8" class="empty">No orders yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong>#<?= $o['id'] ?></strong></td>
                            <td><?= esc($o['customer_name']) ?></td>
                            <td><?= esc($o['customer_email']) ?></td>
                            <td class="text-right"><strong>&#2547; <?= number_format($o['total_amount'], 2) ?></strong></td>
                            <td><?= esc($o['payment_method']) ?></td>
                            <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
                            <td><span class="status-<?= esc($o['status']) ?>"><?= ucfirst(esc($o['status'])) ?></span></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit"
                                   href="index.php?page=order_detail&id=<?= $o['id'] ?>">View</a>
                                <?php if ($o['status'] === 'pending'): ?>
                                <a class="btn-sm btn-accept"
                                   href="index.php?page=admin_orders&action=accept&id=<?= $o['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Accept order #<?= $o['id'] ?>?')">Accept</a>
                                <a class="btn-sm btn-reject"
                                   href="index.php?page=admin_orders&action=reject&id=<?= $o['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Reject order #<?= $o['id'] ?>?')">Reject</a>
                                <?php endif; ?>
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

    function statusBadge(status) {
        return '<span class="status-' + escapeHtml(status) + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }

    function render(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="8" class="empty">No matching orders.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (o) {
            var actionHtml = '<a class="btn-sm btn-edit" href="index.php?page=order_detail&id=' + o.id + '">View</a>';
            if (o.status === 'pending') {
                actionHtml +=
                    '<a class="btn-sm btn-accept" href="index.php?page=admin_orders&action=accept&id=' + o.id + '&csrf_token=' + CSRF_TOKEN +
                    '" onclick="return confirm(\'Accept order #' + o.id + '?\')">Accept</a>' +
                    '<a class="btn-sm btn-reject" href="index.php?page=admin_orders&action=reject&id=' + o.id + '&csrf_token=' + CSRF_TOKEN +
                    '" onclick="return confirm(\'Reject order #' + o.id + '?\')">Reject</a>';
            }
            var dateStr = o.order_date ? o.order_date.substring(0, 10) : '';
            html += '<tr>' +
                '<td><strong>#' + o.id + '</strong></td>' +
                '<td>' + escapeHtml(o.customer_name) + '</td>' +
                '<td>' + escapeHtml(o.customer_email) + '</td>' +
                '<td class="text-right"><strong>&#2547; ' + parseFloat(o.total_amount).toFixed(2) + '</strong></td>' +
                '<td>' + escapeHtml(o.payment_method) + '</td>' +
                '<td>' + escapeHtml(dateStr) + '</td>' +
                '<td>' + statusBadge(o.status) + '</td>' +
                '<td class="text-right">' + actionHtml + '</td>' +
                '</tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=orders&q=' + encodeURIComponent(input.value.trim()),
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
