<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('phpMongoAdmin');
    session_start();
}

function mongoIsLoggedIn(): bool
{
    return !empty($_SESSION['mongo_logged_in']);
}

function mongoRequireLogin(): void
{
    if (!mongoIsLoggedIn()) {
        header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/index.php');
        exit;
    }
}

function mongoGetConnection(): ?MongoConnection
{
    if (!mongoIsLoggedIn()) {
        return null;
    }

    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    require_once __DIR__ . '/MongoConnection.php';

    $uri = $_SESSION['mongo_uri'] ?? 'mongodb://localhost:27017';
    try {
        $conn = new MongoConnection($uri);
    } catch (Exception $e) {
        session_destroy();
        header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/index.php?error=' . urlencode($e->getMessage()));
        exit;
    }

    return $conn;
}

function mongoGetServerLabel(): string
{
    return $_SESSION['mongo_label'] ?? 'MongoDB';
}

function mongoGetServerDisplay(): string
{
    $cfg = $_SESSION['mongo_server_cfg'] ?? [];
    if (empty($cfg)) {
        $uri = $_SESSION['mongo_uri'] ?? '';
        if ($uri === '') {
            return '';
        }
        return mongoMaskUri($uri);
    }

    // Show the configured target (from config.inc.php)
    if (!empty($cfg['uri'])) {
        $display = mongoMaskUri($cfg['uri']);
    } else {
        $display = $cfg['host'] . ':' . $cfg['port'];
    }

    // If connected via SSH tunnel, show tunnel info
    if (!empty($cfg['ssh_tunnel']) || !empty($cfg['ssh_host'])) {
        $sshInfo = $cfg['ssh_user'] ? ($cfg['ssh_user'] . '@') : '';
        $sshInfo .= $cfg['ssh_host'] ?: $cfg['ssh_tunnel'];
        if (!empty($cfg['ssh_port']) && $cfg['ssh_port'] !== 22) {
            $sshInfo .= ':' . $cfg['ssh_port'];
        }
        $display .= ' (via SSH ' . $sshInfo . ')';
    } elseif (!empty($cfg['socks5_proxy'])) {
        $display .= ' (via SOCKS5 ' . $cfg['socks5_proxy'] . ')';
    }

    return $display;
}

function mongoMaskUri(string $uri): string
{
    $parsed = parse_url($uri);
    if ($parsed === false) {
        return preg_replace('#://[^@]+@#', '://***:***@', $uri);
    }
    $scheme = ($parsed['scheme'] ?? 'mongodb') . '://';
    $host = $parsed['host'] ?? 'localhost';
    $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
    $path = $parsed['path'] ?? '/';
    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
    $credentials = '';
    if (!empty($parsed['user'])) {
        $credentials = '***:***@';
    }
    return $scheme . $credentials . $host . $port . $path . $query;
}
