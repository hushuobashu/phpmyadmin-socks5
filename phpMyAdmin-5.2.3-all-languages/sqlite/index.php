<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';

if (sqliteIsLoggedIn()) {
    header('Location: pages/databases.php');
    exit;
}

$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverIdx = (int) ($_POST['server'] ?? 0);
    $password = $_POST['password'] ?? '';

    // Check access password
    if ($sqlitePassword !== '' && !hash_equals($sqlitePassword, $password)) {
        $error = 'Invalid password.';
    } elseif (!isset($sqliteServers[$serverIdx])) {
        $error = 'Invalid server selection.';
    } else {
        $srv = $sqliteServers[$serverIdx];
        $label = $srv['verbose'] ?: ('SQLite ' . $srv['mode']);

        // Verify connectivity
        try {
            require_once __DIR__ . '/includes/SqliteDriver.php';

            if ($srv['mode'] === 'ssh') {
                $driver = new SqliteSshDriver($srv);
                // Test SSH connectivity by listing the first directory
                if (!empty($srv['directories'])) {
                    $driver->listFiles($srv['directories'][0]);
                }
            } else {
                // Local mode: verify at least one directory exists
                $hasDir = false;
                foreach ($srv['directories'] as $dir) {
                    if (is_dir($dir)) {
                        $hasDir = true;
                        break;
                    }
                }
                if (!$hasDir) {
                    throw new \RuntimeException('None of the configured directories exist.');
                }
            }

            $_SESSION['sqlite_logged_in'] = true;
            $_SESSION['sqlite_server_idx'] = $serverIdx;
            $_SESSION['sqlite_label'] = $label;

            header('Location: pages/databases.php');
            exit;
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQLite Admin - Login</title>
    <link rel="stylesheet" href="/themes/pmahomme/css/theme.css">
    <style>
        body { background: #f4f4f4; }
        .login-box { max-width: 480px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white text-center">
                <h4 class="mb-0">SQLite Admin</h4>
            </div>
            <div class="card-body">
<?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

                <form method="post">
<?php if (!empty($sqliteServers)): ?>
                    <div class="mb-3">
                        <label class="form-label">Server</label>
                        <select name="server" class="form-select">
<?php foreach ($sqliteServers as $idx => $srv): ?>
                            <option value="<?= $idx ?>"><?= htmlspecialchars($srv['verbose'] ?: ('SQLite ' . $srv['mode'] . ' #' . $idx)) ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
<?php endif; ?>

<?php if ($sqlitePassword !== ''): ?>
                    <div class="mb-3">
                        <label class="form-label">Access Password</label>
                        <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                    </div>
<?php endif; ?>

                    <button type="submit" class="btn btn-primary w-100">Connect</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
