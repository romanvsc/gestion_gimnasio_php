<?php
// backend/api/payments/index.php - GET list + POST create

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('GET', 'POST');

$payload   = requireAuth();
$companyId = (int)$payload['company_id'];
$db        = getDB();

// ── GET: listar pagos ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $month    = (int)($_GET['month'] ?? date('m'));
    $year     = (int)($_GET['year'] ?? date('Y'));
    $memberId = (int)($_GET['member_id'] ?? 0);
    $page     = max(1, (int)($_GET['page']  ?? 1));
    $limit    = min(100, max(5, (int)($_GET['limit'] ?? 10)));
    $offset   = ($page - 1) * $limit;

    $where  = ['p.company_id = :company_id'];
    $params = [':company_id' => $companyId];

    if ($month >= 1 && $month <= 12 && $year >= 2000) {
        $periodStart = sprintf('%04d-%02d-01', $year, $month);
        $periodEnd = (new DateTimeImmutable($periodStart))->modify('first day of next month')->format('Y-m-d');
        $where[] = 'p.payment_date >= :period_start AND p.payment_date < :period_end';
        $params[':period_start'] = $periodStart;
        $params[':period_end'] = $periodEnd;
    }
    if ($memberId > 0) {
        $where[]               = 'p.member_id = :member_id';
        $params[':member_id']  = $memberId;
    }

    $whereClause = implode(' AND ', $where);

    $countStmt = $db->prepare("SELECT COUNT(*) FROM payments p WHERE {$whereClause}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sumStmt = $db->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE {$whereClause}");
    $sumStmt->execute($params);
    $totalAmount = (float)$sumStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT
            p.id, p.amount, p.concept, p.payment_date, p.period_start, p.period_end,
            p.method, p.legacy_method_name, p.notes, p.created_at,
            p.member_id,
            CONCAT(m.first_name, ' ', m.last_name) AS member_name,
            pp.name AS plan_name,
            u.name AS registered_by_name
        FROM payments p
        JOIN members m ON m.id = p.member_id AND m.company_id = p.company_id
        LEFT JOIN payment_plans pp ON pp.id = m.plan_id AND pp.company_id = m.company_id
        LEFT JOIN users u ON u.id = p.registered_by AND u.company_id = p.company_id
        WHERE {$whereClause}
        ORDER BY p.payment_date DESC, p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    jsonResponse([
        'data'         => $stmt->fetchAll(),
        'total_amount' => $totalAmount,
        'meta'         => [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
        ],
    ]);
}

// ── POST: registrar pago ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body     = getJsonBody();
    $memberId = (int)($body['member_id'] ?? 0);
    $amount   = (float)($body['amount']  ?? 0);

    if ($memberId === 0 || $amount <= 0) {
        errorResponse('member_id y amount son requeridos', 422);
    }

    // Verificar miembro de la empresa
    $memberStmt = $db->prepare('
        SELECT
            m.id, m.first_name, m.last_name, m.membership_valid_until,
            pp.name AS plan_name, pp.price AS plan_price, pp.duration_days AS plan_duration_days
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

    $method = in_array($body['method'] ?? '', ['cash','transfer','card','other'])
        ? $body['method'] : 'cash';

    $paymentDate = (string)($body['payment_date'] ?? date('Y-m-d'));
    if (!isValidDate($paymentDate)) {
        errorResponse('Fecha de pago invalida', 422);
    }
    if (isset($body['period_start']) && $body['period_start'] !== '' && !isValidDate((string)$body['period_start'])) {
        errorResponse('Fecha de inicio de periodo invalida', 422);
    }
    if (isset($body['period_end']) && $body['period_end'] !== '' && !isValidDate((string)$body['period_end'])) {
        errorResponse('Fecha de fin de periodo invalida', 422);
    }
    $durationDays = max(1, (int)($member['plan_duration_days'] ?? 30));
    $currentValidUntil = $member['membership_valid_until'] ?? null;
    $baseDate = new DateTimeImmutable($paymentDate);

    if ($currentValidUntil !== null && $currentValidUntil >= $paymentDate) {
        $baseDate = new DateTimeImmutable($currentValidUntil);
    }

    $periodStart = $baseDate->format('Y-m-d');
    $newValidUntil = $baseDate->modify('+' . ($durationDays - 1) . ' days')->format('Y-m-d');

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            INSERT INTO payments (company_id, member_id, amount, concept, payment_date, period_start, period_end, method, legacy_method_name, notes, registered_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)
        ");
        $stmt->execute([
            $companyId,
            $memberId,
            $amount,
            $body['concept']      ?? ($member['plan_name'] ? 'Cuota ' . $member['plan_name'] : 'Cuota mensual'),
            $paymentDate,
            $body['period_start'] ?? $periodStart,
            $body['period_end'] ?? $newValidUntil,
            $method,
            $body['notes']        ?? null,
            $payload['user_id'],
        ]);

        $newId = (int)$db->lastInsertId();

        $db->prepare('UPDATE members SET membership_valid_until = ? WHERE id = ? AND company_id = ?')
           ->execute([$newValidUntil, $memberId, $companyId]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        errorResponse('No se pudo registrar el pago', 500);
    }

    $payment = $db->prepare('SELECT * FROM payments WHERE id = ? AND company_id = ?');
    $payment->execute([$newId, $companyId]);
    $row = $payment->fetch();
    $row['member_name'] = $member['first_name'] . ' ' . $member['last_name'];
    $row['plan_name'] = $member['plan_name'];
    $row['membership_valid_until'] = $newValidUntil;
    $row['period_start'] = $body['period_start'] ?? $periodStart;
    $row['period_end'] = $body['period_end'] ?? $newValidUntil;

    jsonResponse($row, 201);
}

function isValidDate(string $date): bool {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}
