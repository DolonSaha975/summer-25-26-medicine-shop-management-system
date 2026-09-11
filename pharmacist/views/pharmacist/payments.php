<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Confirmation &mdash; Pharmacist</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/pharmacist_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Payment Confirmation</h1>
            <p class="page-sub">Verify and confirm order payments</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['paid' => 'Payment marked as paid.', 'refunded' => 'Payment marked as refunded.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by customer, method, transaction...">
            </div>
            <span class="badge" id="resultCount"><?= count($payments) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th>Verified By</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="8" class="empty">No payments recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>#<?= $p['order_ref'] ?></td>
                            <td><?= esc($p['customer_name']) ?></td>
                            <td><?= esc($p['payment_method']) ?></td>
                            <td class="text-right">&#2547; <?= number_format($p['amount'], 2) ?></td>
                            <td><span class="status-<?= esc($p['status']) ?>"><?= ucfirst(esc($p['status'])) ?></span></td>
                            <td><?= esc($p['verified_by_name'] ?? '—') ?></td>
                            <td class="text-right">
                                <?php if ($p['status'] === 'pending'): ?>
                                <a class="btn-sm btn-accept"
                                   href="index.php?page=pharmacist_payments&action=mark_paid&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Mark this payment as paid?')">Mark Paid</a>
                                <?php elseif ($p['status'] === 'paid'): ?>
                                <a class="btn-sm btn-reject"
                                   href="index.php?page=pharmacist_payments&action=mark_refunded&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Mark this payment as refunded?')">Refund</a>
                                <?php else: ?>
                                    &#8212;
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

<footer class="footer">&copy; <?= date('Y') ?> Medicine Shop Management &mdash; Pharmacist Portal</footer>

<script src="assets/js/app.js"></script>
<script>
var CSRF_TOKEN = '<?php echo csrf_token(); ?>';
(function () {
    var input   = document.getElementById('searchInput');
    var body    = document.getElementById('tableBody');
    var counter = document.getElementById('resultCount');
    var timer;

    function actionCell(p) {
        if (p.status === 'pending') {
            return '<a class="btn-sm btn-accept" href="index.php?page=pharmacist_payments&action=mark_paid&id=' + p.id + '&csrf_token=' + CSRF_TOKEN +
                   '" onclick="return confirm(\'Mark this payment as paid?\')">Mark Paid</a>';
        }
        if (p.status === 'paid') {
            return '<a class="btn-sm btn-reject" href="index.php?page=pharmacist_payments&action=mark_refunded&id=' + p.id + '&csrf_token=' + CSRF_TOKEN +
                   '" onclick="return confirm(\'Mark this payment as refunded?\')">Refund</a>';
        }
        return '&#8212;';
    }

    function render(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="8" class="empty">No matching payments.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (p, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>#' + p.order_ref + '</td>' +
                '<td>' + escapeHtml(p.customer_name) + '</td>' +
                '<td>' + escapeHtml(p.payment_method) + '</td>' +
                '<td class="text-right">&#2547; ' + parseFloat(p.amount).toFixed(2) + '</td>' +
                '<td><span class="status-' + escapeHtml(p.status) + '">' + escapeHtml(p.status.charAt(0).toUpperCase() + p.status.slice(1)) + '</span></td>' +
                '<td>' + (p.verified_by_name ? escapeHtml(p.verified_by_name) : '&#8212;') + '</td>' +
                '<td class="text-right">' + actionCell(p) + '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=payments&q=' + encodeURIComponent(input.value.trim()),
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
