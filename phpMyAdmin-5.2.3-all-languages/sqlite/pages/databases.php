<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
sqliteRequireLogin();

$driver = sqliteGetDriver();
$directories = sqliteGetDirectories();
$pageTitle = 'Databases';

// Handle create database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'create') {
    sqliteVerifyCsrf();

    $newName = trim($_POST['db_name'] ?? '');
    $targetDir = $_POST['directory'] ?? '';

    if ($newName === '') {
        sqliteFlash('Database name is required.', 'danger');
    } elseif (!in_array($targetDir, $directories, true)) {
        sqliteFlash('Invalid directory.', 'danger');
    } else {
        $dbPath = rtrim($targetDir, '/') . '/' . $newName;

        try {
            $driver->createDatabase($dbPath);
            sqliteFlash('Database "' . $newName . '" created.');
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
        echo '<div class="alert alert-warning">Error scanning ' . h($dir) . ': ' . h($e->getMessage()) . '</div>';
    }
}
?>

<h4>Databases</h4>

<!-- Create database form -->
<form method="post" class="row g-2 mb-4">
    <?= sqliteCsrfField() ?>
    <input type="hidden" name="action" value="create">
    <div class="col-auto">
        <input type="text" name="db_name" class="form-control form-control-sm" placeholder="New database name" required>
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
        <button type="submit" class="btn btn-sm btn-success">Create Database</button>
    </div>
</form>

<?php if (empty($allFiles)): ?>
    <div class="alert alert-info">No SQLite database files found in the configured directories.</div>
<?php else: ?>
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Path</th>
            <th>Actions</th>
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
                <a href="tables.php?db=<?= urlencode($file['path']) ?>" class="btn btn-sm btn-outline-primary">Browse</a>
                <a href="query.php?db=<?= urlencode($file['path']) ?>" class="btn btn-sm btn-outline-info">Query</a>
                <a href="export.php?db=<?= urlencode($file['path']) ?>" class="btn btn-sm btn-outline-success">Export</a>
            </td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
