<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Medicines &mdash; MediShop</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Browse Medicines</h1>
            <p class="page-sub">Find the medicine you need — filter by vendor or type</p>
        </div>
    </div>

    <!-- ============ Search & Filter Bar ============ -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-toolbar" style="flex-wrap:wrap;gap:12px;">
            <div class="search-wrap" style="max-width:340px;">
                <input type="text" id="searchInput" class="search-input"
                       placeholder="Search medicines..."
                       value="<?= esc($search) ?>">
            </div>
            <select id="vendorFilter" class="filter-select">
                <option value="">All Vendors</option>
                <?php foreach ($vendors as $v): ?>
                    <option value="<?= esc($v['vendor_name']) ?>"
                        <?= $vendorFlt === $v['vendor_name'] ? 'selected' : '' ?>>
                        <?= esc($v['vendor_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="typeFilter" class="filter-select">
                <option value="">All Types</option>
                <option value="solid"  <?= $typeFlt === 'solid'  ? 'selected' : '' ?>>Solid</option>
                <option value="liquid" <?= $typeFlt === 'liquid' ? 'selected' : '' ?>>Liquid</option>
            </select>
            <span class="badge" id="resultCount"><?= count($medicines) ?> medicines</span>
        </div>
    </div>

    <!-- ============ Medicine Grid ============ -->
    <div class="medicines-grid" id="medicinesGrid">
        <?php if (empty($medicines)): ?>
            <p style="color:var(--text-muted);padding:20px;">No medicines found.</p>
        <?php else: ?>
            <?php foreach ($medicines as $med): ?>
            <div class="med-card">
                <div class="med-card-img">
                    <?php if (!empty($med['image_path']) && file_exists($med['image_path'])): ?>
                        <img src="<?= esc($med['image_path']) ?>" alt="<?= esc($med['name']) ?>">
                    <?php else: ?>
                    <?php endif; ?>
                </div>
                <div class="med-card-body">
                    <div class="med-card-title"><?= esc($med['name']) ?></div>
                    <div class="med-card-vendor"><?= esc($med['vendor_name']) ?></div>
                    <span class="med-card-type type-<?= esc($med['category_type']) ?>">
                        <?= ucfirst(esc($med['category_type'])) ?>
                    </span>
                    <div class="med-card-price">&#2547; <?= number_format($med['price'], 2) ?></div>
                    <div class="med-card-stock">
                        <?php if ($med['availability'] > 0): ?>
                            <?= esc($med['availability']) ?> in stock
                        <?php else: ?>
                            <span class="out-of-stock">Out of stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="med-card-actions">
                        <a href="index.php?page=medicine_detail&id=<?= $med['id'] ?>"
                           class="btn btn-ghost" style="padding:7px 12px;font-size:13px;flex:1;text-align:center;">
                           Details
                        </a>
                        <?php if ($med['availability'] > 0 && isCustomer()): ?>
                            <input type="number" class="qty-input" min="1"
                                   max="<?= $med['availability'] ?>"
                                   value="1" id="qty_<?= $med['id'] ?>">
                            <button class="btn btn-primary"
                                    style="padding:7px 12px;font-size:13px;"
                                    onclick="addToCart(<?= $med['id'] ?>, <?= $med['availability'] ?>, 'qty_<?= $med['id'] ?>')">
                                &#43; Cart
                            </button>
                        <?php elseif ($med['availability'] > 0 && !isLoggedIn()): ?>
                            <a href="index.php?page=login"
                               class="btn btn-primary" style="padding:7px 12px;font-size:13px;">
                               &#43; Cart
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop &mdash; Medicine Shop Management System</footer>

<!-- Toast -->
<div id="toast" style="position:fixed;bottom:24px;right:24px;background:var(--primary);color:#fff;
     padding:12px 20px;border-radius:var(--radius-sm);font-size:14px;font-weight:600;
     box-shadow:0 4px 20px rgba(0,0,0,.2);display:none;z-index:999;transition:all .3s;">
</div>

<script src="assets/js/app.js"></script>
<script>
(function () {
    var searchInput  = document.getElementById('searchInput');
    var vendorFilter = document.getElementById('vendorFilter');
    var typeFilter   = document.getElementById('typeFilter');
    var grid         = document.getElementById('medicinesGrid');
    var counter      = document.getElementById('resultCount');
    var timer;

    function renderGrid(meds) {
        if (!meds.length) {
            grid.innerHTML = '<p style="color:var(--text-muted);padding:20px;">No medicines found.</p>';
            counter.textContent = '0 medicines';
            return;
        }
        var html = '';
        meds.forEach(function(m) {
            var imgHtml = m.image_path
                ? '<img src="' + escapeHtml(m.image_path) + '" alt="' + escapeHtml(m.name) + '">'
                : '';
            var stockHtml = parseInt(m.availability) > 0
                ? ' ' + escapeHtml(m.availability) + ' in stock'
                : '<span class="out-of-stock">Out of stock</span>';
            var cartHtml = '';
            <?php if (isCustomer()): ?>
            if (parseInt(m.availability) > 0) {
                cartHtml =
                    '<input type="number" class="qty-input" min="1" max="' + escapeHtml(m.availability) + '" value="1" id="qty_ajax_' + m.id + '">' +
                    '<button class="btn btn-primary" style="padding:7px 12px;font-size:13px;" ' +
                    'onclick="addToCart(' + m.id + ',' + m.availability + ',\'qty_ajax_' + m.id + '\')">&#43; Cart</button>';
            }
            <?php elseif (!isLoggedIn()): ?>
            if (parseInt(m.availability) > 0) {
                cartHtml = '<a href="index.php?page=login" class="btn btn-primary" style="padding:7px 12px;font-size:13px;">&#43; Cart</a>';
            }
            <?php endif; ?>

            html += '<div class="med-card">' +
                '<div class="med-card-img">' + imgHtml + '</div>' +
                '<div class="med-card-body">' +
                    '<div class="med-card-title">' + escapeHtml(m.name) + '</div>' +
                    '<div class="med-card-vendor"> ' + escapeHtml(m.vendor_name) + '</div>' +
                    '<span class="med-card-type type-' + escapeHtml(m.category_type) + '">' + (m.category_type === 'liquid' ? 'Liquid' : 'Solid') + '</span>' +
                    '<div class="med-card-price">&#2547; ' + parseFloat(m.price).toFixed(2) + '</div>' +
                    '<div class="med-card-stock">' + stockHtml + '</div>' +
                    '<div class="med-card-actions">' +
                        '<a href="index.php?page=medicine_detail&id=' + m.id + '" class="btn btn-ghost" style="padding:7px 12px;font-size:13px;flex:1;text-align:center;">Details</a>' +
                        cartHtml +
                    '</div>' +
                '</div></div>';
        });
        grid.innerHTML = html;
        counter.textContent = meds.length + ' medicine' + (meds.length !== 1 ? 's' : '');
    }

    function doSearch() {
        var q      = searchInput.value.trim();
        var vendor = vendorFilter.value;
        var ftype  = typeFilter.value;
        var url    = 'index.php?page=ajax&type=search_medicines&q=' +
                     encodeURIComponent(q) +
                     '&vendor=' + encodeURIComponent(vendor) +
                     '&ftype=' + encodeURIComponent(ftype);
        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(renderGrid)
            .catch(function(e) { console.error(e); });
    }

    function debounced() {
        clearTimeout(timer);
        timer = setTimeout(doSearch, 250);
    }

    searchInput.addEventListener('input', debounced);
    vendorFilter.addEventListener('change', debounced);
    typeFilter.addEventListener('change', debounced);
})();

// Add to cart
function showToast(msg, isError) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = isError ? 'var(--error)' : 'var(--primary)';
    t.style.display = 'block';
    setTimeout(function() { t.style.display = 'none'; }, 2500);
}

function addToCart(medicineId, maxStock, qtyInputId) {
    var qtyEl = document.getElementById(qtyInputId);
    var qty   = parseInt(qtyEl ? qtyEl.value : 1);
    if (isNaN(qty) || qty < 1) { showToast('Quantity must be at least 1', true); return; }
    if (qty > maxStock) { showToast('Only ' + maxStock + ' in stock', true); return; }

    fetch('index.php?page=ajax&type=add_to_cart', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ medicine_id: medicineId, quantity: qty })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.error) { showToast(d.error, true); return; }
        showToast('Added to cart!', false);
        // Update cart badge
        var badges = document.querySelectorAll('.cart-count, #cartCountBadge');
        badges.forEach(function(b) { b.textContent = d.cart_count; b.style.display = 'flex'; });
    })
    .catch(function(e) { showToast('Error adding to cart', true); });
}
</script>

</body>
</html>
