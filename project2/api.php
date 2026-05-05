<?php
// ============================================================
// api.php — REST Web-сервис (Задание 8)
// POST   /api.php       — создать заявку (JSON/XML)
// PUT    /api.php/{id}  — изменить заявку (требует JWT)
// GET    /api.php/{id}  — получить заявку (требует JWT)
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validate.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Определяем формат ответа по заголовку Accept
$acceptJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
           || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
$acceptXml  = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/xml') !== false
           || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/xml') !== false;
$useXml     = $acceptXml && !$acceptJson;

function respond(array $data, int $code = 200, bool $useXml = false): void
{
    http_response_code($code);
    if ($useXml) {
        header('Content-Type: application/xml; charset=utf-8');
        echo arrayToXml($data);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    exit;
}

function arrayToXml(array $data, string $root = 'response'): string
{
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<$root>\n";
    foreach ($data as $key => $val) {
        if (is_array($val)) {
            $xml .= "<$key>" . arrayToXml($val, '') . "</$key>\n";
        } else {
            $xml .= "<$key>" . htmlspecialchars((string) $val, ENT_XML1) . "</$key>\n";
        }
    }
    $xml .= "</$root>";
    return $xml;
}

/** Читает тело запроса как JSON или XML → ассоциативный массив */
function parseBody(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];

    // JSON
    $json = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        return $json;
    }

    // XML
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($raw);
    if ($xml !== false) {
        return json_decode(json_encode($xml), true);
    }

    return [];
}

/** Извлекает и проверяет JWT из заголовка Authorization */
function requireAuth(): array
{
    global $useXml;
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
        respond(['error' => 'Необходима авторизация. Передайте Bearer token.'], 401, $useXml);
    }
    $payload = jwtDecode($m[1]);
    if (!$payload) {
        respond(['error' => 'Неверный или просроченный токен.'], 401, $useXml);
    }
    return $payload;
}

// ============================================================
// Маршрутизация
// ============================================================
$method = $_SERVER['REQUEST_METHOD'];

// Извлекаем ID из URI: /api.php/123  или  ?id=123
$pathId  = null;
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
if (preg_match('#^/(\d+)$#', $pathInfo, $m)) {
    $pathId = (int) $m[1];
} elseif (isset($_GET['id'])) {
    $pathId = (int) $_GET['id'];
}

$allLangs  = getAllLanguages();
$validIds  = array_column($allLangs, 'id');

// ============================================================
// POST /api.php — Создание новой заявки
// ============================================================
if ($method === 'POST' && $pathId === null) {
    $body = parseBody();

    if (empty($body)) {
        respond(['error' => 'Тело запроса пустое или неверный формат (ожидается JSON или XML).'], 400, $useXml);
    }

    // Нормализуем поле languages: может быть массивом или строкой через запятую
    if (isset($body['languages']) && is_string($body['languages'])) {
        $body['languages'] = array_map('intval', explode(',', $body['languages']));
    }
    $body['agreed'] = $body['agreed'] ?? '1';

    $errors = validateForm($body, $validIds);
    if (!empty($errors)) {
        respond(['error' => 'Ошибки валидации', 'details' => $errors], 422, $useXml);
    }

    $login     = generateUniqueLogin();
    $plainPass = bin2hex(random_bytes(6));
    $passHash  = password_hash($plainPass, PASSWORD_BCRYPT);

    $appId = insertApplication($body, $login, $passHash);
    insertApplicationLanguages($appId, array_map('intval', $body['languages']));

    // Генерируем JWT для дальнейших запросов
    $token = jwtEncode([
        'sub'   => $appId,
        'login' => $login,
        'iat'   => time(),
        'exp'   => time() + 365 * 86400,
    ]);

    respond([
        'status'      => 'created',
        'id'          => $appId,
        'login'       => $login,
        'password'    => $plainPass,
        'token'       => $token,
        'profile_url' => rtrim('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/') . '/profile.php',
    ], 201, $useXml);
}

// ============================================================
// GET /api.php/{id} — Получение данных заявки
// ============================================================
if ($method === 'GET' && $pathId !== null) {
    $payload = requireAuth();
    if ($payload['sub'] !== $pathId) {
        respond(['error' => 'Доступ запрещён.'], 403, $useXml);
    }

    $app = getApplicationById($pathId);
    if (!$app) {
        respond(['error' => 'Заявка не найдена.'], 404, $useXml);
    }
    unset($app['password_hash']);

    respond(['status' => 'ok', 'data' => $app], 200, $useXml);
}

// ============================================================
// PUT /api.php/{id} — Изменение данных авторизованным пользователем
// ============================================================
if ($method === 'PUT' && $pathId !== null) {
    $payload = requireAuth();
    if ((int) $payload['sub'] !== $pathId) {
        respond(['error' => 'Доступ запрещён.'], 403, $useXml);
    }

    $app = getApplicationById($pathId);
    if (!$app) {
        respond(['error' => 'Заявка не найдена.'], 404, $useXml);
    }

    $body = parseBody();
    if (empty($body)) {
        respond(['error' => 'Тело запроса пустое.'], 400, $useXml);
    }

    if (isset($body['languages']) && is_string($body['languages'])) {
        $body['languages'] = array_map('intval', explode(',', $body['languages']));
    }
    $body['agreed'] = '1';

    $errors = validateForm($body, $validIds);
    if (!empty($errors)) {
        respond(['error' => 'Ошибки валидации', 'details' => $errors], 422, $useXml);
    }

    updateApplication($pathId, $body);
    replaceApplicationLanguages($pathId, array_map('intval', $body['languages']));

    respond(['status' => 'updated', 'id' => $pathId], 200, $useXml);
}

// ============================================================
// Авторизация: POST /api.php?action=login — получить токен
// ============================================================
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'login') {
    $body = parseBody();
    $login    = $body['login']    ?? '';
    $password = $body['password'] ?? '';

    $app = getApplicationByLogin($login);
    if (!$app || !password_verify($password, $app['password_hash'])) {
        respond(['error' => 'Неверный логин или пароль.'], 401, $useXml);
    }

    $token = jwtEncode([
        'sub'   => $app['id'],
        'login' => $app['login'],
        'iat'   => time(),
        'exp'   => time() + 365 * 86400,
    ]);

    respond(['status' => 'ok', 'token' => $token, 'id' => $app['id']], 200, $useXml);
}

respond(['error' => 'Неверный метод или маршрут.'], 404, $useXml);
