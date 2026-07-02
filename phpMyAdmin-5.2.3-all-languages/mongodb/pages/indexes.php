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

// Handle create/drop index
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    mongoVerifyCsrf();

    if ($_POST['action'] === 'create') {
        $keyField = trim($_POST['key_field'] ?? '');
        $keyDir = (int) ($_POST['key_direction'] ?? 1);
        $unique = !empty($_POST['unique']);
        $sparse = !empty($_POST['sparse']);
        $indexName = trim($_POST['index_name'] ?? '');

        if ($keyField !== '') {
            try {
                $keys = [$keyField => $keyDir];
                $options = [];
                if ($indexName) {
                    $options['name'] = $indexName;
                }
                if ($unique) {
                    $options['unique'] = true;
                }
                if ($sparse) {
                    $options['sparse'] = true;
                }

                $conn->createIndex($currentDb, $currentCol, $keys, $options);
                mongoFlash(__('index_created'));
            } catch (Exception $e) {
                mongoFlash($e->getMessage(), 'danger');
            }
        }
    } elseif ($_POST['action'] === 'drop') {
        $indexName = $_POST['index_name'] ?? '';
        if ($indexName !== '' && $indexName !== '_id_') {
            try {
                $conn->dropIndex($currentDb, $currentCol, $indexName);
                mongoFlash(__('index_dropped', $indexName));
            } catch (Exception $e) {
                mongoFlash($e->getMessage(), 'danger');
            }
        }
    }

    header('Location: indexes.php?db=' . urlencode($currentDb) . '&col=' . urlencode($currentCol));
    exit;
}

$pageTitle = $currentCol . ' - ' . __('indexes');
require_once __DIR__ . '/../includes/layout_header.php';

try {
    $indexes = $conn->listIndexes($currentDb, $currentCol);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}
?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link" href="documents.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('browse') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="query.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('query') ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="indexes.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('indexes') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="collection_stats.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('stats') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="export.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('export') ?></a></li>
</ul>

<h4><?= __('indexes_on') ?> <code><?= h($currentCol) ?></code></h4>

<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th><?= __('name') ?></th>
            <th><?= __('index_keys') ?></th>
            <th><?= __('unique') ?></th>
            <th><?= __('sparse') ?></th>
            <th><?= __('actions') ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($indexes as $idx):
    $name = (string) ($idx['name'] ?? '');
    $keys = isset($idx['key']) ? json_encode((array) $idx['key']) : '';
    $unique = !empty($idx['unique']);
    $sparse = !empty($idx['sparse']);
?>
        <tr>
            <td><code><?= h($name) ?></code></td>
            <td><code><?= h($keys) ?></code></td>
            <td><?= $unique ? '<span class="badge bg-info">' . __('yes') . '</span>' : '-' ?></td>
            <td><?= $sparse ? '<span class="badge bg-warning">' . __('yes') . '</span>' : '-' ?></td>
            <td>
<?php if ($name !== '_id_'): ?>
                <form method="post" style="display:inline" onsubmit="return confirm(<?= h(json_encode(__('confirm_drop_index', $name))) ?>);">
                    <?= mongoCsrfField() ?>
                    <input type="hidden" name="action" value="drop">
                    <input type="hidden" name="index_name" value="<?= h($name) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= __('drop') ?></button>
                </form>
<?php else: ?>
                <span class="text-muted">-</span>
<?php endif; ?>
            </td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>

<h5 class="mt-4"><?= __('create_index') ?></h5>
<form method="post" class="row g-2 align-items-end">
    <?= mongoCsrfField() ?>
    <input type="hidden" name="action" value="create">
    <div class="col-auto">
        <label class="form-label"><?= __('field') ?></label>
        <input type="text" name="key_field" class="form-control form-control-sm" required placeholder="field_name">
    </div>
    <div class="col-auto">
        <label class="form-label"><?= __('direction') ?></label>
        <select name="key_direction" class="form-select form-select-sm">
            <option value="1"><?= __('ascending') ?></option>
            <option value="-1"><?= __('descending') ?></option>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label"><?= __('name') ?> <small>(<?= __('optional') ?>)</small></label>
        <input type="text" name="index_name" class="form-control form-control-sm" placeholder="<?= __('auto') ?>">
    </div>
    <div class="col-auto pt-4">
        <div class="form-check form-check-inline">
            <input type="checkbox" name="unique" class="form-check-input" id="idx-unique">
            <label class="form-check-label" for="idx-unique"><?= __('unique') ?></label>
        </div>
        <div class="form-check form-check-inline">
            <input type="checkbox" name="sparse" class="form-check-input" id="idx-sparse">
            <label class="form-check-label" for="idx-sparse"><?= __('sparse') ?></label>
        </div>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-success"><?= __('create_index') ?></button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
