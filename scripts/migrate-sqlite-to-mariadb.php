<?php
/**
 * One-shot migration: SQLite (EasyTime schema) -> MariaDB (Docker).
 * Usage: docker compose --profile migrate run --rm migrate
 */

declare(strict_types=1);

ini_set('memory_limit', '512M');

putenv('DB_DRIVER=mysql');

spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = dirname(__DIR__) . '/' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

use App\Core\Database;

$sourcePath = getenv('SQLITE_SOURCE_PATH') ?: dirname(__DIR__) . '/database/database.sqlite';
$batchSize  = 500;

if (!is_readable($sourcePath)) {
    fwrite(STDERR, "SQLite file not found: {$sourcePath}\n");
    fwrite(STDERR, "Run: python3 database/convert_legacy_deploy.py --source /path/to/dump.sql\n");
    exit(1);
}

$sqlite = new PDO('sqlite:' . $sourcePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mysql = Database::getConnection();

$stammdatenTables = [
    'mitarbeiter',
    'standorte',
    'klassen',
    'dokumente',
    'mitarbeiter_dokumente',
    'mitarbeiter_standorte',
    'standort_vertretung',
    'eintritt',
    'abmeldung',
    'aenderungsmeldung',
    'taetigkeitsart',
    'vorlagen',
    'app_settings',
];

$fullTables = [
    'mitarbeiter',
    'standorte',
    'klassen',
    'dokumente',
    'mitarbeiter_dokumente',
    'mitarbeiter_standorte',
    'standort_vertretung',
    'eintritt',
    'abmeldung',
    'aenderungsmeldung',
    'taetigkeitsart',
    'event',
    'urlaub',
    'urlaubssperre',
    'urlaub_kommentar',
    'urlaub_event',
    'vorlagen',
    'app_settings',
];

$profile = strtolower((string) (getenv('MIGRATE_PROFILE') ?: 'stammdaten'));
$tables = match ($profile) {
    'full' => $fullTables,
    'stammdaten' => $stammdatenTables,
    default => throw new InvalidArgumentException("Unknown MIGRATE_PROFILE: {$profile}"),
};

echo "Migration profile: {$profile}\n";

$mysql->exec('SET FOREIGN_KEY_CHECKS = 0');

foreach ($tables as $table) {
    $exists = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name=" . $sqlite->quote($table))->fetchColumn();
    if (!$exists) {
        echo "Skip (not in SQLite): {$table}\n";
        continue;
    }

    $mysql->exec("TRUNCATE TABLE `{$table}`");

    $countStmt = $sqlite->query("SELECT COUNT(*) FROM `{$table}`");
    $total     = (int) $countStmt->fetchColumn();
    if ($total === 0) {
        echo "Empty: {$table}\n";
        continue;
    }

    $sample = $sqlite->query("SELECT * FROM `{$table}` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $columns = array_keys($sample);
    $quotedCols = array_map(static fn ($c) => '`' . str_replace('`', '``', $c) . '`', $columns);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $colList = implode(', ', $quotedCols);
    $insert = $mysql->prepare("INSERT INTO `{$table}` ({$colList}) VALUES ({$placeholders})");

    $offset = 0;
    $count  = 0;
    while ($offset < $total) {
        $stmt = $sqlite->prepare("SELECT * FROM `{$table}` LIMIT :lim OFFSET :off");
        $stmt->bindValue(':lim', $batchSize, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === []) {
            break;
        }
        foreach ($rows as $row) {
            $insert->execute(array_values($row));
            $count++;
        }
        $offset += $batchSize;
        echo "  {$table}: {$count}/{$total}\r";
    }

    if (in_array('id', $columns, true)) {
        $maxId = (int) $mysql->query("SELECT COALESCE(MAX(id), 0) FROM `{$table}`")->fetchColumn();
        if ($maxId > 0) {
            $mysql->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = " . ($maxId + 1));
        }
    }

    echo "Migrated {$table}: {$count} rows\n";
}

$mysql->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "Done.\n";
