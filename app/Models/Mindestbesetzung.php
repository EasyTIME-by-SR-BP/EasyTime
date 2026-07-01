<?php

namespace App\Models;

use App\Core\AustrianHolidays;
use App\Core\Database;
use PDO;

class Mindestbesetzung {
    /** @var list<int> */
    public const WEEKDAYS = [1, 2, 3, 4, 5, 6, 7];

    public static function ensureSchema(PDO $db): void {
        if (Database::isMysql()) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS standort_mindestbesetzung (
                    standort_id INT NOT NULL,
                    weekday     TINYINT NOT NULL,
                    min_count   INT NOT NULL DEFAULT 0,
                    PRIMARY KEY (standort_id, weekday),
                    FOREIGN KEY (standort_id) REFERENCES standorte(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $db->exec("
                CREATE TABLE IF NOT EXISTS abteilung_mindestbesetzung (
                    abteilung_id INT NOT NULL,
                    weekday      TINYINT NOT NULL,
                    min_count    INT NOT NULL DEFAULT 0,
                    PRIMARY KEY (abteilung_id, weekday),
                    FOREIGN KEY (abteilung_id) REFERENCES abteilungen(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS standort_mindestbesetzung (
                    standort_id INTEGER NOT NULL,
                    weekday     INTEGER NOT NULL,
                    min_count   INTEGER NOT NULL DEFAULT 0,
                    PRIMARY KEY (standort_id, weekday),
                    FOREIGN KEY(standort_id) REFERENCES standorte(id) ON DELETE CASCADE
                )
            ");
            $db->exec("
                CREATE TABLE IF NOT EXISTS abteilung_mindestbesetzung (
                    abteilung_id INTEGER NOT NULL,
                    weekday      INTEGER NOT NULL,
                    min_count    INTEGER NOT NULL DEFAULT 0,
                    PRIMARY KEY (abteilung_id, weekday),
                    FOREIGN KEY(abteilung_id) REFERENCES abteilungen(id) ON DELETE CASCADE
                )
            ");
        }
    }

    public static function getGlobalMinimum(): int {
        return max(0, (int) Request::getSetting('min_staff_available', '1'));
    }

    /** @return array<int, array<int, int>> */
    public static function getStandortRules(): array {
        $db = Database::getConnection();
        if (!self::tableExists($db, 'standort_mindestbesetzung')) {
            return [];
        }
        $rules = [];
        foreach ($db->query('SELECT standort_id, weekday, min_count FROM standort_mindestbesetzung')->fetchAll() as $row) {
            $sid = (int) ($row['standort_id'] ?? 0);
            $dow = (int) ($row['weekday'] ?? 0);
            if ($sid > 0 && in_array($dow, self::WEEKDAYS, true)) {
                $rules[$sid][$dow] = max(0, (int) ($row['min_count'] ?? 0));
            }
        }
        return $rules;
    }

    /** @return array<int, array<int, int>> */
    public static function getAbteilungRules(): array {
        $db = Database::getConnection();
        if (!self::tableExists($db, 'abteilung_mindestbesetzung')) {
            return [];
        }
        $rules = [];
        foreach ($db->query('SELECT abteilung_id, weekday, min_count FROM abteilung_mindestbesetzung')->fetchAll() as $row) {
            $aid = (int) ($row['abteilung_id'] ?? 0);
            $dow = (int) ($row['weekday'] ?? 0);
            if ($aid > 0 && in_array($dow, self::WEEKDAYS, true)) {
                $rules[$aid][$dow] = max(0, (int) ($row['min_count'] ?? 0));
            }
        }
        return $rules;
    }

    /** @param array<int|string, array<int|string, int|string>> $submitted */
    public static function saveStandortRules(array $submitted): void {
        $db = Database::getConnection();
        self::ensureSchema($db);
        $db->exec('DELETE FROM standort_mindestbesetzung');
        $stmt = $db->prepare('INSERT INTO standort_mindestbesetzung (standort_id, weekday, min_count) VALUES (?, ?, ?)');
        foreach ($submitted as $standortId => $days) {
            $sid = (int) $standortId;
            if ($sid <= 0 || !is_array($days)) {
                continue;
            }
            foreach (self::WEEKDAYS as $dow) {
                $min = max(0, (int) ($days[$dow] ?? $days[(string) $dow] ?? 0));
                if ($min > 0) {
                    $stmt->execute([$sid, $dow, $min]);
                }
            }
        }
    }

    /** @param array<int|string, array<int|string, int|string>> $submitted */
    public static function saveAbteilungRules(array $submitted): void {
        $db = Database::getConnection();
        self::ensureSchema($db);
        $db->exec('DELETE FROM abteilung_mindestbesetzung');
        $stmt = $db->prepare('INSERT INTO abteilung_mindestbesetzung (abteilung_id, weekday, min_count) VALUES (?, ?, ?)');
        foreach ($submitted as $abteilungId => $days) {
            $aid = (int) $abteilungId;
            if ($aid <= 0 || !is_array($days)) {
                continue;
            }
            foreach (self::WEEKDAYS as $dow) {
                $min = max(0, (int) ($days[$dow] ?? $days[(string) $dow] ?? 0));
                if ($min > 0) {
                    $stmt->execute([$aid, $dow, $min]);
                }
            }
        }
    }

    public static function passes(int $requestUserId, string $startDate, string $endDate, ?int $ignoreRequestId = null): bool {
        return self::collectIssues($requestUserId, $startDate, $endDate, $ignoreRequestId, false) === [];
    }

    /** @return list<string> */
    public static function getWarnings(int $requestUserId, string $startDate, string $endDate, ?int $ignoreRequestId = null): array {
        return self::collectIssues($requestUserId, $startDate, $endDate, $ignoreRequestId, true);
    }

    /** @return list<string> */
    public static function getBlockingIssues(int $requestUserId, string $startDate, string $endDate, ?int $ignoreRequestId = null): array {
        return self::collectIssues($requestUserId, $startDate, $endDate, $ignoreRequestId, false);
    }

    /**
     * @return list<string>
     */
    private static function collectIssues(int $requestUserId, string $startDate, string $endDate, ?int $ignoreRequestId, bool $includePending): array {
        $issues = [];
        $db = Database::getConnection();
        $userStandorte = self::getUserStandortIds($requestUserId);
        $userAbteilungen = self::getUserAbteilungIds($requestUserId);
        $standortRules = self::getStandortRules();
        $abteilungRules = self::getAbteilungRules();
        $globalMin = self::getGlobalMinimum();
        $holidays = self::holidaysForRange($startDate, $endDate);

        $current = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        while ($current <= $end) {
            $day = $current->format('Y-m-d');
            $dow = (int) $current->format('N');
            if (isset($holidays[$day])) {
                $current->modify('+1 day');
                continue;
            }

            if ($globalMin > 0) {
                $total = self::countEmployees($db, null, null);
                $absent = self::countAbsent($db, $day, $requestUserId, $ignoreRequestId, $includePending, null, null);
                $available = $total - $absent;
                if ($available < $globalMin) {
                    $issues[] = self::formatIssue('global', $day, $available, $globalMin);
                }
            }

            foreach ($userStandorte as $standortId) {
                $min = (int) ($standortRules[$standortId][$dow] ?? 0);
                if ($min <= 0) {
                    continue;
                }
                $total = self::countEmployees($db, $standortId, null);
                if ($total === 0) {
                    continue;
                }
                $absent = self::countAbsent($db, $day, $requestUserId, $ignoreRequestId, $includePending, $standortId, null);
                $available = $total - $absent;
                if ($available < $min) {
                    $label = self::getStandortLabel($standortId);
                    $issues[] = self::formatIssue('standort', $day, $available, $min, $label);
                }
            }

            foreach ($userAbteilungen as $abteilungId) {
                $min = (int) ($abteilungRules[$abteilungId][$dow] ?? 0);
                if ($min <= 0) {
                    continue;
                }
                $total = self::countEmployees($db, null, $abteilungId);
                if ($total === 0) {
                    continue;
                }
                $absent = self::countAbsent($db, $day, $requestUserId, $ignoreRequestId, $includePending, null, $abteilungId);
                $available = $total - $absent;
                if ($available < $min) {
                    $label = self::getAbteilungLabel($abteilungId);
                    $issues[] = self::formatIssue('abteilung', $day, $available, $min, $label);
                }
            }

            $current->modify('+1 day');
        }

        return array_values(array_unique($issues));
    }

    private static function formatIssue(string $type, string $day, int $available, int $required, string $label = ''): string {
        $formattedDay = implode('.', array_reverse(explode('-', $day)));
        $replace = static fn (string $template): string => str_replace(
            ['{available}', '{required}'],
            [(string) $available, (string) $required],
            $template
        );
        if ($type === 'global') {
            return $formattedDay . ': ' . $replace(\App\Core\I18n::get('coverage.issue_global'));
        }
        return $formattedDay . ' · ' . $label . ': ' . $replace(\App\Core\I18n::get('coverage.issue_group'));
    }

    private static function countEmployees(PDO $db, ?int $standortId, ?int $abteilungId): int {
        $joins = '';
        $where = "LOWER(COALESCE(m.berechtigung, '')) NOT IN ('administrator', 'admin', 'ceo')";
        if ($standortId !== null) {
            $joins .= ' INNER JOIN mitarbeiter_standorte ms ON ms.mitarbeiter_id = m.id';
            $where .= ' AND ms.standort_id = ' . (int) $standortId;
        }
        if ($abteilungId !== null) {
            $joins .= ' INNER JOIN mitarbeiter_abteilungen ma ON ma.mitarbeiter_id = m.id';
            $where .= ' AND ma.abteilung_id = ' . (int) $abteilungId;
        }
        $sql = "SELECT COUNT(DISTINCT m.id) FROM mitarbeiter m{$joins} WHERE {$where}";
        return (int) $db->query($sql)->fetchColumn();
    }

    private static function countAbsent(
        PDO $db,
        string $day,
        int $requestUserId,
        ?int $ignoreRequestId,
        bool $includePending,
        ?int $standortId,
        ?int $abteilungId
    ): int {
        $approvedFlags = $includePending ? '0, 1, 3, 5' : '1';
        $ignoreClause = ($ignoreRequestId !== null) ? ' AND u.id != ' . (int) $ignoreRequestId : '';

        $joins = '';
        $scope = '';
        if ($standortId !== null) {
            $joins .= ' INNER JOIN mitarbeiter_standorte ms ON ms.mitarbeiter_id = u.mitarbeiter_id';
            $scope .= ' AND ms.standort_id = ' . (int) $standortId;
        }
        if ($abteilungId !== null) {
            $joins .= ' INNER JOIN mitarbeiter_abteilungen ma ON ma.mitarbeiter_id = u.mitarbeiter_id';
            $scope .= ' AND ma.abteilung_id = ' . (int) $abteilungId;
        }

        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT u.mitarbeiter_id)
            FROM urlaub u
            {$joins}
            WHERE COALESCE(u.genehmigt, 0) IN ({$approvedFlags})
              AND u.beginn <= :day AND u.ende >= :day
              AND u.mitarbeiter_id != :uid
              {$scope}
              {$ignoreClause}
        ");
        $stmt->execute([':day' => $day, ':uid' => $requestUserId]);
        return (int) $stmt->fetchColumn() + 1;
    }

    /** @return list<int> */
    private static function getUserStandortIds(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT standort_id FROM mitarbeiter_standorte WHERE mitarbeiter_id = ?');
        $stmt->execute([$userId]);
        return array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    }

    /** @return list<int> */
    private static function getUserAbteilungIds(int $userId): array {
        $db = Database::getConnection();
        if (!self::tableExists($db, 'mitarbeiter_abteilungen')) {
            return [];
        }
        $stmt = $db->prepare('SELECT abteilung_id FROM mitarbeiter_abteilungen WHERE mitarbeiter_id = ?');
        $stmt->execute([$userId]);
        return array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    }

    private static function getStandortLabel(int $standortId): string {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT ort FROM standorte WHERE id = ? LIMIT 1');
        $stmt->execute([$standortId]);
        return (string) ($stmt->fetchColumn() ?: ('#' . $standortId));
    }

    private static function getAbteilungLabel(int $abteilungId): string {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT bezeichnung FROM abteilungen WHERE id = ? LIMIT 1');
        $stmt->execute([$abteilungId]);
        return (string) ($stmt->fetchColumn() ?: ('#' . $abteilungId));
    }

    /** @return array<string, true> */
    private static function holidaysForRange(string $startDate, string $endDate): array {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $holidays = [];
        for ($year = (int) $start->format('Y'); $year <= (int) $end->format('Y'); $year++) {
            foreach (AustrianHolidays::getDatesForYear($year) as $holiday) {
                $holidays[$holiday] = true;
            }
        }
        return $holidays;
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
