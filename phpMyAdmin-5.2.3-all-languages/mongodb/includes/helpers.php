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
        die(__('csrf_mismatch'));
    }
}

function __($key, ...$args): string
{
    $text = $GLOBALS['_lang'][$key] ?? $key;
    return $args ? sprintf($text, ...$args) : $text;
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
        if ($val instanceof MongoDB\BSON\ObjectId) {
            $result[$key] = ['$oid' => (string) $val];
        } elseif ($val instanceof MongoDB\BSON\UTCDateTime) {
            $result[$key] = ['$date' => $val->toDateTime()->format('c')];
        } elseif (is_object($val) || is_array($val)) {
            $result[$key] = bsonDocToArray($val);
        } else {
            $result[$key] = $val;
        }
    }

    return $result;
}

/**
 * Convert extended JSON patterns back to BSON types before saving to MongoDB.
 * {"$oid": "..."} -> MongoDB\BSON\ObjectId
 * {"$date": "..."} -> MongoDB\BSON\UTCDateTime
 */
function jsonDocToBson(array $doc): array
{
    $result = [];
    foreach ($doc as $key => $val) {
        if (is_array($val)) {
            if (isset($val['$oid']) && count($val) === 1) {
                $result[$key] = new MongoDB\BSON\ObjectId($val['$oid']);
            } elseif (isset($val['$date']) && count($val) === 1) {
                $ts = strtotime($val['$date']);
                $result[$key] = new MongoDB\BSON\UTCDateTime($ts !== false ? $ts * 1000 : 0);
            } else {
                $result[$key] = jsonDocToBson($val);
            }
        } else {
            $result[$key] = $val;
        }
    }

    return $result;
}
