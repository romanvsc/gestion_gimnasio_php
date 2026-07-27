<?php
// backend/api/members/status.php - PATCH /:id/status (baja/alta)

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('PATCH');

$payload   = requireAuth();
$companyId = (int)$payload['company_id'];
$memberId  = (int)($_GET['id'] ?? 0);
$db        = getDB();

if ($memberId === 0) {
    errorResponse('ID de miembro inválido', 400);
}

$body   = getJsonBody();
$status = $body['status'] ?? '';

if (!in_array($status, ['active', 'inactive'], true)) {
    errorResponse('Estado inválido. Debe ser "active" o "inactive"', 422);
}

$stmt = $db->prepare('UPDATE members SET status = ? WHERE id = ? AND company_id = ?');
$stmt->execute([$status, $memberId, $companyId]);

if ($stmt->rowCount() === 0) {
    errorResponse('Miembro no encontrado', 404);
}

$member = $db->prepare('SELECT * FROM members WHERE id = ? AND company_id = ?');
$member->execute([$memberId, $companyId]);

jsonResponse($member->fetch());
