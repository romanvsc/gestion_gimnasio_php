<?php
// backend/config/cors.php

declare(strict_types=1);

function setCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    $allowedOrigins = [
        $_ENV['FRONTEND_URL'] ?? '',
        'http://localhost:5174',
        'http://localhost:4173',
        'http://127.0.0.1:5174',
    ];

    if (in_array($origin, array_filter($allowedOrigins), true)) {
        header("Access-Control-Allow-Origin: {$origin}");
    } elseif (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        header("Access-Control-Allow-Origin: *");
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
