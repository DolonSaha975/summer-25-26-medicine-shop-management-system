<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Cart &mdash; MediShop</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">My Cart</h1>
            <p class="page-sub">Review your selected medicines before checkout</p>
        </div>
        <a href="index.php?page=home" class="btn btn-ghost">&larr; Continue Shopping</a>
    </div>

    <?php if (empty($items)): ?>
        <div class="card form-card" style="text-align:center;padding:60px 24px;">
            <h3 style="margin-bottom:8px;">Your cart is empty</h3>
            <p style="color:var(--text-muted);margin-bottom:20px;">Browse medicines and add to cart</p>
            <a href="index.php?page=home" class="btn btn-primary">Browse Medicines</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <!-- Cart Items -->
            <div class="card" id="cartItemsCard">
                <?php foreach ($items as $item): ?>
                <div class="cart-item" id="cartRow_<?= $item['id'] ?>">
                    <div class="cart-item-img">
                        <?php if (!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                            <img src="<?= esc($item['image_path']) ?>" alt="">
                        <?php else: ?>
                        <?php endif; ?>
                    </div>
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?= esc($item['name']) ?></div>
                        <div class="cart-item-vendor"><?= esc($item['vendor_name']) ?></div>
                        <div class="cart-item-price">&#2547; <?= number_format($item['price'], 2) ?> each</div>
                    </div>
                    <div class="qty-controls">
                        <button class="qty-btn"
                                onclick="changeQty(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>, <?= $item['availability'] ?>)">
                            &minus;
                        </button>
                        <span class="qty-display" id="qtyDisplay_<?= $item['id'] ?>">
                            <?= $item['quantity'] ?>
                        </span>
                        <button class="qty-btn"
                                onclick="changeQty(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>, <?= $item['availability'] ?>)">
                            &#43;
                        </button>
                    </div>
                    <div class="subtotal" id="subtotal_<?= $item['id'] ?>">
                        &#2547; <?= number_format($item['price'] * $item['quantity'], 2) ?>
                    </div>
                    <button class="remove-btn"
                            onclick="removeItem(<?= $item['id'] ?>)">
                        Remove
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary -->
            <div class="summary-card">
                <div class="summary-title">Order Summary</div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="cartTotal">&#2547; <?= number_format($total, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery</span>
                    <span style="color:var(--primary);font-weight:600;">Free</span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span id="cartTotalBottom">&#2547; <?= number_format($total, 2) ?></span>
                </div>
                <a href="index.php?page=checkout&step=address"
                   class="btn btn-primary btn-block" style="margin-top:16px;">
                    Proceed to Checkout &rarr;
                </a>
            </div>
        </div>
    <?php endif; ?>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop</footer>

<div id="toast" style="position:fixed;bottom:24px;right:24px;background:var(--primary);color:#fff;
     padding:12px 20px;border-radius:var(--radius-sm);font-size:14px;font-weight:600;
     box-shadow:0 4px 20px rgba(0,0,0,.2);display:none;z-index:999;">
</div>

<script src="assets/js/app.js"></script>
<script>
function showToast(msg, isError) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = isError ? 'var(--error)' : 'var(--primary)';
    t.style.display = 'block';
    setTimeout(function() { t.style.display = 'none'; }, 2500);
}

function updateCartBadge(count) {
    document.querySelectorAll('.cart-count, #cartCountBadge').forEach(function(b) {
        b.textContent = count;
        b.style.display = count > 0 ? 'flex' : 'none';
    });
}

function changeQty(cartId, newQty, maxStock) {
    if (newQty < 1) { showToast('Minimum quantity is 1', true); return; }
    if (newQty > maxStock) { showToast('Only ' + maxStock + ' in stock', true); return; }
    fetch('index.php?page=ajax&type=update_cart', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart_id: cartId, quantity: newQty })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.error) { showToast(d.error, true); return; }
        document.getElementById('qtyDisplay_' + cartId).textContent = newQty;
        document.getElementById('subtotal_' + cartId).textContent = '৳ ' + d.new_subtotal;
        document.getElementById('cartTotal').textContent = '৳ ' + d.cart_total;
        document.getElementById('cartTotalBottom').textContent = '৳ ' + d.cart_total;
        updateCartBadge(d.cart_count);
    })
    .catch(function() { showToast('Error updating cart', true); });
}

function removeItem(cartId) {
    if (!confirm('Remove this item from cart?')) return;
    var body = new URLSearchParams({ cart_id: cartId }).toString();
    fetch('index.php?page=ajax&type=remove_from_cart', {
        method: 'DELETE',
        credentials: 'same-origin',
        body: body,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.error) { showToast(d.error, true); return; }
        var row = document.getElementById('cartRow_' + cartId);
        if (row) row.remove();
        document.getElementById('cartTotal').textContent = '৳ ' + d.cart_total;
        document.getElementById('cartTotalBottom').textContent = '৳ ' + d.cart_total;
        updateCartBadge(d.cart_count);
        if (d.cart_count === 0) location.reload();
    })
    .catch(function() { showToast('Error removing item', true); });
}
</script>

</body>
</html>
