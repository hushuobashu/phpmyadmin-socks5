<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'SQLite Admin';
$currentDb = $currentDb ?? ($_GET['db'] ?? '');
$currentTable = $currentTable ?? ($_GET['table'] ?? '');

require_once __DIR__ . '/helpers.php';

$sqliteBase = '/sqlite';
$pmaBase = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $pmaBase ?>/themes/pmahomme/css/theme.css">
    <link rel="stylesheet" href="<?= $pmaBase ?>/js/vendor/codemirror/lib/codemirror.css">
    <link rel="stylesheet" href="<?= $sqliteBase ?>/assets/sqlite.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <a class="navbar-brand" href="<?= $sqliteBase ?>/pages/databases.php">
            <strong>SQLite</strong>
        </a>
        <span class="navbar-text text-light me-auto ms-3">
            <?= h(sqliteGetServerLabel()) ?>
        </span>
        <a href="<?= $pmaBase ?>/index.php" class="btn btn-outline-secondary btn-sm me-2">MySQL</a>
        <a href="/mongodb/pages/databases.php" class="btn btn-outline-secondary btn-sm me-2">MongoDB</a>
        <a href="<?= $sqliteBase ?>/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </nav>

    <div class="container-fluid mt-0">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sqlite-sidebar" class="col-md-2 pt-3 border-end bg-light" style="min-height: calc(100vh - 56px); overflow-y: auto;">
                <div id="sqlite-tree">Loading...</div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 pt-3 px-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= $sqliteBase ?>/pages/databases.php">Databases</a></li>
<?php if ($currentDb): ?>
                        <li class="breadcrumb-item"><a href="<?= $sqliteBase ?>/pages/tables.php?db=<?= urlencode($currentDb) ?>"><?= h(basename($currentDb)) ?></a></li>
<?php endif; ?>
<?php if ($currentTable): ?>
                        <li class="breadcrumb-item active"><?= h($currentTable) ?></li>
<?php endif; ?>
                    </ol>
                </nav>

                <!-- Flash messages -->
<?php foreach (sqliteFlashMessages() as $msg): ?>
                <div class="alert alert-<?= h($msg['type']) ?> alert-dismissible fade show" role="alert">
                    <?= h($msg['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
<?php endforeach; ?>
