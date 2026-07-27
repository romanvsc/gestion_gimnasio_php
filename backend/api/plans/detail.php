<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('PUT', 'DELETE');

$payload = requireAuth();
$companyId = (int)$payload['company_id'];
$planId = (int)($_GET['id'] ?? 0);
$db = getDB();

requireAdmin($payload);

if ($planId === 0) errorResponse('ID de plan invalido', 400);

$exists = $db->prepare('SELECT id FROM payment_plans WHERE id = ? AND company_id = ? LIMIT 1');
$exists->execute([$planId, $companyId]);
if (!$exists->fetch()) errorResponse('Plan no encontrado', 404);

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $db->prepare('UPDATE payment_plans SET status = "inactive" WHERE id = ? AND company_id = ?')
       ->execute([$planId, $companyId]);
    jsonResponse(['message' => 'Plan desactivado correctamente']);
}

$body = getJsonBody();
$allowed = ['name', 'price', 'club_member_price', 'duration_days', 'description', 'status'];
$fields = [];
$params = [':id' => $planId, ':company_id' => $companyId];

if (array_key_exists('name', $body) && trim((string)$body['name']) === '') errorResponse('El nombre del plan es requerido', 422);
if (array_key_exists('price', $body) && (float)$body['price'] <= 0) errorResponse('El precio del plan debe ser mayor a 0', 422);
if (array_key_exists('club_member_price', $body) && $body['club_member_price'] !== '' && $body['club_member_price'] !== null && (float)$body['club_member_price'] <= 0) errorResponse('El precio de socio club debe ser mayor a 0', 422);
if (array_key_exists('duration_days', $body) && (int)$body['duration_days'] < 1) errorResponse('La duracion debe ser de al menos 1 dia', 422);
if (array_key_exists('status', $body) && !in_array($body['status'], ['active', 'inactive'], true)) errorResponse('Estado de plan invalido', 422);

foreach ($allowed as $field) {
    if (array_key_exists($field, $body)) {
        $fields[] = "{$field} = :{$field}";
        $params[":{$field}"] = $body[$field] !== '' ? $body[$field] : null;
    }
}

if (empty($fields)) errorResponse('No hay campos para actualizar', 422);

$db->prepare('UPDATE payment_plans SET ' . implode(', ', $fields) . ' WHERE id = :id AND company_id = :company_id')
   ->execute($params);

$stmt = $db->prepare('SELECT id, name, price, club_member_price, duration_days, description, status, created_at FROM payment_plans WHERE id = ? AND company_id = ?');
$stmt->execute([$planId, $companyId]);
jsonResponse($stmt->fetch());
