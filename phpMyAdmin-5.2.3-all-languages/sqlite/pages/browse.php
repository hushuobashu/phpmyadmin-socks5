<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
sqliteRequireLogin();

$driver = sqliteGetDriver();
$currentDb = $_GET['db'] ?? '';
$currentTable = $_GET['table'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$skip = ($page - 1) * $perPage;
$sortCol = $_GET['sort'] ?? '';
$sortDir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

if (empty($currentDb) || empty($currentTable)) {
    header('Location: databases.php');
    exit;
}

$pageTitle = $currentTable . ' - ' . __('browse');
require_once __DIR__ . '/../includes/layout_header.php';

$quotedTable = '"' . str_replace('"', '""', $currentTable) . '"';

try {
    $countResult = $driver->query($currentDb, 'SELECT COUNT(*) as cnt FROM ' . $quotedTable);
    $total = (int) ($countResult[0]['cnt'] ?? 0);

    $orderClause = '';
    if ($sortCol !== '') {
        $orderClause = ' ORDER BY "' . str_replace('"', '""', $sortCol) . '" ' . $sortDir;
    } else {
        $orderClause = ' ORDER BY rowid ASC';
    }

    $rows = $driver->query($currentDb,
        'SELECT rowid AS __rowid__, * FROM ' . $quotedTable . $orderClause . ' LIMIT ' . $perPage . ' OFFSET ' . $skip
    );

    $columns = $driver->tableInfo($currentDb, $currentTable);
} catch (\Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}

$totalPages = max(1, (int) ceil($total / $perPage));
$colNames = array_column($columns, 'name');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><?= h($currentTable) ?> <small class="text-muted">(<?= number_format($total) ?> <?= __('rows') ?>)</small> <small class="text-muted fs-6"><?= h($currentDb) ?></small></h4>
    <a href="row_edit.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&mode=insert" class="btn btn-sm btn-success"><?= __('insert_row') ?></a>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link active" href="browse.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>"><?= __('browse') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="structure.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>"><?= __('structure') ?></a></li>
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

<?php if (empty($rows)): ?>
    <div class="alert alert-info"><?= __('no_data_found') ?></div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-sm">
    <thead>
        <tr>
<?php foreach ($colNames as $col):
    $nextDir = ($sortCol === $col && $sortDir === 'ASC') ? 'DESC' : 'ASC';
    $arrow = '';
    if ($sortCol === $col) {
        $arrow = $sortDir === 'ASC' ? ' &#9650;' : ' &#9660;';
    }
?>
            <th>
                <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&sort=<?= urlencode($col) ?>&dir=<?= $nextDir ?>&page=1" class="text-decoration-none"><?= h($col) ?><?= $arrow ?></a>
            </th>
<?php endforeach; ?>
            <th><?= __('actions') ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($rows as $row):
    $rowid = $row['__rowid__'] ?? '';
?>
        <tr data-rowid="<?= h((string) $rowid) ?>">
<?php foreach ($colNames as $col):
    $val = $row[$col] ?? null;
    $rawVal = $val === null ? '' : (string) $val;
    $isNull = ($val === null);
    if ($val === null) {
        $display = '<span class="text-muted">NULL</span>';
    } elseif (is_string($val) && strlen($val) > 100) {
        $display = h(mb_substr($val, 0, 100)) . '&hellip;';
    } else {
        $display = h((string) $val);
    }
?>
            <td class="editable-cell" data-col="<?= h($col) ?>" data-value="<?= h($rawVal) ?>" data-null="<?= $isNull ? '1' : '0' ?>"><?= $display ?></td>
<?php endforeach; ?>
            <td class="text-nowrap">
                <a href="row_edit.php?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&rowid=<?= urlencode((string) $rowid) ?>" class="btn btn-sm btn-outline-primary"><?= __('edit') ?></a>
                <form method="post" action="row_delete.php" style="display:inline" onsubmit="return confirm(<?= h(json_encode(__('confirm_delete_row'))) ?>);">
                    <?= sqliteCsrfField() ?>
                    <input type="hidden" name="db" value="<?= h($currentDb) ?>">
                    <input type="hidden" name="table" value="<?= h($currentTable) ?>">
                    <input type="hidden" name="rowid" value="<?= h((string) $rowid) ?>">
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
            <a class="page-link" href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&sort=<?= urlencode($sortCol) ?>&dir=<?= $sortDir ?>&page=<?= $page - 1 ?>"><?= __('prev') ?></a>
        </li>
<?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&sort=<?= urlencode($sortCol) ?>&dir=<?= $sortDir ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
<?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&sort=<?= urlencode($sortCol) ?>&dir=<?= $sortDir ?>&page=<?= $page + 1 ?>"><?= __('next') ?></a>
        </li>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<script>
(function() {
    var db = <?= json_encode($currentDb) ?>;
    var table = <?= json_encode($currentTable) ?>;
    var editing = null;

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
                    + '&table=' + encodeURIComponent(table)
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
                        td.textContent = newVal.length > 100 ? newVal.substring(0, 100) + '…' : newVal;
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
})();
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
