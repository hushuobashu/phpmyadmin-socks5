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

$pageTitle = $currentCol . ' - ' . __('query');
$results = null;
$error = '';
$queryType = $_POST['type'] ?? ($_GET['type'] ?? 'find');
$filterJson = $_POST['filter'] ?? '{}';
$projectionJson = $_POST['projection'] ?? '';
$sortJson = $_POST['sort'] ?? '';
$limit = (int) ($_POST['limit'] ?? 25);
$skip = (int) ($_POST['skip'] ?? 0);
$pipelineJson = $_POST['pipeline'] ?? "[\n    \n]";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($queryType === 'find') {
            $filter = json_decode($filterJson, true);
            if ($filter === null && $filterJson !== 'null' && $filterJson !== '{}') {
                throw new Exception('Invalid filter JSON: ' . json_last_error_msg());
            }
            $filter = $filter ?? [];

            $options = ['limit' => $limit, 'skip' => $skip];

            if ($projectionJson !== '') {
                $projection = json_decode($projectionJson, true);
                if ($projection === null) {
                    throw new Exception('Invalid projection JSON');
                }
                $options['projection'] = $projection;
            }

            if ($sortJson !== '') {
                $sort = json_decode($sortJson, true);
                if ($sort === null) {
                    throw new Exception('Invalid sort JSON');
                }
                $options['sort'] = $sort;
            }

            $results = $conn->find($currentDb, $currentCol, $filter, $options);
        } elseif ($queryType === 'aggregate') {
            $pipeline = json_decode($pipelineJson, true);
            if (!is_array($pipeline)) {
                throw new Exception('Invalid pipeline JSON: must be an array');
            }
            $results = $conn->aggregate($currentDb, $currentCol, $pipeline);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/layout_header.php';
?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link" href="documents.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('browse') ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="query.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('query') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="indexes.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('indexes') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="collection_stats.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('stats') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="export.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('export') ?></a></li>
</ul>

<h4><?= __('query_title') ?> <code><?= h($currentCol) ?></code></h4>

<!-- Query type tabs -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $queryType === 'find' ? 'active' : '' ?>" href="#find-tab" data-bs-toggle="tab" onclick="document.getElementById('query-type').value='find'"><?= __('find') ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $queryType === 'aggregate' ? 'active' : '' ?>" href="#agg-tab" data-bs-toggle="tab" onclick="document.getElementById('query-type').value='aggregate'"><?= __('aggregate') ?></a>
    </li>
</ul>

<?php if ($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="type" id="query-type" value="<?= h($queryType) ?>">

    <div class="tab-content">
        <!-- Find tab -->
        <div class="tab-pane <?= $queryType === 'find' ? 'show active' : '' ?>" id="find-tab">
            <div class="row mb-2">
                <div class="col-md-12">
                    <label class="form-label"><?= __('filter') ?></label>
                    <textarea name="filter" class="form-control form-control-sm font-monospace" rows="3"><?= h($filterJson) ?></textarea>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="form-label"><?= __('projection') ?> <small class="text-muted">(<?= __('optional') ?>)</small></label>
                    <input type="text" name="projection" class="form-control form-control-sm font-monospace" value="<?= h($projectionJson) ?>" placeholder='{"field": 1}'>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= __('sort') ?> <small class="text-muted">(<?= __('optional') ?>)</small></label>
                    <input type="text" name="sort" class="form-control form-control-sm font-monospace" value="<?= h($sortJson) ?>" placeholder='{"_id": -1}'>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label"><?= __('limit') ?></label>
                    <input type="number" name="limit" class="form-control form-control-sm" value="<?= $limit ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= __('skip') ?></label>
                    <input type="number" name="skip" class="form-control form-control-sm" value="<?= $skip ?>">
                </div>
            </div>
        </div>

        <!-- Aggregate tab -->
        <div class="tab-pane <?= $queryType === 'aggregate' ? 'show active' : '' ?>" id="agg-tab">
            <div class="mb-3">
                <label class="form-label"><?= __('pipeline') ?></label>
                <textarea name="pipeline" class="form-control font-monospace" rows="8"><?= h($pipelineJson) ?></textarea>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><?= __('execute') ?></button>
    <a href="export.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>" class="btn btn-outline-success"><?= __('export') ?></a>
</form>

<?php if ($results !== null): ?>
<hr>
<p class="text-muted"><?= __('documents_returned', count($results)) ?></p>

<?php if (!empty($results)): ?>
<div class="table-responsive">
<table class="table table-striped table-sm">
    <thead>
        <tr>
<?php
    $keys = [];
    foreach ($results as $doc) {
        foreach ((array) $doc as $k => $v) {
            $keys[$k] = true;
        }
    }
    $keys = array_slice(array_keys($keys), 0, 10);
    foreach ($keys as $k):
?>
            <th><?= h($k) ?></th>
<?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
<?php foreach ($results as $doc):
    $docArr = (array) $doc;
?>
        <tr class="doc-row">
<?php foreach ($keys as $k):
    $val = $docArr[$k] ?? null;
    if ($val instanceof MongoDB\BSON\ObjectId) {
        $display = (string) $val;
    } elseif ($val instanceof MongoDB\BSON\UTCDateTime) {
        $display = $val->toDateTime()->format('Y-m-d H:i:s');
    } elseif (is_array($val) || is_object($val)) {
        $display = mb_substr(json_encode($val, JSON_UNESCAPED_UNICODE), 0, 100);
    } elseif (is_null($val)) {
        $display = 'null';
    } elseif (is_bool($val)) {
        $display = $val ? 'true' : 'false';
    } else {
        $display = mb_substr((string) $val, 0, 100);
    }
?>
            <td><?= h($display) ?></td>
<?php endforeach; ?>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
