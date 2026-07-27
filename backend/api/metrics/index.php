<?php
// backend/api/metrics/index.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('GET');

$payload   = requireAuth();
$companyId = (int)$payload['company_id'];
$db        = getDB();

$today = date('Y-m-d');
$tomorrow = (new DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d');
$monthStart = date('Y-m-01');
$nextMonthStart = (new DateTimeImmutable($monthStart))->modify('first day of next month')->format('Y-m-d');
$weekStart = (new DateTimeImmutable($today))->modify('monday this week')->format('Y-m-d');
$last7Start = (new DateTimeImmutable($today))->modify('-6 days')->format('Y-m-d');
$last6MonthStart = (new DateTimeImmutable($monthStart))->modify('-5 months')->format('Y-m-d');

$activeStmt = $db->prepare('SELECT COUNT(*) FROM members WHERE company_id = ? AND status = "active"');
$activeStmt->execute([$companyId]);
$totalActive = (int)$activeStmt->fetchColumn();

$inactiveStmt = $db->prepare('SELECT COUNT(*) FROM members WHERE company_id = ? AND status = "inactive"');
$inactiveStmt->execute([$companyId]);
$totalInactive = (int)$inactiveStmt->fetchColumn();

$quotaOkStmt = $db->prepare("
    SELECT COUNT(*)
    FROM members
    WHERE company_id = ?
      AND status = 'active'
      AND membership_valid_until IS NOT NULL
      AND membership_valid_until >= ?
");
$quotaOkStmt->execute([$companyId, $today]);
$quotaOk = (int)$quotaOkStmt->fetchColumn();

$inDebt = $totalActive - $quotaOk;

$newThisMonthStmt = $db->prepare("
    SELECT COUNT(*)
    FROM members
    WHERE company_id = ?
      AND created_at >= ?
      AND created_at < ?
");
$newThisMonthStmt->execute([$companyId, $monthStart, $nextMonthStart]);
$newThisMonth = (int)$newThisMonthStmt->fetchColumn();

$checkinsHoyStmt = $db->prepare("
    SELECT COUNT(*)
    FROM checkins
    WHERE company_id = ?
      AND access_allowed = 1
      AND checkin_at >= ?
      AND checkin_at < ?
");
$checkinsHoyStmt->execute([$companyId, $today, $tomorrow]);
$checkinsToday = (int)$checkinsHoyStmt->fetchColumn();

$checkinsWeekStmt = $db->prepare("
    SELECT COUNT(*)
    FROM checkins
    WHERE company_id = ?
      AND access_allowed = 1
      AND checkin_at >= ?
");
$checkinsWeekStmt->execute([$companyId, $weekStart]);
$checkinsWeek = (int)$checkinsWeekStmt->fetchColumn();

$revenueMonthStmt = $db->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM payments
    WHERE company_id = ?
      AND payment_date >= ?
      AND payment_date < ?
");
$revenueMonthStmt->execute([$companyId, $monthStart, $nextMonthStart]);
$revenueMonth = (float)$revenueMonthStmt->fetchColumn();

$paymentsCountStmt = $db->prepare("
    SELECT COUNT(*)
    FROM payments
    WHERE company_id = ?
      AND payment_date >= ?
      AND payment_date < ?
");
$paymentsCountStmt->execute([$companyId, $monthStart, $nextMonthStart]);
$paymentsCount = (int)$paymentsCountStmt->fetchColumn();

$last7Stmt = $db->prepare("
    SELECT DATE(checkin_at) AS day, COUNT(*) AS count
    FROM checkins
    WHERE company_id = ?
      AND access_allowed = 1
      AND checkin_at >= ?
    GROUP BY DATE(checkin_at)
    ORDER BY day ASC
");
$last7Stmt->execute([$companyId, $last7Start]);
$last7Days = $last7Stmt->fetchAll();

$checkinsTrend = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $checkinsTrend[$day] = 0;
}
foreach ($last7Days as $row) {
    $checkinsTrend[$row['day']] = (int)$row['count'];
}
$checkinsTrendArray = array_map(
    fn($day, $count) => ['date' => $day, 'count' => $count],
    array_keys($checkinsTrend),
    array_values($checkinsTrend)
);

$last6MonthsStmt = $db->prepare("
    SELECT
        DATE_FORMAT(payment_date, '%Y-%m') AS month,
        COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE company_id = ?
      AND payment_date >= ?
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month ASC
");
$last6MonthsStmt->execute([$companyId, $last6MonthStart]);
$revenueRows = $last6MonthsStmt->fetchAll();

$revenueByMonth = [];
foreach ($revenueRows as $row) {
    $revenueByMonth[$row['month']] = (float)$row['total'];
}

$revenueChart = [];
for ($i = 5; $i >= 0; $i--) {
    $month = (new DateTimeImmutable($monthStart))->modify("-{$i} months")->format('Y-m');
    $revenueChart[] = ['month' => $month, 'total' => $revenueByMonth[$month] ?? 0];
}

jsonResponse([
    'members' => [
        'total_active'   => $totalActive,
        'total_inactive' => $totalInactive,
        'quota_ok'       => $quotaOk,
        'in_debt'        => max(0, $inDebt),
        'new_this_month' => $newThisMonth,
    ],
    'checkins' => [
        'today'       => $checkinsToday,
        'this_week'   => $checkinsWeek,
        'trend_7days' => $checkinsTrendArray,
    ],
    'payments' => [
        'revenue_this_month' => $revenueMonth,
        'count_this_month'   => $paymentsCount,
        'revenue_chart'      => $revenueChart,
    ],
]);
