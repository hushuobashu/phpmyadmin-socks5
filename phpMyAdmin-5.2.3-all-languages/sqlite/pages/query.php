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

$pageTitle = ($currentTable ?: basename($currentDb)) . ' - ' . __('query');
$sql = $_POST['sql'] ?? '';
$results = null;
$error = '';
$affectedRows = null;
$execTime = 0;
$editableTable = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sql !== '') {
    $start = microtime(true);
    try {
        $trimmedSql = trim($sql);
        $isSelect = preg_match('/^\s*(SELECT|PRAGMA|EXPLAIN)\b/i', $trimmedSql);

        if ($isSelect) {
            // Detect simple SELECT from single table to enable inline editing
            if (preg_match('/^\s*SELECT\s+(.+?)\s+FROM\s+"?([a-zA-Z_][\w]*)"?\s*(WHERE|ORDER|LIMIT|GROUP|HAVING|$)/is', $trimmedSql, $m)) {
                $editableTable = $m[2];
                // Inject rowid if not already present
                if (stripos($trimmedSql, 'rowid') === false && stripos($trimmedSql, '__rowid__') === false) {
                    $trimmedSql = preg_replace('/^\s*SELECT\s+/i', 'SELECT rowid AS __rowid__, ', $trimmedSql);
                }
            }
            $results = $driver->query($currentDb, $trimmedSql);
        } else {
            $affectedRows = $driver->exec($currentDb, $trimmedSql);
        }
    } catch (\Exception $e) {
        $error = $e->getMessage();
        $editableTable = '';
    }
    $execTime = microtime(true) - $start;
}

// Default SQL
if ($sql === '' && $currentTable !== '') {
    $sql = 'SELECT rowid, * FROM "' . str_replace('"', '""', $currentTable) . '" LIMIT 25';
}

require_once __DIR__ . '/../includes/layout_header.php';
?>

<h4><?= __('query_title') ?> <code><?= h($currentTable ?: basename($currentDb)) ?></code></h4>

<?php if ($currentTable !== ''): ?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link" href="browse.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>"><?= __('browse') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="structure.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>"><?= __('structure') ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="query.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>"><?= __('query') ?></a></li>
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
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<form method="post">
    <div class="mb-3">
        <textarea id="sql-editor" name="sql" class="form-control font-monospace" rows="6"><?= h($sql) ?></textarea>
    </div>
    <div class="d-flex gap-2 mb-3">
        <button type="submit" class="btn btn-primary"><?= __('execute') ?></button>
        <a href="tables.php?db=<?= urlencode($currentDb) ?>" class="btn btn-outline-secondary"><?= __('back_to_tables') ?></a>
    </div>
</form>

<?php if ($results !== null): ?>
<hr>
<p class="text-muted"><?= __('rows_returned', count($results), round($execTime * 1000, 1)) ?></p>

<?php if (!empty($results)):
    $allCols = array_keys($results[0]);
    // Determine if inline editing is available
    $hasRowid = in_array('__rowid__', $allCols, true) || in_array('rowid', $allCols, true);
    $rowidKey = in_array('__rowid__', $allCols, true) ? '__rowid__' : (in_array('rowid', $allCols, true) ? 'rowid' : '');
    $canEdit = ($editableTable !== '' && $hasRowid);
    // Display columns: hide __rowid__ from view
    $displayCols = array_filter($allCols, function ($c) { return $c !== '__rowid__'; });
?>
<div class="table-responsive">
<table class="table table-striped table-hover table-sm">
    <thead>
        <tr>
<?php foreach ($displayCols as $col): ?>
            <th><?= h($col) ?></th>
<?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
<?php foreach ($results as $row):
    $rowid = $rowidKey !== '' ? ($row[$rowidKey] ?? '') : '';
?>
        <tr<?= $canEdit ? ' data-rowid="' . h((string) $rowid) . '"' : '' ?>>
<?php foreach ($displayCols as $col):
    $val = $row[$col] ?? null;
    $rawVal = $val === null ? '' : (string) $val;
    $isNull = ($val === null);
    if ($val === null) {
        $display = '<span class="text-muted">NULL</span>';
    } elseif (is_string($val) && strlen($val) > 200) {
        $display = h(mb_substr($val, 0, 200)) . '&hellip;';
    } else {
        $display = h((string) $val);
    }
    if ($canEdit && $col !== 'rowid'): ?>
            <td class="editable-cell" data-col="<?= h($col) ?>" data-value="<?= h($rawVal) ?>" data-null="<?= $isNull ? '1' : '0' ?>"><?= $display ?></td>
<?php   else: ?>
            <td><?= $display ?></td>
<?php   endif;
endforeach; ?>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
</div>
<?php else: ?>
<p class="text-muted"><?= __('query_no_results') ?></p>
<?php endif; ?>
<?php endif; ?>

<?php if ($affectedRows !== null): ?>
<hr>
<div class="alert alert-success">
    <?= __('query_success', $affectedRows, round($execTime * 1000, 1)) ?>
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

    // Inline editing
    var db = <?= json_encode($currentDb) ?>;
    var editTable = <?= json_encode($editableTable) ?>;
    var editing = null;

    if (!editTable) return;

    document.querySelectorAll('.editable-cell').forEach(function(td) {
        td.addEventListener('dblclick', function() {
            if (editing) return;
            startEdit(td);
        });
    });

    function startEdit(td) {
        editing = td;
        var col = td.getAttribute('data-col');
        var val = td.getAttribute('data-value');
        var isNull = td.getAttribute('data-null') === '1';
        var rowid = td.closest('tr').getAttribute('data-rowid');
        var originalHtml = td.innerHTML;

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control form-control-sm';
        input.value = isNull ? '' : val;
        if (isNull) input.placeholder = 'NULL';

        td.innerHTML = '';
        td.appendChild(input);
        input.focus();
        input.select();

        function save() {
            var newVal = input.value;
            var setNull = (newVal === '' && isNull);

            fetch('../ajax/inline_edit.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'db=' + encodeURIComponent(db)
                    + '&table=' + encodeURIComponent(editTable)
                    + '&rowid=' + encodeURIComponent(rowid)
                    + '&column=' + encodeURIComponent(col)
                    + '&value=' + encodeURIComponent(newVal)
                    + '&is_null=' + (setNull ? '1' : '0')
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    alert(data.error);
                    td.innerHTML = originalHtml;
                } else {
                    if (setNull) {
                        td.innerHTML = '<span class="text-muted">NULL</span>';
                        td.setAttribute('data-value', '');
                        td.setAttribute('data-null', '1');
                    } else {
                        td.textContent = newVal.length > 200 ? newVal.substring(0, 200) + '…' : newVal;
                        td.setAttribute('data-value', newVal);
                        td.setAttribute('data-null', '0');
                    }
                }
                editing = null;
            })
            .catch(function() {
                td.innerHTML = originalHtml;
                editing = null;
            });
        }

        function cancel() {
            td.innerHTML = originalHtml;
            editing = null;
        }

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); save(); }
            if (e.key === 'Escape') { e.preventDefault(); cancel(); }
        });

        input.addEventListener('blur', function() {
            setTimeout(function() { if (editing === td) save(); }, 100);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
