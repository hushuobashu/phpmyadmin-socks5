<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (!sqliteIsLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']);
    exit;
}

$driver = sqliteGetDriver();
$db = $_POST['db'] ?? '';
$sql = $_POST['sql'] ?? '';

if (empty($db) || empty($sql)) {
    echo json_encode(['error' => 'Missing db or sql']);
    exit;
}

try {
    $trimmedSql = trim($sql);
    $isSelect = preg_match('/^\s*(SELECT|PRAGMA|EXPLAIN)\b/i', $trimmedSql);

    if ($isSelect) {
        $rows = $driver->query($db, $trimmedSql);
        echo json_encode(['results' => $rows, 'count' => count($rows)]);
    } else {
        $affected = $driver->exec($db, $trimmedSql);
        echo json_encode(['affected' => $affected]);
    }
} catch (\Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
