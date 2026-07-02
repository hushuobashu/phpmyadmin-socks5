<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
mongoRequireLogin();

$conn = mongoGetConnection();
$pageTitle = __('databases');

require_once __DIR__ . '/../includes/layout_header.php';

try {
    $databases = $conn->listDatabases();
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}
?>

<h4><?= __('databases') ?></h4>

<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th><?= __('name') ?></th>
            <th><?= __('size') ?></th>
            <th><?= __('collections') ?></th>
            <th><?= __('actions') ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($databases as $db):
    $dbInfo = (array) $db;
    $dbName = (string) $dbInfo['name'];
    $dbSize = (int) ($dbInfo['sizeOnDisk'] ?? 0);
?>
        <tr>
            <td>
                <a href="collections.php?db=<?= urlencode($dbName) ?>">
                    <strong><?= h($dbName) ?></strong>
                </a>
            </td>
            <td><?= formatBytes($dbSize) ?></td>
            <td>
                <a href="collections.php?db=<?= urlencode($dbName) ?>" class="btn btn-sm btn-outline-primary"><?= __('browse') ?></a>
            </td>
            <td>
                <a href="collections.php?db=<?= urlencode($dbName) ?>" class="btn btn-sm btn-outline-secondary"><?= __('collections') ?></a>
            </td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
