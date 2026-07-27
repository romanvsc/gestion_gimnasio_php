<?php
// Validaciones compartidas del contexto socios/membresias.

declare(strict_types=1);

require_once __DIR__ . '/response.php';

function normalizeMemberPayload(array $body, bool $isCreate): array {
    foreach (['first_name', 'last_name'] as $field) {
        if ($isCreate || array_key_exists($field, $body)) {
            $value = trim((string)($body[$field] ?? ''));
            if ($value === '') {
                errorResponse('Nombre y apellido son requeridos', 422);
            }
            $body[$field] = $value;
        }
    }

    if (array_key_exists('email', $body)) {
        $email = normalizeOptionalString($body['email']);
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            errorResponse('Email invalido', 422);
        }
        $body['email'] = $email;
    }

    foreach (['birthdate', 'joined_at', 'medical_certificate_valid_until', 'membership_valid_until'] as $field) {
        if (array_key_exists($field, $body)) {
            $date = normalizeOptionalString($body[$field]);
            if ($date !== null && !isValidYmdDate($date)) {
                errorResponse("Fecha invalida en {$field}", 422);
            }
            $body[$field] = $date;
        }
    }

    foreach (['weight_kg', 'height_cm'] as $field) {
        if (array_key_exists($field, $body)) {
            if ($body[$field] === '' || $body[$field] === null) {
                $body[$field] = null;
                continue;
            }

            $value = (float)$body[$field];
            if ($value <= 0) {
                errorResponse("El valor de {$field} debe ser mayor a 0", 422);
            }
            $body[$field] = $value;
        }
    }

    if (array_key_exists('gender', $body)) {
        $gender = normalizeOptionalString($body['gender']);
        if ($gender !== null && !in_array($gender, ['male', 'female', 'other'], true)) {
            errorResponse('Genero invalido', 422);
        }
        $body['gender'] = $gender;
    }

    foreach (['phone', 'dni', 'address', 'notes', 'photo_url'] as $field) {
        if (array_key_exists($field, $body)) {
            $body[$field] = normalizeOptionalString($body[$field]);
        }
    }

    return $body;
}

function normalizeOptionalString(mixed $value): ?string {
    $value = trim((string)($value ?? ''));
    return $value === '' ? null : $value;
}

function isValidYmdDate(string $date): bool {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}
