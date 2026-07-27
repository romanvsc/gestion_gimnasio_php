<?php
// backend/index.php - Router principal de la API

declare(strict_types=1);

// Cargar variables de entorno desde .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$uploadRequestPath = str_starts_with($requestUri, '/api/uploads/')
    ? substr($requestUri, 4)
    : $requestUri;

if (str_starts_with($uploadRequestPath, '/uploads/')) {
    $uploadsRoot = realpath(__DIR__ . '/uploads');
    $requested = realpath(__DIR__ . $uploadRequestPath);

    if ($uploadsRoot !== false && $requested !== false && str_starts_with($requested, $uploadsRoot) && is_file($requested)) {
        $mime = mime_content_type($requested) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($requested));
        readfile($requested);
        exit;
    }

    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config/cors.php';
setCorsHeaders();

header('Content-Type: application/json; charset=utf-8');

// Parsear la ruta. Soporta /api/... local y /subcarpeta/api/... en hosting.
$method     = $_SERVER['REQUEST_METHOD'];
$apiPos     = strpos($requestUri, '/api/');

if ($requestUri === '/api') {
    $path = '/';
} elseif ($apiPos !== false) {
    $path = substr($requestUri, $apiPos + 4);
} else {
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $path      = ($scriptDir !== '' && $scriptDir !== '.' && str_starts_with($requestUri, $scriptDir))
        ? substr($requestUri, strlen($scriptDir))
        : $requestUri;
}

$path = '/' . ltrim($path, '/');

// Tabla de rutas: [method, pattern, handler_file]
$routes = [
    ['POST',  '/auth/login',   'api/auth/login.php'],
    ['GET',   '/auth/me',      'api/auth/me.php'],

    ['GET',   '/members',      'api/members/index.php'],
    ['POST',  '/members',      'api/members/index.php'],

    ['GET',   '/checkins',     'api/checkins/index.php'],
    ['POST',  '/checkins',     'api/checkins/index.php'],

    ['GET',   '/payments',     'api/payments/index.php'],
    ['POST',  '/payments',     'api/payments/index.php'],

    ['GET',   '/cash-transactions', 'api/cash-transactions/index.php'],

    ['GET',   '/metrics',      'api/metrics/index.php'],
    ['GET',   '/settings',     'api/settings/index.php'],
    ['PUT',   '/settings',     'api/settings/index.php'],
    ['POST',  '/settings/logo','api/settings/logo.php'],
    ['GET',   '/plans',        'api/plans/index.php'],
    ['POST',  '/plans',        'api/plans/index.php'],
];

if (preg_match('#^/plans/(\d+)$#', $path, $matches)) {
    $_GET['id'] = (int)$matches[1];
    if (in_array($method, ['PUT', 'DELETE'], true)) {
        require __DIR__ . '/api/plans/detail.php';
        exit;
    }
}

// Rutas con ID de miembro
if (preg_match('#^/members/(\d+)(/status)?$#', $path, $matches)) {
    $memberId   = (int)$matches[1];
    $subRoute   = $matches[2] ?? '';
    $_GET['id'] = $memberId;

    if ($subRoute === '/status' && $method === 'PATCH') {
        require __DIR__ . '/api/members/status.php';
        exit;
    }
    if (in_array($method, ['GET', 'PUT'], true)) {
        require __DIR__ . '/api/members/detail.php';
        exit;
    }
    if ($method === 'DELETE') {
        require __DIR__ . '/api/members/detail.php';
        exit;
    }
}

foreach ($routes as [$routeMethod, $routePath, $handler]) {
    if ($method === $routeMethod && $path === $routePath) {
        require __DIR__ . '/' . $handler;
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Route not found', 'path' => $path, 'method' => $method]);
