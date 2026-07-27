<?php
// backend/api/checkins/index.php - GET list + POST create

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('GET', 'POST');

$payload   = requireAuth();
$companyId = (int)$payload['company_id'];
$db        = getDB();

// ── GET: listar check-ins ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date     = $_GET['date']      ?? date('Y-m-d');
    $memberId = (int)($_GET['member_id'] ?? 0);
    $page     = max(1, (int)($_GET['page']  ?? 1));
    $limit    = min(100, max(10, (int)($_GET['limit'] ?? 30)));
    $offset   = ($page - 1) * $limit;

    $where  = ['c.company_id = :company_id'];
    $params = [':company_id' => $companyId];

    if ($date) {
        if (!isValidDate($date)) {
            errorResponse('Fecha invalida', 422);
        }
        $nextDate = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
        $where[] = 'c.checkin_at >= :date_start AND c.checkin_at < :date_end';
        $params[':date_start'] = $date;
        $params[':date_end'] = $nextDate;
    }
    if ($memberId > 0) {
        $where[]              = 'c.member_id = :member_id';
        $params[':member_id'] = $memberId;
    }

    $whereClause = implode(' AND ', $where);

    $countStmt = $db->prepare("SELECT COUNT(*) FROM checkins c WHERE {$whereClause}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT
            c.id, c.checkin_at, c.access_allowed, c.member_id,
            CONCAT(m.first_name, ' ', m.last_name) AS member_name,
            m.dni AS member_dni,
            u.name AS registered_by_name
        FROM checkins c
        JOIN members m ON m.id = c.member_id AND m.company_id = c.company_id
        LEFT JOIN users u ON u.id = c.registered_by AND u.company_id = c.company_id
        WHERE {$whereClause}
        ORDER BY c.checkin_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    jsonResponse([
        'data' => $stmt->fetchAll(),
        'meta' => ['total' => $total, 'page' => $page, 'limit' => $limit],
    ]);
}

// ── POST: registrar check-in ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body     = getJsonBody();
    $memberId = (int)($body['member_id'] ?? 0);
    $confirmDuplicate = (bool)($body['confirm_duplicate'] ?? false);

    if ($memberId === 0) {
        errorResponse('member_id es requerido', 422);
    }

    // Verificar miembro activo y de la empresa
    $memberStmt = $db->prepare('SELECT id, first_name, last_name, status FROM members WHERE id = ? AND company_id = ? LIMIT 1');
    $memberStmt->execute([$memberId, $companyId]);
    $member = $memberStmt->fetch();

    if (!$member) {
        errorResponse('Miembro no encontrado', 404);
    }
    $memberStatus = strtolower(trim((string)($member['status'] ?? '')));
    $isActive = in_array($memberStatus, ['active', 'activo', '1'], true);
    if (!$isActive) {
        errorResponse('El miembro no está activo', 409);
    }

    $settingsStmt = $db->prepare('SELECT checkin_duplicate_policy FROM companies WHERE id = ? LIMIT 1');
    $settingsStmt->execute([$companyId]);
    $duplicatePolicy = $settingsStmt->fetchColumn() ?: 'confirm';

    $duplicateStmt = $db->prepare('
        SELECT id, checkin_at
        FROM checkins
        WHERE company_id = ? AND member_id = ? AND checkin_at >= CURDATE() AND checkin_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ORDER BY checkin_at DESC
        LIMIT 1
    ');
    $duplicateStmt->execute([$companyId, $memberId]);
    $existingCheckin = $duplicateStmt->fetch();

    if ($existingCheckin && $duplicatePolicy === 'block') {
        errorResponse('Este miembro ya hizo check-in hoy', 409, [
            'duplicate_checkin' => true,
            'policy' => 'block',
            'existing_checkin_at' => $existingCheckin['checkin_at'],
        ]);
    }

    if ($existingCheckin && $duplicatePolicy === 'confirm' && !$confirmDuplicate) {
        errorResponse('Este miembro ya hizo check-in hoy. Confirma si queres registrarlo nuevamente.', 409, [
            'duplicate_checkin' => true,
            'policy' => 'confirm',
            'existing_checkin_at' => $existingCheckin['checkin_at'],
        ]);
    }

    $stmt = $db->prepare('INSERT INTO checkins (company_id, member_id, access_allowed, registered_by) VALUES (?, ?, 1, ?)');
    $stmt->execute([$companyId, $memberId, $payload['user_id']]);
    $newId = (int)$db->lastInsertId();

    jsonResponse([
        'id'          => $newId,
        'member_id'   => $memberId,
        'member_name' => $member['first_name'] . ' ' . $member['last_name'],
        'checkin_at'  => date('Y-m-d H:i:s'),
        'message'     => '¡Check-in registrado!',
    ], 201);
}

function isValidDate(string $date): bool {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}
