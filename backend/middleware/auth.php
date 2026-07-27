<?php
// backend/middleware/auth.php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../helpers/response.php';

function requireAuth(): array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        errorResponse('Unauthorized', 401);
    }

    $token   = substr($authHeader, 7);
    $payload = jwtDecode($token);

    if ($payload === null) {
        errorResponse('Invalid or expired token', 401);
    }

    if (empty($payload['company_id']) || empty($payload['user_id'])) {
        errorResponse('Invalid token payload', 401);
    }

    return $payload;
}

function requireRole(string|array $roles, ?array $payload = null): array {
    $payload = $payload ?? requireAuth();
    $allowedRoles = is_array($roles) ? $roles : [$roles];

    if (!in_array($payload['role'] ?? '', $allowedRoles, true)) {
        errorResponse('Forbidden', 403);
    }

    return $payload;
}

function requireAdmin(?array $payload = null): array {
    return requireRole('admin', $payload);
}
