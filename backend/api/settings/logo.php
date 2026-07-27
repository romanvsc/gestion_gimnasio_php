<?php
// backend/api/settings/logo.php - POST upload company logo

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('POST');

$payload = requireAuth();
requireAdmin($payload);

$companyId = (int)$payload['company_id'];
$db = getDB();

if (!isset($_FILES['logo']) || !is_array($_FILES['logo'])) {
    errorResponse('Archivo de logo requerido', 422);
}

$file = $_FILES['logo'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    errorResponse(uploadErrorMessage((int)$file['error']), 422);
}

$maxBytes = 2 * 1024 * 1024;
if ((int)$file['size'] > $maxBytes) {
    errorResponse('El logo no puede superar 2 MB', 422);
}

$tmpName = (string)$file['tmp_name'];
$mime = mime_content_type($tmpName) ?: '';
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

if (!isset($allowed[$mime])) {
    errorResponse('Formato de logo invalido. Usa JPG, PNG, WEBP o GIF', 422);
}

$uploadDir = dirname(__DIR__, 2) . '/uploads/company-logos';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
    errorResponse('No se pudo preparar la carpeta de logos', 500);
}

$filename = sprintf(
    'company_%d_%s_%s.%s',
    $companyId,
    date('YmdHis'),
    bin2hex(random_bytes(4)),
    $allowed[$mime]
);
$target = $uploadDir . '/' . $filename;

if (!move_uploaded_file($tmpName, $target)) {
    errorResponse('No se pudo guardar el logo', 500);
}

$logoUrl = '/api/uploads/company-logos/' . $filename;

$stmt = $db->prepare('SELECT logo_url FROM companies WHERE id = ? LIMIT 1');
$stmt->execute([$companyId]);
$previousLogo = $stmt->fetchColumn();

$db->prepare('UPDATE companies SET logo_url = ? WHERE id = ?')->execute([$logoUrl, $companyId]);

deletePreviousLocalLogo(is_string($previousLogo) ? $previousLogo : null, $logoUrl);

$stmt = $db->prepare("
    SELECT id, name, email, phone, address, city, country, logo_url, opening_hours,
           checkin_duplicate_policy, status
    FROM companies
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$companyId]);

jsonResponse($stmt->fetch(), 201);

function uploadErrorMessage(int $code): string {
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El logo supera el tamaño permitido',
        UPLOAD_ERR_PARTIAL => 'El logo se subio parcialmente',
        UPLOAD_ERR_NO_FILE => 'Archivo de logo requerido',
        default => 'No se pudo subir el logo',
    };
}

function deletePreviousLocalLogo(?string $previousLogo, string $newLogo): void {
    if ($previousLogo === null || $previousLogo === $newLogo) {
        return;
    }

    $prefixes = ['/uploads/company-logos/', '/api/uploads/company-logos/'];
    $matchedPrefix = null;
    foreach ($prefixes as $prefix) {
        if (str_starts_with($previousLogo, $prefix)) {
            $matchedPrefix = $prefix;
            break;
        }
    }

    if ($matchedPrefix === null) {
        return;
    }

    $relativePath = str_starts_with($previousLogo, '/api/uploads/')
        ? substr($previousLogo, 4)
        : $previousLogo;
    $path = dirname(__DIR__, 2) . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $baseDir = realpath(dirname(__DIR__, 2) . '/uploads/company-logos');
    $realPath = realpath($path);

    if ($baseDir !== false && $realPath !== false && str_starts_with($realPath, $baseDir) && is_file($realPath)) {
        @unlink($realPath);
    }
}
