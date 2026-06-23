<?php

namespace App\Models;

use App\Core\AustrianHolidays;
use App\Core\Database;

class Request {
    private static function statusToFlag(string $status): int {
        return match ($status) {
            'approved' => 1,
            'rejected' => 2,
            'storno_requested' => 3,
            'cancelled' => 4,
            'change_requested' => 5,
            default => 0, // pending
        };
    }

    private static function flagToStatus($flag): string {
        return match ((int) $flag) {
            1 => 'approved',
            2 => 'rejected',
            3 => 'storno_requested',
            4 => 'cancelled',
            5 => 'change_requested',
            default => 'pending',
        };
    }

    private static function mapVacationRow(array $row): array {
        $start  = (string) ($row['beginn'] ?? '');
        $end    = (string) ($row['ende'] ?? '');
        $days   = (int) ($row['tage_im_urlaub'] ?? 0);
        $status = self::flagToStatus($row['genehmigt'] ?? 0);
        return [
            'id'          => (int) ($row['id'] ?? 0),
            'user_id'     => (int) ($row['mitarbeiter_id'] ?? 0),
            'approver_id' => null,
            'start_date'  => $start,
            'end_date'    => $end,
            'net_days'    => $days,
            'type'        => 'vacation',
            'deducted_hours' => 0,
            'status'      => $status,
            'admin_comment' => null,
            'wunsch_start_date' => !empty($row['wunsch_beginn']) ? (string) $row['wunsch_beginn'] : null,
            'wunsch_end_date'   => !empty($row['wunsch_ende']) ? (string) $row['wunsch_ende'] : null,
            'wunsch_net_days'   => isset($row['wunsch_tage']) ? (int) $row['wunsch_tage'] : null,
            'created_at'  => $start,
            'decided_at'  => null,
            'firstname'   => (string) ($row['vorname'] ?? ''),
            'lastname'    => (string) ($row['nachname'] ?? ''),
            'email'       => (string) ($row['email'] ?? ''),
        ];
    }

