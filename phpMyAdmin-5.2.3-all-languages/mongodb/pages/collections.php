<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
mongoRequireLogin();

$conn = mongoGetConnection();
$currentDb = $_GET['db'] ?? '';

if (empty($currentDb)) {
    header('Location: databases.php');
    exit;
}

// Handle create collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    mongoVerifyCsrf();

    if ($_POST['action'] === 'create' && !empty($_POST['collection_name'])) {
        try {
            $conn->createCollection($currentDb, trim($_POST['collection_name']));
            mongoFlash(__('collection_created', trim($_POST['collection_name'])));
        } catch (Exception $e) {
            mongoFlash($e->getMessage(), 'danger');
        }
    } elseif ($_POST['action'] === 'drop' && !empty($_POST['collection_name'])) {
        try {
            $conn->dropCollection($currentDb, $_POST['collection_name']);
            mongoFlash(__('collection_dropped', $_POST['collection_name']));
        } catch (Exception $e) {
            mongoFlash($e->getMessage(), 'danger');
        }
    }

    header('Location: collections.php?db=' . urlencode($currentDb));
    exit;
}

$pageTitle = $currentDb . ' - ' . __('collections');
require_once __DIR__ . '/../includes/layout_header.php';

try {
    $collections = $conn->listCollections($currentDb);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><?= __('collections_in') ?> <code><?= h($currentDb) ?></code></h4>
</div>

<!-- Create collection form -->
<form method="post" class="row g-2 mb-4">
    <?= mongoCsrfField() ?>
    <input type="hidden" name="action" value="create">
    <div class="col-auto">
        <input type="text" name="collection_name" class="form-control form-control-sm" placeholder="<?= __('collection_name') ?>" required>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-success"><?= __('create_collection') ?></button>
    </div>
</form>

<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th><?= __('name') ?></th>
            <th><?= __('type') ?></th>
            <th><?= __('actions') ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($collections as $col):
    $colInfo = (array) $col;
    $colName = (string) $colInfo['name'];
    $colType = (string) ($colInfo['type'] ?? 'collection');
?>
        <tr>
            <td>
                <a href="documents.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($colName) ?>">
                    <strong><?= h($colName) ?></strong>
                </a>
            </td>
            <td><span class="badge bg-secondary"><?= h($colType) ?></span></td>
            <td>
                <a href="documents.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($colName) ?>" class="btn btn-sm btn-outline-primary"><?= __('browse') ?></a>
                <a href="query.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($colName) ?>" class="btn btn-sm btn-outline-info"><?= __('query') ?></a>
                <a href="indexes.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($colName) ?>" class="btn btn-sm btn-outline-secondary"><?= __('indexes') ?></a>
                <a href="collection_stats.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($colName) ?>" class="btn btn-sm btn-outline-dark"><?= __('stats') ?></a>
                <a href="export.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($colName) ?>" class="btn btn-sm btn-outline-success"><?= __('export') ?></a>
                <form method="post" style="display:inline" onsubmit="return confirm(<?= h(json_encode(__('confirm_drop_collection', $colName))) ?>);">
                    <?= mongoCsrfField() ?>
                    <input type="hidden" name="action" value="drop">
                    <input type="hidden" name="collection_name" value="<?= h($colName) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= __('drop') ?></button>
                </form>
            </td>
        </tr>
<?php endforeach; ?>
<?php if (empty($collections)): ?>
        <tr><td colspan="3" class="text-muted"><?= __('no_collections_found') ?></td></tr>
<?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
