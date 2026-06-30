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

$pageTitle = $currentTable . ' - Structure';
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

<h4>Structure: <code><?= h($currentTable) ?></code></h4>

<!-- Columns -->
<h5 class="mt-4">Columns</h5>
<table class="table table-striped table-sm">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Type</th>
            <th>Not NULL</th>
            <th>Default</th>
            <th>PK</th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($columns as $col): ?>
        <tr>
            <td><?= (int) $col['cid'] ?></td>
            <td><strong><?= h($col['name']) ?></strong></td>
            <td><?= h($col['type']) ?></td>
            <td><?= $col['notnull'] ? 'YES' : '' ?></td>
            <td><?= $col['dflt_value'] !== null ? h($col['dflt_value']) : '<span class="text-muted">NULL</span>' ?></td>
            <td><?= $col['pk'] ? 'YES' : '' ?></td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>

<!-- Indexes -->
<?php if (!empty($indexes)): ?>
<h5 class="mt-4">Indexes</h5>
<table class="table table-striped table-sm">
    <thead>
        <tr>
            <th>Name</th>
            <th>Unique</th>
            <th>Columns</th>
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
            <td><?= $idx['unique'] ? 'YES' : '' ?></td>
            <td><?= h(implode(', ', $idxCols)) ?></td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- CREATE statement -->
<?php if (!empty($createSql) && !empty($createSql[0]['sql'])): ?>
<h5 class="mt-4">CREATE Statement</h5>
<pre class="bg-light p-3 border rounded"><code><?= h($createSql[0]['sql']) ?></code></pre>
<?php endif; ?>

<div class="mt-3">
    <a href="browse.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>" class="btn btn-sm btn-outline-primary">Browse Data</a>
    <a href="query.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>" class="btn btn-sm btn-outline-info">Query</a>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
