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

    if ($serverIdx > 0 && isset($mongoServers[$serverIdx])) {
        $srv = $mongoServers[$serverIdx];
        $label = $srv['verbose'] ?: ($srv['host'] . ':' . $srv['port']);

        if (!empty($srv['uri'])) {
            $uri = $srv['uri'];
            if (!empty($srv['ssh_tunnel']) || !empty($srv['socks5_proxy'])) {
                require_once __DIR__ . '/includes/MongoTunnel.php';
                $uri = MongoTunnel::rewriteUri($uri, $srv);
            }
        } else {
            $host = $srv['host'];
            $port = (int) $srv['port'];
            $username = $username ?: $srv['username'];
            $password = $password ?: $srv['password'];
            $authDb = $authDb ?: $srv['auth_database'];

            if (!empty($srv['ssh_tunnel']) || !empty($srv['socks5_proxy'])) {
                require_once __DIR__ . '/includes/MongoTunnel.php';
                $resolved = MongoTunnel::resolve($srv);
                $host = $resolved['host'];
                $port = $resolved['port'];
            }

            $uri = '';
        }
    } else {
        $label = $host . ':' . $port;
        $uri = '';
    }

    if ($uri === '') {
        $uri = 'mongodb://';
        if ($username !== '') {
            $uri .= urlencode($username) . ':' . urlencode($password) . '@';
        }
        $uri .= $host . ':' . $port . '/';
        if ($username !== '') {
            $uri .= '?authSource=' . urlencode($authDb);
        }
    }

    try {
        require_once __DIR__ . '/includes/MongoConnection.php';
        $conn = new MongoConnection($uri);
        $conn->ping();

        $_SESSION['mongo_logged_in'] = true;
        $_SESSION['mongo_uri'] = $uri;
        $_SESSION['mongo_label'] = $label;
        $_SESSION['mongo_server_idx'] = $serverIdx;
        $_SESSION['mongo_server_cfg'] = [
            'host' => isset($srv) ? $srv['host'] : $host,
            'port' => isset($srv) ? (int) $srv['port'] : $port,
            'uri'  => isset($srv) ? $srv['uri'] : '',
            'ssh_tunnel' => isset($srv) ? ($srv['ssh_tunnel'] ?? '') : '',
            'ssh_host'   => isset($srv) ? ($srv['ssh_host'] ?? '') : '',
            'ssh_port'   => isset($srv) ? (int) ($srv['ssh_port'] ?? 22) : 22,
            'ssh_user'   => isset($srv) ? ($srv['ssh_user'] ?? '') : '',
            'socks5_proxy' => isset($srv) ? ($srv['socks5_proxy'] ?? '') : '',
        ];

        header('Location: pages/databases.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$langCode = $GLOBALS['_mongo_lang_code'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $langCode ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login_title') ?></title>
    <link rel="stylesheet" href="/themes/pmahomme/css/theme.css">
    <link rel="stylesheet" href="/mongodb/assets/mongodb.css">
    <style>
        body { background: #f4f4f4; }
        .login-box { max-width: 480px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="card shadow-sm">
            <div class="card-header text-center" style="background: #888; color: #fff;">
                <h4 class="mb-0"><?= __('mongodb_admin') ?></h4>
                <div class="mt-2">
                    <a href="?lang=en" class="btn btn-sm <?= $langCode === 'en' ? 'btn-light' : 'btn-outline-light' ?>">EN</a>
                    <a href="?lang=zh" class="btn btn-sm <?= $langCode === 'zh' ? 'btn-light' : 'btn-outline-light' ?>">中文</a>
                </div>
            </div>
            <div class="card-body">
<?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

                <form method="post">
<?php if (!empty($mongoServers)): ?>
                    <div class="mb-3">
                        <label class="form-label"><?= __('server') ?></label>
                        <select name="server" id="serverSelect" class="form-select">
                            <option value="0" data-has-uri="0"><?= __('manual') ?></option>
<?php foreach ($mongoServers as $idx => $srv): ?>
                            <option value="<?= $idx ?>" data-has-uri="<?= !empty($srv['uri']) ? '1' : '0' ?>"><?= h($srv['verbose'] ?: ($srv['uri'] ? 'URI connection' : ($srv['host'] . ':' . $srv['port']))) ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
<?php endif; ?>

                    <div id="manual-fields">
                        <div class="row mb-3">
                            <div class="col-8">
                                <label class="form-label"><?= __('host') ?></label>
                                <input type="text" name="host" class="form-control" value="localhost">
                            </div>
                            <div class="col-4">
                                <label class="form-label"><?= __('port') ?></label>
                                <input type="number" name="port" class="form-control" value="27017">
                            </div>
                        </div>
                    </div>

                    <div id="credential-fields">
                        <div class="mb-3">
                            <label class="form-label"><?= __('username') ?> <small class="text-muted">(<?= __('optional') ?>)</small></label>
                            <input type="text" name="username" class="form-control" autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= __('password') ?></label>
                            <input type="password" name="password" class="form-control" autocomplete="current-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= __('auth_database') ?></label>
                            <input type="text" name="auth_database" class="form-control" value="admin">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><?= __('connect') ?></button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('serverSelect')?.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var hasUri = opt.getAttribute('data-has-uri') === '1';
        var isManual = this.value === '0';
        document.getElementById('manual-fields').style.display = isManual ? '' : 'none';
        document.getElementById('credential-fields').style.display = hasUri ? 'none' : '';
    });
    </script>
</body>
</html>
