<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
sqliteRequireLogin();

$driver = sqliteGetDriver();
$directories = sqliteGetDirectories();
$pageTitle = __('databases');

// Handle create database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'create') {
    sqliteVerifyCsrf();

    $newName = trim($_POST['db_name'] ?? '');
    $targetDir = $_POST['directory'] ?? '';

    if ($newName === '') {
        sqliteFlash(__('db_name_required'), 'danger');
    } elseif (!in_array($targetDir, $directories, true)) {
        sqliteFlash(__('invalid_directory'), 'danger');
    } else {
        $dbPath = rtrim($targetDir, '/') . '/' . $newName;

        try {
            $driver->createDatabase($dbPath);
            sqliteFlash(__('db_created', $newName));
        } catch (\Exception $e) {
            sqliteFlash($e->getMessage(), 'danger');
        }
    }

    header('Location: databases.php');
    exit;
}

require_once __DIR__ . '/../includes/layout_header.php';

$allFiles = [];
foreach ($directories as $dir) {
    try {
        $files = $driver->listFiles($dir);
        foreach ($files as &$f) {
            $f['directory'] = $dir;
        }
        $allFiles = array_merge($allFiles, $files);
    } catch (\Exception $e) {
        echo '<div class="alert alert-warning">' . __('error_scanning', h($dir), h($e->getMessage())) . '</div>';
    }
}
?>

<h4><?= __('databases') ?></h4>

<!-- Create database form -->
<form method="post" class="row g-2 mb-4">
    <?= sqliteCsrfField() ?>
    <input type="hidden" name="action" value="create">
    <div class="col-auto">
        <input type="text" name="db_name" class="form-control form-control-sm" placeholder="<?= __('new_db_name') ?>" required>
    </div>
<?php if (count($directories) > 1): ?>
    <div class="col-auto">
        <select name="directory" class="form-select form-select-sm">
<?php foreach ($directories as $dir): ?>
            <option value="<?= h($dir) ?>"><?= h($dir) ?></option>
<?php endforeach; ?>
        </select>
    </div>
<?php else: ?>
    <input type="hidden" name="directory" value="<?= h($directories[0] ?? '') ?>">
<?php endif; ?>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-success"><?= __('create_database') ?></button>
    </div>
</form>

<?php if (empty($allFiles)): ?>
    <div class="alert alert-info"><?= __('no_databases_found') ?></div>
<?php else: ?>
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th><?= __('name') ?></th>
            <th><?= __('size') ?></th>
            <th><?= __('path') ?></th>
            <th><?= __('actions') ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($allFiles as $file): ?>
        <tr>
            <td>
                <a href="tables.php?db=<?= urlencode($file['path']) ?>">
                    <strong><?= h($file['name']) ?></strong>
                </a>
            </td>
            <td><?= formatBytes($file['size']) ?></td>
            <td><small class="text-muted"><?= h($file['directory']) ?></small></td>
            <td>
                <a href="tables.php?db=<?= urlencode($file['path']) ?>" class="btn btn-sm btn-outline-primary"><?= __('browse') ?></a>
                <a href="query.php?db=<?= urlencode($file['path']) ?>" class="btn btn-sm btn-outline-info"><?= __('query') ?></a>
                <a href="export.php?db=<?= urlencode($file['path']) ?>" class="btn btn-sm btn-outline-success"><?= __('export') ?></a>
            </td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
