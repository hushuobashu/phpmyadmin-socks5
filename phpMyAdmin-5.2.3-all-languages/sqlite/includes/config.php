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
