<?php $u = $_SESSION['user']; $cp = $_GET['page'] ?? ''; ?>
<header class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="index.php?page=supplier">
            <span>MediShop Supplier</span>
        </a>
        <nav class="nav-links">
            <a href="index.php?page=supplier"           <?= in_array($cp, ['supplier','supplier_dashboard']) ? 'class="active"' : '' ?>>Dashboard</a>
            <a href="index.php?page=supplier_stock"     <?= $cp === 'supplier_stock'   ? 'class="active"' : '' ?>>Stock Supply</a>
            <a href="index.php?page=supplier_sales"     <?= $cp === 'supplier_sales'   ? 'class="active"' : '' ?>>Sale History</a>
            <a href="index.php?page=supplier_ratings"   <?= $cp === 'supplier_ratings' ? 'class="active"' : '' ?>>Store Ratings</a>
        </nav>
        <div class="nav-user">
            <span class="user-pill">
                <span class="user-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></span>
                <span class="user-meta">
                    <span class="user-name"><?= esc($u['name']) ?></span>
                    <span class="user-role">Supplier</span>
                </span>
            </span>
            <a href="index.php?page=logout" class="btn-logout">Logout</a>
        </div>
    </div>
</header>
