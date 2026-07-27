<?php
// backend/api/members/index.php - GET list + POST create

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/member_validation.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('GET', 'POST');

$payload   = requireAuth();
$companyId = (int)$payload['company_id'];
$db        = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = trim($_GET['search'] ?? '');
    $status = strtolower(trim((string)($_GET['status'] ?? '')));
    $quota  = strtolower(trim((string)($_GET['quota'] ?? '')));
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = min(100, max(5, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $activeStatusSql = "LOWER(TRIM(m.status)) IN ('active', 'activo', '1')";
    $quotaCurrentSql = "{$activeStatusSql} AND m.membership_valid_until IS NOT NULL AND m.membership_valid_until >= CURDATE()";
    $quotaDebtSql = "{$activeStatusSql} AND (m.membership_valid_until IS NULL OR m.membership_valid_until < CURDATE())";

    $where  = ['m.company_id = :company_id'];
    $params = [':company_id' => $companyId];

    if ($search !== '') {
        $where[] = '(m.first_name LIKE :search1 OR m.last_name LIKE :search2 OR m.dni LIKE :search3 OR m.email LIKE :search4 OR m.phone LIKE :search5)';
        $searchParam = "%{$search}%";
        $params[':search1'] = $searchParam;
        $params[':search2'] = $searchParam;
        $params[':search3'] = $searchParam;
        $params[':search4'] = $searchParam;
        $params[':search5'] = $searchParam;
    }

    if (in_array($status, ['active', 'activo', '1'], true)) {
        $where[] = $activeStatusSql;
    } elseif (in_array($status, ['inactive', 'inactivo', '0'], true)) {
        $where[] = "LOWER(TRIM(m.status)) IN ('inactive', 'inactivo', '0')";
    }

    if (in_array($quota, ['paid', 'al_dia'], true)) {
        $where[] = $quotaCurrentSql;
    } elseif (in_array($quota, ['debt', 'mora'], true)) {
        $where[] = $quotaDebtSql;
    }

    $whereClause = implode(' AND ', $where);

    $countStmt = $db->prepare("SELECT COUNT(*) FROM members m WHERE {$whereClause}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $statsStmt = $db->prepare("
        SELECT
            SUM(CASE WHEN {$activeStatusSql} THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN {$quotaCurrentSql} THEN 1 ELSE 0 END) AS paid_count,
            SUM(CASE WHEN {$quotaDebtSql} THEN 1 ELSE 0 END) AS debt_count
        FROM members m
        WHERE m.company_id = :company_id
    ");
    $statsStmt->execute([':company_id' => $companyId]);
    $stats = $statsStmt->fetch() ?: ['active_count' => 0, 'paid_count' => 0, 'debt_count' => 0];

    $stmt = $db->prepare("
        SELECT
            m.id, m.first_name, m.last_name, m.email, m.phone,
            m.dni, m.birthdate, m.gender, m.plan_id, m.membership_valid_until,
            m.photo_url, m.medical_certificate_valid_until, m.weight_kg, m.height_cm,
            m.joined_at, m.is_club_member, m.status, m.created_at,
            pp.name AS plan_name, pp.price AS plan_price, pp.club_member_price, pp.duration_days AS plan_duration_days,
            IF({$quotaCurrentSql}, 1, 0) AS quota_current
        FROM members m
        LEFT JOIN payment_plans pp ON pp.id = m.plan_id AND pp.company_id = m.company_id
        WHERE {$whereClause}
        ORDER BY m.last_name ASC, m.first_name ASC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $members = $stmt->fetchAll();

    foreach ($members as &$m) {
        $m['id'] = (int)$m['id'];
        $m['plan_id'] = $m['plan_id'] !== null ? (int)$m['plan_id'] : null;
        $m['plan_price'] = $m['plan_price'] !== null ? (float)$m['plan_price'] : null;
        $m['club_member_price'] = $m['club_member_price'] !== null ? (float)$m['club_member_price'] : null;
        $m['plan_duration_days'] = $m['plan_duration_days'] !== null ? (int)$m['plan_duration_days'] : null;
        $m['weight_kg'] = $m['weight_kg'] !== null ? (float)$m['weight_kg'] : null;
        $m['height_cm'] = $m['height_cm'] !== null ? (float)$m['height_cm'] : null;
        $m['is_club_member'] = (bool)$m['is_club_member'];
        $m['quota_current'] = (bool)$m['quota_current'];
        $rawStatus = strtolower(trim((string)($m['status'] ?? '')));
        $m['status'] = in_array($rawStatus, ['active', 'activo', '1'], true) ? 'active' : 'inactive';
    }

    jsonResponse([
        'data' => $members,
        'meta' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
            'stats' => [
                'active' => (int)($stats['active_count'] ?? 0),
                'paid' => (int)($stats['paid_count'] ?? 0),
                'debt' => (int)($stats['debt_count'] ?? 0),
            ],
        ],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = normalizeMemberPayload(getJsonBody(), true);

    $firstName = $body['first_name'];
    $lastName  = $body['last_name'];

    $planId = isset($body['plan_id']) && $body['plan_id'] !== '' ? (int)$body['plan_id'] : null;
    if ($planId !== null) {
        $planStmt = $db->prepare('SELECT id FROM payment_plans WHERE id = ? AND company_id = ? AND status = "active" LIMIT 1');
        $planStmt->execute([$planId, $companyId]);
        if (!$planStmt->fetch()) {
            errorResponse('Plan no encontrado o inactivo', 422);
        }
    }

    $stmt = $db->prepare("
        INSERT INTO members
            (company_id, first_name, last_name, email, phone, dni, birthdate, address, gender,
             photo_url, medical_certificate_valid_until, weight_kg, height_cm, joined_at,
             is_club_member, plan_id, notes, status)
        VALUES
            (:company_id, :first_name, :last_name, :email, :phone, :dni, :birthdate, :address, :gender,
             :photo_url, :medical_certificate_valid_until, :weight_kg, :height_cm, :joined_at,
             :is_club_member, :plan_id, :notes, :status)
    ");
    $stmt->execute([
        ':company_id' => $companyId,
        ':first_name' => $firstName,
        ':last_name'  => $lastName,
        ':email'      => $body['email']     ?? null,
        ':phone'      => $body['phone']     ?? null,
        ':dni'        => $body['dni']       ?? null,
        ':birthdate'  => $body['birthdate'] ?? null,
        ':address'    => $body['address']   ?? null,
        ':gender'     => $body['gender'] ?? null,
        ':photo_url'  => $body['photo_url'] ?? null,
        ':medical_certificate_valid_until' => $body['medical_certificate_valid_until'] ?? null,
        ':weight_kg'  => isset($body['weight_kg']) && $body['weight_kg'] !== '' ? (float)$body['weight_kg'] : null,
        ':height_cm'  => isset($body['height_cm']) && $body['height_cm'] !== '' ? (float)$body['height_cm'] : null,
        ':joined_at'  => $body['joined_at'] ?? null,
        ':is_club_member' => !empty($body['is_club_member']) ? 1 : 0,
        ':plan_id'    => $planId,
        ':notes'      => $body['notes']     ?? null,
        ':status'     => 'active',
    ]);

    $newId = (int)$db->lastInsertId();

    $member = $db->prepare('SELECT * FROM members WHERE id = ? AND company_id = ?');
    $member->execute([$newId, $companyId]);

    jsonResponse($member->fetch(), 201);
}
