<?php

namespace App\Core;

use App\Models\Notification;
use App\Models\RequestEvent;
use App\Services\Inbox;
use PDO;

/**
 * Demo-/Testdaten für lokale Entwicklung (SQLite) und Produktion (MariaDB).
 */
class DatabaseSeeder {
    public const DEMO_PASSWORD = 'easytime';

    /** @var list<array<string, mixed>> */
    private const USERS = [
        ['id' => 1,  'personal_id' => 'A001', 'vorname' => 'Stefan',  'nachname' => 'Reich',   'email' => 'a001@demo.easytime.at', 'position' => 'Leitung',           'berechtigung' => 'Administrator', 'urlaubsanspruch' => 240, 'akt_wochen_std' => 40, 'klasse' => 'B - Personenkraftwagen'],
        ['id' => 2,  'personal_id' => 'A002', 'vorname' => 'Maria',   'nachname' => 'Höll',    'email' => 'a002@demo.easytime.at', 'position' => 'Geschäftsführung',  'berechtigung' => 'Administrator', 'urlaubsanspruch' => 240, 'akt_wochen_std' => 40, 'klasse' => 'B - Personenkraftwagen'],
        ['id' => 3,  'personal_id' => 'M001', 'vorname' => 'Anna',    'nachname' => 'Berger',  'email' => 'm001@demo.easytime.at', 'position' => 'Fahrlehrerin',      'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 38, 'klasse' => 'B - Personenkraftwagen'],
        ['id' => 4,  'personal_id' => 'M002', 'vorname' => 'Lukas',   'nachname' => 'Gruber',  'email' => 'm002@demo.easytime.at', 'position' => 'Fahrlehrer',        'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 40, 'klasse' => 'B - Personenkraftwagen'],
        ['id' => 5,  'personal_id' => 'M003', 'vorname' => 'Sophie',  'nachname' => 'Wagner',  'email' => 'm003@demo.easytime.at', 'position' => 'Fahrlehrerin',      'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 38, 'klasse' => 'A - Motorrad'],
        ['id' => 6,  'personal_id' => 'M004', 'vorname' => 'Thomas',  'nachname' => 'Hofer',   'email' => 'm004@demo.easytime.at', 'position' => 'Fahrlehrer',        'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 40, 'klasse' => 'CE - Lastkraftwagen Anhänger'],
        ['id' => 7,  'personal_id' => 'M005', 'vorname' => 'Julia',   'nachname' => 'Kern',    'email' => 'm005@demo.easytime.at', 'position' => 'Verwaltung',        'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 38, 'klasse' => 'B - Personenkraftwagen'],
        ['id' => 8,  'personal_id' => 'M006', 'vorname' => 'Markus',  'nachname' => 'Pichler', 'email' => 'm006@demo.easytime.at', 'position' => 'Fahrlehrer',        'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 40, 'klasse' => 'B - Personenkraftwagen'],
        ['id' => 9,  'personal_id' => 'M007', 'vorname' => 'Eva',     'nachname' => 'Schuster','email' => 'm007@demo.easytime.at', 'position' => 'Fahrlehrerin',      'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 38, 'klasse' => 'B - Personenkraftwagen'],
        ['id' => 10, 'personal_id' => 'M008', 'vorname' => 'Daniel',  'nachname' => 'Fuchs',   'email' => 'm008@demo.easytime.at', 'position' => 'Fahrlehrer',        'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 40, 'klasse' => 'D - Autobus'],
        ['id' => 11, 'personal_id' => 'M009', 'vorname' => 'Laura',   'nachname' => 'Brandl',  'email' => 'm009@demo.easytime.at', 'position' => 'Fahrlehrerin',      'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 38, 'klasse' => 'A - Motorrad'],
        ['id' => 12, 'personal_id' => 'M010', 'vorname' => 'Michael', 'nachname' => 'Ortner',  'email' => 'm010@demo.easytime.at', 'position' => 'Fahrlehrer',        'berechtigung' => 'Mitarbeiter',   'urlaubsanspruch' => 200, 'akt_wochen_std' => 40, 'klasse' => 'B - Personenkraftwagen'],
    ];

    public static function credentialsHelp(): string {
        $lines = [
            'Test-Zugänge (Personal-ID oder E-Mail, Passwort für alle: ' . self::DEMO_PASSWORD . ')',
            '',
            'Administratoren:',
            '  A001  Stefan Reich   (a001@demo.easytime.at)',
            '  A002  Maria Höll     (a002@demo.easytime.at)',
            '',
            'Mitarbeiter:',
        ];
        foreach (self::USERS as $user) {
            if (($user['berechtigung'] ?? '') !== 'Mitarbeiter') {
                continue;
            }
            $lines[] = sprintf(
                '  %s  %s %s  (%s)',
                $user['personal_id'],
                $user['vorname'],
                $user['nachname'],
                $user['email']
            );
        }
        return implode("\n", $lines);
    }

    public static function seedFreshDatabase(PDO $db): void {
        self::seedUsers($db);
        self::seedDemoData($db);
    }

    public static function resetAndSeed(PDO $db): void {
        self::clearAllData($db);
        self::seedUsers($db);
        self::seedDemoData($db);
        self::resetAutoIncrement($db);
    }

    private static function clearAllData(PDO $db): void {
        $tables = [
            'notifications',
            'urlaub_ereignis',
            'urlaub_kommentar',
            'urlaub_event',
            'urlaub',
            'taetigkeit',
            'zuschlag',
            'uebertrag',
            'monatsbericht_view',
            'event',
            'urlaubssperre',
            'standort_vertretung',
            'klassen',
            'eintritt',
            'abmeldung',
            'aenderungsmeldung',
            'mitarbeiter_dokumente',
            'dokumente',
            'mitarbeiter_standorte',
            'vorlagen',
            'standorte',
            'taetigkeitsart',
            'app_settings',
            'mitarbeiter',
        ];

        if (Database::isMysql()) {
            $db->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $table) {
                if (!self::tableExists($db, $table)) {
                    continue;
                }
                $db->exec("TRUNCATE TABLE `{$table}`");
            }
            $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            return;
        }

        $db->exec('PRAGMA foreign_keys = OFF');
        foreach ($tables as $table) {
            if (!self::tableExists($db, $table)) {
                continue;
            }
            $db->exec("DELETE FROM {$table}");
        }
        $db->exec('PRAGMA foreign_keys = ON');
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

    private static function seedUsers(PDO $db): void {
        if (Database::isMysql()) {
            $stmt = $db->prepare("
                INSERT INTO mitarbeiter (
                    id, personal_id, vorname, nachname, email, position, status,
                    password, berechtigung, urlaubsanspruch, akt_wochen_std
                ) VALUES (
                    :id, :personal_id, :vorname, :nachname, :email, :position, 0,
                    :password, :berechtigung, :urlaubsanspruch, :akt_wochen_std
                )
            ");
        } else {
            $stmt = $db->prepare("
                INSERT OR REPLACE INTO mitarbeiter (
                    id, personal_id, vorname, nachname, email, position, status,
                    password, berechtigung, urlaubsanspruch, akt_wochen_std
                ) VALUES (
                    :id, :personal_id, :vorname, :nachname, :email, :position, 0,
                    :password, :berechtigung, :urlaubsanspruch, :akt_wochen_std
                )
            ");
        }

        foreach (self::USERS as $user) {
            $stmt->execute([
                'id'              => $user['id'],
                'personal_id'     => $user['personal_id'],
                'vorname'         => $user['vorname'],
                'nachname'        => $user['nachname'],
                'email'           => $user['email'],
                'position'        => $user['position'],
                'password'        => self::DEMO_PASSWORD,
                'berechtigung'    => $user['berechtigung'],
                'urlaubsanspruch' => $user['urlaubsanspruch'],
                'akt_wochen_std'  => $user['akt_wochen_std'],
            ]);
        }
    }

    private static function seedDemoData(PDO $db): void {
        $today = new \DateTimeImmutable('today');
        $d = static fn (string $modifier): string => $today->modify($modifier)->format('Y-m-d');
        $yearStart = $today->format('Y') . '-01-01';

        $classId = 1;
        foreach (self::USERS as $user) {
            $db->prepare('INSERT INTO klassen (id, klasse, mitarbeiter_id) VALUES (?, ?, ?)')
                ->execute([$classId, $user['klasse'], $user['id']]);
            $classId++;
        }

        $db->exec("
            INSERT INTO standorte (id, ort, kostenstelle, strasse, hausnummer, plz) VALUES
            (1, 'Ybbs an der Donau', 11, 'Gewerbestraße', '14', 3370),
            (2, 'Pöchlarn', 11, 'Regensburgerstraße', '14', 3380),
            (3, 'Wieselburg an der Erlauf', 11, 'Anton-Fahrner-Gasse', '2', 3250),
            (4, 'Gmünd', 21, 'Bahnhofstraße', '21', 3950),
            (5, 'Horn', 31, 'Am Kuhberg', '5', 3580),
            (6, 'St. Pölten', 61, 'Hofstatt', '5', 3100)
        ");

        $db->exec("
            INSERT INTO standort_vertretung (id, standort_id, vertreter_id, prioritaet) VALUES
            (1, 1, 3, 1), (2, 1, 4, 2),
            (3, 2, 5, 1), (4, 2, 6, 2),
            (5, 3, 7, 1), (6, 3, 8, 2),
            (7, 4, 9, 1), (8, 5, 10, 1), (9, 6, 11, 1)
        ");

        $db->exec("
            INSERT INTO taetigkeitsart (id, bezeichnung) VALUES
            (1, 'lektion'), (3, 'regie'), (4, 'pruefung'),
            (5, 'krank'), (6, 'feiertag'), (7, 'urlaub')
        ");

        $eintrittRows = [
            [1, 1, '2018-01-15', 40, 8, 'KV Admin', 0],
            [2, 2, '2019-03-01', 40, 7, 'KV Admin', 0],
            [3, 3, '2021-04-12', 38, 5, 'KV Fahrlehrer', 5],
            [4, 4, '2020-09-01', 40, 6, 'KV Fahrlehrer', 3],
            [5, 5, '2022-01-10', 38, 4, 'KV Fahrlehrer', 2],
            [6, 6, '2019-11-20', 40, 6, 'KV Fahrlehrer', 4],
            [7, 7, '2023-02-01', 38, 3, 'KV Büro', 0],
            [8, 8, '2020-06-15', 40, 6, 'KV Fahrlehrer', 1],
            [9, 9, '2021-08-23', 38, 5, 'KV Fahrlehrer', 6],
            [10, 10, '2018-12-03', 40, 7, 'KV Fahrlehrer', 2],
            [11, 11, '2022-07-11', 38, 4, 'KV Fahrlehrer', 0],
            [12, 12, '2023-09-18', 40, 3, 'KV Fahrlehrer', 0],
        ];
        $eintrittStmt = $db->prepare('
            INSERT INTO eintritt (id, mitarbeiter_id, eintrittsdatum, std_woche, berufsjahr, einstufung, offener_urlaub)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        foreach ($eintrittRows as $row) {
            $eintrittStmt->execute($row);
        }

        $db->exec("
            INSERT INTO mitarbeiter_standorte (mitarbeiter_id, standort_id, basis) VALUES
            (1, 1, 1), (2, 1, 1), (3, 1, 1), (4, 2, 1), (5, 3, 1),
            (6, 1, 1), (7, 6, 1), (8, 2, 1), (9, 3, 1), (10, 4, 1),
            (11, 5, 1), (12, 1, 1)
        ");

        $db->prepare("
            INSERT INTO event (id, standort_id, start, ende, titel, bemerkung, klassen, urlaub_akzeptabel, in_urlaub, eventtyp, status)
            VALUES (1, 1, ?, ?, 'Team-Schulung Fahrlehrer', 'Interne Abstimmung Q3', 'B - Personenkraftwagen', 1, 0, 'Theorie', 0)
        ")->execute([$d('+12 days'), $d('+12 days')]);

        $vacations = [
            ['id' => 1,  'user' => 3,  'start' => $d('+10 days'), 'end' => $d('+14 days'), 'days' => 5, 'flag' => 0, 'events' => ['created']],
            ['id' => 2,  'user' => 4,  'start' => $d('+20 days'), 'end' => $d('+24 days'), 'days' => 5, 'flag' => 1, 'events' => ['created', 'approved']],
            ['id' => 3,  'user' => 5,  'start' => $d('+30 days'), 'end' => $d('+34 days'), 'days' => 5, 'flag' => 3, 'events' => ['created', 'approved', 'storno_requested']],
            ['id' => 4,  'user' => 6,  'start' => $d('+7 days'),  'end' => $d('+9 days'),  'days' => 3, 'flag' => 0, 'events' => ['created']],
            ['id' => 5,  'user' => 7,  'start' => $d('-20 days'), 'end' => $d('-16 days'), 'days' => 5, 'flag' => 1, 'events' => ['created', 'approved']],
            ['id' => 6,  'user' => 8,  'start' => $d('+45 days'), 'end' => $d('+48 days'), 'days' => 4, 'flag' => 2, 'events' => ['created', 'rejected']],
            ['id' => 7,  'user' => 9,  'start' => $d('+55 days'), 'end' => $d('+59 days'), 'days' => 5, 'flag' => 1, 'events' => ['created', 'approved']],
            ['id' => 8,  'user' => 10, 'start' => $d('-40 days'), 'end' => $d('-37 days'), 'days' => 4, 'flag' => 4, 'events' => ['created', 'approved', 'cancelled']],
            ['id' => 9,  'user' => 11, 'start' => $d('+70 days'), 'end' => $d('+74 days'), 'days' => 5, 'flag' => 1, 'events' => ['created', 'approved']],
            ['id' => 10, 'user' => 12, 'start' => $d('+3 days'),  'end' => $d('+5 days'),  'days' => 3, 'flag' => 0, 'events' => ['created']],
            ['id' => 11, 'user' => 4,  'start' => $d('-10 days'), 'end' => $d('-8 days'),  'days' => 3, 'flag' => 1, 'events' => ['created', 'approved']],
        ];

        $vacStmt = $db->prepare('
            INSERT INTO urlaub (id, mitarbeiter_id, beginn, ende, tage_im_urlaub, beginn_in_worten, ende_in_worten, vertretung_id, buero, buero_vertretung_id, genehmigt)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 1, NULL, ?)
        ');
        foreach ($vacations as $vac) {
            $vacStmt->execute([
                $vac['id'],
                $vac['user'],
                $vac['start'],
                $vac['end'],
                $vac['days'],
                'Demo',
                'Demo',
                $vac['flag'],
            ]);
        }

        $db->exec('INSERT INTO urlaub_event (id, event_id, urlaub_id) VALUES (1, 1, 2)');
        $db->exec("INSERT INTO urlaubssperre (id, von, bis, ganzjaehrig) VALUES (1, '2025-12-24', '2026-01-06', 0)");

        $taetStmt = $db->prepare('
            INSERT INTO taetigkeit (id, datum, mitarbeiter_id, taetigkeitsart_id, stunden)
            VALUES (?, ?, ?, 1, ?)
        ');
        $taetStmt->execute([1, $d('-1 day'), 3, 7.5]);
        $taetStmt->execute([2, $d('-1 day'), 4, 8.0]);
        $taetStmt->execute([3, $d('-2 days'), 5, 6.0]);
        $taetStmt->execute([4, $d('-3 days'), 8, 7.0]);

        $ueberStmt = $db->prepare('
            INSERT INTO uebertrag (mitarbeiter_id, datum, uebertrag_urlaub, uebertrag_ueberstunden, ang_wochen_std, monats_soll)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $ueberData = [
            [3, 2.5, 4.0, 38.0, 152.0],
            [4, 1.0, 2.0, 40.0, 160.0],
            [5, 3.0, 1.5, 38.0, 152.0],
            [7, 0.5, 6.0, 38.0, 152.0],
            [9, 2.0, 3.5, 38.0, 152.0],
        ];
        foreach ($ueberData as [$uid, $urlaub, $ueStd, $wochen, $soll]) {
            $ueberStmt->execute([$uid, $yearStart, $urlaub, $ueStd, $wochen, $soll]);
        }

        $db->prepare('
            INSERT INTO zuschlag (mitarbeiter_id, datum, gr10_pro_tag, wochenende, nacht, A, C, E, F, D, theorie)
            VALUES (?, ?, 0.5, 0, 0, 0, 0, 0, 0, 0, 0.25)
        ')->execute([4, $d('-2 days')]);

        if (Database::isMysql()) {
            $db->exec("INSERT IGNORE INTO app_settings (`key`, `value`) VALUES ('min_staff_available', '1'), ('max_fenstertage', '0')");
        } else {
            $db->exec("INSERT OR IGNORE INTO app_settings (key, value) VALUES ('min_staff_available', '1'), ('max_fenstertage', '0')");
        }

        if (self::tableExists($db, 'urlaub_ereignis')) {
            foreach ($vacations as $vac) {
                $actor = $vac['user'];
                foreach ($vac['events'] as $eventType) {
                    $eventActor = in_array($eventType, ['approved', 'rejected', 'cancelled'], true) ? 1 : $actor;
                    RequestEvent::log((int) $vac['id'], $eventActor, $eventType);
                }
            }
        }

        if (self::tableExists($db, 'notifications')) {
            Notification::create(1, 'Offene Urlaubsanträge', '3 neue Anträge warten auf Freigabe.', 'approval', [
                'type' => Inbox::TYPE_TASK,
                'resolution_mode' => Inbox::RESOLUTION_SHARED,
                'thread_id' => 'vacation-approval',
                'action_url' => '/?tab=operations',
            ]);
            Notification::create(3, 'Urlaub beantragt', 'Dein Antrag vom ' . $d('+10 days') . ' wurde eingereicht.', 'info');
            Notification::create(5, 'Storno beantragt', 'Dein Storno für ' . $d('+30 days') . ' wird geprüft.', 'info');
            Notification::create(8, 'Antrag abgelehnt', 'Dein Urlaub ' . $d('+45 days') . ' wurde abgelehnt.', 'rejected');
        }
    }

    private static function resetAutoIncrement(PDO $db): void {
        if (Database::isMysql()) {
            $tables = [
                'mitarbeiter' => 12,
                'klassen' => 12,
                'standorte' => 6,
                'standort_vertretung' => 9,
                'eintritt' => 12,
                'taetigkeitsart' => 7,
                'event' => 1,
                'urlaub' => 11,
                'urlaubssperre' => 1,
                'urlaub_event' => 1,
                'taetigkeit' => 4,
            ];
            foreach ($tables as $table => $maxId) {
                if (!self::tableExists($db, $table)) {
                    continue;
                }
                $db->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = " . ($maxId + 1));
            }
            return;
        }

        $tables = [
            'klassen', 'standorte', 'standort_vertretung', 'eintritt', 'event',
            'urlaub', 'urlaubssperre', 'urlaub_event', 'taetigkeit', 'taetigkeitsart',
        ];
        foreach ($tables as $table) {
            if (!self::tableExists($db, $table)) {
                continue;
            }
            $max = (int) $db->query("SELECT COALESCE(MAX(id), 0) FROM {$table}")->fetchColumn();
            $db->exec('DELETE FROM sqlite_sequence WHERE name = ' . $db->quote($table));
            if ($max > 0) {
                $db->exec('INSERT INTO sqlite_sequence (name, seq) VALUES (' . $db->quote($table) . ", {$max})");
            }
        }
    }
}
