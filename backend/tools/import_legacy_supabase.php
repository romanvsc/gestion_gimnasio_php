<?php
// Importa los CSV de gym_viejo_supabase al modelo actual.
//
// Uso:
//   php backend/tools/import_legacy_supabase.php --dry-run --csv-dir=gym_viejo_supabase
//   php backend/tools/import_legacy_supabase.php --execute --csv-dir=gym_viejo_supabase --company-name="Ghost Gym"

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

const EXPECTED_COUNTS = [
    'companies' => 1,
    'users' => 6,
    'plans' => 2,
    'members' => 277,
    'payments' => 435,
    'checkins' => 510,
    'payment_methods' => 5,
    'financial_concepts' => 11,
    'cash_transactions' => 437,
];

const EXPECTED_PAYMENT_SUM = 7419000.00;
const EXPECTED_TRANSACTION_SUM = 7449000.00;

$options = getopt('', ['dry-run', 'execute', 'csv-dir:', 'company-name:']);
$execute = array_key_exists('execute', $options);
$dryRun = array_key_exists('dry-run', $options) || !$execute;

if ($execute && array_key_exists('dry-run', $options)) {
    fail('Usa --dry-run o --execute, no ambos.');
}

$csvDir = (string)($options['csv-dir'] ?? dirname(__DIR__, 2) . '/gym_viejo_supabase');
$companyName = trim((string)($options['company-name'] ?? 'Ghost Gym'));

if ($companyName === '') {
    fail('El nombre de empresa no puede estar vacio.');
}

$csvDir = realpath($csvDir) ?: $csvDir;
if (!is_dir($csvDir)) {
    fail("No existe el directorio CSV: {$csvDir}");
}

$files = [
    'attendance' => 'attendance_rows.csv',
    'concepts' => 'concepts_rows.csv',
    'config' => 'config_rows.csv',
    'members' => 'members_rows.csv',
    'payments' => 'payments_rows.csv',
    'payment_methods' => 'payment_methods_rows.csv',
    'plans' => 'plans_rows.csv',
    'staff' => 'staff_rows.csv',
    'transactions' => 'transactions_rows.csv',
    'member_status' => 'v_socios_estado_rows.csv',
];

$data = [];
foreach ($files as $key => $file) {
    $path = $csvDir . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path)) {
        fail("Falta el CSV requerido: {$path}");
    }
    $data[$key] = readCsv($path);
}

$report = buildValidationReport($data);
printReport($report, $dryRun ? 'dry-run' : 'execute', $csvDir, $companyName);

if ($report['has_errors']) {
    fail('La validacion encontro errores. No se ejecuta la importacion.');
}

if ($dryRun) {
    echo PHP_EOL . 'Dry run finalizado. No se escribieron datos.' . PHP_EOL;
    exit(0);
}

loadEnv(dirname(__DIR__) . '/.env');
loadEnv(dirname(__DIR__, 2) . '/.env');

$db = getDB();
$db->beginTransaction();

try {
    $companyId = upsertCompany($db, $data['config'][0] ?? [], $companyName);
    $importRunId = insertImportRun($db, $companyId, $csvDir, $report);

    $userMap = upsertUsers($db, $companyId, $data['staff']);
    $planMap = upsertPlans($db, $companyId, $data['plans']);
    upsertPaymentMethods($db, $companyId, $data['payment_methods']);
    upsertFinancialConcepts($db, $companyId, $data['concepts']);

    $membershipValidity = calculateMembershipValidity($data['payments']);
    $memberMap = upsertMembers($db, $companyId, $data['members'], $membershipValidity, $planMap);
    $paymentUserMap = mapPaymentCreatedBy($data['transactions']);
    $paymentMap = upsertPayments($db, $companyId, $data['payments'], $memberMap, $planMap, $userMap, $paymentUserMap);
    upsertCheckins($db, $companyId, $data['attendance'], $memberMap);
    upsertCashTransactions($db, $companyId, $data['transactions'], $paymentMap, $userMap);

    completeImportRun($db, $importRunId, 'completed', $report, null);
    $db->commit();

    writeCredentialReport($userMap['credentials'], $companyName);
    echo PHP_EOL . "Importacion completada para company_id={$companyId}." . PHP_EOL;
} catch (Throwable $e) {
    $db->rollBack();
    fail('Error durante la importacion: ' . $e->getMessage());
}

