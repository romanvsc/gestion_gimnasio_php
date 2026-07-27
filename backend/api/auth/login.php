<?php
// backend/api/auth/login.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/jwt.php';
require_once __DIR__ . '/../../helpers/response.php';

requireMethod('POST');

$body  = getJsonBody();
$email = trim($body['email'] ?? '');
$pass  = $body['password'] ?? '';

if (empty($email) || empty($pass)) {
    errorResponse('Email y contraseña son requeridos', 422);
}

$db   = getDB();
$stmt = $db->prepare(
    'SELECT u.*, c.name AS company_name, c.status AS company_status
     FROM users u
     JOIN companies c ON c.id = u.company_id
     WHERE u.email = ? AND u.status = "active"
     LIMIT 1'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password_hash'])) {
    errorResponse('Credenciales inválidas', 401);
}

if ($user['company_status'] !== 'active') {
    errorResponse('La empresa no está activa', 403);
}

// Actualizar ultimo login
$db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

$token = jwtEncode([
    'user_id'      => (int)$user['id'],
    'company_id'   => (int)$user['company_id'],
    'role'         => $user['role'],
]);

jsonResponse([
    'token' => $token,
    'user'  => [
        'id'           => (int)$user['id'],
        'name'         => $user['name'],
        'email'        => $user['email'],
        'role'         => $user['role'],
        'company_id'   => (int)$user['company_id'],
        'company_name' => $user['company_name'],
    ],
]);
