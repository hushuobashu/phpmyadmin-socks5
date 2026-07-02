<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'MongoDB Admin';
$currentDb = $currentDb ?? ($_GET['db'] ?? '');
$currentCol = $currentCol ?? ($_GET['col'] ?? '');

require_once __DIR__ . '/helpers.php';

$mongoBase = '/mongodb';
$pmaBase = '';
$langCode = $GLOBALS['_mongo_lang_code'] ?? 'en';

// Build language switch URL preserving current query params
$langParams = $_GET;
unset($langParams['lang']);
$langQueryBase = http_build_query($langParams);
$langPrefix = $langQueryBase ? '?' . $langQueryBase . '&lang=' : '?lang=';
?>
<!DOCTYPE html>
<html lang="<?= $langCode ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $pmaBase ?>/themes/pmahomme/css/theme.css">
    <link rel="stylesheet" href="<?= $pmaBase ?>/js/vendor/codemirror/lib/codemirror.css">
    <link rel="stylesheet" href="<?= $mongoBase ?>/assets/mongodb.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark px-3">
        <a class="navbar-brand" href="<?= $mongoBase ?>/pages/databases.php">
            <strong>MongoDB</strong>
        </a>
        <span class="navbar-text text-light me-auto ms-3">
            <?= h(mongoGetServerLabel()) ?>
            <small class="ms-2 opacity-75"><?= h(mongoGetServerDisplay()) ?></small>
        </span>
        <a href="<?= $langPrefix ?>en" class="btn btn-sm me-1 <?= $langCode === 'en' ? 'btn-light' : 'btn-outline-light' ?>">EN</a>
        <a href="<?= $langPrefix ?>zh" class="btn btn-sm me-3 <?= $langCode === 'zh' ? 'btn-light' : 'btn-outline-light' ?>">中文</a>
        <a href="<?= $mongoBase ?>/pages/server_info.php" class="btn btn-outline-light btn-sm me-2"><?= __('server_info') ?></a>
        <a href="<?= $pmaBase ?>/index.php" class="btn btn-outline-secondary btn-sm me-2"><?= __('mysql') ?></a>
        <a href="/sqlite/pages/databases.php" class="btn btn-outline-secondary btn-sm me-2"><?= __('sqlite') ?></a>
        <a href="<?= $mongoBase ?>/logout.php" class="btn btn-outline-danger btn-sm"><?= __('logout') ?></a>
    </nav>

    <div class="container-fluid mt-0">
        <div class="row">
            <!-- Sidebar -->
            <nav id="mongo-sidebar" class="col-md-2 pt-3 border-end bg-light" style="min-height: calc(100vh - 56px); overflow-y: auto;">
<?php $_srvIdx = $_SESSION['mongo_server_idx'] ?? 0; ?>
                <div id="serverChoice">
                    <select id="select_server" class="form-select form-select-sm" onchange="location.href='<?= $mongoBase ?>/switch_server.php?server='+this.value">
<?php foreach ($mongoServers as $_i => $_srv): ?>
                        <option value="<?= $_i ?>"<?= $_i === $_srvIdx ? ' selected' : '' ?>><?= h($_srv['verbose'] ?: ($_srv['uri'] ? 'URI connection' : ($_srv['host'] . ':' . $_srv['port']))) ?></option>
<?php endforeach; ?>
                    </select>
                </div>
                <div id="mongo-tree"><?= __('loading') ?></div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 pt-3 px-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= $mongoBase ?>/pages/databases.php"><?= __('databases') ?></a></li>
<?php if ($currentDb): ?>
                        <li class="breadcrumb-item"><a href="<?= $mongoBase ?>/pages/collections.php?db=<?= urlencode($currentDb) ?>"><?= h($currentDb) ?></a></li>
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