function readCsv(string $path): array {
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        fail("No se pudo abrir {$path}");
    }

    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        return [];
    }

    $rows = [];
    while (($values = fgetcsv($handle)) !== false) {
        if ($values === [null] || $values === false) {
            continue;
        }
        $row = [];
        foreach ($headers as $index => $header) {
            $row[$header] = $values[$index] ?? null;
        }
        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
}

function buildValidationReport(array $data): array {
    $memberIds = indexBy($data['members'], 'id');
    $paymentIds = indexBy($data['payments'], 'id');
    $staffIds = indexBy($data['staff'], 'id');

    $paymentsMissingMember = array_values(array_filter($data['payments'], fn($row) => !isset($memberIds[$row['member_id'] ?? ''])));
    $attendanceMissingMember = array_values(array_filter($data['attendance'], fn($row) => !isset($memberIds[$row['member_id'] ?? ''])));
    $linkedTransactions = array_values(array_filter($data['transactions'], fn($row) => trim((string)($row['payment_id'] ?? '')) !== ''));
    $transactionsMissingPayment = array_values(array_filter($linkedTransactions, fn($row) => !isset($paymentIds[$row['payment_id'] ?? ''])));
    $transactionsMissingStaff = array_values(array_filter($data['transactions'], function ($row) use ($staffIds) {
        $createdBy = trim((string)($row['created_by'] ?? ''));
        return $createdBy !== '' && !isset($staffIds[$createdBy]);
    }));

    $paymentSum = sumColumn($data['payments'], 'monto');
    $transactionSum = sumColumn($data['transactions'], 'monto');
    $quotaStates = groupCount($data['member_status'], 'estado_cuota');

    $counts = [
        'companies' => count($data['config']),
        'users' => count($data['staff']),
        'plans' => count($data['plans']),
        'members' => count($data['members']),
        'payments' => count($data['payments']),
        'checkins' => count($data['attendance']),
        'payment_methods' => count($data['payment_methods']),
        'financial_concepts' => count($data['concepts']),
        'cash_transactions' => count($data['transactions']),
    ];

    $errors = [];
    foreach (EXPECTED_COUNTS as $key => $expected) {
        if (($counts[$key] ?? 0) !== $expected) {
            $errors[] = "Conteo inesperado para {$key}: esperado {$expected}, actual " . ($counts[$key] ?? 0);
        }
    }
    if (abs($paymentSum - EXPECTED_PAYMENT_SUM) > 0.01) {
        $errors[] = "Total de pagos inesperado: esperado " . EXPECTED_PAYMENT_SUM . ", actual {$paymentSum}";
    }
    if (abs($transactionSum - EXPECTED_TRANSACTION_SUM) > 0.01) {
        $errors[] = "Total de transacciones inesperado: esperado " . EXPECTED_TRANSACTION_SUM . ", actual {$transactionSum}";
    }
    if (count($paymentsMissingMember) > 0) $errors[] = 'Hay pagos sin socio.';
    if (count($attendanceMissingMember) > 0) $errors[] = 'Hay asistencias sin socio.';
    if (count($transactionsMissingPayment) > 0) $errors[] = 'Hay transacciones ligadas sin pago.';
    if (count($transactionsMissingStaff) > 0) $errors[] = 'Hay transacciones con staff inexistente.';

    if (($quotaStates['activo'] ?? 0) !== 61 || ($quotaStates['vencido'] ?? 0) !== 212 || ($quotaStates['sin_pago'] ?? 0) !== 4) {
        $errors[] = 'El estado de cuota no coincide con el snapshot esperado.';
    }

    return [
        'counts' => $counts,
        'payment_sum' => $paymentSum,
        'transaction_sum' => $transactionSum,
        'unlinked_transaction_count' => count(array_filter($data['transactions'], fn($row) => trim((string)($row['payment_id'] ?? '')) === '')),
        'quota_states' => $quotaStates,
        'integrity' => [
            'payments_missing_member' => count($paymentsMissingMember),
            'attendance_missing_member' => count($attendanceMissingMember),
            'transactions_missing_payment' => count($transactionsMissingPayment),
            'transactions_missing_staff' => count($transactionsMissingStaff),
        ],
        'errors' => $errors,
        'has_errors' => count($errors) > 0,
    ];
}

