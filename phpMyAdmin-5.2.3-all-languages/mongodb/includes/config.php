<?php
declare(strict_types=1);

define('MONGO_ROOT', dirname(__DIR__));
define('PMA_ROOT', dirname(MONGO_ROOT));

$cfg = [];

$configFile = PMA_ROOT . '/config.inc.php';
if (file_exists($configFile)) {
    include $configFile;
}

$mongoServers = $cfg['MongoDB'] ?? [];

$mongoDefaults = [
    'verbose'        => '',
    'host'           => 'localhost',
    'port'           => 27017,
    'username'       => '',
    'password'       => '',
    'auth_database'  => 'admin',
    'uri'            => '',
    'ssh_tunnel'     => '',
    'ssh_host'       => '',
    'ssh_port'       => 22,
    'ssh_user'       => '',
    'ssh_key'        => '',
    'ssh_password'   => '',
    'ssh_extra_args' => '',
    'socks5_proxy'   => '',
    'socks5_user'    => '',
    'socks5_pass'    => '',
];

foreach ($mongoServers as $idx => $srv) {
    $mongoServers[$idx] = array_merge($mongoDefaults, $srv);
}

// i18n
if (session_status() === PHP_SESSION_NONE) {
    session_name('phpMongoAdmin');
    session_start();
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'zh'], true)) {
    $_SESSION['mongo_lang'] = $_GET['lang'];
}
$GLOBALS['_mongo_lang_code'] = $_SESSION['mongo_lang'] ?? 'en';
$GLOBALS['_lang'] = require MONGO_ROOT . '/lang/' . $GLOBALS['_mongo_lang_code'] . '.php';

