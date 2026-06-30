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

$pageTitle = $currentCol . ' - Stats';
require_once __DIR__ . '/../includes/layout_header.php';

try {
    $stats = $conn->getCollectionStats($currentDb, $currentCol);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}
?>

<h4>Stats: <code><?= h($currentCol) ?></code></h4>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong>Collection Stats</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Namespace</td><td><code><?= h($stats['ns'] ?? '') ?></code></td></tr>
                    <tr><td>Document Count</td><td><strong><?= number_format((int) ($stats['count'] ?? 0)) ?></strong></td></tr>
                    <tr><td>Avg Document Size</td><td><?= formatBytes((int) ($stats['avgObjSize'] ?? 0)) ?></td></tr>
                    <tr><td>Storage Size</td><td><?= formatBytes((int) ($stats['storageSize'] ?? 0)) ?></td></tr>
                    <tr><td>Total Index Size</td><td><?= formatBytes((int) ($stats['totalIndexSize'] ?? 0)) ?></td></tr>
                    <tr><td>Number of Indexes</td><td><?= (int) ($stats['nindexes'] ?? 0) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

<?php if (!empty($stats['indexSizes'])): ?>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong>Index Sizes</strong></div>
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
