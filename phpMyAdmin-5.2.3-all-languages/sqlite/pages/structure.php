<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
sqliteRequireLogin();

$driver = sqliteGetDriver();
$currentDb = $_GET['db'] ?? '';
$currentTable = $_GET['table'] ?? '';

if (empty($currentDb) || empty($currentTable)) {
    header('Location: databases.php');
    exit;
}

$pageTitle = $currentTable . ' - ' . __('structure');
require_once __DIR__ . '/../includes/layout_header.php';

try {
    $columns = $driver->tableInfo($currentDb, $currentTable);
    $indexes = $driver->indexList($currentDb, $currentTable);
    $createSql = $driver->query($currentDb,
        "SELECT sql FROM sqlite_master WHERE name = '" . str_replace("'", "''", $currentTable) . "'"
    );
} catch (\Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}
?>

<h4><?= __('structure_title') ?> <code><?= h($currentTable) ?></code></h4>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link" href="browse.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>"><?= __('browse') ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="structure.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>"><?= __('structure') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="query.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>"><?= __('query') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="export.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>"><?= __('export') ?></a></li>
    <li class="nav-item">
        <form method="post" action="tables.php?db=<?= urlencode($currentDb) ?>" style="display:inline" onsubmit="return confirm(<?= h(json_encode(__('confirm_drop_table', $currentTable))) ?>);">
            <?= sqliteCsrfField() ?>
            <input type="hidden" name="action" value="drop">
            <input type="hidden" name="table_name" value="<?= h($currentTable) ?>">
            <button type="submit" class="nav-link text-danger"><?= __('drop') ?></button>
        </form>
    </li>
</ul>

<!-- Columns -->
<h5 class="mt-4"><?= __('columns') ?></h5>
<table class="table table-striped table-sm">
    <thead>
        <tr>
            <th>#</th>
            <th><?= __('name') ?></th>
            <th><?= __('type') ?></th>
            <th><?= __('not_null') ?></th>
            <th><?= __('default_val') ?></th>
            <th><?= __('pk') ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($columns as $col): ?>
        <tr>
            <td><?= (int) $col['cid'] ?></td>
            <td><strong><?= h($col['name']) ?></strong></td>
            <td><?= h($col['type']) ?></td>
            <td><?= $col['notnull'] ? __('yes') : '' ?></td>
            <td><?= $col['dflt_value'] !== null ? h($col['dflt_value']) : '<span class="text-muted">NULL</span>' ?></td>
            <td><?= $col['pk'] ? __('yes') : '' ?></td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>

<!-- Indexes -->
<?php if (!empty($indexes)): ?>
<h5 class="mt-4"><?= __('indexes') ?></h5>
<table class="table table-striped table-sm">
    <thead>
        <tr>
            <th><?= __('name') ?></th>
            <th><?= __('unique') ?></th>
            <th><?= __('columns') ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($indexes as $idx):
    $idxCols = [];
    try {
        $idxInfo = $driver->query($currentDb, 'PRAGMA index_info("' . str_replace('"', '""', $idx['name']) . '")');
        foreach ($idxInfo as $ic) {
            $idxCols[] = $ic['name'];
        }
    } catch (\Exception $e) {}
?>
        <tr>
            <td><?= h($idx['name']) ?></td>
            <td><?= $idx['unique'] ? __('yes') : '' ?></td>
            <td><?= h(implode(', ', $idxCols)) ?></td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- CREATE statement -->
<?php if (!empty($createSql) && !empty($createSql[0]['sql'])): ?>
<h5 class="mt-4"><?= __('create_statement') ?></h5>
<pre class="bg-light p-3 border rounded"><code><?= h($createSql[0]['sql']) ?></code></pre>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
