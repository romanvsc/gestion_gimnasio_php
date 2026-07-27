<?php
// backend/api/members/detail.php - GET detail + PUT update + DELETE

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/member_validation.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('GET', 'PUT', 'DELETE');

$payload   = requireAuth();
$companyId = (int)$payload['company_id'];
$memberId  = (int)($_GET['id'] ?? 0);
$db        = getDB();

if ($memberId === 0) {
    errorResponse('ID de miembro invalido', 400);
}

$memberStmt = $db->prepare('
    SELECT m.*, pp.name AS plan_name, pp.price AS plan_price, pp.club_member_price, pp.duration_days AS plan_duration_days
    FROM members m
    LEFT JOIN payment_plans pp ON pp.id = m.plan_id AND pp.company_id = m.company_id
    WHERE m.id = ? AND m.company_id = ?
    LIMIT 1
');
$memberStmt->execute([$memberId, $companyId]);
$member = $memberStmt->fetch();

if (!$member) {
    errorResponse('Miembro no encontrado', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rawStatus = strtolower(trim((string)($member['status'] ?? '')));
    $isActive = in_array($rawStatus, ['active', 'activo', '1'], true);
    $validUntil = $member['membership_valid_until'] ?? null;

    $member['quota_current'] = $isActive && $validUntil !== null && $validUntil >= date('Y-m-d');
    $member['id'] = (int)$member['id'];
    $member['plan_id'] = $member['plan_id'] !== null ? (int)$member['plan_id'] : null;
    $member['plan_price'] = $member['plan_price'] !== null ? (float)$member['plan_price'] : null;
    $member['club_member_price'] = $member['club_member_price'] !== null ? (float)$member['club_member_price'] : null;
    $member['plan_duration_days'] = $member['plan_duration_days'] !== null ? (int)$member['plan_duration_days'] : null;
    $member['weight_kg'] = $member['weight_kg'] !== null ? (float)$member['weight_kg'] : null;
    $member['height_cm'] = $member['height_cm'] !== null ? (float)$member['height_cm'] : null;
    $member['is_club_member'] = (bool)$member['is_club_member'];

    $paymentsStmt = $db->prepare("
        SELECT p.*, u.name AS registered_by_name
        FROM payments p
        LEFT JOIN users u ON u.id = p.registered_by AND u.company_id = p.company_id
        WHERE p.member_id = ? AND p.company_id = ?
        ORDER BY p.payment_date DESC
        LIMIT 12
    ");
    $paymentsStmt->execute([$memberId, $companyId]);
    $member['payments'] = $paymentsStmt->fetchAll();

    $checkinsStmt = $db->prepare("
        SELECT c.id, c.checkin_at, c.access_allowed, u.name AS registered_by_name
        FROM checkins c
        LEFT JOIN users u ON u.id = c.registered_by AND u.company_id = c.company_id
        WHERE c.member_id = ? AND c.company_id = ?
        ORDER BY c.checkin_at DESC
        LIMIT 20
    ");
    $checkinsStmt->execute([$memberId, $companyId]);
    $member['checkins'] = $checkinsStmt->fetchAll();

    jsonResponse($member);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $body = normalizeMemberPayload(getJsonBody(), false);

    $fields = [];
    $params = [];
    if (array_key_exists('plan_id', $body) && $body['plan_id'] !== '' && $body['plan_id'] !== null) {
        $planStmt = $db->prepare('SELECT id FROM payment_plans WHERE id = ? AND company_id = ? AND status = "active" LIMIT 1');
        $planStmt->execute([(int)$body['plan_id'], $companyId]);
        if (!$planStmt->fetch()) {
            errorResponse('Plan no encontrado o inactivo', 422);
        }
        $body['plan_id'] = (int)$body['plan_id'];
    }

    if (array_key_exists('membership_valid_until', $body)) {
        requireAdmin($payload);
    }

    $allowed = [
        'first_name','last_name','email','phone','dni','birthdate','address','gender','plan_id',
        'membership_valid_until','notes','photo_url','medical_certificate_valid_until',
        'weight_kg','height_cm','joined_at','is_club_member',
    ];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "{$field} = :{$field}";
            if (in_array($field, ['weight_kg', 'height_cm'], true)) {
                $params[":{$field}"] = $body[$field];
            } elseif ($field === 'is_club_member') {
                $params[":{$field}"] = !empty($body[$field]) ? 1 : 0;
            } else {
                $params[":{$field}"] = $body[$field] !== '' ? $body[$field] : null;
            }
        }
    }

    if (empty($fields)) {
        errorResponse('No hay campos para actualizar', 422);
    }

    $params[':id'] = $memberId;
    $params[':company_id'] = $companyId;

    $db->prepare('UPDATE members SET ' . implode(', ', $fields) . ' WHERE id = :id AND company_id = :company_id')
       ->execute($params);

    $memberStmt->execute([$memberId, $companyId]);
    jsonResponse($memberStmt->fetch());
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireAdmin($payload);

    $db->prepare('UPDATE members SET status = "inactive" WHERE id = ? AND company_id = ?')
       ->execute([$memberId, $companyId]);
    jsonResponse(['message' => 'Miembro desactivado correctamente']);
}
