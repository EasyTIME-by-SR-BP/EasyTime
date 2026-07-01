<?php

namespace App\Services;

use App\Core\Database;
use App\Core\DatabaseSeeder;
use App\Models\User;
use PDO;

/**
 * Bereitet importierte Legacy-Daten für den Produktions-Deploy vor:
 * Demo-Passwort, Passwort-Änderung beim ersten Login, Demo-Admin, Pool-Migration.
 */
class LegacyImportFinalizer {
    public const DEMO_ADMIN_PERSONAL_ID = 'A000';

    /** @var list<string> */
    private const DEFAULT_ABTEILUNGEN = ['Fahrlehrer', 'Verwaltung', 'Büro', 'Allgemein'];

    /** @var list<string> */
    private const DEFAULT_FUEHRERSCHEINKLASSEN = [
        'B - Personenkraftwagen',
        'A - Motorrad',
        'BE - Anhänger',
    ];

    public static function run(?PDO $db = null): void {
        $db = $db ?? Database::getConnection();

        echo "Setze Demo-Passwort für alle Mitarbeiter …\n";
        self::resetAllPasswords($db);

        echo "Aktiviere Passwort-Änderung beim ersten Login …\n";
        self::setMustChangeForAllActive($db);

        echo "Stelle Demo-Admin sicher …\n";
        self::ensureDemoAdmin($db);

        echo "Lösche Pool-Migrations-Flags (Führerschein/Abteilung) …\n";
        self::resetPoolMigrationFlags($db);

        echo "Führe Schema-/Pool-Migration aus …\n";
        Database::migrateLegacyAssignmentPools();

        echo "Ergänze fehlende Demo-Pools …\n";
        self::ensureDefaultPools($db);

        echo "Fertig.\n";
        self::printSummary($db);
    }

    private static function resetAllPasswords(PDO $db): void {
        $hash = password_hash(DatabaseSeeder::DEMO_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE mitarbeiter SET password = ?');
        $stmt->execute([$hash]);
        echo '  ' . $stmt->rowCount() . " Passwörter gesetzt (Klartext: " . DatabaseSeeder::DEMO_PASSWORD . ").\n";
    }

    private static function setMustChangeForAllActive(PDO $db): void {
        $rows = $db->query('SELECT id FROM mitarbeiter WHERE COALESCE(status, 0) = 0')->fetchAll();
        $count = 0;
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            User::setMustChangePassword($id, true);
            $count++;
        }
        echo "  {$count} aktive Mitarbeiter müssen Passwort ändern.\n";
    }