    public static function getAll() {
        $db   = Database::getConnection();
        $stmt = $db->query("
            SELECT u.*, m.vorname, m.nachname, m.email
            FROM urlaub u
            JOIN mitarbeiter m ON u.mitarbeiter_id = m.id
            ORDER BY u.beginn DESC
        ");
        $rows = $stmt->fetchAll();
        return array_map([self::class, 'mapVacationRow'], $rows);
    }

    public static function getByUserId($userId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, m.vorname, m.nachname, m.email
            FROM urlaub u
            JOIN mitarbeiter m ON u.mitarbeiter_id = m.id
            WHERE u.mitarbeiter_id = ?
            ORDER BY u.beginn DESC
        ");
        $stmt->execute([(int) $userId]);
        $rows = $stmt->fetchAll();
        return array_map([self::class, 'mapVacationRow'], $rows);
    }

    public static function create($userId, $startDate, $endDate, $netDays, $type = 'vacation', $deductedHours = 0) {
        if (self::hasBlockedOverlap($startDate, $endDate)) {
            return false;
        }
        if (self::hasUserVacationOverlap($userId, $startDate, $endDate)) {
            return false;
        }

        $stats = self::calculateUserVacationStats($userId);
        if ((int) $netDays > (int) ($stats['remaining'] ?? 0)) {
            return 'insufficient_balance';
        }

        // Fenstertage-Limit prüfen (0 = deaktiviert)
        $maxFenstertage = (int) self::getSetting('max_fenstertage', '0');
        if ($maxFenstertage > 0 && self::countFenstertage((string) $startDate, (string) $endDate) > $maxFenstertage) {
            return 'fenstertage_exceeded';
        }

        $db   = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO urlaub (mitarbeiter_id, beginn, ende, tage_im_urlaub, beginn_in_worten, ende_in_worten, vertretung_id, buero, buero_vertretung_id, genehmigt)
            VALUES (?, ?, ?, ?, NULL, NULL, NULL, 0, NULL, 0)
        ");
        if (!$stmt->execute([(int) $userId, $startDate, $endDate, (int) $netDays])) {
            return false;
        }
        return (int) $db->lastInsertId();
    }

    public static function createAdminVacation($userId, $approverId, $startDate, $endDate, $netDays, $comment = null) {
        if (self::hasBlockedOverlap($startDate, $endDate)) {
            return false;
        }
        if (self::hasUserVacationOverlap($userId, $startDate, $endDate)) {
            return false;
        }
        $db   = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO urlaub (mitarbeiter_id, beginn, ende, tage_im_urlaub, beginn_in_worten, ende_in_worten, vertretung_id, buero, buero_vertretung_id, genehmigt)
            VALUES (?, ?, ?, ?, NULL, NULL, NULL, 0, NULL, 1)
        ");
        if (!$stmt->execute([(int) $userId, $startDate, $endDate, (int) $netDays])) {
            return false;
        }
        return (int) $db->lastInsertId();
    }

    public static function calculateNetDays(string $startDate, string $endDate): int {
        if ($startDate === '' || $endDate === '' || $endDate < $startDate) {
            return 0;
        }
        return (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;
    }

    public static function decide($requestId, $approverId, $status, $comment = null, $startDate = null, $endDate = null) {
        $req = self::getById($requestId);
        if (!$req) {
            return false;
        }

        if ((string) $status === 'approved') {
            $start = ($startDate && $endDate) ? $startDate : (string) $req['start_date'];
            $end = ($startDate && $endDate) ? $endDate : (string) $req['end_date'];
            $netDays = self::calculateNetDays($start, $end);
            if ($netDays <= 0) {
                return false;
            }

            if ($start !== $req['start_date'] || $end !== $req['end_date']) {
                if (self::hasBlockedOverlap($start, $end)) {
                    return false;
                }
                if (self::hasUserVacationOverlap((int) $req['user_id'], $start, $end, (int) $requestId)) {
                    return false;
                }
                $oldDays = (int) ($req['net_days'] ?? 0);
                if ($netDays > $oldDays) {
                    $stats = self::calculateUserVacationStats((int) $req['user_id']);
                    if (($netDays - $oldDays) > (int) ($stats['remaining'] ?? 0)) {
                        return 'insufficient_balance';
                    }
                }
                $db = Database::getConnection();
                $upd = $db->prepare('UPDATE urlaub SET beginn = ?, ende = ?, tage_im_urlaub = ? WHERE id = ?');
                if (!$upd->execute([$start, $end, $netDays, (int) $requestId])) {
                    return false;
                }
                $req = self::getById($requestId);
            }

            if ($req && !self::passesMinimumCoverage($req['user_id'], $req['start_date'], $req['end_date'], (int) $requestId)) {
                return false;
            }
        }

        $db   = Database::getConnection();
        $stmt = $db->prepare('UPDATE urlaub SET genehmigt = ? WHERE id = ?');
        return $stmt->execute([self::statusToFlag((string) $status), (int) $requestId]);
    }

    public static function getById($requestId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, m.vorname, m.nachname, m.email
            FROM urlaub u
            JOIN mitarbeiter m ON m.id = u.mitarbeiter_id
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int) $requestId]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        return self::mapVacationRow($row);
    }

    public static function withdrawRequest($id, $userId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM urlaub WHERE id = ? AND mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 0");
        return $stmt->execute([(int) $id, (int) $userId]);
    }

    public static function requestStorno($id, $userId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("UPDATE urlaub SET genehmigt = 3 WHERE id = ? AND mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 1");
        return $stmt->execute([(int) $id, (int) $userId]);
    }

    public static function withdrawStornoRequest($id, $userId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("UPDATE urlaub SET genehmigt = 1 WHERE id = ? AND mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 3");
        return $stmt->execute([(int) $id, (int) $userId]);
    }

