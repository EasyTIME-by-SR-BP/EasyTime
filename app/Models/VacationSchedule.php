<?php

namespace App\Models;

use App\Core\AustrianHolidays;
use App\Core\Database;
use PDO;

class VacationSchedule {
    public const DEFAULT_DAY_MINUTES = 480;
    public const DEFAULT_WORK_START = '08:00';
    public const DEFAULT_WORK_END = '16:00';

    public static function ensureSchema(PDO $db): void {
        if (!Database::columnExists($db, 'urlaub', 'ist_ganztag')) {
            $type = Database::isMysql() ? 'TINYINT NOT NULL DEFAULT 1' : 'INTEGER NOT NULL DEFAULT 1';
            $db->exec("ALTER TABLE urlaub ADD COLUMN ist_ganztag {$type}");
        }
        if (!Database::columnExists($db, 'urlaub', 'minuten_abwesend')) {
            $type = Database::isMysql() ? 'INT NULL' : 'INTEGER DEFAULT NULL';
            $db->exec("ALTER TABLE urlaub ADD COLUMN minuten_abwesend {$type}");
        }
        if (!Database::columnExists($db, 'urlaub', 'wunsch_plan_json')) {
            $type = Database::isMysql() ? 'TEXT NULL' : 'TEXT DEFAULT NULL';
            $db->exec("ALTER TABLE urlaub ADD COLUMN wunsch_plan_json {$type}");
        }

        if (Database::isMysql()) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS urlaub_tagesplan (
                    id           INT AUTO_INCREMENT PRIMARY KEY,
                    urlaub_id    INT NOT NULL,
                    datum        DATE NOT NULL,
                    von_uhrzeit  TIME NULL,
                    bis_uhrzeit  TIME NULL,
                    minuten      INT NOT NULL DEFAULT 0,
                    ist_ganztag  TINYINT NOT NULL DEFAULT 1,
                    UNIQUE KEY uq_urlaub_datum (urlaub_id, datum),
                    FOREIGN KEY (urlaub_id) REFERENCES urlaub(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS urlaub_tagesplan (
                    id           INTEGER PRIMARY KEY AUTOINCREMENT,
                    urlaub_id    INTEGER NOT NULL,
                    datum        TEXT NOT NULL,
                    von_uhrzeit  TEXT NULL,
                    bis_uhrzeit  TEXT NULL,
                    minuten      INTEGER NOT NULL DEFAULT 0,
                    ist_ganztag  INTEGER NOT NULL DEFAULT 1,
                    UNIQUE(urlaub_id, datum),
                    FOREIGN KEY(urlaub_id) REFERENCES urlaub(id) ON DELETE CASCADE
                )
            ");
        }
    }

    public static function getDailyWorkMinutes(int $userId): int {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT akt_wochen_std FROM mitarbeiter WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $weekly = (int) ($stmt->fetchColumn() ?: 0);
        if ($weekly <= 0) {
            return self::DEFAULT_DAY_MINUTES;
        }
        return max(60, (int) round(($weekly / 5) * 60));
    }

    /** @return list<string> Mon–Fri excluding AT holidays (for Urlaubstage counting). */
    public static function listWorkdaysInRange(string $startDate, string $endDate): array {
        if ($startDate === '' || $endDate === '' || $endDate < $startDate) {
            return [];
        }
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $holidays = [];
        for ($year = (int) $start->format('Y'); $year <= (int) $end->format('Y'); $year++) {
            foreach (AustrianHolidays::getDatesForYear($year) as $holiday) {
                $holidays[$holiday] = true;
            }
        }
        $days = [];
        $current = clone $start;
        while ($current <= $end) {
            $ymd = $current->format('Y-m-d');
            $dow = (int) $current->format('N');
            if ($dow <= 5 && !isset($holidays[$ymd])) {
                $days[] = $ymd;
            }
            $current->modify('+1 day');
        }
        return $days;
    }

    /** @return list<string> Every calendar day in range (incl. weekends). */
    public static function listCalendarDaysInRange(string $startDate, string $endDate): array {
        if ($startDate === '' || $endDate === '' || $endDate < $startDate) {
            return [];
        }
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $days = [];
        $current = clone $start;
        while ($current <= $end) {
            $days[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
        return $days;
    }

    /**
     * @return list<array{date: string, from: string, to: string, full_day: bool, minutes: int}>
     */
    public static function buildDefaultSegments(int $userId, string $startDate, string $endDate): array {
        $dailyMinutes = $userId > 0 ? self::getDailyWorkMinutes($userId) : self::DEFAULT_DAY_MINUTES;
        $from = self::DEFAULT_WORK_START;
        $to = self::minutesToTime(self::timeToMinutes($from) + $dailyMinutes);
        $segments = [];
        foreach (self::listCalendarDaysInRange($startDate, $endDate) as $date) {
            $segments[] = [
                'date' => $date,
                'from' => $from,
                'to' => $to,
                'full_day' => true,
                'minutes' => $dailyMinutes,
            ];
        }
        return $segments;
    }

    /**
     * @param mixed $raw
     * @return list<array{date: string, from: string, to: string, full_day: bool, minutes: int}>
     */
    public static function parseSubmittedSegments(int $userId, string $startDate, string $endDate, $raw, bool $isFullDay): array {
        if ($isFullDay) {
            return self::buildDefaultSegments($userId, $startDate, $endDate);
        }
        if (!is_array($raw)) {
            return self::buildDefaultSegments($userId, $startDate, $endDate);
        }
        $allowed = array_fill_keys(self::listCalendarDaysInRange($startDate, $endDate), true);
        $dailyMinutes = self::getDailyWorkMinutes($userId);
        $segments = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $date = (string) ($row['date'] ?? '');
            if ($date === '' || !isset($allowed[$date])) {
                continue;
            }
            $fullDay = !empty($row['full_day']);
            if ($fullDay) {
                $from = self::DEFAULT_WORK_START;
                $to = self::minutesToTime(self::timeToMinutes($from) + $dailyMinutes);
                $minutes = $dailyMinutes;
            } else {
                $fromRaw = trim((string) ($row['from'] ?? ''));
                $toRaw = trim((string) ($row['to'] ?? ''));
                $hasFrom = $fromRaw !== '';
                $hasTo = $toRaw !== '';
                $workEnd = self::minutesToTime(self::timeToMinutes(self::DEFAULT_WORK_START) + $dailyMinutes);
                if (!$hasFrom && !$hasTo) {
                    $from = self::DEFAULT_WORK_START;
                    $to = $workEnd;
                    $minutes = $dailyMinutes;
                    $fullDay = true;
                } else {
                    $from = $hasFrom ? self::normalizeTime($fromRaw) : self::DEFAULT_WORK_START;
                    $to = $hasTo ? self::normalizeTime($toRaw) : $workEnd;
                    $minutes = max(0, self::timeToMinutes($to) - self::timeToMinutes($from));
                }
            }
            if ($minutes <= 0) {
                continue;
            }
            $segments[] = [
                'date' => $date,
                'from' => $from,
                'to' => $to,
                'full_day' => $fullDay,
                'minutes' => $minutes,
            ];
        }
        usort($segments, static fn (array $a, array $b): int => strcmp($a['date'], $b['date']));
        return $segments;
    }

    /** @param list<array{date: string, from: string, to: string, full_day: bool, minutes: int}> $segments */
    public static function totalMinutes(array $segments): int {
        $sum = 0;
        foreach ($segments as $segment) {
            $sum += (int) ($segment['minutes'] ?? 0);
        }
        return $sum;
    }

    public static function minutesToDayEquivalent(int $minutes, int $userId): int {
        $daily = self::getDailyWorkMinutes($userId);
        if ($minutes <= 0 || $daily <= 0) {
            return 0;
        }
        return max(1, (int) ceil($minutes / $daily));
    }

    /** @param list<array{date: string, from: string, to: string, full_day: bool, minutes: int}> $segments */
    public static function saveForRequest(int $urlaubId, array $segments, bool $isFullDay): void {
        $db = Database::getConnection();
        self::ensureSchema($db);
        $total = self::totalMinutes($segments);
        $stmt = $db->prepare('UPDATE urlaub SET ist_ganztag = ?, minuten_abwesend = ? WHERE id = ?');
        $stmt->execute([$isFullDay ? 1 : 0, $total, $urlaubId]);

        $db->prepare('DELETE FROM urlaub_tagesplan WHERE urlaub_id = ?')->execute([$urlaubId]);
        if ($segments === []) {
            return;
        }
        $insert = $db->prepare('
            INSERT INTO urlaub_tagesplan (urlaub_id, datum, von_uhrzeit, bis_uhrzeit, minuten, ist_ganztag)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        foreach ($segments as $segment) {
            $insert->execute([
                $urlaubId,
                $segment['date'],
                $segment['from'],
                $segment['to'],
                (int) $segment['minutes'],
                !empty($segment['full_day']) ? 1 : 0,
            ]);
        }
    }

    /** @return array<int, list<array{date: string, from: string, to: string, full_day: bool, minutes: int}>> */
    public static function getByRequestIds(array $requestIds): array {
        if ($requestIds === []) {
            return [];
        }
        $db = Database::getConnection();
        if (!Database::tableExists($db, 'urlaub_tagesplan')) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
        $stmt = $db->prepare("
            SELECT urlaub_id, datum, von_uhrzeit, bis_uhrzeit, minuten, ist_ganztag
            FROM urlaub_tagesplan
            WHERE urlaub_id IN ({$placeholders})
            ORDER BY datum ASC
        ");
        $stmt->execute(array_values(array_map('intval', $requestIds)));
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $rid = (int) ($row['urlaub_id'] ?? 0);
            $map[$rid][] = [
                'date' => (string) ($row['datum'] ?? ''),
                'from' => self::normalizeTime((string) ($row['von_uhrzeit'] ?? '')),
                'to' => self::normalizeTime((string) ($row['bis_uhrzeit'] ?? '')),
                'full_day' => (int) ($row['ist_ganztag'] ?? 1) === 1,
                'minutes' => (int) ($row['minuten'] ?? 0),
            ];
        }
        return $map;
    }

    public static function normalizeTime(string $time): string {
        $time = trim($time);
        if ($time === '') {
            return self::DEFAULT_WORK_START;
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            [$h, $m] = array_map('intval', explode(':', $time));
            return sprintf('%02d:%02d', max(0, min(23, $h)), max(0, min(59, $m)));
        }
        return self::DEFAULT_WORK_START;
    }

    public static function timeToMinutes(string $time): int {
        $time = self::normalizeTime($time);
        [$h, $m] = array_map('intval', explode(':', $time));
        return $h * 60 + $m;
    }

    public static function minutesToTime(int $minutes): string {
        $minutes = max(0, min(24 * 60 - 1, $minutes));
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function formatHours(int $minutes): string {
        $hours = $minutes / 60;
        if (abs($hours - round($hours)) < 0.05) {
            return (string) (int) round($hours);
        }
        return number_format($hours, 1, ',', '');
    }
}
