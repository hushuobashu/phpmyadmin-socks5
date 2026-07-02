<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
mongoRequireLogin();

$conn = mongoGetConnection();
$currentDb = $_GET['db'] ?? '';
$currentCol = $_GET['col'] ?? '';

if (empty($currentDb) || empty($currentCol)) {
    header('Location: databases.php');
    exit;
}

$pageTitle = $currentCol . ' - ' . __('stats_title');
require_once __DIR__ . '/../includes/layout_header.php';

try {
    $stats = $conn->getCollectionStats($currentDb, $currentCol);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}
?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link" href="documents.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('browse') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="query.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('query') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="indexes.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('indexes') ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="collection_stats.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('stats') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="export.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('export') ?></a></li>
</ul>

<h4><?= __('stats_title') ?>: <code><?= h($currentCol) ?></code></h4>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong><?= __('collection_stats') ?></strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td><?= __('namespace') ?></td><td><code><?= h($stats['ns'] ?? '') ?></code></td></tr>
                    <tr><td><?= __('document_count') ?></td><td><strong><?= number_format((int) ($stats['count'] ?? 0)) ?></strong></td></tr>
                    <tr><td><?= __('avg_doc_size') ?></td><td><?= formatBytes((int) ($stats['avgObjSize'] ?? 0)) ?></td></tr>
                    <tr><td><?= __('storage_size') ?></td><td><?= formatBytes((int) ($stats['storageSize'] ?? 0)) ?></td></tr>
                    <tr><td><?= __('total_index_size') ?></td><td><?= formatBytes((int) ($stats['totalIndexSize'] ?? 0)) ?></td></tr>
                    <tr><td><?= __('number_of_indexes') ?></td><td><?= (int) ($stats['nindexes'] ?? 0) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

<?php if (!empty($stats['indexSizes'])): ?>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong><?= __('index_sizes') ?></strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
<?php foreach ((array) $stats['indexSizes'] as $name => $size): ?>
                    <tr><td><code><?= h($name) ?></code></td><td><?= formatBytes((int) $size) ?></td></tr>
<?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
