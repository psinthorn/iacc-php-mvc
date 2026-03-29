# View Templates Reference

## Standard List Page

```php
<?php
// app/Views/module/list.php
include __DIR__ . '/../layouts/head.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i><?= $title ?? 'Module List' ?></h5>
                    <a href="?page=module_create" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Create New
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td><?= $item['created_at'] ?></td>
                                <td>
                                    <a href="?page=module_view&id=<?= intval($item['id']) ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="?page=module_edit&id=<?= intval($item['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
```

## Standard Form Page

```php
<?php
// app/Views/module/form.php
include __DIR__ . '/../layouts/head.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #11998e, #38ef7d); color: white;">
                <h5 class="mb-0"><i class="fas fa-plus me-2"></i><?= $title ?? 'Create' ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?page=module_create">
                    <?= csrf_field() ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                    <a href="?page=module_list" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
```

## UI Design Conventions

- **Gradient headers**: Each module uses a distinct gradient color
- **Font**: Inter (Google Fonts)
- **Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Cards**: Use `.card` with gradient `.card-header`
- **Tables**: `.table .table-hover` inside `.table-responsive`
- **Buttons**: Bootstrap button classes with FA icons
- **Badges**: Status indicators using `.badge`
- **Modals**: Bootstrap modals for confirmations
- **Always escape output**: `htmlspecialchars()` for user data
