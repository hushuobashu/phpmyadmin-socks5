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

// Handle create/drop table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    sqliteVerifyCsrf();

    if ($_POST['action'] === 'create' && !empty($_POST['table_name'])) {
        $tableName = trim($_POST['table_name']);
        $columns = $_POST['col_name'] ?? [];
        $types = $_POST['col_type'] ?? [];

        $colDefs = [];
        foreach ($columns as $i => $colName) {
            $colName = trim($colName);
            if ($colName === '') {
                continue;
            }
            $colType = $types[$i] ?? 'TEXT';
            $colDefs[] = '"' . str_replace('"', '""', $colName) . '" ' . $colType;
        }

        if (empty($colDefs)) {
            sqliteFlash('At least one column is required.', 'danger');
        } else {
            $sql = 'CREATE TABLE "' . str_replace('"', '""', $tableName) . '" (' . implode(', ', $colDefs) . ')';
            try {
                $driver->exec($currentDb, $sql);
                sqliteFlash('Table "' . $tableName . '" created.');
            } catch (\Exception $e) {
                sqliteFlash($e->getMessage(), 'danger');
            }
        }
    } elseif ($_POST['action'] === 'drop' && !empty($_POST['table_name'])) {
        $tableName = $_POST['table_name'];
        try {
            $driver->exec($currentDb, 'DROP TABLE "' . str_replace('"', '""', $tableName) . '"');
            sqliteFlash('Table "' . $tableName . '" dropped.');
        } catch (\Exception $e) {
            sqliteFlash($e->getMessage(), 'danger');
        }
    }

    header('Location: tables.php?db=' . urlencode($currentDb));
    exit;
}

$pageTitle = basename($currentDb) . ' - Tables';
require_once __DIR__ . '/../includes/layout_header.php';

try {
    $tables = $driver->tableList($currentDb);
} catch (\Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Tables in <code><?= h(basename($currentDb)) ?></code> <small class="text-muted fs-6"><?= h($currentDb) ?></small></h4>
</div>

<!-- Create table form -->
<div class="card mb-4">
    <div class="card-header">
        <a data-bs-toggle="collapse" href="#createTableForm" class="text-decoration-none">Create Table</a>
    </div>
    <div class="collapse" id="createTableForm">
        <div class="card-body">
            <form method="post">
                <?= sqliteCsrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label class="form-label">Table name</label>
                    <input type="text" name="table_name" class="form-control form-control-sm" required>
                </div>
                <div id="column-defs">
                    <div class="row mb-2">
                        <div class="col-6">
                            <input type="text" name="col_name[]" class="form-control form-control-sm" placeholder="Column name" required>
                        </div>
                        <div class="col-6">
                            <select name="col_type[]" class="form-select form-select-sm">
                                <option>TEXT</option>
                                <option>INTEGER</option>
                                <option>REAL</option>
                                <option>BLOB</option>
                                <option>NUMERIC</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addColumn()">+ Add Column</button>
                <div>
                    <button type="submit" class="btn btn-sm btn-success">Create Table</button>
                </div>
            </form>
        </div>
    </div>
</div>

<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($tables as $tbl): ?>
        <tr>
            <td>
                <a href="browse.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tbl['name']) ?>">
                    <strong><?= h($tbl['name']) ?></strong>
                </a>
            </td>
            <td><span class="badge bg-secondary"><?= h($tbl['type']) ?></span></td>
            <td>
                <a href="browse.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tbl['name']) ?>" class="btn btn-sm btn-outline-primary">Browse</a>
                <a href="structure.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tbl['name']) ?>" class="btn btn-sm btn-outline-secondary">Structure</a>
                <a href="query.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tbl['name']) ?>" class="btn btn-sm btn-outline-info">Query</a>
                <a href="export.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tbl['name']) ?>" class="btn btn-sm btn-outline-success">Export</a>
<?php if ($tbl['type'] === 'table'): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Drop table \'<?= h($tbl['name']) ?>\'?');">
                    <?= sqliteCsrfField() ?>
                    <input type="hidden" name="action" value="drop">
                    <input type="hidden" name="table_name" value="<?= h($tbl['name']) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Drop</button>
                </form>
<?php endif; ?>
            </td>
        </tr>
<?php endforeach; ?>
<?php if (empty($tables)): ?>
        <tr><td colspan="3" class="text-muted">No tables found.</td></tr>
<?php endif; ?>
    </tbody>
</table>

<script>
function addColumn() {
    var container = document.getElementById('column-defs');
    var row = container.querySelector('.row').cloneNode(true);
    row.querySelector('input').value = '';
    row.querySelector('input').removeAttribute('required');
    container.appendChild(row);
}
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
