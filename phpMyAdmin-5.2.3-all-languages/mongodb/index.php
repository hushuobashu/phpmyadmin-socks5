<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';

// Already logged in
if (mongoIsLoggedIn()) {
    header('Location: pages/databases.php');
    exit;
}

$error = $_GET['error'] ?? '';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverIdx = (int) ($_POST['server'] ?? 0);
    $host = trim($_POST['host'] ?? 'localhost');
    $port = (int) ($_POST['port'] ?? 27017);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $authDb = trim($_POST['auth_database'] ?? 'admin');

    // If a pre-configured server is selected, use its settings
    if ($serverIdx > 0 && isset($mongoServers[$serverIdx])) {
        $srv = $mongoServers[$serverIdx];
        $host = $srv['host'];
        $port = (int) $srv['port'];
        $username = $username ?: $srv['username'];
        $password = $password ?: $srv['password'];
        $authDb = $authDb ?: $srv['auth_database'];
        $label = $srv['verbose'] ?: ($host . ':' . $port);

        // Resolve tunnel if configured
        if (!empty($srv['ssh_tunnel']) || !empty($srv['socks5_proxy'])) {
            require_once __DIR__ . '/includes/MongoTunnel.php';
            $resolved = MongoTunnel::resolve($srv);
            $host = $resolved['host'];
            $port = $resolved['port'];
        }
    } else {
        $label = $host . ':' . $port;
    }

    // Build connection URI
    $uri = 'mongodb://';
    if ($username !== '') {
        $uri .= urlencode($username) . ':' . urlencode($password) . '@';
    }
    $uri .= $host . ':' . $port . '/';
    if ($username !== '') {
        $uri .= '?authSource=' . urlencode($authDb);
    }

    try {
        require_once __DIR__ . '/includes/MongoConnection.php';
        $conn = new MongoConnection($uri);
        $conn->ping();

        $_SESSION['mongo_logged_in'] = true;
        $_SESSION['mongo_uri'] = $uri;
        $_SESSION['mongo_label'] = $label;
        $_SESSION['mongo_server_idx'] = $serverIdx;

        header('Location: pages/databases.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MongoDB Admin - Login</title>
    <link rel="stylesheet" href="<?= dirname($_SERVER['SCRIPT_NAME']) ?>/../themes/pmahomme/css/theme.css">
    <style>
        body { background: #f4f4f4; }
        .login-box { max-width: 480px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white text-center">
                <h4 class="mb-0">MongoDB Admin</h4>
            </div>
            <div class="card-body">
<?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

                <form method="post">
<?php if (!empty($mongoServers)): ?>
                    <div class="mb-3">
                        <label class="form-label">Server</label>
                        <select name="server" id="serverSelect" class="form-select">
                            <option value="0">-- Manual --</option>
<?php foreach ($mongoServers as $idx => $srv): ?>
                            <option value="<?= $idx ?>"><?= h($srv['verbose'] ?: ($srv['host'] . ':' . $srv['port'])) ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
<?php endif; ?>

                    <div id="manual-fields">
                        <div class="row mb-3">
                            <div class="col-8">
                                <label class="form-label">Host</label>
                                <input type="text" name="host" class="form-control" value="localhost">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Port</label>
                                <input type="number" name="port" class="form-control" value="27017">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username <small class="text-muted">(optional)</small></label>
                        <input type="text" name="username" class="form-control" autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Auth Database</label>
                        <input type="text" name="auth_database" class="form-control" value="admin">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Connect</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('serverSelect')?.addEventListener('change', function() {
        document.getElementById('manual-fields').style.display = this.value === '0' ? '' : 'none';
    });
    </script>
</body>
</html>
