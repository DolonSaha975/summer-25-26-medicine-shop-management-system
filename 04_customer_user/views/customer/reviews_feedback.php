<?php $isEditingFeedback = !empty($editingFeedback); $isEditingReview = !empty($editingReview); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reviews &amp; Feedback &mdash; Medicine Shop Management</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Reviews &amp; Feedback</h1>
            <p class="page-sub">Rate a medicine, or tell us anything else — and manage everything you've sent</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = [
            'feedback_updated' => 'Feedback updated.',
            'feedback_deleted' => 'Feedback deleted.',
            'review_updated'   => 'Review updated — it will show again once approved.',
            'review_deleted'   => 'Review deleted.',
        ]; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= esc($success) ?></div>
    <?php endif; ?>

    <div class="reviews-feedback-grid">

        <!-- ============ REVIEWS COLUMN ============ -->
        <div>
            <?php if ($isEditingReview): ?>
            <div class="card form-card">
                <h3 class="card-title">Edit Review &mdash; <?= esc($editingReview['medicine_name']) ?></h3>
                <form method="POST" action="index.php?page=feedback&action=review_update&id=<?= $editingReview['id'] ?>" class="form" id="editReviewForm" novalidate>
    <?= csrf_field() ?>
                    <div class="field">
                        <label>Your Rating *</label>
                        <div class="star-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="erstar<?= $i ?>" value="<?= $i ?>"
                                       <?= (int)$editingReview['rating'] === $i ? 'checked' : '' ?>>
                                <label for="erstar<?= $i ?>">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="field">
                        <label for="comment">Comment</label>
                        <textarea id="comment" name="comment" rows="3"><?= esc($editingReview['comment'] ?? '') ?></textarea>
                    </div>
                    <div class="form-actions">
                        <a href="index.php?page=feedback" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
            <script>
            document.getElementById('editReviewForm').addEventListener('submit', function (e) {
                if (!document.querySelector('#editReviewForm input[name="rating"]:checked')) {
                    e.preventDefault();
                    alert('Please select a star rating.');
                }
            });
            </script>
            <?php else: ?>
            <div class="card form-card">
                <h3 class="card-title">Write a Review</h3>
                <form method="POST" action="index.php?page=feedback&action=review_add" class="form" id="addReviewForm" novalidate>
    <?= csrf_field() ?>
                    <div class="field">
                        <label for="medicine_id">Medicine *</label>
                        <select id="medicine_id" name="medicine_id" required>
                            <option value="">-- Select a medicine --</option>
                            <?php foreach ($medicines as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Your Rating *</label>
                        <div class="star-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="nrstar<?= $i ?>" value="<?= $i ?>">
                                <label for="nrstar<?= $i ?>">&#9733;</label>
                            <?php endfor; ?>
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
            <script>
            document.getElementById('addReviewForm').addEventListener('submit', function (e) {
                if (!document.querySelector('#addReviewForm input[name="rating"]:checked')) {
                    e.preventDefault();
                    alert('Please select a star rating.');
                }
            });
            </script>
            <?php endif; ?>

            <div class="card">
                <div class="card-toolbar">
                    <div class="search-wrap">
                        <input type="text" id="reviewSearch" class="search-input" placeholder="Search your reviews..." value="<?= esc($rq ?? '') ?>">
                    </div>
                    <span class="badge" id="reviewCount"><?= count($myReviews) ?> total</span>
                </div>
                <div style="padding:4px 20px 16px;" id="reviewList">
                    <?php if (empty($myReviews)): ?>
                        <p style="color:var(--text-muted);font-size:13.5px;padding:16px 0;">You haven't reviewed any medicines yet.</p>
                    <?php else: ?>
                        <?php foreach ($myReviews as $r): ?>
                            <div class="review-card">
                                <div class="review-head">
                                    <span class="review-name">
                                        <a href="index.php?page=medicine_detail&id=<?= $r['medicine_id'] ?>"><?= esc($r['medicine_name']) ?></a>
                                    </span>
                                    <span class="status-<?= esc($r['status']) ?>"><?= ucfirst(esc($r['status'])) ?></span>
                                </div>
                                <span class="stars"><?= str_repeat('&#9733;', (int)$r['rating']) ?><span class="stars-muted"><?= str_repeat('&#9733;', 5 - (int)$r['rating']) ?></span></span>
                                <?php if (!empty($r['comment'])): ?>
                                    <p class="review-comment"><?= esc($r['comment']) ?></p>
                                <?php endif; ?>
                                <div style="margin-top:8px;">
                                    <a class="btn-sm btn-edit" href="index.php?page=feedback&action=review_edit&id=<?= $r['id'] ?>">Edit</a>
                                    <a class="btn-sm btn-delete" href="index.php?page=feedback&action=review_delete&id=<?= $r['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                       onclick="return confirm('Delete this review?')">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ============ FEEDBACK COLUMN ============ -->
        <div>
            <?php if ($isEditingFeedback): ?>
            <div class="card form-card">
                <h3 class="card-title">Edit Feedback</h3>
                <form method="POST" action="index.php?page=feedback&action=feedback_update&id=<?= $editingFeedback['id'] ?>" class="form" novalidate>
    <?= csrf_field() ?>
                    <div class="field">
                        <label for="subject">Subject (optional)</label>
                        <input type="text" id="subject" name="subject" value="<?= esc($editingFeedback['subject']) ?>">
                    </div>
                    <div class="field">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="4" required><?= esc($editingFeedback['message']) ?></textarea>
                    </div>
                    <div class="form-actions">
                        <a href="index.php?page=feedback" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="card form-card">
                <h3 class="card-title">Send Feedback</h3>
                <form method="POST" action="index.php?page=feedback&action=feedback_add" class="form" novalidate>
    <?= csrf_field() ?>
                    <div class="field">
                        <label for="subject">Subject (optional)</label>
                        <input type="text" id="subject" name="subject" placeholder="e.g. Delivery was late">
                    </div>
                    <div class="field">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="4" placeholder="Tell us more..." required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Send Feedback</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-toolbar">
                    <div class="search-wrap">
                        <input type="text" id="feedbackSearch" class="search-input" placeholder="Search your feedback..." value="<?= esc($fq ?? '') ?>">
                    </div>
                    <span class="badge" id="feedbackCount"><?= count($myFeedback) ?> total</span>
                </div>
                <div style="padding:4px 20px 16px;" id="feedbackList">
                    <?php if (empty($myFeedback)): ?>
                        <p style="color:var(--text-muted);font-size:13.5px;padding:16px 0;">You haven't sent any feedback yet.</p>
                    <?php else: ?>
                        <?php foreach ($myFeedback as $f): ?>
                            <div style="padding:14px 0;border-bottom:1px solid var(--border);">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <strong><?= esc($f['subject']) ?></strong>
                                    <span class="status-<?= esc($f['status']) ?>"><?= ucfirst(esc($f['status'])) ?></span>
                                </div>
                                <p style="font-size:13.5px;color:var(--text);margin-top:4px;"><?= nl2br(esc($f['message'])) ?></p>
                                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= date('d M Y, h:i A', strtotime($f['created_at'])) ?></div>
                                <?php if (!empty($f['admin_reply'])): ?>
                                    <div style="margin-top:8px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 12px;">
                                        <strong style="font-size:12.5px;color:var(--primary);">MediShop Team:</strong>
                                        <p style="font-size:13px;margin-top:2px;"><?= nl2br(esc($f['admin_reply'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                <div style="margin-top:8px;">
                                    <a class="btn-sm btn-edit" href="index.php?page=feedback&action=feedback_edit&id=<?= $f['id'] ?>">Edit</a>
                                    <a class="btn-sm btn-delete" href="index.php?page=feedback&action=feedback_delete&id=<?= $f['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                       onclick="return confirm('Delete this feedback?')">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop &mdash; Medicine Shop Management System</footer>

<script src="assets/js/app.js"></script>
<script>
(function () {
    var CSRF_TOKEN = '<?php echo csrf_token(); ?>';

    function statusLabel(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    // ---- Reviews search ----
    var reviewInput = document.getElementById('reviewSearch');
    var reviewList  = document.getElementById('reviewList');
    var reviewCount = document.getElementById('reviewCount');
    var reviewTimer;

    function renderReviews(rows) {
        if (!rows.length) {
            reviewList.innerHTML = '<p style="color:var(--text-muted);font-size:13.5px;padding:16px 0;">No matching reviews.</p>';
            reviewCount.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (r) {
            var full = ''; for (var i = 0; i < r.rating; i++) full += '&#9733;';
            var empty = ''; for (var i = r.rating; i < 5; i++) empty += '&#9733;';
            html += '<div class="review-card">' +
                '<div class="review-head">' +
                    '<span class="review-name"><a href="index.php?page=medicine_detail&id=' + r.medicine_id + '">' + escapeHtml(r.medicine_name) + '</a></span>' +
                    '<span class="status-' + escapeHtml(r.status) + '">' + escapeHtml(statusLabel(r.status)) + '</span>' +
                '</div>' +
                '<span class="stars">' + full + '<span class="stars-muted">' + empty + '</span></span>' +
                (r.comment ? '<p class="review-comment">' + escapeHtml(r.comment) + '</p>' : '') +
                '<div style="margin-top:8px;">' +
                    '<a class="btn-sm btn-edit" href="index.php?page=feedback&action=review_edit&id=' + r.id + '">Edit</a>' +
                    '<a class="btn-sm btn-delete" href="index.php?page=feedback&action=review_delete&id=' + r.id + '&csrf_token=' + CSRF_TOKEN + '" onclick="return confirm(\'Delete this review?\')">Delete</a>' +
                '</div></div>';
        });
        reviewList.innerHTML = html;
        reviewCount.textContent = rows.length + (reviewInput.value.trim() ? ' results' : ' total');
    }

    reviewInput.addEventListener('input', function () {
        clearTimeout(reviewTimer);
        reviewTimer = setTimeout(function () {
            fetch('index.php?page=ajax&type=my_reviews&q=' + encodeURIComponent(reviewInput.value.trim()), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(renderReviews)
                .catch(function (e) { console.error(e); });
        }, 250);
    });

    // ---- Feedback search ----
    var fbInput = document.getElementById('feedbackSearch');
    var fbList  = document.getElementById('feedbackList');
    var fbCount = document.getElementById('feedbackCount');
    var fbTimer;

    function renderFeedback(rows) {
        if (!rows.length) {
            fbList.innerHTML = '<p style="color:var(--text-muted);font-size:13.5px;padding:16px 0;">No matching feedback.</p>';
            fbCount.textContent = '0 results';
            return;
        }
        var html = '';
        rows.forEach(function (f) {
            html += '<div style="padding:14px 0;border-bottom:1px solid var(--border);">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                    '<strong>' + escapeHtml(f.subject) + '</strong>' +
                    '<span class="status-' + escapeHtml(f.status) + '">' + escapeHtml(statusLabel(f.status)) + '</span>' +
                '</div>' +
                '<p style="font-size:13.5px;color:var(--text);margin-top:4px;">' + escapeHtml(f.message).replace(/\n/g,'<br>') + '</p>' +
                '<div style="font-size:12px;color:var(--text-muted);margin-top:4px;">' + escapeHtml(f.created_at ? f.created_at.substring(0,10) : '') + '</div>' +
                (f.admin_reply ? '<div style="margin-top:8px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 12px;">' +
                    '<strong style="font-size:12.5px;color:var(--primary);">MediShop Team:</strong>' +
                    '<p style="font-size:13px;margin-top:2px;">' + escapeHtml(f.admin_reply).replace(/\n/g,'<br>') + '</p></div>' : '') +
                '<div style="margin-top:8px;">' +
                    '<a class="btn-sm btn-edit" href="index.php?page=feedback&action=feedback_edit&id=' + f.id + '">Edit</a>' +
                    '<a class="btn-sm btn-delete" href="index.php?page=feedback&action=feedback_delete&id=' + f.id + '&csrf_token=' + CSRF_TOKEN + '" onclick="return confirm(\'Delete this feedback?\')">Delete</a>' +
                '</div></div>';
        });
        fbList.innerHTML = html;
        fbCount.textContent = rows.length + (fbInput.value.trim() ? ' results' : ' total');
    }

    fbInput.addEventListener('input', function () {
        clearTimeout(fbTimer);
        fbTimer = setTimeout(function () {
            fetch('index.php?page=ajax&type=my_feedback&q=' + encodeURIComponent(fbInput.value.trim()), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(renderFeedback)
                .catch(function (e) { console.error(e); });
        }, 250);
    });
})();
</script>
</body>
</html>
