<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('GET', 'POST');

$payload = requireAuth();
$companyId = (int)$payload['company_id'];
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = strtolower(trim((string)($_GET['status'] ?? '')));
    $where = ['company_id = :company_id'];
    $params = [':company_id' => $companyId];

    if (in_array($status, ['active', 'inactive'], true)) {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }

    $stmt = $db->prepare('
        SELECT id, name, price, club_member_price, duration_days, description, status, created_at
        FROM payment_plans
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY status ASC, price ASC, name ASC
    ');
    $stmt->execute($params);
    $plans = $stmt->fetchAll();

    foreach ($plans as &$plan) {
        $plan['id'] = (int)$plan['id'];
        $plan['price'] = (float)$plan['price'];
        $plan['club_member_price'] = $plan['club_member_price'] !== null ? (float)$plan['club_member_price'] : null;
        $plan['duration_days'] = (int)$plan['duration_days'];
    }

    jsonResponse(['data' => $plans]);
}

requireAdmin($payload);

$body = getJsonBody();
$name = trim((string)($body['name'] ?? ''));
$price = (float)($body['price'] ?? 0);
$clubMemberPrice = isset($body['club_member_price']) && $body['club_member_price'] !== '' ? (float)$body['club_member_price'] : null;
$durationDays = (int)($body['duration_days'] ?? 30);
$status = in_array($body['status'] ?? 'active', ['active', 'inactive'], true) ? $body['status'] : 'active';

if ($name === '') errorResponse('El nombre del plan es requerido', 422);
if ($price <= 0) errorResponse('El precio del plan debe ser mayor a 0', 422);
if ($clubMemberPrice !== null && $clubMemberPrice <= 0) errorResponse('El precio de socio club debe ser mayor a 0', 422);
if ($durationDays < 1) errorResponse('La duracion debe ser de al menos 1 dia', 422);

$stmt = $db->prepare('
    INSERT INTO payment_plans (company_id, name, price, club_member_price, duration_days, description, status)
    VALUES (:company_id, :name, :price, :club_member_price, :duration_days, :description, :status)
');
$stmt->execute([
    ':company_id' => $companyId,
    ':name' => $name,
    ':price' => $price,
    ':club_member_price' => $clubMemberPrice,
    ':duration_days' => $durationDays,
    ':description' => $body['description'] ?? null,
    ':status' => $status,
]);

$id = (int)$db->lastInsertId();
$plan = $db->prepare('SELECT id, name, price, club_member_price, duration_days, description, status, created_at FROM payment_plans WHERE id = ? AND company_id = ?');
$plan->execute([$id, $companyId]);
jsonResponse($plan->fetch(), 201);
