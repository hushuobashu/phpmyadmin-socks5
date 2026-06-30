<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
mongoRequireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: databases.php');
    exit;
}

mongoVerifyCsrf();

$conn = mongoGetConnection();
$db = $_POST['db'] ?? '';
$col = $_POST['col'] ?? '';
$docId = $_POST['id'] ?? '';

if (empty($db) || empty($col) || $docId === '') {
    header('Location: databases.php');
    exit;
}

try {
    $filter = [];
    if (preg_match('/^[a-f0-9]{24}$/', $docId)) {
        $filter = ['_id' => new MongoDB\BSON\ObjectId($docId)];
    } else {
        $decoded = json_decode($docId, true);
        $filter = ['_id' => $decoded ?? $docId];
    }

    $conn->deleteOne($db, $col, $filter);
    mongoFlash('Document deleted.');
} catch (Exception $e) {
    mongoFlash($e->getMessage(), 'danger');
}

header('Location: documents.php?db=' . urlencode($db) . '&col=' . urlencode($col));
exit;
