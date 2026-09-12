<?php $u = $_SESSION['user']; $cp = $_GET['page'] ?? ''; ?>
<header class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="index.php?page=admin">
            <span>MediShop Admin</span>
        </a>
        <nav class="nav-links">
            <a href="index.php?page=admin"             <?= $cp === 'admin'             ? 'class="active"' : '' ?>>Dashboard</a>
            <a href="index.php?page=admin_medicines"   <?= $cp === 'admin_medicines'   ? 'class="active"' : '' ?>>Medicines</a>
            <a href="index.php?page=admin_categories"  <?= $cp === 'admin_categories'  ? 'class="active"' : '' ?>>Categories</a>
            <a href="index.php?page=admin_expiry"      <?= $cp === 'admin_expiry'      ? 'class="active"' : '' ?>>Expiry/Damage</a>
            <a href="index.php?page=admin_orders"      <?= $cp === 'admin_orders'      ? 'class="active"' : '' ?>>Orders</a>
            <a href="index.php?page=admin_reviews"     <?= $cp === 'admin_reviews'     ? 'class="active"' : '' ?>>Reviews</a>
            <a href="index.php?page=admin_feedback"    <?= $cp === 'admin_feedback'    ? 'class="active"' : '' ?>>Feedback</a>
            <a href="index.php?page=admin_customers"   <?= $cp === 'admin_customers'   ? 'class="active"' : '' ?>>Customers</a>
            <a href="index.php?page=admin_pharmacists" <?= $cp === 'admin_pharmacists' ? 'class="active"' : '' ?>>Pharmacists</a>
            <a href="index.php?page=admin_suppliers"   <?= $cp === 'admin_suppliers'   ? 'class="active"' : '' ?>>Suppliers</a>
        </nav>
        <div class="nav-user">
            <span class="user-pill">
                <span class="user-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></span>
                <span class="user-meta">
                    <span class="user-name"><?= esc($u['name']) ?></span>
                    <span class="user-role">Admin</span>
                </span>
            </span>
            <a href="index.php?page=logout" class="btn-logout">Logout</a>
        </div>
    </div>
</header>
