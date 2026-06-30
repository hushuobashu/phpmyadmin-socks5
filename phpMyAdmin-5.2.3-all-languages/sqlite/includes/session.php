<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

session_name('phpSqliteAdmin');
session_start();

function sqliteIsLoggedIn(): bool
{
    return !empty($_SESSION['sqlite_logged_in']);
}

function sqliteRequireLogin(): void
{
    if (!sqliteIsLoggedIn()) {
        header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/index.php');
        exit;
    }
}

function sqliteGetDriver(): ?SqliteDriverInterface
{
    if (!sqliteIsLoggedIn()) {
        return null;
    }

    static $driver = null;
    if ($driver !== null) {
        return $driver;
    }

    require_once __DIR__ . '/SqliteDriver.php';

    $serverIdx = $_SESSION['sqlite_server_idx'] ?? 0;

    require_once __DIR__ . '/config.php';
    global $sqliteServers;

    if (!isset($sqliteServers[$serverIdx])) {
        session_destroy();
        header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/index.php?error=' . urlencode('Server configuration not found'));
        exit;
    }

    $srv = $sqliteServers[$serverIdx];

    if ($srv['mode'] === 'ssh') {
        $driver = new SqliteSshDriver($srv);
    } else {
        $driver = new SqliteLocalDriver();
    }

    return $driver;
}

function sqliteGetServerLabel(): string
{
    return $_SESSION['sqlite_label'] ?? 'SQLite';
}

function sqliteGetDirectories(): array
{
    $serverIdx = $_SESSION['sqlite_server_idx'] ?? 0;

    require_once __DIR__ . '/config.php';
    global $sqliteServers;

    if (!isset($sqliteServers[$serverIdx])) {
        return [];
    }

    return $sqliteServers[$serverIdx]['directories'] ?? [];
}
