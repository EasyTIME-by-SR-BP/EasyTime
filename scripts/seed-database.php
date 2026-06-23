<?php
/**
 * Demo-Datenbank neu befüllen (SQLite, lokale Entwicklung).
 *
 *   pnpm db:seed
 *   php scripts/seed-database.php
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

if (getenv('DB_DRIVER') === 'mysql') {
    fwrite(STDERR, "Abgebrochen: DB_DRIVER=mysql ist gesetzt. Reseed nur ohne MariaDB (SQLite).\n");
    exit(1);
}

putenv('DB_DRIVER=sqlite');
$_ENV['DB_DRIVER']    = 'sqlite';
$_SERVER['DB_DRIVER'] = 'sqlite';

try {
    Database::reseed();
} catch (Throwable $e) {
    fwrite(STDERR, 'Fehler: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "Datenbank wurde mit Demo-Daten neu befüllt.\n";
echo "Alle Daten wurden zurückgesetzt (2 Admins, 10 Mitarbeiter).\n\n";
echo DatabaseSeeder::credentialsHelp() . "\n";
