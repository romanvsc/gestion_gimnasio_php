<?php
// backend/api/auth/me.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireMethod('GET');

$payload = requireAuth();
$db      = getDB();

$stmt = $db->prepare(
    'SELECT u.id, u.name, u.email, u.role, u.company_id, c.name AS company_name
     FROM users u
     JOIN companies c ON c.id = u.company_id
     WHERE u.id = ? AND u.company_id = ? AND u.status = "active"
     LIMIT 1'
);
$stmt->execute([$payload['user_id'], $payload['company_id']]);
$user = $stmt->fetch();

if (!$user) {
    errorResponse('Usuario no encontrado', 404);
}

jsonResponse([
    'id'           => (int)$user['id'],
    'name'         => $user['name'],
    'email'        => $user['email'],
    'role'         => $user['role'],
    'company_id'   => (int)$user['company_id'],
    'company_name' => $user['company_name'],
]);
