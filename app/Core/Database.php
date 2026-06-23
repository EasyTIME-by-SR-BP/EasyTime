<?php

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    private static string $dbPath = __DIR__ . '/../../database/database.sqlite';

    public static function isMysql(): bool {
        return strtolower((string) (getenv('DB_DRIVER') ?: 'sqlite')) === 'mysql';
    }

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                if (self::isMysql()) {
                    self::$instance = self::connectMysql();
                    self::ensureSchemaUpToDate();
                } else {
                    self::$instance = self::connectSqlite();
                }
            } catch (PDOException $e) {
                die('Database Connection failed: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function upsertAppSetting(string $key, string $value): void {
        $db = self::getConnection();
        if (self::isMysql()) {
            $stmt = $db->prepare("
                INSERT INTO app_settings (`key`, `value`) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
            ");
        } else {
            $stmt = $db->prepare('INSERT OR REPLACE INTO app_settings (key, value) VALUES (?, ?)');
        }
        $stmt->execute([$key, $value]);
    }

    private static function connectMysql(): PDO {
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_DATABASE') ?: 'easytime';
        $user = getenv('DB_USERNAME') ?: 'easytime';
        $pass = getenv('DB_PASSWORD') ?: '';

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $pdo->exec('SET NAMES utf8mb4');
        return $pdo;
    }

    private static function connectSqlite(): PDO {
        $isNew = !file_exists(self::$dbPath);

        if (!is_dir(dirname(self::$dbPath))) {
            mkdir(dirname(self::$dbPath), 0777, true);
        }

        $pdo = new PDO('sqlite:' . self::$dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        self::$instance = $pdo;

        if ($isNew) {
            self::initializeSchema();
            DatabaseSeeder::seedFreshDatabase($pdo);
        }
        self::ensureSchemaUpToDate();

        return $pdo;
    }

    private static function ensureSchemaUpToDate(): void {
        $db = self::$instance;
        if ($db === null) {
            return;
        }

        if (self::isMysql()) {
            $hasBase = (int) $db->query("
                SELECT COUNT(*) FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name IN ('mitarbeiter', 'urlaub')
            ")->fetchColumn();
            if ($hasBase < 2) {
                return;
            }

            $db->exec("
                CREATE TABLE IF NOT EXISTS urlaub_kommentar (
                    id             INT AUTO_INCREMENT PRIMARY KEY,
                    urlaub_id      INT NOT NULL,
                    mitarbeiter_id INT NOT NULL,
                    kommentar      TEXT NOT NULL,
                    erstellt_am    DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (urlaub_id) REFERENCES urlaub(id) ON DELETE CASCADE,
                    FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $db->exec("
                CREATE TABLE IF NOT EXISTS app_settings (
                    `key`   VARCHAR(64) PRIMARY KEY,
                    `value` TEXT NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $db->exec("INSERT IGNORE INTO app_settings (`key`, `value`) VALUES ('min_staff_available', '1')");
            $db->exec("INSERT IGNORE INTO app_settings (`key`, `value`) VALUES ('max_fenstertage', '0')");
            $db->exec("
                CREATE TABLE IF NOT EXISTS notifications (
                    id                  INT AUTO_INCREMENT PRIMARY KEY,
                    user_id             INT NOT NULL,
                    title               VARCHAR(255) NOT NULL,
                    message             TEXT NOT NULL,
                    category            VARCHAR(32) DEFAULT 'info',
                    type                VARCHAR(16) DEFAULT 'info',
                    resolution_mode     VARCHAR(16) DEFAULT 'individual',
                    thread_id           VARCHAR(64) DEFAULT NULL,
                    action_url          TEXT DEFAULT NULL,
                    related_user_id     INT DEFAULT NULL,
                    is_read             TINYINT DEFAULT 0,
                    is_resolved         TINYINT DEFAULT 0,
                    resolved_at         DATETIME DEFAULT NULL,
                    resolved_by_user_id INT DEFAULT NULL,
                    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            self::ensureNotificationColumns($db);
            self::ensureRequestEventsTable($db);
            return;
        }

        $db->exec('PRAGMA foreign_keys = ON;');
        $hasBaseTables = $db->query("
            SELECT COUNT(*)
            FROM sqlite_master
            WHERE type = 'table'
              AND name IN ('mitarbeiter', 'urlaub')
        ")->fetchColumn();
        if ((int) $hasBaseTables < 2) {
            return;
        }

        $db->exec("
            CREATE TABLE IF NOT EXISTS urlaub_kommentar (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                urlaub_id      INTEGER NOT NULL,
                mitarbeiter_id INTEGER NOT NULL,
                kommentar      TEXT    NOT NULL,
                erstellt_am    DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(urlaub_id)      REFERENCES urlaub(id)       ON DELETE CASCADE,
                FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id)  ON DELETE CASCADE
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS app_settings (
                key   TEXT PRIMARY KEY,
                value TEXT NOT NULL DEFAULT ''
            )
        ");
        $db->exec("INSERT OR IGNORE INTO app_settings (key, value) VALUES ('min_staff_available', '1')");
        $db->exec("INSERT OR IGNORE INTO app_settings (key, value) VALUES ('max_fenstertage', '0')");

        $db->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id             INTEGER NOT NULL,
                title               TEXT NOT NULL,
                message             TEXT NOT NULL,
                category            TEXT DEFAULT 'info',
                type                TEXT DEFAULT 'info',
                resolution_mode     TEXT DEFAULT 'individual',
                thread_id           TEXT DEFAULT NULL,
                action_url          TEXT DEFAULT NULL,
                related_user_id     INTEGER DEFAULT NULL,
                is_read             INTEGER DEFAULT 0,
                is_resolved         INTEGER DEFAULT 0,
                resolved_at         DATETIME DEFAULT NULL,
                resolved_by_user_id INTEGER DEFAULT NULL,
                created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(user_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
            )
        ");
        self::ensureNotificationColumns($db);
        self::ensureRequestEventsTable($db);
    }

    private static function ensureRequestEventsTable(PDO $db): void {
        if (self::isMysql()) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS urlaub_ereignis (
                    id             INT AUTO_INCREMENT PRIMARY KEY,
                    urlaub_id      INT NOT NULL,
                    mitarbeiter_id INT NOT NULL,
                    ereignis       VARCHAR(32) NOT NULL,
                    nachricht      TEXT DEFAULT NULL,
                    erstellt_am    DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (urlaub_id) REFERENCES urlaub(id) ON DELETE CASCADE,
                    FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            return;
        }
        $db->exec("
            CREATE TABLE IF NOT EXISTS urlaub_ereignis (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                urlaub_id      INTEGER NOT NULL,
                mitarbeiter_id INTEGER NOT NULL,
                ereignis       TEXT NOT NULL,
                nachricht      TEXT DEFAULT NULL,
                erstellt_am    DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(urlaub_id) REFERENCES urlaub(id) ON DELETE CASCADE,
                FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
            )
        ");
    }

    private static function ensureNotificationColumns(PDO $db): void {
        $columns = [
            'type'                => self::isMysql() ? "VARCHAR(16) DEFAULT 'info'" : "TEXT DEFAULT 'info'",
            'resolution_mode'     => self::isMysql() ? "VARCHAR(16) DEFAULT 'individual'" : "TEXT DEFAULT 'individual'",
            'thread_id'           => self::isMysql() ? 'VARCHAR(64) DEFAULT NULL' : 'TEXT DEFAULT NULL',
            'action_url'          => 'TEXT DEFAULT NULL',
            'related_user_id'     => self::isMysql() ? 'INT DEFAULT NULL' : 'INTEGER DEFAULT NULL',
            'is_resolved'         => self::isMysql() ? 'TINYINT DEFAULT 0' : 'INTEGER DEFAULT 0',
            'resolved_at'         => 'DATETIME DEFAULT NULL',
            'resolved_by_user_id' => self::isMysql() ? 'INT DEFAULT NULL' : 'INTEGER DEFAULT NULL',
        ];

        foreach ($columns as $name => $definition) {
            if (self::columnExists($db, 'notifications', $name)) {
                continue;
            }
            $db->exec("ALTER TABLE notifications ADD COLUMN {$name} {$definition}");
        }

        if (self::columnExists($db, 'notifications', 'resolution_mode')) {
            $db->exec("
                UPDATE notifications
                SET resolution_mode = 'shared'
                WHERE thread_id IS NOT NULL
                  AND thread_id != ''
                  AND thread_id NOT LIKE 'individual_%'
                  AND (resolution_mode IS NULL OR resolution_mode = '' OR resolution_mode = 'individual')
            ");
            $db->exec("
                UPDATE notifications
                SET resolution_mode = 'individual'
                WHERE resolution_mode IS NULL OR resolution_mode = ''
            ");
        }
    }

    private static function columnExists(PDO $db, string $table, string $column): bool {
        if (self::isMysql()) {
            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = ?
                  AND column_name = ?
            ");
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        }

        $stmt = $db->query("PRAGMA table_info({$table})");
        foreach ($stmt->fetchAll() as $row) {
            if (($row['name'] ?? '') === $column) {
                return true;
            }
        }
        return false;
    }

    private static function initializeSchema() {
        $db = self::$instance;
        $schema = "
        CREATE TABLE IF NOT EXISTS mitarbeiter (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            personal_id     TEXT,
            vorname         TEXT,
            nachname        TEXT,
            strasse         TEXT,
            hausnummer      TEXT,
            plz             INTEGER,
            ort             TEXT,
            geb_datum       TEXT,
            sv_nummer       TEXT,
            geschlecht      TEXT,
            email           TEXT,
            firmen_telefon  TEXT,
            privat_telefon  TEXT,
            iban            TEXT,
            bic             TEXT,
            position        TEXT,
            weitere_position TEXT,
            bemerkung       TEXT,
            status          INTEGER,
            password        TEXT,
            berechtigung    TEXT,
            urlaubsanspruch INTEGER,
            akt_wochen_std  INTEGER
        );

        CREATE TABLE IF NOT EXISTS standorte (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            ort          TEXT,
            kostenstelle INTEGER,
            strasse      TEXT,
            hausnummer   TEXT,
            plz          INTEGER
        );

        CREATE TABLE IF NOT EXISTS klassen (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            klasse         TEXT,
            mitarbeiter_id INTEGER NOT NULL,
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id)
        );

        CREATE TABLE IF NOT EXISTS dokumente (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            url          TEXT,
            upload_datum TEXT
        );

        CREATE TABLE IF NOT EXISTS mitarbeiter_dokumente (
            mitarbeiter_id INTEGER NOT NULL,
            dokument_id    INTEGER NOT NULL,
            PRIMARY KEY (mitarbeiter_id, dokument_id),
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id),
            FOREIGN KEY(dokument_id)    REFERENCES dokumente(id)
        );

        CREATE TABLE IF NOT EXISTS mitarbeiter_standorte (
            mitarbeiter_id INTEGER NOT NULL,
            standort_id    INTEGER NOT NULL,
            basis          INTEGER NOT NULL,
            PRIMARY KEY (mitarbeiter_id, standort_id),
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id),
            FOREIGN KEY(standort_id)    REFERENCES standorte(id)
        );

        CREATE TABLE IF NOT EXISTS standort_vertretung (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            standort_id  INTEGER NOT NULL,
            vertreter_id INTEGER NOT NULL,
            prioritaet   INTEGER NOT NULL
        );

        CREATE TABLE IF NOT EXISTS eintritt (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            mitarbeiter_id INTEGER NOT NULL,
            eintrittsdatum TEXT,
            std_woche      INTEGER,
            berufsjahr     INTEGER,
            einstufung     TEXT,
            offener_urlaub INTEGER,
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id)
        );

        CREATE TABLE IF NOT EXISTS abmeldung (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            mitarbeiter_id  INTEGER NOT NULL,
            datum_abmeldung TEXT,
            datum_anmeldung TEXT,
            resturlaub      INTEGER,
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id)
        );

        CREATE TABLE IF NOT EXISTS aenderungsmeldung (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            mitarbeiter_id  INTEGER NOT NULL,
            bearbeitet_von  INTEGER NOT NULL,
            datum           TEXT,
            std_woche       INTEGER,
            urlaubsanspruch INTEGER,
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id),
            FOREIGN KEY(bearbeitet_von) REFERENCES mitarbeiter(id)
        );

        CREATE TABLE IF NOT EXISTS taetigkeitsart (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            bezeichnung TEXT
        );

        CREATE TABLE IF NOT EXISTS taetigkeit (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            datum             TEXT,
            mitarbeiter_id    INTEGER NOT NULL,
            taetigkeitsart_id INTEGER NOT NULL,
            stunden           REAL,
            FOREIGN KEY(mitarbeiter_id)    REFERENCES mitarbeiter(id)    ON DELETE CASCADE,
            FOREIGN KEY(taetigkeitsart_id) REFERENCES taetigkeitsart(id) ON DELETE CASCADE,
            UNIQUE (datum, mitarbeiter_id, taetigkeitsart_id)
        );

        CREATE TABLE IF NOT EXISTS event (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            standort_id       INTEGER NOT NULL,
            start             TEXT,
            ende              TEXT,
            titel             TEXT,
            eventtyp          TEXT NOT NULL,
            klassen           TEXT,
            urlaub_akzeptabel INTEGER,
            in_urlaub         INTEGER NOT NULL,
            status            INTEGER NOT NULL,
            bemerkung         TEXT,
            FOREIGN KEY(standort_id) REFERENCES standorte(id)
        );

        CREATE TABLE IF NOT EXISTS urlaub (
            id                  INTEGER PRIMARY KEY AUTOINCREMENT,
            mitarbeiter_id      INTEGER NOT NULL,
            beginn              TEXT,
            ende                TEXT,
            tage_im_urlaub      INTEGER,
            beginn_in_worten    TEXT,
            ende_in_worten      TEXT,
            genehmigt           INTEGER,
            vertretung_id       INTEGER,
            buero               INTEGER,
            buero_vertretung_id INTEGER,
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id)
        );

        CREATE TABLE IF NOT EXISTS urlaubssperre (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            von         TEXT,
            bis         TEXT,
            ganzjaehrig INTEGER
        );

        CREATE TABLE IF NOT EXISTS urlaub_kommentar (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            urlaub_id      INTEGER NOT NULL,
            mitarbeiter_id INTEGER NOT NULL,
            kommentar      TEXT    NOT NULL,
            erstellt_am    DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(urlaub_id)      REFERENCES urlaub(id)      ON DELETE CASCADE,
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS urlaub_event (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id  INTEGER NOT NULL,
            urlaub_id INTEGER NOT NULL
        );

        CREATE TABLE IF NOT EXISTS uebertrag (
            mitarbeiter_id         INTEGER NOT NULL,
            datum                  TEXT    NOT NULL,
            uebertrag_urlaub       REAL,
            uebertrag_ueberstunden REAL,
            ang_wochen_std         REAL,
            monats_soll            REAL,
            PRIMARY KEY (mitarbeiter_id, datum),
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS vorlagen (
            id  INTEGER PRIMARY KEY AUTOINCREMENT,
            typ TEXT,
            url TEXT
        );

        CREATE TABLE IF NOT EXISTS zuschlag (
            mitarbeiter_id INTEGER NOT NULL,
            datum          TEXT    NOT NULL,
            gr10_pro_tag   REAL,
            wochenende     REAL,
            nacht          REAL,
            A              REAL,
            C              REAL,
            E              REAL,
            F              REAL,
            D              REAL,
            theorie        REAL,
            PRIMARY KEY (mitarbeiter_id, datum),
            FOREIGN KEY(mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS monatsbericht_view (
            mitarbeiter_id INTEGER,
            datum          TEXT,
            A              REAL,
            C              REAL,
            E              REAL,
            F              REAL,
            D              REAL,
            theorie        REAL,
            nacht          REAL,
            wochenende     REAL,
            gr10_pro_tag   REAL,
            lektion        REAL,
            regie          REAL,
            pruefung       REAL,
            krank          REAL,
            feiertag       REAL,
            urlaub_col     REAL
        );
        ";
        $db->exec($schema);
    }

    public static function reseed(): void {
        $pdo = self::getConnection();
        DatabaseSeeder::resetAndSeed($pdo);
        self::$instance = $pdo;
    }
}
