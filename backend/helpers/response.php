<?php
// backend/helpers/response.php

declare(strict_types=1);

function jsonResponse(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function errorResponse(string $message, int $status = 400, ?array $errors = null): never {
    $body = ['error' => $message];
    if ($errors !== null) {
        $body['errors'] = $errors;
    }
    jsonResponse($body, $status);
}

function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function requireMethod(string ...$methods): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        errorResponse('Method not allowed', 405);
    }
}
