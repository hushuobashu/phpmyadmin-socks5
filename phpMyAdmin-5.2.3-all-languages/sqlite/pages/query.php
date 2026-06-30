<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
sqliteRequireLogin();

$driver = sqliteGetDriver();
$currentDb = $_GET['db'] ?? '';
$currentTable = $_GET['table'] ?? '';

if (empty($currentDb)) {
    header('Location: databases.php');
    exit;
}

$pageTitle = ($currentTable ?: basename($currentDb)) . ' - Query';
$sql = $_POST['sql'] ?? '';
$results = null;
$error = '';
$affectedRows = null;
$execTime = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sql !== '') {
    $start = microtime(true);
    try {
        $trimmedSql = trim($sql);
        $isSelect = preg_match('/^\s*(SELECT|PRAGMA|EXPLAIN)\b/i', $trimmedSql);

        if ($isSelect) {
            $results = $driver->query($currentDb, $trimmedSql);
        } else {
            $affectedRows = $driver->exec($currentDb, $trimmedSql);
        }
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
    $execTime = microtime(true) - $start;
}

// Default SQL
if ($sql === '' && $currentTable !== '') {
    $sql = 'SELECT rowid, * FROM "' . str_replace('"', '""', $currentTable) . '" LIMIT 25';
}

require_once __DIR__ . '/../includes/layout_header.php';
?>

<h4>Query <code><?= h($currentTable ?: basename($currentDb)) ?></code></h4>

<?php if ($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<form method="post">
    <div class="mb-3">
        <textarea id="sql-editor" name="sql" class="form-control font-monospace" rows="6"><?= h($sql) ?></textarea>
    </div>
    <div class="d-flex gap-2 mb-3">
        <button type="submit" class="btn btn-primary">Execute</button>
        <a href="tables.php?db=<?= urlencode($currentDb) ?>" class="btn btn-outline-secondary">Back to Tables</a>
    </div>
</form>

<?php if ($results !== null): ?>
<hr>
<p class="text-muted"><?= count($results) ?> row(s) returned in <?= round($execTime * 1000, 1) ?> ms</p>

<?php if (!empty($results)): ?>
<div class="table-responsive">
<table class="table table-striped table-sm">
    <thead>
        <tr>
<?php foreach (array_keys($results[0]) as $col): ?>
            <th><?= h($col) ?></th>
<?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
<?php foreach ($results as $row): ?>
        <tr>
<?php foreach ($row as $val): ?>
            <td><?= $val === null ? '<span class="text-muted">NULL</span>' : h(mb_substr((string) $val, 0, 200)) ?></td>
<?php endforeach; ?>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
</div>
<?php else: ?>
<p class="text-muted">Query returned no results.</p>
<?php endif; ?>
<?php endif; ?>

<?php if ($affectedRows !== null): ?>
<hr>
<div class="alert alert-success">
    Query executed successfully. <?= $affectedRows ?> row(s) affected. (<?= round($execTime * 1000, 1) ?> ms)
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('sql-editor');
    if (typeof CodeMirror !== 'undefined') {
        var cm = CodeMirror.fromTextArea(textarea, {
            mode: 'text/x-sql',
            lineNumbers: true,
            matchBrackets: true,
            indentUnit: 4,
            tabSize: 4
        });
        cm.setSize(null, 200);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
