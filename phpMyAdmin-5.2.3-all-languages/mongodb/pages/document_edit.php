<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
mongoRequireLogin();

$conn = mongoGetConnection();
$currentDb = $_GET['db'] ?? '';
$currentCol = $_GET['col'] ?? '';
$docId = $_GET['id'] ?? '';
$mode = $_GET['mode'] ?? 'edit';

if (empty($currentDb) || empty($currentCol)) {
    header('Location: databases.php');
    exit;
}

$error = '';
$jsonContent = "{\n    \n}";

// Load existing document for edit mode
if ($mode !== 'insert' && $docId !== '') {
    try {
        $filter = [];
        if (preg_match('/^[a-f0-9]{24}$/', $docId)) {
            $filter = ['_id' => new MongoDB\BSON\ObjectId($docId)];
        } else {
            $decoded = json_decode($docId, true);
            $filter = ['_id' => $decoded ?? $docId];
        }

        $docs = $conn->find($currentDb, $currentCol, $filter, ['limit' => 1]);
        if (!empty($docs)) {
            $doc = bsonDocToArray($docs[0]);
            $jsonContent = json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $error = 'Document not found.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mongoVerifyCsrf();

    $jsonInput = $_POST['document'] ?? '';
    $document = json_decode($jsonInput, true);

    if ($document === null && json_last_error() !== JSON_ERROR_NONE) {
        $error = 'Invalid JSON: ' . json_last_error_msg();
        $jsonContent = $jsonInput;
    } else {
        // Convert extended JSON ($oid, $date) back to BSON types
        $document = jsonDocToBson($document);

        try {
            if ($mode === 'insert') {
                $conn->insertOne($currentDb, $currentCol, $document);
                mongoFlash('Document inserted.');
            } else {
                // For update: remove _id from replacement, use it as filter
                $updateId = $document['_id'] ?? null;
                unset($document['_id']);

                $filter = [];
                if ($updateId instanceof MongoDB\BSON\ObjectId) {
                    $filter = ['_id' => $updateId];
                } elseif (is_string($updateId) && preg_match('/^[a-f0-9]{24}$/', $updateId)) {
                    $filter = ['_id' => new MongoDB\BSON\ObjectId($updateId)];
                } else {
                    $filter = ['_id' => $updateId];
                }

                $conn->replaceOne($currentDb, $currentCol, $filter, $document);
                mongoFlash('Document updated.');
            }

            header('Location: documents.php?db=' . urlencode($currentDb) . '&col=' . urlencode($currentCol));
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
            $jsonContent = $jsonInput;
        }
    }
}

$pageTitle = ($mode === 'insert' ? 'Insert' : 'Edit') . ' Document';
require_once __DIR__ . '/../includes/layout_header.php';
?>

<h4><?= $mode === 'insert' ? 'Insert Document' : 'Edit Document' ?></h4>

<?php if ($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<form method="post">
    <?= mongoCsrfField() ?>
    <div class="mb-3">
        <textarea id="doc-editor" name="document" style="display:none;"><?= h($jsonContent) ?></textarea>
        <div id="codemirror-wrapper"></div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $mode === 'insert' ? 'Insert' : 'Save' ?></button>
        <a href="documents.php?db=<?= urlencode($currentDb) ?>&col=<?= urlencode($currentCol) ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('doc-editor');
    var wrapper = document.getElementById('codemirror-wrapper');
    if (typeof CodeMirror !== 'undefined') {
        var cm = CodeMirror(wrapper, {
            value: textarea.value,
            mode: {name: 'javascript', json: true},
            lineNumbers: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            indentUnit: 4,
            tabSize: 4
        });
        cm.setSize(null, 400);
        cm.on('change', function() {
            textarea.value = cm.getValue();
        });
    } else {
        textarea.style.display = '';
        textarea.style.width = '100%';
        textarea.style.height = '400px';
        textarea.style.fontFamily = 'monospace';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
