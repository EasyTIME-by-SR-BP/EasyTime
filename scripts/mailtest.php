<?php

declare(strict_types=1);

/**
 * SMTP-Test — nutzt dieselbe Konfiguration wie MailService (.env / Docker env_file).
 *
 * Usage:
 *   php scripts/mailtest.php recipient@example.com
 *   docker compose exec web php scripts/mailtest.php recipient@example.com
 */

spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = __DIR__ . '/../' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

use App\Services\MailService;

$recipients = array_slice($argv, 1);
if ($recipients === []) {
    $fallback = trim((string) (getenv('MAIL_TEST_TO') ?: ''));
    if ($fallback !== '') {
        $recipients = array_map('trim', explode(',', $fallback));
    }
}

if ($recipients === []) {
    fwrite(STDERR, "Usage: php scripts/mailtest.php email@example.com [weitere…]\n");
    exit(1);
}

foreach ($recipients as $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "Ungültige Adresse: {$email}\n");
        exit(1);
    }
}

foreach ($recipients as $email) {
    try {
        MailService::sendTestEmail($email);
        echo "OK: {$email}\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "FEHLER ({$email}): " . $e->getMessage() . "\n");
        exit(1);
    }
}
