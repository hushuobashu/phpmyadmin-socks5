<?php
declare(strict_types=1);

function sqliteCsrfToken(): string
{
    if (empty($_SESSION['sqlite_csrf'])) {
        $_SESSION['sqlite_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['sqlite_csrf'];
}

function sqliteCsrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(sqliteCsrfToken()) . '">';
}

function sqliteVerifyCsrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals(sqliteCsrfToken(), $token)) {
        http_response_code(403);
        die('CSRF token mismatch');
    }
}

function sqliteFlash(string $message, string $type = 'success'): void
{
    $_SESSION['sqlite_flash'][] = ['message' => $message, 'type' => $type];
}

function sqliteFlashMessages(): array
{
    $msgs = $_SESSION['sqlite_flash'] ?? [];
    unset($_SESSION['sqlite_flash']);
    return $msgs;
}

function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function formatBytes(int $bytes, int $decimals = 1): string
{
    if ($bytes === 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = (int) floor(log($bytes, 1024));

    return round($bytes / pow(1024, $i), $decimals) . ' ' . $units[$i];
}
