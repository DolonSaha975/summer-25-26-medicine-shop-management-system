<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($med['name']) ?> &mdash; MediShop</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/navbar.php'; ?>

<main class="main-content">
    <p style="margin-bottom:16px;">
        <a href="index.php?page=home">&larr; Back to Medicines</a>
    </p>

    <div class="card form-card">
        <div class="med-detail-layout">
            <!-- Image -->
            <div class="med-detail-img">
                <?php if (!empty($med['image_path']) && file_exists($med['image_path'])): ?>
                    <img src="<?= esc($med['image_path']) ?>" alt="<?= esc($med['name']) ?>">
                <?php else: ?>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div>
                <div class="med-detail-name"><?= esc($med['name']) ?></div>
                <div class="med-detail-vendor"><?= esc($med['vendor_name']) ?></div>
                <div class="detail-meta">
                    <span class="detail-tag"><?= esc($med['category_name']) ?></span>
                    <span class="detail-tag type-<?= esc($med['category_type']) ?> med-card-type">
                        <?= ucfirst(esc($med['category_type'])) ?>
                    </span>
                    <?php if ($med['availability'] > 0): ?>
                        <span class="detail-tag" style="color:var(--primary);">
                            <?= esc($med['availability']) ?> in stock
                        </span>
                    <?php else: ?>
                        <span class="detail-tag out-of-stock">Out of stock</span>
                    <?php endif; ?>
                    <?php if (!empty($med['requires_prescription'])): ?>
                        <span class="rx-tag">Prescription Required</span>
                    <?php endif; ?>
                    <?php if ($medicineLimit): ?>
                        <span class="limit-tag">Max <?= esc($medicineLimit['max_qty_per_customer']) ?> per customer</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($med['expiry_date'])): ?>
                    <p style="font-size:12.5px;color:var(--text-muted);margin-top:2px;">
                        Expiry: <?= date('d M Y', strtotime($med['expiry_date'])) ?>
                    </p>
                <?php endif; ?>
                <?php if ($ratingSummary['count'] > 0): ?>
                    <p style="margin-top:4px;">
                        <span class="stars"><?= str_repeat('&#9733;', round($ratingSummary['average'])) ?><span class="stars-muted"><?= str_repeat('&#9733;', 5 - round($ratingSummary['average'])) ?></span></span>
                        <span style="font-size:12.5px;color:var(--text-muted);"><?= $ratingSummary['average'] ?> (<?= $ratingSummary['count'] ?> review<?= $ratingSummary['count'] > 1 ? 's' : '' ?>)</span>
                    </p>
                <?php endif; ?>
                <div class="med-detail-price">&#2547; <?= number_format($med['price'], 2) ?></div>

                <?php if (!empty($med['description'])): ?>
                    <p class="med-desc"><?= esc($med['description']) ?></p>
                <?php endif; ?>

                <?php if ($med['availability'] > 0): ?>
                    <?php if (isCustomer()): ?>
                        <?php $maxQty = $medicineLimit ? min($med['availability'], $medicineLimit['max_qty_per_customer']) : $med['availability']; ?>
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <label style="font-weight:600;font-size:14px;">Quantity:</label>
                            <input type="number" id="detailQty" class="qty-input"
                                   min="1" max="<?= $maxQty ?>" value="1"
                                   style="width:72px;">
                            <button class="btn btn-primary"
                                    onclick="addToCartDetail(<?= $med['id'] ?>, <?= $maxQty ?>)">
                                Add to Cart
                            </button>
                        </div>
                    <?php else: ?>
                        <a href="index.php?page=login" class="btn btn-primary">
                            Login to Add to Cart
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============ Reviews Section ============ -->
    <div class="card" style="max-width:700px;">
        <div class="card-toolbar" style="justify-content:space-between;">
            <span style="font-weight:700;font-size:16px;">Customer Reviews</span>
            <span class="badge"><?= count($reviews) ?> review<?= count($reviews) !== 1 ? 's' : '' ?></span>
        </div>
        <div style="padding:6px 20px 18px;">
            <?php if (empty($reviews)): ?>
                <p style="color:var(--text-muted);font-size:13.5px;">No reviews yet. Be the first to review this medicine!</p>
            <?php else: ?>
                <?php foreach ($reviews as $r): ?>
                    <div class="review-card">
                        <div class="review-head">
                            <span class="review-name"><?= esc($r['customer_name']) ?></span>
                            <span class="review-date"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                        </div>
                        <span class="stars"><?= str_repeat('&#9733;', (int)$r['rating']) ?><span class="stars-muted"><?= str_repeat('&#9733;', 5 - (int)$r['rating']) ?></span></span>
                        <?php if (!empty($r['comment'])): ?>
                            <p class="review-comment"><?= esc($r['comment']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isCustomer()): ?>
    <div class="card form-card" style="max-width:520px;">
        <h3 class="card-title">Write a Review</h3>
        <?php if (!empty($reviewError)): ?>
            <div class="alert alert-error"><?= esc($reviewError) ?></div>
        <?php endif; ?>
        <?php if (!empty($reviewSuccess)): ?>
            <div class="alert alert-success"><?= esc($reviewSuccess) ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php?page=medicine_detail&id=<?= $med['id'] ?>" class="form" id="reviewForm" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="submit_review" value="1">
            <div class="field">
                <label>Your Rating *</label>
                <div class="star-input">
                    <input type="radio" name="rating" id="rstar5" value="5"><label for="rstar5">&#9733;</label>
                    <input type="radio" name="rating" id="rstar4" value="4"><label for="rstar4">&#9733;</label>
                    <input type="radio" name="rating" id="rstar3" value="3"><label for="rstar3">&#9733;</label>
                    <input type="radio" name="rating" id="rstar2" value="2"><label for="rstar2">&#9733;</label>
                    <input type="radio" name="rating" id="rstar1" value="1"><label for="rstar1">&#9733;</label>
                </div>
            </div>
            <div class="field">
                <label for="comment">Comment (optional)</label>
                <textarea id="comment" name="comment" rows="3" placeholder="Share your experience with this medicine..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
        </form>
    </div>
    <script src="assets/js/app.js"></script>
<script>
    document.getElementById('reviewForm').addEventListener('submit', function (e) {
        if (!document.querySelector('input[name="rating"]:checked')) {
            e.preventDefault();
            alert('Please select a star rating.');
        }
    });
    </script>
    <?php endif; ?>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop &mdash; Medicine Shop Management System</footer>

<div id="toast" style="position:fixed;bottom:24px;right:24px;background:var(--primary);color:#fff;
     padding:12px 20px;border-radius:var(--radius-sm);font-size:14px;font-weight:600;
     box-shadow:0 4px 20px rgba(0,0,0,.2);display:none;z-index:999;">
</div>

<script>
function showToast(msg, isError) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = isError ? 'var(--error)' : 'var(--primary)';
    t.style.display = 'block';
    setTimeout(function() { t.style.display = 'none'; }, 2500);
}

function addToCartDetail(medicineId, maxStock) {
    var qty = parseInt(document.getElementById('detailQty').value);
    if (isNaN(qty) || qty < 1) { showToast('Enter a valid quantity', true); return; }
    if (qty > maxStock)         { showToast('Only ' + maxStock + ' in stock', true); return; }
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
        var badge = document.querySelector('.cart-count');
        if (badge) badge.textContent = d.cart_count;
    })
    .catch(function() { showToast('Error', true); });
}
</script>

</body>
</html>