    public static function requestChange($id, $userId, $newStart, $newEnd, $netDays) {
        $req = self::getById($id);
        if (!$req || (int) $req['user_id'] !== (int) $userId || ($req['status'] ?? '') !== 'approved') {
            return false;
        }
        if ($newStart === $req['start_date'] && $newEnd === $req['end_date']) {
            return false;
        }
        if (self::hasBlockedOverlap($newStart, $newEnd)) {
            return 'blocked_period';
        }
        if (self::hasUserVacationOverlap((int) $userId, $newStart, $newEnd, (int) $id)) {
            return 'request_conflict';
        }

        $oldDays = (int) ($req['net_days'] ?? 0);
        $newDays = (int) $netDays;
        if ($newDays > $oldDays) {
            $stats = self::calculateUserVacationStats((int) $userId);
            if (($newDays - $oldDays) > (int) ($stats['remaining'] ?? 0)) {
                return 'insufficient_balance';
            }
        }

        $maxFenstertage = (int) self::getSetting('max_fenstertage', '0');
        if ($maxFenstertage > 0 && self::countFenstertage((string) $newStart, (string) $newEnd) > $maxFenstertage) {
            return 'fenstertage_exceeded';
        }

        $db   = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE urlaub
            SET genehmigt = 5, wunsch_beginn = ?, wunsch_ende = ?, wunsch_tage = ?
            WHERE id = ? AND mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 1
        ");
        return $stmt->execute([(string) $newStart, (string) $newEnd, $newDays, (int) $id, (int) $userId]) ? true : false;
    }

    public static function withdrawChangeRequest($id, $userId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE urlaub
            SET genehmigt = 1, wunsch_beginn = NULL, wunsch_ende = NULL, wunsch_tage = NULL
            WHERE id = ? AND mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 5
        ");
        return $stmt->execute([(int) $id, (int) $userId]);
    }

    public static function decideChange($requestId, $approve, $startDate = null, $endDate = null) {
        $req = self::getById($requestId);
        if (!$req || ($req['status'] ?? '') !== 'change_requested') {
            return false;
        }

        $db = Database::getConnection();

        if (!$approve) {
            $stmt = $db->prepare("
                UPDATE urlaub
                SET genehmigt = 1, wunsch_beginn = NULL, wunsch_ende = NULL, wunsch_tage = NULL
                WHERE id = ?
            ");
            return $stmt->execute([(int) $requestId]);
        }

        $newStart = ($startDate && $endDate) ? $startDate : (string) ($req['wunsch_start_date'] ?? '');
        $newEnd = ($startDate && $endDate) ? $endDate : (string) ($req['wunsch_end_date'] ?? '');
        $netDays = self::calculateNetDays($newStart, $newEnd);
        if ($netDays <= 0) {
            return false;
        }

        if (self::hasBlockedOverlap($newStart, $newEnd)) {
            return false;
        }
        if (self::hasUserVacationOverlap((int) $req['user_id'], $newStart, $newEnd, (int) $requestId)) {
            return false;
        }
        if (!self::passesMinimumCoverage((int) $req['user_id'], $newStart, $newEnd, (int) $requestId)) {
            return false;
        }

        $oldDays = (int) ($req['net_days'] ?? 0);
        if ($netDays > $oldDays) {
            $stats = self::calculateUserVacationStats((int) $req['user_id']);
            if (($netDays - $oldDays) > (int) ($stats['remaining'] ?? 0)) {
                return 'insufficient_balance';
            }
        }

        $stmt = $db->prepare("
            UPDATE urlaub
            SET beginn = ?, ende = ?, tage_im_urlaub = ?, genehmigt = 1,
                wunsch_beginn = NULL, wunsch_ende = NULL, wunsch_tage = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$newStart, $newEnd, $netDays, (int) $requestId]);
    }