function upsertCompany(PDO $db, array $config, string $companyName): int {
    $email = nullable($config['email_contacto'] ?? null) ?? 'legacy-ghost-gym@example.invalid';
    $stmt = $db->prepare('SELECT id FROM companies WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $existingId = $stmt->fetchColumn();

    $params = [
        ':name' => $companyName,
        ':email' => $email,
        ':phone' => nullable($config['whatsapp'] ?? null),
        ':address' => nullable($config['direccion'] ?? null),
        ':logo_url' => nullable($config['logo_url'] ?? null),
        ':opening_hours' => nullable($config['horarios_apertura'] ?? null),
    ];

    if ($existingId) {
        $params[':id'] = (int)$existingId;
        $db->prepare('
            UPDATE companies
            SET name = :name, email = :email, phone = :phone, address = :address,
                logo_url = :logo_url, opening_hours = :opening_hours, status = "active"
            WHERE id = :id
        ')->execute($params);
        return (int)$existingId;
    }

    $db->prepare('
        INSERT INTO companies (name, email, phone, address, logo_url, opening_hours, status)
        VALUES (:name, :email, :phone, :address, :logo_url, :opening_hours, "active")
    ')->execute($params);

    return (int)$db->lastInsertId();
}

function upsertUsers(PDO $db, int $companyId, array $staffRows): array {
    $map = [];
    $credentials = [];

    foreach ($staffRows as $row) {
        $legacyId = trim((string)$row['id']);
        $email = nullable($row['email'] ?? null) ?? "{$legacyId}@legacy.local";
        $name = nullable($row['usuario'] ?? null) ?? $email;
        $role = strtolower((string)($row['rol'] ?? '')) === 'admin' ? 'admin' : 'staff';
        $status = parseBool($row['activo'] ?? null) ? 'active' : 'inactive';

        $stmt = $db->prepare('SELECT id FROM users WHERE company_id = ? AND legacy_staff_id = ? LIMIT 1');
        $stmt->execute([$companyId, $legacyId]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            $stmt = $db->prepare('SELECT id FROM users WHERE company_id = ? AND email = ? LIMIT 1');
            $stmt->execute([$companyId, $email]);
            $userId = $stmt->fetchColumn();
        }

        if ($userId) {
            $db->prepare('
                UPDATE users
                SET legacy_staff_id = ?, name = ?, email = ?, role = ?, status = ?
                WHERE id = ? AND company_id = ?
            ')->execute([$legacyId, $name, $email, $role, $status, (int)$userId, $companyId]);
            $map[$legacyId] = (int)$userId;
            continue;
        }

        $temporaryPassword = generateTemporaryPassword();
        $hash = password_hash($temporaryPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare('
            INSERT INTO users (company_id, legacy_staff_id, name, email, password_hash, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ')->execute([$companyId, $legacyId, $name, $email, $hash, $role, $status, normalizeDateTime($row['created_at'] ?? null) ?? date('Y-m-d H:i:s')]);

        $userId = (int)$db->lastInsertId();
        $map[$legacyId] = $userId;
        $credentials[] = ['name' => $name, 'email' => $email, 'role' => $role, 'password' => $temporaryPassword];
    }

    return ['map' => $map, 'credentials' => $credentials];
}

function upsertPlans(PDO $db, int $companyId, array $planRows): array {
    $map = [];
    foreach ($planRows as $row) {
        $legacyId = (int)$row['id'];
        $stmt = $db->prepare('SELECT id FROM payment_plans WHERE company_id = ? AND legacy_plan_id = ? LIMIT 1');
        $stmt->execute([$companyId, $legacyId]);
        $planId = $stmt->fetchColumn();

        $params = [
            ':company_id' => $companyId,
            ':legacy_plan_id' => $legacyId,
            ':name' => trim((string)$row['nombre']),
            ':price' => decimal($row['precio'] ?? 0),
            ':club_member_price' => nullableDecimal($row['precio_socio'] ?? null),
            ':duration_days' => max(1, (int)$row['dias_duracion']),
            ':status' => parseBool($row['activo'] ?? null) ? 'active' : 'inactive',
        ];

        if ($planId) {
            $params[':id'] = (int)$planId;
            $db->prepare('
                UPDATE payment_plans
                SET name = :name, price = :price, club_member_price = :club_member_price,
                    duration_days = :duration_days, status = :status
                WHERE id = :id AND company_id = :company_id
            ')->execute(onlyParams($params, [
                ':name', ':price', ':club_member_price', ':duration_days', ':status', ':id', ':company_id',
            ]));
            $map[$legacyId] = (int)$planId;
            continue;
        }

        $db->prepare('
            INSERT INTO payment_plans (company_id, legacy_plan_id, name, price, club_member_price, duration_days, description, status)
            VALUES (:company_id, :legacy_plan_id, :name, :price, :club_member_price, :duration_days, NULL, :status)
        ')->execute($params);
        $map[$legacyId] = (int)$db->lastInsertId();
    }

    return $map;
}

function upsertPaymentMethods(PDO $db, int $companyId, array $rows): void {
    foreach ($rows as $row) {
        $legacyId = (int)$row['id'];
        $status = parseBool($row['activo'] ?? null) ? 'active' : 'inactive';
        $stmt = $db->prepare('SELECT id FROM payment_methods WHERE company_id = ? AND legacy_method_id = ? LIMIT 1');
        $stmt->execute([$companyId, $legacyId]);
        if ($stmt->fetchColumn()) {
            $db->prepare('UPDATE payment_methods SET name = ?, status = ? WHERE company_id = ? AND legacy_method_id = ?')
               ->execute([trim((string)$row['nombre']), $status, $companyId, $legacyId]);
            continue;
        }
        $db->prepare('INSERT INTO payment_methods (company_id, legacy_method_id, name, status, created_at) VALUES (?, ?, ?, ?, ?)')
           ->execute([$companyId, $legacyId, trim((string)$row['nombre']), $status, normalizeDateTime($row['created_at'] ?? null) ?? date('Y-m-d H:i:s')]);
    }
}

function upsertFinancialConcepts(PDO $db, int $companyId, array $rows): void {
    foreach ($rows as $row) {
        $legacyId = (int)$row['id'];
        $type = mapConceptType($row['tipo'] ?? null);
        $status = parseBool($row['activo'] ?? null) ? 'active' : 'inactive';
        $stmt = $db->prepare('SELECT id FROM financial_concepts WHERE company_id = ? AND legacy_concept_id = ? LIMIT 1');
        $stmt->execute([$companyId, $legacyId]);
        if ($stmt->fetchColumn()) {
            $db->prepare('UPDATE financial_concepts SET name = ?, type = ?, status = ? WHERE company_id = ? AND legacy_concept_id = ?')
               ->execute([trim((string)$row['nombre']), $type, $status, $companyId, $legacyId]);
            continue;
        }
        $db->prepare('INSERT INTO financial_concepts (company_id, legacy_concept_id, name, type, status, created_at) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$companyId, $legacyId, trim((string)$row['nombre']), $type, $status, normalizeDateTime($row['created_at'] ?? null) ?? date('Y-m-d H:i:s')]);
    }
}

function upsertMembers(PDO $db, int $companyId, array $rows, array $membershipValidity, array $planMap): array {
    $map = [];
    foreach ($rows as $row) {
        $legacyId = trim((string)$row['id']);
        $planId = $planMap[(int)$row['plan_id']] ?? null;
        $status = parseBool($row['activo'] ?? null) ? 'active' : 'inactive';

        $stmt = $db->prepare('SELECT id FROM members WHERE company_id = ? AND legacy_member_id = ? LIMIT 1');
        $stmt->execute([$companyId, $legacyId]);
        $memberId = $stmt->fetchColumn();

        $params = [
            ':company_id' => $companyId,
            ':legacy_member_id' => $legacyId,
            ':first_name' => trim((string)($row['nombre'] ?? '')),
            ':last_name' => trim((string)($row['apellido'] ?? '')),
            ':email' => nullable($row['email'] ?? null),
            ':phone' => nullable($row['telefono'] ?? null),
            ':dni' => nullable($row['dni'] ?? null),
            ':birthdate' => normalizeDate($row['fecha_nacimiento'] ?? null),
            ':photo_url' => nullable($row['foto_url'] ?? null),
            ':medical_certificate_valid_until' => normalizeDate($row['apto_fisico'] ?? null),
            ':weight_kg' => nullableDecimal($row['peso'] ?? null),
            ':height_cm' => nullableDecimal($row['altura'] ?? null),
            ':joined_at' => normalizeDate($row['fecha_alta'] ?? null),
            ':is_club_member' => parseBool($row['es_socio_club'] ?? null) ? 1 : 0,
            ':plan_id' => $planId,
            ':membership_valid_until' => $membershipValidity[$legacyId] ?? null,
            ':notes' => nullable($row['notas'] ?? null),
            ':status' => $status,
            ':created_at' => normalizeDateTime($row['created_at'] ?? null) ?? date('Y-m-d H:i:s'),
        ];

        if ($params[':first_name'] === '') $params[':first_name'] = 'Sin nombre';
        if ($params[':last_name'] === '') $params[':last_name'] = 'Sin apellido';

        if ($memberId) {
            $params[':id'] = (int)$memberId;
            $db->prepare('
                UPDATE members
                SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone,
                    dni = :dni, birthdate = :birthdate, photo_url = :photo_url,
                    medical_certificate_valid_until = :medical_certificate_valid_until,
                    weight_kg = :weight_kg, height_cm = :height_cm, joined_at = :joined_at,
                    is_club_member = :is_club_member, plan_id = :plan_id,
                    membership_valid_until = :membership_valid_until, notes = :notes, status = :status
                WHERE id = :id AND company_id = :company_id
            ')->execute(onlyParams($params, [
                ':first_name', ':last_name', ':email', ':phone', ':dni', ':birthdate', ':photo_url',
                ':medical_certificate_valid_until', ':weight_kg', ':height_cm', ':joined_at',
                ':is_club_member', ':plan_id', ':membership_valid_until', ':notes', ':status',
                ':id', ':company_id',
            ]));
            $map[$legacyId] = (int)$memberId;
            continue;
        }

        $db->prepare('
            INSERT INTO members (
                company_id, legacy_member_id, first_name, last_name, email, phone, dni, birthdate,
                photo_url, medical_certificate_valid_until, weight_kg, height_cm, joined_at,
                is_club_member, plan_id, membership_valid_until, notes, status, created_at
            ) VALUES (
                :company_id, :legacy_member_id, :first_name, :last_name, :email, :phone, :dni, :birthdate,
                :photo_url, :medical_certificate_valid_until, :weight_kg, :height_cm, :joined_at,
                :is_club_member, :plan_id, :membership_valid_until, :notes, :status, :created_at
            )
        ')->execute($params);
        $map[$legacyId] = (int)$db->lastInsertId();
    }

    return $map;
}

function upsertPayments(PDO $db, int $companyId, array $rows, array $memberMap, array $planMap, array $userMapResult, array $paymentUserMap): array {
    $map = [];
    $userMap = $userMapResult['map'];

    foreach ($rows as $row) {
        $legacyId = trim((string)$row['id']);
        $memberId = $memberMap[$row['member_id']] ?? null;
        if (!$memberId) {
            throw new RuntimeException("Pago {$legacyId} sin socio mapeado.");
        }

        $legacyPlanId = (int)$row['plan_id'];
        $planName = findPlanName($legacyPlanId);
        $registeredByLegacy = $paymentUserMap[$legacyId] ?? null;
        $registeredBy = $registeredByLegacy ? ($userMap[$registeredByLegacy] ?? null) : null;
        $paymentDate = normalizeDate($row['created_at'] ?? null) ?? normalizeDate($row['fecha_inicio'] ?? null) ?? date('Y-m-d');

        $stmt = $db->prepare('SELECT id FROM payments WHERE company_id = ? AND legacy_payment_id = ? LIMIT 1');
        $stmt->execute([$companyId, $legacyId]);
        $paymentId = $stmt->fetchColumn();

        $params = [
            ':company_id' => $companyId,
            ':legacy_payment_id' => $legacyId,
            ':member_id' => $memberId,
            ':amount' => decimal($row['monto'] ?? 0),
            ':concept' => $planName ? 'Cuota ' . $planName : 'Cuota mensual',
            ':payment_date' => $paymentDate,
            ':period_start' => normalizeDate($row['fecha_inicio'] ?? null),
            ':period_end' => normalizeDate($row['fecha_fin'] ?? null),
            ':method' => mapPaymentMethod($row['metodo_pago'] ?? null),
            ':legacy_method_name' => nullable($row['metodo_pago'] ?? null),
            ':registered_by' => $registeredBy,
            ':created_at' => normalizeDateTime($row['created_at'] ?? null) ?? date('Y-m-d H:i:s'),
        ];

        if ($paymentId) {
            $params[':id'] = (int)$paymentId;
            $db->prepare('
                UPDATE payments
                SET member_id = :member_id, amount = :amount, concept = :concept, payment_date = :payment_date,
                    period_start = :period_start, period_end = :period_end, method = :method,
                    legacy_method_name = :legacy_method_name, registered_by = :registered_by
                WHERE id = :id AND company_id = :company_id
            ')->execute(onlyParams($params, [
                ':member_id', ':amount', ':concept', ':payment_date', ':period_start', ':period_end',
                ':method', ':legacy_method_name', ':registered_by', ':id', ':company_id',
            ]));
            $map[$legacyId] = (int)$paymentId;
            continue;
        }

        $db->prepare('
            INSERT INTO payments (
                company_id, legacy_payment_id, member_id, amount, concept, payment_date,
                period_start, period_end, method, legacy_method_name, registered_by, created_at
            ) VALUES (
                :company_id, :legacy_payment_id, :member_id, :amount, :concept, :payment_date,
                :period_start, :period_end, :method, :legacy_method_name, :registered_by, :created_at
            )
        ')->execute($params);
        $map[$legacyId] = (int)$db->lastInsertId();
    }

    return $map;
}

function upsertCheckins(PDO $db, int $companyId, array $rows, array $memberMap): void {
    foreach ($rows as $row) {
        $legacyId = trim((string)$row['id']);
        $memberId = $memberMap[$row['member_id']] ?? null;
        if (!$memberId) {
            throw new RuntimeException("Asistencia {$legacyId} sin socio mapeado.");
        }

        $stmt = $db->prepare('SELECT id FROM checkins WHERE company_id = ? AND legacy_attendance_id = ? LIMIT 1');
        $stmt->execute([$companyId, $legacyId]);
        $checkinId = $stmt->fetchColumn();

        $params = [
            ':company_id' => $companyId,
            ':legacy_attendance_id' => $legacyId,
            ':member_id' => $memberId,
            ':checkin_at' => normalizeDateTime($row['created_at'] ?? null) ?? date('Y-m-d H:i:s'),
            ':access_allowed' => parseBool($row['acceso_permitido'] ?? null) ? 1 : 0,
        ];

        if ($checkinId) {
            $params[':id'] = (int)$checkinId;
            $db->prepare('
                UPDATE checkins
                SET member_id = :member_id, checkin_at = :checkin_at, access_allowed = :access_allowed
                WHERE id = :id AND company_id = :company_id
            ')->execute(onlyParams($params, [
                ':member_id', ':checkin_at', ':access_allowed', ':id', ':company_id',
            ]));
            continue;
        }

        $db->prepare('
            INSERT INTO checkins (company_id, legacy_attendance_id, member_id, checkin_at, access_allowed)
            VALUES (:company_id, :legacy_attendance_id, :member_id, :checkin_at, :access_allowed)
        ')->execute($params);
    }
}

function upsertCashTransactions(PDO $db, int $companyId, array $rows, array $paymentMap, array $userMapResult): void {
    $userMap = $userMapResult['map'];

    foreach ($rows as $row) {
        $legacyId = trim((string)$row['id']);
        $paymentLegacyId = trim((string)($row['payment_id'] ?? ''));
        $paymentId = $paymentLegacyId !== '' ? ($paymentMap[$paymentLegacyId] ?? null) : null;
        $createdByLegacy = trim((string)($row['created_by'] ?? ''));
        $registeredBy = $createdByLegacy !== '' ? ($userMap[$createdByLegacy] ?? null) : null;

        $stmt = $db->prepare('SELECT id FROM cash_transactions WHERE company_id = ? AND legacy_transaction_id = ? LIMIT 1');
        $stmt->execute([$companyId, $legacyId]);
        $transactionId = $stmt->fetchColumn();

        $params = [
            ':company_id' => $companyId,
            ':legacy_transaction_id' => $legacyId,
            ':type' => mapTransactionType($row['tipo'] ?? null),
            ':category' => trim((string)($row['categoria'] ?? 'Otros')),
            ':description' => nullable($row['descripcion'] ?? null),
            ':amount' => decimal($row['monto'] ?? 0),
            ':payment_id' => $paymentId,
            ':registered_by' => $registeredBy,
            ':transaction_at' => normalizeDateTime($row['created_at'] ?? null) ?? date('Y-m-d H:i:s'),
        ];

        if ($transactionId) {
            $params[':id'] = (int)$transactionId;
            $db->prepare('
                UPDATE cash_transactions
                SET type = :type, category = :category, description = :description, amount = :amount,
                    payment_id = :payment_id, registered_by = :registered_by, transaction_at = :transaction_at
                WHERE id = :id AND company_id = :company_id
            ')->execute(onlyParams($params, [
                ':type', ':category', ':description', ':amount', ':payment_id', ':registered_by',
                ':transaction_at', ':id', ':company_id',
            ]));
            continue;
        }

        $db->prepare('
            INSERT INTO cash_transactions (
                company_id, legacy_transaction_id, type, category, description,
                amount, payment_id, registered_by, transaction_at
            ) VALUES (
                :company_id, :legacy_transaction_id, :type, :category, :description,
                :amount, :payment_id, :registered_by, :transaction_at
            )
        ')->execute($params);
    }
}

function insertImportRun(PDO $db, int $companyId, string $csvDir, array $report): int {
    $stmt = $db->prepare('
        INSERT INTO legacy_import_runs (company_id, source_name, csv_dir, mode, status, expected_counts, actual_counts)
        VALUES (?, "gym_viejo_supabase", ?, "execute", "started", ?, ?)
    ');
    $stmt->execute([
        $companyId,
        $csvDir,
        json_encode(EXPECTED_COUNTS, JSON_UNESCAPED_UNICODE),
        json_encode($report['counts'], JSON_UNESCAPED_UNICODE),
    ]);
    return (int)$db->lastInsertId();
}

function completeImportRun(PDO $db, int $id, string $status, array $report, ?array $errors): void {
    $db->prepare('
        UPDATE legacy_import_runs
        SET status = ?, actual_counts = ?, errors = ?, finished_at = NOW()
        WHERE id = ?
    ')->execute([
        $status,
        json_encode($report, JSON_UNESCAPED_UNICODE),
        $errors !== null ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
        $id,
    ]);
}

function writeCredentialReport(array $credentials, string $companyName): void {
    if (count($credentials) === 0) {
        echo PHP_EOL . 'No se generaron credenciales nuevas para staff.' . PHP_EOL;
        return;
    }

    if ((string)(getenv('WRITE_LEGACY_CREDENTIAL_REPORT') ?: '') !== '1') {
        echo PHP_EOL . 'Se generaron credenciales temporales para staff, pero no se guardaron en archivo.' . PHP_EOL;
        echo 'Define WRITE_LEGACY_CREDENTIAL_REPORT=1 solo en un entorno controlado si necesitas exportarlas.' . PHP_EOL;
        return;
    }

    $safeName = preg_replace('/[^a-z0-9]+/i', '-', strtolower($companyName));
    $path = __DIR__ . '/legacy_import_credentials_' . trim((string)$safeName, '-') . '_' . date('Ymd_His') . '.txt';
    $lines = ["Credenciales temporales - {$companyName}", 'Generado: ' . date('c'), ''];
    foreach ($credentials as $item) {
        $lines[] = "{$item['name']} <{$item['email']}> role={$item['role']} password={$item['password']}";
    }
    file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
    echo PHP_EOL . "Credenciales temporales guardadas en: {$path}" . PHP_EOL;
}

function calculateMembershipValidity(array $payments): array {
    $validity = [];
    foreach ($payments as $row) {
        $memberId = trim((string)$row['member_id']);
        $periodEnd = normalizeDate($row['fecha_fin'] ?? null);
        if ($periodEnd === null) continue;
        if (!isset($validity[$memberId]) || $periodEnd > $validity[$memberId]) {
            $validity[$memberId] = $periodEnd;
        }
    }
    return $validity;
}

function mapPaymentCreatedBy(array $transactions): array {
    $map = [];
    foreach ($transactions as $row) {
        $paymentId = trim((string)($row['payment_id'] ?? ''));
        $createdBy = trim((string)($row['created_by'] ?? ''));
        if ($paymentId !== '' && $createdBy !== '') {
            $map[$paymentId] = $createdBy;
        }
    }
    return $map;
}

function findPlanName(int $legacyPlanId): ?string {
    static $names = [17 => '3 veces semanal', 18 => 'Todos los dias'];
    return $names[$legacyPlanId] ?? null;
}

function printReport(array $report, string $mode, string $csvDir, string $companyName): void {
    echo "Legacy Supabase import ({$mode})" . PHP_EOL;
    echo "CSV dir: {$csvDir}" . PHP_EOL;
    echo "Company: {$companyName}" . PHP_EOL . PHP_EOL;

    echo "Conteos:" . PHP_EOL;
    foreach ($report['counts'] as $key => $value) {
        echo "  - {$key}: {$value}" . PHP_EOL;
    }

    echo PHP_EOL . 'Totales:' . PHP_EOL;
    echo '  - payments_sum: ' . number_format((float)$report['payment_sum'], 2, '.', '') . PHP_EOL;
    echo '  - transactions_sum: ' . number_format((float)$report['transaction_sum'], 2, '.', '') . PHP_EOL;
    echo '  - unlinked_transaction_count: ' . $report['unlinked_transaction_count'] . PHP_EOL;

    echo PHP_EOL . 'Integridad:' . PHP_EOL;
    foreach ($report['integrity'] as $key => $value) {
        echo "  - {$key}: {$value}" . PHP_EOL;
    }

    echo PHP_EOL . 'Estado de cuota:' . PHP_EOL;
    foreach ($report['quota_states'] as $key => $value) {
        echo "  - {$key}: {$value}" . PHP_EOL;
    }

    if ($report['has_errors']) {
        echo PHP_EOL . 'Errores:' . PHP_EOL;
        foreach ($report['errors'] as $error) {
            echo "  - {$error}" . PHP_EOL;
        }
    }
}

function loadEnv(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

function indexBy(array $rows, string $field): array {
    $indexed = [];
    foreach ($rows as $row) {
        $indexed[(string)($row[$field] ?? '')] = $row;
    }
    return $indexed;
}

function groupCount(array $rows, string $field): array {
    $counts = [];
    foreach ($rows as $row) {
        $key = (string)($row[$field] ?? '');
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }
    ksort($counts);
    return $counts;
}

function onlyParams(array $params, array $keys): array {
    return array_intersect_key($params, array_flip($keys));
}

function sumColumn(array $rows, string $field): float {
    $sum = 0.0;
    foreach ($rows as $row) {
        $sum += decimal($row[$field] ?? 0);
    }
    return $sum;
}

function nullable(mixed $value): ?string {
    $value = trim((string)($value ?? ''));
    return $value === '' ? null : $value;
}

function decimal(mixed $value): float {
    $value = str_replace(',', '.', trim((string)($value ?? '0')));
    return is_numeric($value) ? (float)$value : 0.0;
}

function nullableDecimal(mixed $value): ?float {
    $value = trim((string)($value ?? ''));
    return $value === '' ? null : decimal($value);
}

function parseBool(mixed $value): bool {
    return in_array(strtolower(trim((string)($value ?? ''))), ['1', 'true', 't', 'yes', 'si', 'sí'], true);
}

function normalizeDate(mixed $value): ?string {
    $value = trim((string)($value ?? ''));
    if ($value === '') return null;
    return substr($value, 0, 10);
}

function normalizeDateTime(mixed $value): ?string {
    $value = trim((string)($value ?? ''));
    if ($value === '') return null;
    $value = str_replace('T', ' ', $value);
    $value = preg_replace('/\+\d{2}(:?\d{2})?$/', '', $value) ?? $value;
    if (str_contains($value, '.')) {
        $value = substr($value, 0, strpos($value, '.'));
    }
    return substr($value, 0, 19);
}

function mapPaymentMethod(mixed $value): string {
    $value = strtolower(trim((string)($value ?? '')));
    if ($value === 'efectivo') return 'cash';
    if (str_contains($value, 'transferencia')) return 'transfer';
    if (str_contains($value, 'debito') || str_contains($value, 'débito') || str_contains($value, 'credito') || str_contains($value, 'crédito')) return 'card';
    return 'other';
}

function mapConceptType(mixed $value): string {
    $value = strtoupper(trim((string)($value ?? '')));
    return match ($value) {
        'EGRESO' => 'expense',
        'AMBOS' => 'both',
        default => 'income',
    };
}

function mapTransactionType(mixed $value): string {
    return strtoupper(trim((string)($value ?? ''))) === 'EGRESO' ? 'expense' : 'income';
}

function generateTemporaryPassword(): string {
    return 'Legacy-' . bin2hex(random_bytes(4)) . '!';
}

function fail(string $message): never {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
