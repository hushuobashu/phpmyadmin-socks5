<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (!sqliteIsLoggedIn()) {
    echo json_encode(['error' => __('not_authenticated')]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => __('post_required')]);
    exit;
}

$driver = sqliteGetDriver();
$db = $_POST['db'] ?? '';
$table = $_POST['table'] ?? '';
$rowid = isset($_POST['rowid']) ? $_POST['rowid'] : '';
$column = $_POST['column'] ?? '';
$value = array_key_exists('value', $_POST) ? $_POST['value'] : '';
$isNull = ($_POST['is_null'] ?? '0') === '1';

if ($db === '' || $table === '' || $rowid === '' || $column === '') {
    echo json_encode(['error' => __('missing_params')]);
    exit;
}

$quotedTable = '"' . str_replace('"', '""', $table) . '"';
$quotedCol = '"' . str_replace('"', '""', $column) . '"';
$quotedVal = $isNull ? 'NULL' : "'" . str_replace("'", "''", $value) . "'";

$sql = 'UPDATE ' . $quotedTable . ' SET ' . $quotedCol . ' = ' . $quotedVal . ' WHERE rowid = ' . (int) $rowid;

try {
    $affected = $driver->exec($db, $sql);
    echo json_encode(['ok' => true, 'affected' => $affected]);
} catch (\Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
