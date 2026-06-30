<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (!mongoIsLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']);
    exit;
}

$conn = mongoGetConnection();
$db = $_POST['db'] ?? '';
$col = $_POST['col'] ?? '';
$type = $_POST['type'] ?? 'find';

if (empty($db) || empty($col)) {
    echo json_encode(['error' => 'Missing db or collection']);
    exit;
}

try {
    if ($type === 'find') {
        $filter = json_decode($_POST['filter'] ?? '{}', true) ?? [];
        $options = [];

        $limit = (int) ($_POST['limit'] ?? 25);
        $skip = (int) ($_POST['skip'] ?? 0);
        $options['limit'] = $limit;
        $options['skip'] = $skip;

        if (!empty($_POST['projection'])) {
            $options['projection'] = json_decode($_POST['projection'], true);
        }
        if (!empty($_POST['sort'])) {
            $options['sort'] = json_decode($_POST['sort'], true);
        }

        $docs = $conn->find($db, $col, $filter, $options);
        $results = [];
        foreach ($docs as $doc) {
            $results[] = bsonDocToArray($doc);
        }

        echo json_encode(['results' => $results, 'count' => count($results)]);
    } elseif ($type === 'aggregate') {
        $pipeline = json_decode($_POST['pipeline'] ?? '[]', true);
        if (!is_array($pipeline)) {
            throw new Exception('Pipeline must be a JSON array');
        }

        $docs = $conn->aggregate($db, $col, $pipeline);
        $results = [];
        foreach ($docs as $doc) {
            $results[] = bsonDocToArray($doc);
        }

        echo json_encode(['results' => $results, 'count' => count($results)]);
    } else {
        echo json_encode(['error' => 'Unknown query type']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
