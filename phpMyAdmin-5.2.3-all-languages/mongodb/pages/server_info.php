<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
mongoRequireLogin();

$conn = mongoGetConnection();
$pageTitle = 'Server Info';
$currentDb = '';
$currentCol = '';

require_once __DIR__ . '/../includes/layout_header.php';

try {
    $buildInfo = $conn->buildInfo();
    $serverStatus = $conn->serverStatus();
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . h($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../includes/layout_footer.php';
    exit;
}
?>

<h4>Server Information</h4>

<div class="row">
    <!-- Build Info -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><strong>Build Info</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Version</td><td><strong><?= h($buildInfo['version'] ?? 'N/A') ?></strong></td></tr>
                    <tr><td>Git Version</td><td><code><?= h(substr($buildInfo['gitVersion'] ?? '', 0, 12)) ?></code></td></tr>
                    <tr><td>Allocator</td><td><?= h($buildInfo['allocator'] ?? 'N/A') ?></td></tr>
                    <tr><td>JavaScript Engine</td><td><?= h($buildInfo['javascriptEngine'] ?? 'N/A') ?></td></tr>
                    <tr><td>Bits</td><td><?= (int) ($buildInfo['bits'] ?? 0) ?></td></tr>
                    <tr><td>Max BSON Size</td><td><?= formatBytes((int) ($buildInfo['maxBsonObjectSize'] ?? 0)) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Connection Info -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><strong>Connections</strong></div>
            <div class="card-body">
<?php $conns = $serverStatus['connections'] ?? []; ?>
                <table class="table table-sm mb-0">
                    <tr><td>Current</td><td><strong><?= (int) ($conns['current'] ?? 0) ?></strong></td></tr>
                    <tr><td>Available</td><td><?= (int) ($conns['available'] ?? 0) ?></td></tr>
                    <tr><td>Total Created</td><td><?= number_format((int) ($conns['totalCreated'] ?? 0)) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Uptime -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><strong>Server Status</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Uptime</td><td><?= number_format((int) ($serverStatus['uptime'] ?? 0)) ?> seconds</td></tr>
                    <tr><td>Host</td><td><?= h($serverStatus['host'] ?? 'N/A') ?></td></tr>
                    <tr><td>Process</td><td><?= h($serverStatus['process'] ?? 'N/A') ?></td></tr>
                    <tr><td>PID</td><td><?= (int) ($serverStatus['pid'] ?? 0) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Op Counters -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><strong>Op Counters</strong></div>
            <div class="card-body">
<?php $ops = $serverStatus['opcounters'] ?? []; ?>
                <table class="table table-sm mb-0">
                    <tr><td>Insert</td><td><?= number_format((int) ($ops['insert'] ?? 0)) ?></td></tr>
                    <tr><td>Query</td><td><?= number_format((int) ($ops['query'] ?? 0)) ?></td></tr>
                    <tr><td>Update</td><td><?= number_format((int) ($ops['update'] ?? 0)) ?></td></tr>
                    <tr><td>Delete</td><td><?= number_format((int) ($ops['delete'] ?? 0)) ?></td></tr>
                    <tr><td>GetMore</td><td><?= number_format((int) ($ops['getmore'] ?? 0)) ?></td></tr>
                    <tr><td>Command</td><td><?= number_format((int) ($ops['command'] ?? 0)) ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
