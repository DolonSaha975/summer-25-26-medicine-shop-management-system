<?php $u = $_SESSION['user']; $cp = $_GET['page'] ?? ''; ?>
<header class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="index.php?page=pharmacist">
            <span>MediShop Pharmacist</span>
        </a>
        <nav class="nav-links">
            <a href="index.php?page=pharmacist"               <?= in_array($cp, ['pharmacist','pharmacist_dashboard']) ? 'class="active"' : '' ?>>Dashboard</a>
            <a href="index.php?page=pharmacist_prescriptions" <?= $cp === 'pharmacist_prescriptions' ? 'class="active"' : '' ?>>Prescriptions</a>
            <a href="index.php?page=pharmacist_limits"        <?= $cp === 'pharmacist_limits'        ? 'class="active"' : '' ?>>Purchase Limits</a>
            <a href="index.php?page=pharmacist_payments"      <?= $cp === 'pharmacist_payments'      ? 'class="active"' : '' ?>>Payments</a>
        </nav>
        <div class="nav-user">
            <span class="user-pill">
                <span class="user-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></span>
                <span class="user-meta">
                    <span class="user-name"><?= esc($u['name']) ?></span>
                    <span class="user-role">Pharmacist</span>
                </span>
            </span>
            <a href="index.php?page=logout" class="btn-logout">Logout</a>
        </div>
    </div>
</header>
