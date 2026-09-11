<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rate Our Store &mdash; MediShop</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Rate Our Store</h1>
            <p class="page-sub">How was your overall experience shopping with us?</p>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(2,1fr);max-width:460px;">
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

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= esc($success) ?></div>
    <?php endif; ?>

    <div class="card form-card" style="max-width:520px;">
        <h3 class="card-title">Leave a Rating</h3>
        <form method="POST" action="index.php?page=rate_store" class="form" novalidate>
    <?= csrf_field() ?>
            <div class="field">
                <label>Your Rating *</label>
                <div class="star-input">
                    <input type="radio" name="rating" id="star5" value="5"><label for="star5" title="5 stars">&#9733;</label>
                    <input type="radio" name="rating" id="star4" value="4"><label for="star4" title="4 stars">&#9733;</label>
                    <input type="radio" name="rating" id="star3" value="3"><label for="star3" title="3 stars">&#9733;</label>
                    <input type="radio" name="rating" id="star2" value="2"><label for="star2" title="2 stars">&#9733;</label>
                    <input type="radio" name="rating" id="star1" value="1"><label for="star1" title="1 star">&#9733;</label>
                </div>
            </div>
            <div class="field">
                <label for="comment">Comment (optional)</label>
                <textarea id="comment" name="comment" rows="3" placeholder="What did you like or what can we improve?"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitRatingBtn">Submit Rating</button>
            </div>
        </form>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop &mdash; Medicine Shop Management System</footer>

<script src="assets/js/app.js"></script>
<script>
document.querySelector('form').addEventListener('submit', function (e) {
    var checked = document.querySelector('input[name="rating"]:checked');
    if (!checked) {
        e.preventDefault();
        alert('Please select a star rating before submitting.');
    }
});
</script>

</body>
</html>
