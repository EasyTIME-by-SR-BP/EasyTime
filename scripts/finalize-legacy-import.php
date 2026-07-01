<?php
/**
 * Finalisiert importierte Legacy-Daten für den Deploy.
 *
 *   php scripts/finalize-legacy-import.php          (SQLite, vor MariaDB-Migration)
 *   DB_DRIVER=mysql php scripts/finalize-legacy-import.php   (MariaDB, nach Migration)
 *
 * In Docker (nach migrate):
 *   docker compose run --rm web php scripts/finalize-legacy-import.php
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

use App\Services\LegacyImportFinalizer;

try {
    LegacyImportFinalizer::run();
} catch (Throwable $e) {
    fwrite(STDERR, 'Fehler: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