    private static function ensureDemoAdmin(PDO $db): void {
        $personalId = self::DEMO_ADMIN_PERSONAL_ID;
        $email = getenv('LEGACY_DEMO_ADMIN_EMAIL') ?: 'admin@easytime.local';
        $hash = password_hash(DatabaseSeeder::DEMO_PASSWORD, PASSWORD_DEFAULT);

        $lookup = $db->prepare('SELECT id FROM mitarbeiter WHERE personal_id = ? OR email = ? LIMIT 1');
        $lookup->execute([$personalId, $email]);
        $existingId = (int) $lookup->fetchColumn();

        if ($existingId > 0) {
            $update = $db->prepare("
                UPDATE mitarbeiter
                SET personal_id = ?, vorname = 'EasyTime', nachname = 'Admin',
                    email = ?, berechtigung = 'Administrator', status = 0, password = ?
                WHERE id = ?
            ");
            $update->execute([$personalId, $email, $hash, $existingId]);
            echo "  Demo-Admin aktualisiert (ID {$existingId}, {$personalId} / {$email}).\n";
            User::setMustChangePassword($existingId, true);
            return;
        }

        if (Database::isMysql()) {
            $insert = $db->prepare("
                INSERT INTO mitarbeiter (
                    personal_id, vorname, nachname, email, position, status,
                    password, berechtigung, urlaubsanspruch, akt_wochen_std
                ) VALUES (?, 'EasyTime', 'Admin', ?, 'Leitung', 0, ?, 'Administrator', 240, 40)
            ");
        } else {
            $insert = $db->prepare("
                INSERT INTO mitarbeiter (
                    personal_id, vorname, nachname, email, position, status,
                    password, berechtigung, urlaubsanspruch, akt_wochen_std
                ) VALUES (?, 'EasyTime', 'Admin', ?, 'Leitung', 0, ?, 'Administrator', 240, 40)
            ");
        }
        $insert->execute([$personalId, $email, $hash]);
        $newId = (int) $db->lastInsertId();
        echo "  Demo-Admin angelegt (ID {$newId}, {$personalId} / {$email}).\n";
        User::setMustChangePassword($newId, true);
    }

    private static function resetPoolMigrationFlags(PDO $db): void {
        if (!self::tableExists($db, 'app_settings')) {
            return;
        }
        $keys = ['license_class_pool_migrated', 'abteilung_pool_migrated'];
        if (Database::isMysql()) {
            $stmt = $db->prepare('DELETE FROM app_settings WHERE `key` IN (?, ?)');
        } else {
            $stmt = $db->prepare('DELETE FROM app_settings WHERE key IN (?, ?)');
        }
        $stmt->execute($keys);
    }

    private static function ensureDefaultPools(PDO $db): void {
        if (!self::tableExists($db, 'fuehrerscheinklassen')) {
            return;
        }

        $poolCount = (int) $db->query('SELECT COUNT(*) FROM fuehrerscheinklassen')->fetchColumn();
        if ($poolCount === 0) {
            $insert = $db->prepare('INSERT INTO fuehrerscheinklassen (bezeichnung) VALUES (?)');
            foreach (self::DEFAULT_FUEHRERSCHEINKLASSEN as $name) {
                try {
                    $insert->execute([$name]);
                } catch (\PDOException $e) {
                    // duplicate
                }
            }
            echo '  Standard-Führerscheinklassen angelegt.' . "\n";
        }

        if (!self::tableExists($db, 'abteilungen')) {
            return;
        }

        $abtCount = (int) $db->query('SELECT COUNT(*) FROM abteilungen')->fetchColumn();
        if ($abtCount === 0) {
            $insert = $db->prepare('INSERT INTO abteilungen (bezeichnung) VALUES (?)');
            foreach (self::DEFAULT_ABTEILUNGEN as $name) {
                try {
                    $insert->execute([$name]);
                } catch (\PDOException $e) {
                    // duplicate
                }
            }
            echo '  Standard-Abteilungen angelegt.' . "\n";
        }

        self::linkUnassignedEmployees($db);
    }

    private static function linkUnassignedEmployees(PDO $db): void {
        if (!self::tableExists($db, 'mitarbeiter_abteilungen')) {
            return;
        }

        $defaultAbt = $db->query("SELECT id FROM abteilungen ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($defaultAbt) {
            $unassigned = $db->query("
                SELECT m.id FROM mitarbeiter m
                LEFT JOIN mitarbeiter_abteilungen ma ON ma.mitarbeiter_id = m.id
                WHERE ma.mitarbeiter_id IS NULL AND COALESCE(m.status, 0) = 0
            ")->fetchAll();
            if (Database::isMysql()) {
                $link = $db->prepare('INSERT IGNORE INTO mitarbeiter_abteilungen (mitarbeiter_id, abteilung_id) VALUES (?, ?)');
            } else {
                $link = $db->prepare('INSERT OR IGNORE INTO mitarbeiter_abteilungen (mitarbeiter_id, abteilung_id) VALUES (?, ?)');
            }
            $linked = 0;
            foreach ($unassigned as $row) {
                $link->execute([(int) $row['id'], (int) $defaultAbt]);
                $linked++;
            }
            if ($linked > 0) {
                echo "  {$linked} Mitarbeiter der Standard-Abteilung zugeordnet.\n";
            }
        }

        if (!self::tableExists($db, 'mitarbeiter_fuehrerscheinklassen')) {
            return;
        }

        $defaultClass = $db->query('SELECT id FROM fuehrerscheinklassen ORDER BY id ASC LIMIT 1')->fetchColumn();
        if (!$defaultClass) {
            return;
        }

        $unassignedClass = $db->query("
            SELECT m.id FROM mitarbeiter m
            LEFT JOIN mitarbeiter_fuehrerscheinklassen mf ON mf.mitarbeiter_id = m.id
            WHERE mf.mitarbeiter_id IS NULL AND COALESCE(m.status, 0) = 0
        ")->fetchAll();

        if (Database::isMysql()) {
            $linkClass = $db->prepare('INSERT IGNORE INTO mitarbeiter_fuehrerscheinklassen (mitarbeiter_id, klasse_id) VALUES (?, ?)');
        } else {
            $linkClass = $db->prepare('INSERT OR IGNORE INTO mitarbeiter_fuehrerscheinklassen (mitarbeiter_id, klasse_id) VALUES (?, ?)');
        }
        $linkedClass = 0;
        foreach ($unassignedClass as $row) {
            $linkClass->execute([(int) $row['id'], (int) $defaultClass]);
            $linkedClass++;
        }
        if ($linkedClass > 0) {
            echo "  {$linkedClass} Mitarbeiter einer Standard-Führerscheinklasse zugeordnet.\n";
        }
    }

    private static function printSummary(PDO $db): void {
        $employees = (int) $db->query('SELECT COUNT(*) FROM mitarbeiter')->fetchColumn();
        $standorte = (int) $db->query('SELECT COUNT(*) FROM standorte')->fetchColumn();
        $admins = (int) $db->query("
            SELECT COUNT(*) FROM mitarbeiter
            WHERE LOWER(COALESCE(berechtigung, '')) IN ('administrator', 'admin', 'ceo')
        ")->fetchColumn();

        $email = getenv('LEGACY_DEMO_ADMIN_EMAIL') ?: 'admin@easytime.local';
        echo "\n--- Zusammenfassung ---\n";
        echo "Mitarbeiter: {$employees}, Standorte: {$standorte}, Admins: {$admins}\n";
        echo "Demo-Admin: " . self::DEMO_ADMIN_PERSONAL_ID . " oder {$email}\n";
        echo 'Passwort (alle): ' . DatabaseSeeder::DEMO_PASSWORD . " (beim ersten Login ändern)\n";
        echo "Tutorial startet automatisch nach Passwort-Änderung (neuer Browser).\n";
    }

    private static function tableExists(PDO $db, string $table): bool {
        if (Database::isMysql()) {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = ?
            ");
            $stmt->execute([$table]);
            return (int) $stmt->fetchColumn() > 0;
        }
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }
}
