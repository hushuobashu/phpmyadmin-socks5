<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';

if (sqliteIsLoggedIn()) {
    header('Location: pages/databases.php');
    exit;
}

$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverIdx = (int) ($_POST['server'] ?? 0);
    $password = $_POST['password'] ?? '';

    if ($sqlitePassword !== '' && !hash_equals($sqlitePassword, $password)) {
        $error = __('invalid_password');
    } elseif (!isset($sqliteServers[$serverIdx])) {
        $error = __('invalid_server');
    } else {
        $srv = $sqliteServers[$serverIdx];
        $label = $srv['verbose'] ?: ('SQLite ' . $srv['mode']);

        try {
            require_once __DIR__ . '/includes/SqliteDriver.php';

            if ($srv['mode'] === 'ssh') {
                $driver = new SqliteSshDriver($srv);
                if (!empty($srv['directories'])) {
                    $driver->listFiles($srv['directories'][0]);
                }
            } else {
                $hasDir = false;
                foreach ($srv['directories'] as $dir) {
                    if (is_dir($dir)) {
                        $hasDir = true;
                        break;
                    }
                }
                if (!$hasDir) {
                    throw new \RuntimeException(__('dirs_not_exist'));
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

$langCode = $GLOBALS['_sqlite_lang_code'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $langCode ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login_title') ?></title>
    <link rel="stylesheet" href="/themes/pmahomme/css/theme.css">
    <style>
        body { background: #f4f4f4; }
        .login-box { max-width: 480px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="card shadow-sm">
            <div class="card-header text-center" style="background: #888; color: #fff;">
                <h4 class="mb-0"><?= __('sqlite_admin') ?></h4>
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
<?php if (!empty($sqliteServers)): ?>
                    <div class="mb-3">
                        <label class="form-label"><?= __('server') ?></label>
                        <select name="server" class="form-select">
<?php foreach ($sqliteServers as $idx => $srv): ?>
                            <option value="<?= $idx ?>"><?= h($srv['verbose'] ?: ('SQLite ' . $srv['mode'] . ' #' . $idx)) ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
<?php endif; ?>

<?php if ($sqlitePassword !== ''): ?>
                    <div class="mb-3">
                        <label class="form-label"><?= __('password') ?></label>
                        <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                    </div>
<?php endif; ?>

                    <button type="submit" class="btn btn-primary w-100"><?= __('connect') ?></button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
