<?php
// Shared navbar partial — included by customer-facing views
$cartCount = 0;
if (isCustomer()) {
    $cartCount = getCartCount($conn, $_SESSION['user']['id']);
}
$u = $_SESSION['user'] ?? null;
?>
<header class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="index.php?page=home">
            <span>MediShop</span>
        </a>

        <nav class="nav-links">
            <a href="index.php?page=home">Home</a>
            <?php if (isCustomer()): ?>
                <a href="index.php?page=my_orders">My Orders</a>
                <a href="index.php?page=feedback">Reviews &amp; Feedback</a>
                <a href="index.php?page=rate_store">Rate Us</a>
                <a href="index.php?page=profile">Profile</a>
            <?php endif; ?>
        </nav>

        <div class="nav-user">
            <?php if (isCustomer()): ?>
                <a href="index.php?page=cart" class="cart-btn">
                    Cart
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count" id="cartCountBadge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <?php if ($u): ?>
                <span class="user-pill">
                    <span class="user-avatar">
                        <?php if (!empty($u['profile_picture'] ?? '') && file_exists($u['profile_picture'] ?? '')): ?>
                            <img src="<?= esc($u['profile_picture']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </span>
                    <span class="user-meta">
                        <span class="user-name"><?= esc($u['name']) ?></span>
                        <span class="user-role"><?= ucfirst(esc($u['role'])) ?></span>
                    </span>
                </span>
                <a href="index.php?page=logout" class="btn-logout">Logout</a>
            <?php else: ?>
                <a href="index.php?page=login"    class="btn btn-ghost" style="padding:7px 16px;font-size:13px;">Login</a>
                <a href="index.php?page=register" class="btn btn-primary" style="padding:7px 16px;font-size:13px;">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>
