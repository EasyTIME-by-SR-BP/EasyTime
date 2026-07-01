<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php dispatch-notification-mail.php <job.json>\n");
    exit(1);
}

$jobPath = $argv[1];
if (!is_file($jobPath)) {
    fwrite(STDERR, "Job file not found.\n");
    exit(1);
}

$job = json_decode((string) file_get_contents($jobPath), true);
@unlink($jobPath);

if (!is_array($job)) {
    fwrite(STDERR, "Invalid job file.\n");
    exit(1);
}

$userId = (int) ($job['userId'] ?? 0);
$notificationId = (int) ($job['notificationId'] ?? 0);
$payload = is_array($job['payload'] ?? null) ? $job['payload'] : [];

if ($userId <= 0 || $notificationId <= 0 || $payload === []) {
    fwrite(STDERR, "Invalid job payload.\n");
    exit(1);
}

spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = dirname(__DIR__) . '/' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

use App\Services\MailService;

MailService::sendForNotification($userId, $notificationId, $payload);
