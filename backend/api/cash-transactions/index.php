<?php
// backend/api/cash-transactions/index.php - GET list

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('GET');

$payload = requireAuth();
$companyId = (int)$payload['company_id'];
$db = getDB();

$month = (int)($_GET['month'] ?? date('m'));
$year = (int)($_GET['year'] ?? date('Y'));
$type = strtolower(trim((string)($_GET['type'] ?? '')));
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(5, (int)($_GET['limit'] ?? 25)));
$offset = ($page - 1) * $limit;

$where = ['ct.company_id = :company_id'];
$params = [':company_id' => $companyId];

if ($month >= 1 && $month <= 12 && $year >= 2000) {
    $periodStart = sprintf('%04d-%02d-01', $year, $month);
    $periodEnd = (new DateTimeImmutable($periodStart))->modify('first day of next month')->format('Y-m-d');
    $where[] = 'ct.transaction_at >= :period_start AND ct.transaction_at < :period_end';
    $params[':period_start'] = $periodStart;
    $params[':period_end'] = $periodEnd;
}

if (in_array($type, ['income', 'expense'], true)) {
    $where[] = 'ct.type = :type';
    $params[':type'] = $type;
}

$whereClause = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM cash_transactions ct WHERE {$whereClause}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sumStmt = $db->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN ct.type = 'income' THEN ct.amount ELSE 0 END), 0) AS income_total,
        COALESCE(SUM(CASE WHEN ct.type = 'expense' THEN ct.amount ELSE 0 END), 0) AS expense_total
    FROM cash_transactions ct
    WHERE {$whereClause}
");
$sumStmt->execute($params);
$totals = $sumStmt->fetch() ?: ['income_total' => 0, 'expense_total' => 0];

$stmt = $db->prepare("
    SELECT
        ct.id, ct.type, ct.category, ct.description, ct.amount, ct.transaction_at,
        ct.payment_id, p.legacy_method_name, u.name AS registered_by_name
    FROM cash_transactions ct
    LEFT JOIN payments p ON p.id = ct.payment_id AND p.company_id = ct.company_id
    LEFT JOIN users u ON u.id = ct.registered_by AND u.company_id = ct.company_id
    WHERE {$whereClause}
    ORDER BY ct.transaction_at DESC, ct.id DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

jsonResponse([
    'data' => $stmt->fetchAll(),
    'totals' => [
        'income' => (float)$totals['income_total'],
        'expense' => (float)$totals['expense_total'],
        'net' => (float)$totals['income_total'] - (float)$totals['expense_total'],
    ],
    'meta' => [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pages' => (int)ceil($total / $limit),
    ],
]);
