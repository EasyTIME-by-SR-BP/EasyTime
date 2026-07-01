<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

spl_autoload_register(function ($class) use ($root) {
    if (strpos($class, 'App\\') === 0) {
        $file = $root . '/' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

use App\Models\User;
use App\Services\Inbox;
use App\Services\MailService;

$mnr = $argv[1] ?? 'M001';
$user = User::findByEmailOrMnr($mnr);
if (!$user) {
    fwrite(STDERR, "User {$mnr} not found\n");
    exit(1);
}

$userId = (int) $user['id'];
$email = (string) ($user['email'] ?? '');

echo 'MAIL_ENABLED=' . (MailService::isEnabled() ? 'true' : 'false') . PHP_EOL;
echo "User #{$userId} ({$mnr}) -> {$email}" . PHP_EOL;

Inbox::send([
    'to' => $userId,
    'title' => 'Urlaub genehmigt (Produktionstest)',
    'message' => "Test-Mail für {$mnr}: Antrag wurde genehmigt. Wenn du diese Mail siehst, funktioniert der async Versand.",
    'category' => 'success',
    'type' => Inbox::TYPE_INFO,
    'resolution' => Inbox::RESOLUTION_INDIVIDUAL,
    'dedupe' => false,
]);

echo "Queued inbox notification for {$mnr}" . PHP_EOL;
