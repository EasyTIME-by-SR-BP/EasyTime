<?php
/**
 * MariaDB komplett zurücksetzen und Demo-Daten laden (Produktion / Docker).
 *
 *   docker compose exec web php scripts/seed-mariadb.php
 */

declare(strict_types=1);

spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = dirname(__DIR__) . '/' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

use App\Core\Database;
use App\Core\DatabaseSeeder;

if (getenv('DB_DRIVER') !== 'mysql' && ($_ENV['DB_DRIVER'] ?? '') !== 'mysql') {
    putenv('DB_DRIVER=mysql');
    $_ENV['DB_DRIVER'] = 'mysql';
    $_SERVER['DB_DRIVER'] = 'mysql';
}

try {
    $pdo = Database::getConnection();
    DatabaseSeeder::resetAndSeed($pdo);
} catch (Throwable $e) {
    fwrite(STDERR, 'Fehler: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "MariaDB wurde zurückgesetzt und mit Demo-Daten befüllt.\n\n";
echo DatabaseSeeder::credentialsHelp() . "\n";
