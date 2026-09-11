<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile &mdash; MediShop</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/navbar.php'; ?>

<main class="main-content" style="max-width:640px;">
    <div class="page-header">
        <div>
            <h1 class="page-title">My Profile</h1>
            <p class="page-sub">Update your personal information</p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= esc($success) ?></div>
    <?php endif; ?>

    <div class="card form-card">
        <!-- Avatar preview -->
        <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border);">
            <div class="avatar-preview">
                <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                    <img src="<?= esc($user['profile_picture']) ?>" alt="Profile">
                <?php else: ?>
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-weight:700;font-size:17px;"><?= esc($user['name']) ?></div>
                <div style="color:var(--text-muted);font-size:13px;"><?= esc($user['email']) ?></div>
                <div style="color:var(--primary);font-size:13px;font-weight:600;margin-top:4px;">
                    <?= ucfirst(esc($user['role'])) ?>
                </div>
            </div>
        </div>

        <form method="POST" action="index.php?page=profile"
              enctype="multipart/form-data" class="form" novalidate>
    <?= csrf_field() ?>
            <div class="field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                       value="<?= esc($user['name']) ?>" required>
            </div>
            <div class="field">
                <label>Email Address</label>
                <input type="email" value="<?= esc($user['email']) ?>" disabled
                       style="background:#f8fafc;color:var(--text-muted);">
                <span style="font-size:12px;color:var(--text-muted);">Email cannot be changed</span>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone"
                           value="<?= esc($user['phone'] ?? '') ?>"
                           placeholder="+880 1XXXXXXXXX">
                </div>
                <div class="field">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address"
                           value="<?= esc($user['address'] ?? '') ?>"
                           placeholder="City / Area">
                </div>
            </div>
            <div class="field">
                <label for="profile_picture">Profile Picture</label>
                <input type="file" id="profile_picture" name="profile_picture"
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <span style="font-size:12px;color:var(--text-muted);">Max 2MB. JPG, PNG, GIF or WEBP</span>
            </div>
            <div class="form-actions">
                <a href="index.php?page=home" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    <div class="card form-card" style="margin-top:0;">
        <h3 class="card-title">Account Info</h3>
        <div style="font-size:14px;color:var(--text-muted);">
            Member since: <strong><?= date('d M Y', strtotime($user['created_at'])) ?></strong>
        </div>
        <div style="margin-top:12px;display:flex;gap:12px;">
            <a href="index.php?page=my_orders" class="btn btn-ghost">View My Orders</a>
            <a href="index.php?page=logout"    class="btn btn-danger">Logout</a>
        </div>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop &mdash; Medicine Shop Management System</footer>
</body>
</html>
