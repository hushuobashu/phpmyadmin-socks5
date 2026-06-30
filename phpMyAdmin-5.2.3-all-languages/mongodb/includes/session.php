<?php
declare(strict_types=1);

session_name('phpMongoAdmin');
session_start();

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
