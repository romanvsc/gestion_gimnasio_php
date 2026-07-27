<?php
// Rota passwords legacy sin guardar secretos en archivos.
//
// Uso local:
//   $env:ROTATE_PASSWORD_ROMAN='...'
//   $env:ROTATE_PASSWORD_MAIRA='...'
//   $env:ROTATE_PASSWORD_MICAELA='...'
//   $env:ROTATE_PASSWORD_EUGENIA='...'
//   $env:ROTATE_PASSWORD_CUENCA='...'
//   php backend/tools/rotate_legacy_passwords.php --execute

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$options = getopt('', ['execute', 'dry-run']);
$execute = array_key_exists('execute', $options);

loadEnv(dirname(__DIR__) . '/.env');

$users = [
    'romanvscprogramming@gmail.com' => envRequired('ROTATE_PASSWORD_ROMAN'),
    'maira@gym.com' => envRequired('ROTATE_PASSWORD_MAIRA'),
    'micaela@gym.com' => envRequired('ROTATE_PASSWORD_MICAELA'),
    'eugeniasena@gym.com' => envRequired('ROTATE_PASSWORD_EUGENIA'),
    'cuenca@gym.com' => envRequired('ROTATE_PASSWORD_CUENCA'),
];

$db = getDB();
$db->beginTransaction();

try {
    foreach ($users as $email => $password) {
        $stmt = $db->prepare('SELECT id, company_id, name FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo "WARN: no existe usuario {$email}" . PHP_EOL;
            continue;
        }

        if ($execute) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $update = $db->prepare('UPDATE users SET password_hash = ?, status = "active" WHERE id = ? AND company_id = ?');
            $update->execute([$hash, (int)$user['id'], (int)$user['company_id']]);
        }

        echo ($execute ? 'UPDATED' : 'DRY-RUN') . ": {$email}" . PHP_EOL;
    }

    $testUser = $db->prepare('SELECT id, company_id FROM users WHERE email = ? OR LOWER(name) = ? LIMIT 1');
    $testUser->execute(['admin@gym.com', 'prueba']);
    $test = $testUser->fetch();

    if ($test && $execute) {
        $disable = $db->prepare('UPDATE users SET status = "inactive" WHERE id = ? AND company_id = ?');
        $disable->execute([(int)$test['id'], (int)$test['company_id']]);
    }
    echo ($test ? ($execute ? 'DISABLED' : 'DRY-RUN') . ': usuario de prueba' : 'WARN: no existe usuario de prueba') . PHP_EOL;

    if ($execute) {
        $db->commit();
        echo 'Rotacion completada.' . PHP_EOL;
    } else {
        $db->rollBack();
        echo 'Dry run finalizado. No se escribieron cambios. Usa --execute para aplicar.' . PHP_EOL;
    }
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

function envRequired(string $name): string {
    $value = (string)(getenv($name) ?: '');
    if ($value === '') {
        fwrite(STDERR, "Falta variable de entorno {$name}" . PHP_EOL);
        exit(1);
    }
    return $value;
}

function loadEnv(string $path): void {
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}
