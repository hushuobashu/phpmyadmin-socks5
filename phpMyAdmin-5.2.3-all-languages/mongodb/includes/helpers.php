<?php
declare(strict_types=1);

function mongoCsrfToken(): string
{
    if (empty($_SESSION['mongo_csrf'])) {
        $_SESSION['mongo_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['mongo_csrf'];
}

function mongoCsrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(mongoCsrfToken()) . '">';
}

function mongoVerifyCsrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals(mongoCsrfToken(), $token)) {
        http_response_code(403);
        die('CSRF token mismatch');
    }
}

function mongoFlash(string $message, string $type = 'success'): void
{
    $_SESSION['mongo_flash'][] = ['message' => $message, 'type' => $type];
}

function mongoFlashMessages(): array
{
    $msgs = $_SESSION['mongo_flash'] ?? [];
    unset($_SESSION['mongo_flash']);
    return $msgs;
}

function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function mongoUrl(string $page, array $params = []): string
{
    $base = dirname($_SERVER['SCRIPT_NAME']);
    $url = rtrim($base, '/') . '/' . ltrim($page, '/');
    if ($params) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
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

function bsonToJson($value): string
{
    if ($value instanceof MongoDB\BSON\ObjectId) {
        return json_encode(['$oid' => (string) $value]);
    }

    if ($value instanceof MongoDB\BSON\UTCDateTime) {
        return json_encode(['$date' => $value->toDateTime()->format('c')]);
    }

    if (is_object($value)) {
        $value = (array) $value;
    }

    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function bsonDocToArray($doc): array
{
    if ($doc instanceof MongoDB\BSON\Document) {
        $doc = $doc->toPHP();
    }

    $result = [];
    foreach ((array) $doc as $key => $val) {
        if (is_object($val) && !($val instanceof MongoDB\BSON\ObjectId) && !($val instanceof MongoDB\BSON\UTCDateTime)) {
            $result[$key] = bsonDocToArray($val);
        } elseif (is_array($val)) {
            $result[$key] = bsonDocToArray($val);
        } else {
            $result[$key] = $val;
        }
    }

    return $result;
}
