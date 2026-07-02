<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
sqliteRequireLogin();

$driver = sqliteGetDriver();
$currentDb = $_GET['db'] ?? '';
$currentTable = $_GET['table'] ?? '';
$rowid = $_GET['rowid'] ?? '';
$mode = $_GET['mode'] ?? 'edit';

if (empty($currentDb) || empty($currentTable)) {
    header('Location: databases.php');
    exit;
}

$columns = [];
$rowData = [];
$error = '';

try {
    $columns = $driver->tableInfo($currentDb, $currentTable);
} catch (\Exception $e) {
    $error = $e->getMessage();
}

// Load existing row for edit
if ($mode !== 'insert' && $rowid !== '' && empty($error)) {
    try {
        $quotedTable = '"' . str_replace('"', '""', $currentTable) . '"';
        $rows = $driver->query($currentDb,
            'SELECT * FROM ' . $quotedTable . ' WHERE rowid = ' . (int) $rowid . ' LIMIT 1'
        );
        if (!empty($rows)) {
            $rowData = $rows[0];
        } else {
            $error = __('row_not_found');
        }
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sqliteVerifyCsrf();

    $quotedTable = '"' . str_replace('"', '""', $currentTable) . '"';
    $colNames = array_column($columns, 'name');

    $values = [];
    $nullCols = $_POST['null'] ?? [];

    foreach ($colNames as $col) {
        if (isset($nullCols[$col])) {
            $values[$col] = null;
        } else {
            $values[$col] = $_POST['col'][$col] ?? '';
        }
    }

    try {
        if ($mode === 'insert') {
            $quotedCols = [];
            $quotedVals = [];
            foreach ($values as $col => $val) {
                $quotedCols[] = '"' . str_replace('"', '""', $col) . '"';
                $quotedVals[] = $val === null ? 'NULL' : "'" . str_replace("'", "''", $val) . "'";
            }
            $sql = 'INSERT INTO ' . $quotedTable . ' (' . implode(', ', $quotedCols) . ') VALUES (' . implode(', ', $quotedVals) . ')';
            $driver->exec($currentDb, $sql);
            sqliteFlash(__('row_inserted'));
        } else {
            $sets = [];
            foreach ($values as $col => $val) {
                $quotedCol = '"' . str_replace('"', '""', $col) . '"';
                $sets[] = $quotedCol . ' = ' . ($val === null ? 'NULL' : "'" . str_replace("'", "''", $val) . "'");
            }
            $sql = 'UPDATE ' . $quotedTable . ' SET ' . implode(', ', $sets) . ' WHERE rowid = ' . (int) $rowid;
            $driver->exec($currentDb, $sql);
            sqliteFlash(__('row_updated'));
        }

        header('Location: browse.php?db=' . urlencode($currentDb) . '&table=' . urlencode($currentTable));
        exit;
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = ($mode === 'insert' ? __('insert') : __('edit')) . ' - ' . $currentTable;
require_once __DIR__ . '/../includes/layout_header.php';
?>

<h4><?= $mode === 'insert' ? __('insert_row') : __('edit_row') ?> <code><?= h($currentTable) ?></code></h4>

<?php if ($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<form method="post">
    <?= sqliteCsrfField() ?>
    <table class="table table-sm">
        <thead>
            <tr>
                <th><?= __('column') ?></th>
                <th><?= __('type') ?></th>
                <th><?= __('value') ?></th>
                <th><?= __('null_val') ?></th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($columns as $col):
    $colName = $col['name'];
    $colType = strtoupper($col['type']);
    $val = $rowData[$colName] ?? ($col['dflt_value'] ?? '');
    $isNull = ($mode === 'edit' && isset($rowData[$colName]) && $rowData[$colName] === null);
?>
            <tr>
                <td><strong><?= h($colName) ?></strong></td>
                <td><small class="text-muted"><?= h($col['type']) ?></small></td>
                <td>
<?php if (strpos($colType, 'TEXT') !== false || strpos($colType, 'CLOB') !== false): ?>
                    <textarea name="col[<?= h($colName) ?>]" class="form-control form-control-sm" rows="2"><?= h((string) $val) ?></textarea>
<?php elseif (strpos($colType, 'INT') !== false): ?>
                    <input type="number" name="col[<?= h($colName) ?>]" class="form-control form-control-sm" value="<?= h((string) $val) ?>">
<?php elseif (strpos($colType, 'REAL') !== false || strpos($colType, 'FLOAT') !== false || strpos($colType, 'DOUBLE') !== false): ?>
                    <input type="text" name="col[<?= h($colName) ?>]" class="form-control form-control-sm" value="<?= h((string) $val) ?>" inputmode="decimal">
<?php else: ?>
                    <input type="text" name="col[<?= h($colName) ?>]" class="form-control form-control-sm" value="<?= h((string) $val) ?>">
<?php endif; ?>
                </td>
                <td>
<?php if (!$col['notnull']): ?>
                    <input type="checkbox" name="null[<?= h($colName) ?>]" value="1" <?= $isNull ? 'checked' : '' ?>>
<?php endif; ?>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $mode === 'insert' ? __('insert') : __('save') ?></button>
        <a href="browse.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>" class="btn btn-secondary"><?= __('cancel') ?></a>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
