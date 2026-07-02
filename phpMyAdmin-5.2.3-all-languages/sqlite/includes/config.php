<?php
declare(strict_types=1);

define('SQLITE_ROOT', dirname(__DIR__));
define('PMA_ROOT', dirname(SQLITE_ROOT));

$cfg = [];

$configFile = PMA_ROOT . '/config.inc.php';
if (file_exists($configFile)) {
    include $configFile;
}

$sqliteServers = $cfg['SQLite'] ?? [];
$sqlitePassword = $cfg['SQLitePassword'] ?? '';

$sqliteDefaults = [
    'verbose'      => '',
    'mode'         => 'local',
    'directories'  => [],
    'ssh_host'     => '',
    'ssh_port'     => 22,
    'ssh_user'     => '',
    'ssh_key'      => '',
    'ssh_password' => '',
    'ssh_proxy'    => '',
];

foreach ($sqliteServers as $idx => $srv) {
    $sqliteServers[$idx] = array_merge($sqliteDefaults, $srv);
}

// i18n
if (session_status() === PHP_SESSION_NONE) {
    session_name('phpSqliteAdmin');
    session_start();
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'zh'], true)) {
    $_SESSION['sqlite_lang'] = $_GET['lang'];
}
$GLOBALS['_sqlite_lang_code'] = $_SESSION['sqlite_lang'] ?? 'en';
$GLOBALS['_lang'] = require SQLITE_ROOT . '/lang/' . $GLOBALS['_sqlite_lang_code'] . '.php';
