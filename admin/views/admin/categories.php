<?php $isEdit = !empty($editing); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Categories &mdash; MediShop Admin</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<?php require 'views/partials/admin_navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manage Categories</h1>
            <p class="page-sub">Organize medicines by category (liquid / solid)</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['added' => 'Category added.', 'updated' => 'Category updated.', 'deleted' => 'Category deleted.']; ?>
        <?php if (!empty($msgs[$_GET['msg']])): ?>
            <div class="alert alert-success"><?= $msgs[$_GET['msg']] ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <!-- Add / Edit Form -->
    <div class="card form-card" style="max-width:560px;">
        <h3 class="card-title">
            <?= $isEdit ? ' Edit Category (#' . intval($editing['id']) . ')' : '&#43; Add New Category' ?>
        </h3>
        <form method="POST"
              action="index.php?page=admin_categories&action=<?= $isEdit ? 'update&id=' . intval($editing['id']) : 'add' ?>"
              class="form" novalidate>
            <?= csrf_field() ?>
            <div class="field-row">
                <div class="field">
                    <label for="name">Category Name *</label>
                    <input type="text" id="name" name="name"
                           value="<?= esc($editing['name'] ?? '') ?>"
                           placeholder="e.g. Painkiller" required>
                </div>
                <div class="field">
                    <label for="category_type">Type *</label>
                    <select id="category_type" name="category_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="solid"
                            <?= (isset($editing['category_type']) && $editing['category_type'] === 'solid') ? 'selected' : '' ?>>
                            Solid (tablets, capsules)
                        </option>
                        <option value="liquid"
                            <?= (isset($editing['category_type']) && $editing['category_type'] === 'liquid') ? 'selected' : '' ?>>
                            Liquid (syrups, drops)
                        </option>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <?php if ($isEdit): ?>
                    <a href="index.php?page=admin_categories" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Categories Table -->
    <div class="card">
        <div class="card-toolbar">
            <span style="font-weight:700;font-size:15px;">All Categories</span>
            <span class="badge"><?= count($categories) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category Name</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="5" class="empty">No categories yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $i => $cat): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($cat['name']) ?></strong></td>
                            <td>
                                <span class="med-card-type type-<?= esc($cat['category_type']) ?>">
                                    <?= ucfirst(esc($cat['category_type'])) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($cat['created_at'])) ?></td>
                            <td class="text-right">
                                <a class="btn-sm btn-edit"
                                   href="index.php?page=admin_categories&action=edit&id=<?= $cat['id'] ?>">Edit</a>
                                <a class="btn-sm btn-delete"
                                   href="index.php?page=admin_categories&action=delete&id=<?= $cat['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   onclick="return confirm('Delete this category? All its medicines will also be deleted.')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer class="footer">&copy; <?= date('Y') ?> MediShop Admin Panel</footer>
</body>
</html>
