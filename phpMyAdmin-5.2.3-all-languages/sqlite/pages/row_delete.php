<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
sqliteRequireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: databases.php');
    exit;
}

sqliteVerifyCsrf();

$driver = sqliteGetDriver();
$currentDb = $_POST['db'] ?? '';
$currentTable = $_POST['table'] ?? '';
$rowid = $_POST['rowid'] ?? '';

if (empty($currentDb) || empty($currentTable) || $rowid === '') {
    header('Location: databases.php');
    exit;
}

$quotedTable = '"' . str_replace('"', '""', $currentTable) . '"';

try {
    $driver->exec($currentDb, 'DELETE FROM ' . $quotedTable . ' WHERE rowid = ' . (int) $rowid);
    sqliteFlash('Row deleted.');
} catch (\Exception $e) {
    sqliteFlash($e->getMessage(), 'danger');
}

header('Location: browse.php?db=' . urlencode($currentDb) . '&table=' . urlencode($currentTable));
exit;
