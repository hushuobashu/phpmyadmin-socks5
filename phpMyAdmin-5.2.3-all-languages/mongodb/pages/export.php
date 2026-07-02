<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
mongoRequireLogin();

$conn = mongoGetConnection();
$currentDb = $_GET['db'] ?? '';
$currentCol = $_GET['col'] ?? '';
$format = $_GET['format'] ?? ($_POST['format'] ?? 'json');
$filterJson = $_POST['filter'] ?? '{}';
$doExport = !empty($_POST['export']);

if (empty($currentDb) || empty($currentCol)) {
    header('Location: databases.php');
    exit;
}

if ($doExport) {
    $filter = json_decode($filterJson, true) ?? [];

    try {
        $docs = $conn->find($currentDb, $currentCol, $filter, ['limit' => 10000]);
    } catch (Exception $e) {
        mongoFlash($e->getMessage(), 'danger');
        header('Location: export.php?db=' . urlencode($currentDb) . '&col=' . urlencode($currentCol));
        exit;
    }

    $filename = $currentDb . '_' . $currentCol . '_' . date('Ymd_His');

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');

        // Collect all keys
        $allKeys = [];
        $rows = [];
        foreach ($docs as $doc) {
            $arr = bsonDocToArray($doc);
            $rows[] = $arr;
            foreach ($arr as $k => $v) {
                $allKeys[$k] = true;
            }
        }
        $headers = array_keys($allKeys);
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $h) {
                $val = $row[$h] ?? '';
                if (is_array($val) || is_object($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                } elseif ($val instanceof MongoDB\BSON\ObjectId) {
                    $val = (string) $val;
                }
                $line[] = $val;
            }
            fputcsv($output, $line);
        }

        fclose($output);
        exit;
    } else {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');

        echo "[\n";
        $first = true;
        foreach ($docs as $doc) {
            if (!$first) {
                echo ",\n";
            }
            echo json_encode(bsonDocToArray($doc), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $first = false;
        }
        echo "\n]\n";
        exit;
    }
}

$pageTitle = $currentCol . ' - ' . __('export_title');
require_once __DIR__ . '/../includes/layout_header.php';
?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link" href="documents.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('browse') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="query.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('query') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="indexes.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('indexes') ?></a></li>
    <li class="nav-item"><a class="nav-link" href="collection_stats.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('stats') ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="export.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>"><?= __('export') ?></a></li>
</ul>

<h4><?= __('export_title') ?> <code><?= h($currentCol) ?></code></h4>

<form method="post">
    <div class="mb-3">
        <label class="form-label"><?= __('filter_optional_json') ?></label>
        <input type="text" name="filter" class="form-control font-monospace" value="{}" placeholder="{}">
    </div>
    <div class="mb-3">
        <label class="form-label"><?= __('format') ?></label>
        <div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="format" value="json" id="fmt-json" checked>
                <label class="form-check-label" for="fmt-json">JSON</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="format" value="csv" id="fmt-csv">
                <label class="form-check-label" for="fmt-csv">CSV</label>
            </div>
        </div>
    </div>
    <p class="text-muted"><?= __('max_export_note') ?></p>
    <button type="submit" name="export" value="1" class="btn btn-success"><?= __('export') ?></button>
    <a href="collections.php?db=<?= urlencode($currentDb) ?>" class="btn btn-secondary"><?= __('cancel') ?></a>
</form>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
