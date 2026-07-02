<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
mongoRequireLogin();

$conn = mongoGetConnection();
$currentDb = $_GET['db'] ?? '';
$currentCol = $_GET['col'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$skip = ($page - 1) * $perPage;

if (empty($currentDb) || empty($currentCol)) {
    header('Location: databases.php');
    exit;
}

$pageTitle = $currentCol . ' - ' . __('documents');
require_once __DIR__ . '/../includes/layout_header.php';

try {
    $total = $conn->count($currentDb, $currentCol);
    $docs = $conn->find($currentDb, $currentCol, [], [
        'limit' => $perPage,
        'skip'  => $skip,
        'sort'  => ['_id' => -1],
    ]);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}

$totalPages = max(1, (int) ceil($total / $perPage));

// Collect all keys from documents for table headers
$allKeys = [];
foreach ($docs as $doc) {
    foreach ((array) $doc as $key => $val) {
        $allKeys[$key] = true;
    }
}
$allKeys = array_keys($allKeys);
// Keep _id first, limit columns
$columns = ['_id'];
foreach ($allKeys as $k) {
    if ($k !== '_id' && count($columns) < 8) {
        $columns[] = $k;
    }
}
?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link active" href="documents.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('browse') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="query.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('query') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="indexes.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('indexes') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="collection_stats.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('stats') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="export.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('export') ?></a></li>
</ul>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><?= h($currentCol) ?> <small class="text-muted">(<?= number_format($total) ?> <?= __('documents') ?>)</small></h4>
    <a href="document_edit.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>&mode=insert" class="btn btn-sm btn-success"><?= __('insert_document') ?></a>
</div>

<?php if (empty($docs)): ?>
    <div class="alert alert-info"><?= __('no_documents_found') ?></div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-sm">
    <thead>
        <tr>
<?php foreach ($columns as $col): ?>
            <th><?= h($col) ?></th>
<?php endforeach; ?>
            <th><?= __('actions') ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($docs as $doc):
    $docArr = (array) $doc;
    $idVal = '';
    if (isset($docArr['_id'])) {
        if ($docArr['_id'] instanceof MongoDB\BSON\ObjectId) {
            $idVal = (string) $docArr['_id'];
        } else {
            $idVal = json_encode($docArr['_id']);
        }
    }
?>
        <tr class="doc-row">
<?php foreach ($columns as $col):
    $val = $docArr[$col] ?? null;
    $display = '';
    if ($val === null) {
        $display = '<span class="text-muted">null</span>';
    } elseif ($val instanceof MongoDB\BSON\ObjectId) {
        $display = h((string) $val);
    } elseif ($val instanceof MongoDB\BSON\UTCDateTime) {
        $display = h($val->toDateTime()->format('Y-m-d H:i:s'));
    } elseif (is_array($val) || is_object($val)) {
        $display = h(mb_substr(json_encode($val, JSON_UNESCAPED_UNICODE), 0, 80));
    } elseif (is_bool($val)) {
        $display = $val ? 'true' : 'false';
    } else {
        $display = h(mb_substr((string) $val, 0, 80));
    }
?>
            <td><?= $display ?></td>
<?php endforeach; ?>
            <td class="text-nowrap">
                <a href="document_edit.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>&id=<?= urlencode($idVal) ?>" class="btn btn-sm btn-outline-primary"><?= __('edit') ?></a>
                <form method="post" action="document_delete.php" style="display:inline" onsubmit="return confirm(<?= h(json_encode(__('confirm_delete_document'))) ?>);">
                    <?= mongoCsrfField() ?>
                    <input type="hidden" name="db" value="<?= h($currentDb) ?>">
                    <input type="hidden" name="col" value="<?= h($currentCol) ?>">
                    <input type="hidden" name="id" value="<?= h($idVal) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= __('delete') ?></button>
                </form>
            </td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination pagination-sm">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>&page=<?= $page - 1 ?>"><?= __('prev') ?></a>
        </li>
<?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
<?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>&page=<?= $page + 1 ?>"><?= __('next') ?></a>
        </li>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
