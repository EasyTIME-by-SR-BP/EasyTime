<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php dispatch-notification-mail.php <job.json>\n");
    exit(1);
}

$jobPath = $argv[1];
if (!is_file($jobPath)) {
    error_log('[EasyTime Mail] Dispatch job not found: ' . $jobPath);
    exit(1);
}

$job = json_decode((string) file_get_contents($jobPath), true);
@unlink($jobPath);

if (!is_array($job)) {
    error_log('[EasyTime Mail] Dispatch invalid job JSON');
    exit(1);
}

$userId = (int) ($job['userId'] ?? 0);
$notificationId = (int) ($job['notificationId'] ?? 0);
$payload = is_array($job['payload'] ?? null) ? $job['payload'] : [];

if ($userId <= 0 || $notificationId <= 0 || $payload === []) {
    error_log('[EasyTime Mail] Dispatch invalid job payload for #' . $notificationId);
    exit(1);
}

$root = dirname(__DIR__);
$envFile = $root . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '' || getenv($key) !== false) {
            continue;
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

spl_autoload_register(function ($class) use ($root) {
    if (strpos($class, 'App\\') === 0) {
        $file = $root . '/' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

$autoload = $root . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

use App\Services\MailService;

try {
    error_log('[EasyTime Mail] Dispatch start notification #' . $notificationId . ' user #' . $userId);
    MailService::sendForNotification($userId, $notificationId, $payload);
    error_log('[EasyTime Mail] Dispatch done notification #' . $notificationId);
} catch (Throwable $e) {
    error_log('[EasyTime Mail] Dispatch failed notification #' . $notificationId . ': ' . $e->getMessage());
    exit(1);
}
