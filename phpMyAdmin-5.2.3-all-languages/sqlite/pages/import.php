<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
sqliteRequireLogin();

$driver = sqliteGetDriver();
$currentDb = $_GET['db'] ?? '';

if (empty($currentDb)) {
    header('Location: databases.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sqliteVerifyCsrf();

    if (!empty($_FILES['sqlfile']['tmp_name'])) {
        $content = file_get_contents($_FILES['sqlfile']['tmp_name']);
        if ($content === false || $content === '') {
            $error = 'Failed to read uploaded file or file is empty.';
        } else {
            try {
                $driver->exec($currentDb, $content);
                $success = 'SQL file imported successfully.';
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif (!empty($_POST['sql'])) {
        try {
            $driver->exec($currentDb, $_POST['sql']);
            $success = 'SQL executed successfully.';
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'No file or SQL provided.';
    }
}

$pageTitle = basename($currentDb) . ' - Import';
require_once __DIR__ . '/../includes/layout_header.php';
?>

<h4>Import into <code><?= h(basename($currentDb)) ?></code></h4>

<?php if ($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success"><?= h($success) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">Upload SQL File</div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <?= sqliteCsrfField() ?>
            <div class="mb-3">
                <input type="file" name="sqlfile" class="form-control" accept=".sql,.txt">
            </div>
            <button type="submit" class="btn btn-primary">Import</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Paste SQL</div>
    <div class="card-body">
        <form method="post">
            <?= sqliteCsrfField() ?>
            <div class="mb-3">
                <textarea name="sql" class="form-control font-monospace" rows="8" placeholder="Paste SQL here..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Execute</button>
        </form>
    </div>
</div>

<div class="mt-3">
    <a href="tables.php?db=<?= urlencode($currentDb) ?>" class="btn btn-outline-secondary">Back to Tables</a>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
