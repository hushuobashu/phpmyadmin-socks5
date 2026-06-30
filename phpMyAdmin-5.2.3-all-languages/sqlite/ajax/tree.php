<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (!sqliteIsLoggedIn()) {
    echo json_encode([]);
    exit;
}

$driver = sqliteGetDriver();
$directories = sqliteGetDirectories();
$tree = [];

foreach ($directories as $dir) {
    try {
        $files = $driver->listFiles($dir);
        foreach ($files as $file) {
            $dbNode = ['name' => $file['name'], 'path' => $file['path'], 'tables' => []];
            try {
                $tables = $driver->tableList($file['path']);
                foreach ($tables as $tbl) {
                    $dbNode['tables'][] = $tbl['name'];
                }
            } catch (\Exception $e) {
                // Skip databases we can't read
            }
            $tree[] = $dbNode;
        }
    } catch (\Exception $e) {
        // Skip directories we can't access
    }
}

echo json_encode($tree);
