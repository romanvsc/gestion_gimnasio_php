<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('GET', 'PUT');

$payload = requireAuth();
$companyId = (int)$payload['company_id'];
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("
        SELECT id, name, email, phone, address, city, country, logo_url, opening_hours,
               checkin_duplicate_policy, status
        FROM companies
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $company = $stmt->fetch();
    if (!$company) errorResponse('Empresa no encontrada', 404);
    jsonResponse($company);
}

requireAdmin($payload);

$body = getJsonBody();
$allowed = ['name', 'email', 'phone', 'address', 'city', 'country', 'logo_url', 'opening_hours', 'checkin_duplicate_policy'];
$fields = [];
$params = [':id' => $companyId];

if (array_key_exists('name', $body) && trim((string)$body['name']) === '') {
    errorResponse('El nombre de la empresa es requerido', 422);
}
if (array_key_exists('email', $body) && trim((string)$body['email']) === '') {
    errorResponse('El email de la empresa es requerido', 422);
}
if (array_key_exists('checkin_duplicate_policy', $body)
    && !in_array($body['checkin_duplicate_policy'], ['allow', 'confirm', 'block'], true)) {
    errorResponse('Politica de check-in invalida', 422);
}

foreach ($allowed as $field) {
    if (array_key_exists($field, $body)) {
        $fields[] = "{$field} = :{$field}";
        $params[":{$field}"] = $body[$field] !== '' ? $body[$field] : null;
    }
}

if (empty($fields)) errorResponse('No hay campos para actualizar', 422);

$db->prepare('UPDATE companies SET ' . implode(', ', $fields) . ' WHERE id = :id')->execute($params);

$stmt = $db->prepare("
    SELECT id, name, email, phone, address, city, country, logo_url, opening_hours,
           checkin_duplicate_policy, status
    FROM companies
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$companyId]);
jsonResponse($stmt->fetch());
