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
                mongoFlash('Index created.');
            } catch (Exception $e) {
                mongoFlash($e->getMessage(), 'danger');
            }
        }
    } elseif ($_POST['action'] === 'drop') {
        $indexName = $_POST['index_name'] ?? '';
        if ($indexName !== '' && $indexName !== '_id_') {
            try {
                $conn->dropIndex($currentDb, $currentCol, $indexName);
                mongoFlash('Index "' . $indexName . '" dropped.');
            } catch (Exception $e) {
                mongoFlash($e->getMessage(), 'danger');
            }
        }
    }

    header('Location: indexes.php?db=' . urlencode($currentDb) . '&col=' . urlencode($currentCol));
    exit;
}

$pageTitle = $currentCol . ' - Indexes';
require_once __DIR__ . '/../includes/layout_header.php';

try {
    $indexes = $conn->listIndexes($currentDb, $currentCol);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}
?>

<h4>Indexes on <code><?= h($currentCol) ?></code></h4>

<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Name</th>
            <th>Keys</th>
            <th>Unique</th>
            <th>Sparse</th>
            <th>Actions</th>
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
            <td><?= $unique ? '<span class="badge bg-info">Yes</span>' : '-' ?></td>
            <td><?= $sparse ? '<span class="badge bg-warning">Yes</span>' : '-' ?></td>
            <td>
<?php if ($name !== '_id_'): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Drop index \'<?= h($name) ?>\'?');">
                    <?= mongoCsrfField() ?>
                    <input type="hidden" name="action" value="drop">
                    <input type="hidden" name="index_name" value="<?= h($name) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Drop</button>
                </form>
<?php else: ?>
                <span class="text-muted">-</span>
<?php endif; ?>
            </td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>

<h5 class="mt-4">Create Index</h5>
<form method="post" class="row g-2 align-items-end">
    <?= mongoCsrfField() ?>
    <input type="hidden" name="action" value="create">
    <div class="col-auto">
        <label class="form-label">Field</label>
        <input type="text" name="key_field" class="form-control form-control-sm" required placeholder="field_name">
    </div>
    <div class="col-auto">
        <label class="form-label">Direction</label>
        <select name="key_direction" class="form-select form-select-sm">
            <option value="1">Ascending (1)</option>
            <option value="-1">Descending (-1)</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label">Name <small>(optional)</small></label>
        <input type="text" name="index_name" class="form-control form-control-sm" placeholder="auto">
    </div>
    <div class="col-auto pt-4">
        <div class="form-check form-check-inline">
            <input type="checkbox" name="unique" class="form-check-input" id="idx-unique">
            <label class="form-check-label" for="idx-unique">Unique</label>
        </div>
        <div class="form-check form-check-inline">
            <input type="checkbox" name="sparse" class="form-check-input" id="idx-sparse">
            <label class="form-check-label" for="idx-sparse">Sparse</label>
        </div>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-success">Create</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
