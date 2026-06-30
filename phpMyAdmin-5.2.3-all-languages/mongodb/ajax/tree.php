<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (!mongoIsLoggedIn()) {
    echo json_encode([]);
    exit;
}

$conn = mongoGetConnection();
$tree = [];

try {
    $databases = $conn->listDatabases();
    foreach ($databases as $db) {
        $dbInfo = (array) $db;
        $dbName = (string) $dbInfo['name'];
        $collections = [];
        try {
            $cols = $conn->listCollections($dbName);
            foreach ($cols as $col) {
                $colInfo = (array) $col;
                $collections[] = (string) $colInfo['name'];
            }
            sort($collections);
        } catch (Exception $e) {
            // Skip databases we can't list collections for
        }
        $tree[] = ['name' => $dbName, 'collections' => $collections];
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

echo json_encode($tree);
