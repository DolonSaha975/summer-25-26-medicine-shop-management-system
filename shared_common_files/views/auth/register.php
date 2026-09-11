<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register &mdash; Medicine Shop Management</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

<div class="auth-shell">
    <div class="auth-card">
        <h2>Create Account</h2>
        <p class="muted">Sign up as a customer, pharmacist, or supplier</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= esc($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= esc($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=register" class="form" novalidate>
    <?= csrf_field() ?>
            <div class="field">
                <label for="role">I am registering as</label>
                <select id="role" name="role" class="role-select" required>
                    <option value="customer"   <?= $old['role'] === 'customer'   ? 'selected' : '' ?>>Customer</option>
                    <option value="pharmacist" <?= $old['role'] === 'pharmacist' ? 'selected' : '' ?>>Pharmacist</option>
                    <option value="supplier"   <?= $old['role'] === 'supplier'   ? 'selected' : '' ?>>Supplier</option>
                </select>
            </div>
            <div class="field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                       value="<?= esc($old['name']) ?>"
                       placeholder="e.g. Rahim Uddin" required>
            </div>
            <div class="field">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= esc($old['email']) ?>"
                       placeholder="you@example.com" required>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone"
                           value="<?= esc($old['phone']) ?>"
                           placeholder="+880 1XXXXXXXXX">
                </div>
                <div class="field">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address"
                           value="<?= esc($old['address']) ?>"
                           placeholder="Your city / area">
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           placeholder="Min 6 chars" required>
                </div>
                <div class="field">
                    <label for="confirm">Confirm</label>
                    <input type="password" id="confirm" name="confirm"
                           placeholder="Repeat" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <p class="auth-foot">Already have an account?
            <a href="index.php?page=login">Sign in</a>
        </p>
    </div>
</div>

</body>
</html>