    public static function adminModifyVacation($requestId, $newStart, $newEnd) {
        $req = self::getById($requestId);
        if (!$req || !in_array($req['status'] ?? '', ['approved', 'pending', 'change_requested'], true)) {
            return false;
        }

        $netDays = self::calculateNetDays($newStart, $newEnd);
        if ($netDays <= 0) {
            return false;
        }
        if ($newStart === $req['start_date'] && $newEnd === $req['end_date']) {
            return true;
        }

        if (self::hasBlockedOverlap($newStart, $newEnd)) {
            return false;
        }
        if (self::hasUserVacationOverlap((int) $req['user_id'], $newStart, $newEnd, (int) $requestId)) {
            return false;
        }

        $oldDays = (int) ($req['net_days'] ?? 0);
        if ($netDays > $oldDays) {
            $stats = self::calculateUserVacationStats((int) $req['user_id']);
            if (($netDays - $oldDays) > (int) ($stats['remaining'] ?? 0)) {
                return 'insufficient_balance';
            }
        }

        if (($req['status'] ?? '') === 'approved' || ($req['status'] ?? '') === 'change_requested') {
            if (!self::passesMinimumCoverage((int) $req['user_id'], $newStart, $newEnd, (int) $requestId)) {
                return false;
            }
        }

        $flag = self::statusToFlag((string) ($req['status'] ?? 'pending'));
        if (($req['status'] ?? '') === 'change_requested') {
            $flag = 1;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE urlaub
            SET beginn = ?, ende = ?, tage_im_urlaub = ?, genehmigt = ?,
                wunsch_beginn = NULL, wunsch_ende = NULL, wunsch_tage = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$newStart, $newEnd, $netDays, $flag, (int) $requestId]);
    }

    public static function getBlockedPeriods() {
        $db   = Database::getConnection();
        $stmt = $db->query("SELECT * FROM urlaubssperre ORDER BY von ASC");
        $rows = $stmt->fetchAll();
        return array_map(static function (array $row) {
            return [
                'id'         => (int) ($row['id'] ?? 0),
                'start_date' => $row['von'] ?? null,
                'end_date'   => $row['bis'] ?? null,
                'label'      => null,
                'created_at' => $row['von'] ?? null,
            ];
        }, $rows);
    }

    public static function createBlockedPeriod($startDate, $endDate, $label = null, $createdBy = null) {
        if (self::hasBlockedOverlap($startDate, $endDate)) {
            return false;
        }
        $db   = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO urlaubssperre (von, bis, ganzjaehrig) VALUES (?, ?, 0)");
        return $stmt->execute([$startDate, $endDate]);
    }

    public static function deleteBlockedPeriod($id) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM urlaubssperre WHERE id = ?");
        return $stmt->execute([(int) $id]);
    }

    public static function hasBlockedOverlap($startDate, $endDate) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 1 FROM urlaubssperre
            WHERE von <= :end_date AND bis >= :start_date
            LIMIT 1
        ");
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return (bool) $stmt->fetchColumn();
    }

    public static function hasUserVacationOverlap($userId, $startDate, $endDate, $excludeRequestId = null) {
        $db   = Database::getConnection();
        $excludeClause = ($excludeRequestId !== null) ? ' AND id != :exclude_id' : '';
        $stmt = $db->prepare("
            SELECT 1 FROM urlaub
            WHERE mitarbeiter_id = :user_id
              AND COALESCE(genehmigt, 0) NOT IN (2, 4)
              AND beginn <= :end_date
              AND ende >= :start_date
              {$excludeClause}
            LIMIT 1
        ");
        $params = [':user_id' => $userId, ':start_date' => $startDate, ':end_date' => $endDate];
        if ($excludeRequestId !== null) {
            $params[':exclude_id'] = (int) $excludeRequestId;
        }
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public static function calculateUserVacationStats($userId) {
        $db = Database::getConnection();

        $entitlementStmt = $db->prepare("SELECT urlaubsanspruch FROM mitarbeiter WHERE id = ?");
        $entitlementStmt->execute([(int) $userId]);
        $entitlement = (int) ($entitlementStmt->fetchColumn() ?: 0);

        $approvedStmt = $db->prepare("SELECT COALESCE(SUM(tage_im_urlaub), 0) FROM urlaub WHERE mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 1");
        $approvedStmt->execute([(int) $userId]);
        $approvedDays = (int) $approvedStmt->fetchColumn();

        $plannedStmt = $db->prepare("SELECT COALESCE(SUM(tage_im_urlaub), 0) FROM urlaub WHERE mitarbeiter_id = ? AND COALESCE(genehmigt, 0) IN (0, 3, 5)");
        $plannedStmt->execute([(int) $userId]);
        $plannedDays = (int) $plannedStmt->fetchColumn();

        return [
            'entitlement' => $entitlement,
            'approved'    => $approvedDays,
            'planned'     => $plannedDays,
            'remaining'   => max(0, $entitlement - $approvedDays - $plannedDays)
        ];
    }

    public static function getCapacitySummary($startDate, $endDate) {
        $db = Database::getConnection();

        $employeesTotalStmt = $db->query("SELECT COUNT(*) FROM mitarbeiter WHERE LOWER(COALESCE(berechtigung, '')) != 'administrator'");
        $employeesTotal     = (int) $employeesTotalStmt->fetchColumn();

        $absentStmt = $db->prepare("
            SELECT COUNT(DISTINCT mitarbeiter_id)
            FROM urlaub
            WHERE COALESCE(genehmigt, 0) = 1
              AND beginn <= :end_date
              AND ende >= :start_date
        ");
        $absentStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $absentApproved = (int) $absentStmt->fetchColumn();

        return [
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'employees_total' => $employeesTotal,
            'absent_approved' => $absentApproved,
            'available'       => max(0, $employeesTotal - $absentApproved)
        ];
    }

    /* ── App-Settings (Schlüssel-Wert-Tabelle) ─────────────────── */

    public static function getSetting(string $key, string $default = ''): string {
        $db   = Database::getConnection();
        $keyCol = Database::isMysql() ? '`key`' : 'key';
        $stmt = $db->prepare("SELECT value FROM app_settings WHERE {$keyCol} = ? LIMIT 1");
        $stmt->execute([$key]);
        $val  = $stmt->fetchColumn();
        return ($val !== false) ? (string) $val : $default;
    }

    public static function setSetting(string $key, string $value): void {
        Database::upsertAppSetting($key, $value);
    }

    /**
     * Zählt die Fenstertage (Brückentage) im Zeitraum:
     * ein Werktag gilt als Fenstertag, wenn der Vortag UND der Folgetag
     * jeweils ein Wochenende oder ein gesetzlicher Feiertag ist.
     */
    public static function countFenstertage(string $startDate, string $endDate): int {
        $start = new \DateTime($startDate);
        $end   = new \DateTime($endDate);

        // Feiertage für alle betroffenen Jahre sammeln
        $holidays = [];
        for ($y = (int) $start->format('Y'); $y <= (int) $end->format('Y'); $y++) {
            foreach (AustrianHolidays::getDatesForYear($y) as $h) {
                $holidays[$h] = true;
            }
        }

        $count   = 0;
        $current = clone $start;

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $dow     = (int) $current->format('N'); // 1=Mo … 7=So

            if ($dow <= 5 && !isset($holidays[$dateStr])) {
                // Vortag
                $prev    = (clone $current)->modify('-1 day');
                $prevOff = ((int) $prev->format('N') >= 6) || isset($holidays[$prev->format('Y-m-d')]);
                // Folgetag
                $next    = (clone $current)->modify('+1 day');
                $nextOff = ((int) $next->format('N') >= 6) || isset($holidays[$next->format('Y-m-d')]);

                if ($prevOff && $nextOff) {
                    $count++;
                }
            }

            $current->modify('+1 day');
        }

        return $count;
    }

    /* ── Mindestbesetzung (echte Implementierung) ───────────────── */

    public static function passesMinimumCoverage($requestUserId, $startDate, $endDate, $ignoreRequestId = null): bool {
        $minStaff = (int) self::getSetting('min_staff_available', '1');
        if ($minStaff <= 0) {
            return true;
        }

        $db = Database::getConnection();

        // Gesamtzahl Nicht-Admin-Mitarbeiter
        $total = (int) $db->query(
            "SELECT COUNT(*) FROM mitarbeiter WHERE LOWER(COALESCE(berechtigung,'')) NOT IN ('administrator','admin','ceo')"
        )->fetchColumn();

        if ($total === 0) {
            return true;
        }

        $ignoreClause = ($ignoreRequestId !== null) ? " AND id != " . (int) $ignoreRequestId : '';

        $current = new \DateTime($startDate);
        $end     = new \DateTime($endDate);

        while ($current <= $end) {
            $day = $current->format('Y-m-d');
            $dow = (int) $current->format('N');

            // Wochenenden überspringen
            if ($dow >= 6) {
                $current->modify('+1 day');
                continue;
            }

            // Anzahl bereits genehmigter Abwesenheiten an diesem Tag (ohne anfragenden User)
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT mitarbeiter_id)
                FROM urlaub
                WHERE COALESCE(genehmigt, 0) = 1
                  AND beginn <= :day AND ende >= :day
                  AND mitarbeiter_id != :uid
                $ignoreClause
            ");
            $stmt->execute([':day' => $day, ':uid' => (int) $requestUserId]);
            $absent    = (int) $stmt->fetchColumn() + 1; // +1 für anfragenden User
            $available = $total - $absent;

            if ($available < $minStaff) {
                return false;
            }

            $current->modify('+1 day');
        }

        return true;
    }
}
