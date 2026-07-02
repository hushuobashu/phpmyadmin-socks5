<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
sqliteRequireLogin();

$serverIdx = (int) ($_GET['server'] ?? 0);

if (isset($sqliteServers[$serverIdx])) {
    $_SESSION['sqlite_server_idx'] = $serverIdx;
    $_SESSION['sqlite_label'] = $sqliteServers[$serverIdx]['verbose'] ?: ('SQLite ' . $sqliteServers[$serverIdx]['mode']);
}

header('Location: pages/databases.php');
exit;
