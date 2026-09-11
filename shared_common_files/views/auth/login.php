<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login &mdash; Medicine Shop Management</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

<div class="auth-shell">
    <div class="auth-card">
        <h2>Login</h2>
        <p class="muted">Select your portal and sign in</p>

        <div class="role-tabs" id="roleTabs">
            <button type="button" class="role-tab" data-role="admin">Admin</button>
            <button type="button" class="role-tab" data-role="pharmacist">Pharmacist</button>
            <button type="button" class="role-tab" data-role="supplier">Supplier</button>
            <button type="button" class="role-tab active" data-role="customer">Customer</button>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=login" class="form" novalidate>
    <?= csrf_field() ?>
            <input type="hidden" name="login_role" id="loginRoleInput" value="<?= esc($selectedRole) ?>">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                       value="<?= esc($rememberedEmail) ?>" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <label class="remember-me">
                <input type="checkbox" name="remember_me" value="1" <?= $rememberedEmail !== '' ? 'checked' : '' ?>>
                Remember Me
            </label>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <p class="auth-foot" id="registerHint">New here?
            <a href="index.php?page=register" id="registerLink">Create an account</a>
        </p>

        <p class="hint" id="demoHint" style="display:none;">admin@medicine.com / admin123</p>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
(function () {
    var tabs         = document.querySelectorAll('.role-tab');
    var roleInput    = document.getElementById('loginRoleInput');
    var registerHint = document.getElementById('registerHint');
    var registerLink = document.getElementById('registerLink');
    var demoHint      = document.getElementById('demoHint');

    function applyRole(role) {
        tabs.forEach(function (t) {
            t.classList.toggle('active', t.getAttribute('data-role') === role);
        });
        roleInput.value = role;
        registerHint.style.display = (role !== 'admin') ? 'block' : 'none';
        registerLink.href = 'index.php?page=register&role=' + role;
        demoHint.style.display = (role === 'admin') ? 'block' : 'none';
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            applyRole(tab.getAttribute('data-role'));
        });
    });

    applyRole(roleInput.value || 'customer');
})();
</script>

</body>
</html>
