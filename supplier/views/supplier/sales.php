<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sale History &mdash; Supplier</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/supplier_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Sale History</h1>
            <p class="page-sub">Units sold and revenue for accepted orders</p>
        </div>
    </div>

    <!-- Summary by medicine -->
    <div class="card">
        <div class="card-toolbar" style="justify-content:space-between;">
            <span style="font-weight:700;font-size:15px;">Summary by Medicine</span>
            <span class="badge"><?= count($summary) ?> medicines</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Vendor</th>
                        <th class="text-right">Units Sold</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summary)): ?>
                        <tr><td colspan="5" class="empty">No accepted sales yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($summary as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($s['medicine_name']) ?></strong></td>
                            <td><?= esc($s['vendor_name']) ?></td>
                            <td class="text-right"><?= $s['units_sold'] ?></td>
                            <td class="text-right fw-bold">&#2547; <?= number_format($s['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed transaction list -->
    <div class="card">
        <div class="card-toolbar">
            <div class="search-wrap">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by medicine or customer...">
            </div>
            <span class="badge" id="resultCount"><?= count($detailed) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Customer</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Subtotal</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($detailed)): ?>
                        <tr><td colspan="7" class="empty">No sales recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($detailed as $i => $d): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($d['medicine_name']) ?></strong></td>
                            <td><?= esc($d['customer_name']) ?></td>
                            <td class="text-right"><?= $d['quantity'] ?></td>
                            <td class="text-right">&#2547; <?= number_format($d['unit_price'], 2) ?></td>
                            <td class="text-right fw-bold">&#2547; <?= number_format($d['subtotal'], 2) ?></td>
                            <td><?= date('d M Y', strtotime($d['order_date'])) ?></td>
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
(function () {
    var input   = document.getElementById('searchInput');
    var body    = document.getElementById('tableBody');
    var counter = document.getElementById('resultCount');
    var timer;

    function render(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="7" class="empty">No matching sales.</td></tr>';
            counter.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (d, i) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><strong>' + escapeHtml(d.medicine_name) + '</strong></td>' +
                '<td>' + escapeHtml(d.customer_name) + '</td>' +
                '<td class="text-right">' + d.quantity + '</td>' +
                '<td class="text-right">&#2547; ' + parseFloat(d.unit_price).toFixed(2) + '</td>' +
                '<td class="text-right fw-bold">&#2547; ' + parseFloat(d.subtotal).toFixed(2) + '</td>' +
                '<td>' + escapeHtml(d.order_date ? d.order_date.substring(0,10) : '') + '</td></tr>';
        });
        body.innerHTML = html;
        counter.textContent = rows.length + (input.value.trim() ? ' results' : ' total');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch('index.php?page=ajax&type=sale_history&q=' + encodeURIComponent(input.value.trim()),
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
