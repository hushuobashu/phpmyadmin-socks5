<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'MongoDB Admin';
$currentDb = $currentDb ?? ($_GET['db'] ?? '');
$currentCol = $currentCol ?? ($_GET['col'] ?? '');

require_once __DIR__ . '/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= h(dirname($_SERVER['SCRIPT_NAME'])) ?>/../themes/pmahomme/css/theme.css">
    <link rel="stylesheet" href="<?= h(dirname($_SERVER['SCRIPT_NAME'])) ?>/../js/vendor/codemirror/lib/codemirror.css">
    <link rel="stylesheet" href="<?= h(dirname($_SERVER['SCRIPT_NAME'])) ?>/assets/mongodb.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <a class="navbar-brand" href="<?= h(dirname($_SERVER['SCRIPT_NAME'])) ?>/pages/databases.php">
            <strong>MongoDB</strong>
        </a>
        <span class="navbar-text text-light me-auto ms-3">
            <?= h(mongoGetServerLabel()) ?>
        </span>
        <a href="<?= h(dirname($_SERVER['SCRIPT_NAME'])) ?>/pages/server_info.php" class="btn btn-outline-light btn-sm me-2">Server Info</a>
        <a href="<?= h(dirname($_SERVER['SCRIPT_NAME'])) ?>/../index.php" class="btn btn-outline-secondary btn-sm me-2">MySQL</a>
        <a href="<?= h(dirname($_SERVER['SCRIPT_NAME'])) ?>/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </nav>

    <div class="container-fluid mt-0">
        <div class="row">
            <!-- Sidebar -->
            <nav id="mongo-sidebar" class="col-md-2 pt-3 border-end bg-light" style="min-height: calc(100vh - 56px); overflow-y: auto;">
                <div id="mongo-tree">Loading...</div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 pt-3 px-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= h(dirname($_SERVER['SCRIPT_NAME'])) ?>/pages/databases.php">Databases</a></li>
<?php if ($currentDb): ?>
                        <li class="breadcrumb-item"><a href="<?= h(dirname($_SERVER['SCRIPT_NAME'])) ?>/pages/collections.php?db=<?= urlencode($currentDb) ?>"><?= h($currentDb) ?></a></li>
<?php endif; ?>
<?php if ($currentCol): ?>
                        <li class="breadcrumb-item active"><?= h($currentCol) ?></li>
<?php endif; ?>
                    </ol>
                </nav>

                <!-- Flash messages -->
<?php foreach (mongoFlashMessages() as $msg): ?>
                <div class="alert alert-<?= h($msg['type']) ?> alert-dismissible fade show" role="alert">
                    <?= h($msg['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
<?php endforeach; ?>
