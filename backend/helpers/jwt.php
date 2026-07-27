<?php
// backend/helpers/jwt.php
// JWT HS256 sin dependencias externas

declare(strict_types=1);

require_once __DIR__ . '/response.php';

function jwtEncode(array $payload): string {
    $secret  = getJwtSecret();
    $exp     = (int)($_ENV['JWT_EXPIRATION'] ?? 28800);

    $header  = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat'] = time();
    $payload['exp'] = time() + $exp;
    $body    = base64url_encode(json_encode($payload));

    $sig = base64url_encode(
        hash_hmac('sha256', "{$header}.{$body}", $secret, true)
    );

    return "{$header}.{$body}.{$sig}";
}

function jwtDecode(string $token): ?array {
    $secret = getJwtSecret();
    $parts  = explode('.', $token);

    if (count($parts) !== 3) {
        return null;
    }

    [$header, $body, $sig] = $parts;

    $decodedHeader = json_decode(base64url_decode($header), true);
    if (!is_array($decodedHeader) || ($decodedHeader['alg'] ?? '') !== 'HS256') {
        return null;
    }

    $expected = base64url_encode(
        hash_hmac('sha256', "{$header}.{$body}", $secret, true)
    );

    if (!hash_equals($expected, $sig)) {
        return null;
    }

    $payload = json_decode(base64url_decode($body), true);

    if (!is_array($payload)) {
        return null;
    }

    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null; // token expirado
    }

    return $payload;
}

function getJwtSecret(): string {
    $secret = trim((string)($_ENV['JWT_SECRET'] ?? ''));

    if ($secret === '' || $secret === 'fallback_secret_change_me') {
        errorResponse('Server authentication is not configured', 500);
    }

    return $secret;
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    $padding = strlen($data) % 4;
    if ($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }

    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    return $decoded === false ? '' : $decoded;
}
