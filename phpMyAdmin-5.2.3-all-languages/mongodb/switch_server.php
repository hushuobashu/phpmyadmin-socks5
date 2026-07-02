<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
mongoRequireLogin();

$serverIdx = (int) ($_GET['server'] ?? 0);

if (!isset($mongoServers[$serverIdx])) {
    header('Location: pages/databases.php');
    exit;
}

$srv = $mongoServers[$serverIdx];
$label = $srv['verbose'] ?: ($srv['uri'] ? 'URI connection' : ($srv['host'] . ':' . $srv['port']));

if (!empty($srv['uri'])) {
    $uri = $srv['uri'];
    if (!empty($srv['ssh_tunnel']) || !empty($srv['socks5_proxy'])) {
        require_once __DIR__ . '/includes/MongoTunnel.php';
        $uri = MongoTunnel::rewriteUri($uri, $srv);
    }
} else {
    $host = $srv['host'];
    $port = (int) $srv['port'];
    $username = $srv['username'];
    $password = $srv['password'];
    $authDb = $srv['auth_database'];

    if (!empty($srv['ssh_tunnel']) || !empty($srv['socks5_proxy'])) {
        require_once __DIR__ . '/includes/MongoTunnel.php';
        $resolved = MongoTunnel::resolve($srv);
        $host = $resolved['host'];
        $port = $resolved['port'];
    }

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

    $_SESSION['mongo_uri'] = $uri;
    $_SESSION['mongo_label'] = $label;
    $_SESSION['mongo_server_idx'] = $serverIdx;
    $_SESSION['mongo_server_cfg'] = [
        'host' => $srv['host'],
        'port' => (int) $srv['port'],
        'uri'  => $srv['uri'] ?? '',
        'ssh_tunnel' => $srv['ssh_tunnel'] ?? '',
        'ssh_host'   => $srv['ssh_host'] ?? '',
        'ssh_port'   => (int) ($srv['ssh_port'] ?? 22),
        'ssh_user'   => $srv['ssh_user'] ?? '',
        'socks5_proxy' => $srv['socks5_proxy'] ?? '',
    ];

    header('Location: pages/databases.php');
} catch (Exception $e) {
    header('Location: pages/databases.php?error=' . urlencode($e->getMessage()));
}
exit;
