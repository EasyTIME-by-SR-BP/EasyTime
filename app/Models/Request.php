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

    private static function canTransitionStatus(string $from, string $to): bool {
        if ($from === $to) {
            return false;
        }

        return match ($to) {
            'approved'  => in_array($from, ['pending', 'storno_requested', 'rejected', 'cancelled'], true),
            'rejected'  => $from === 'pending',
            'cancelled' => in_array($from, ['storno_requested', 'approved'], true),
            default     => false,
        };
    }

    private static function statusCountsAgainstBalance(string $status): bool {
        return in_array($status, ['pending', 'approved', 'storno_requested', 'change_requested'], true);
    }

    private static function mapVacationRow(array $row): array {
        $start  = (string) ($row['beginn'] ?? '');
        $end    = (string) ($row['ende'] ?? '');
        $days   = (int) ($row['tage_im_urlaub'] ?? 0);
        $status = self::flagToStatus($row['genehmigt'] ?? 0);
        $minutes = isset($row['minuten_abwesend']) ? (int) $row['minuten_abwesend'] : 0;
        if ($minutes <= 0 && $days > 0) {
            $minutes = $days * VacationSchedule::DEFAULT_DAY_MINUTES;
        }
        return [
            'id'          => (int) ($row['id'] ?? 0),
            'user_id'     => (int) ($row['mitarbeiter_id'] ?? 0),
            'approver_id' => null,
            'start_date'  => $start,
            'end_date'    => $end,
            'net_days'    => $days,
            'type'        => 'vacation',
            'deducted_hours' => 0,
            'ist_ganztag' => !isset($row['ist_ganztag']) || (int) $row['ist_ganztag'] === 1,
            'minuten_abwesend' => $minutes,
            'stunden_display' => VacationSchedule::formatHours($minutes),
            'schedule'    => [],
            'status'      => $status,
            'admin_comment' => null,
            'wunsch_start_date' => !empty($row['wunsch_beginn']) ? (string) $row['wunsch_beginn'] : null,
            'wunsch_end_date'   => !empty($row['wunsch_ende']) ? (string) $row['wunsch_ende'] : null,
            'wunsch_net_days'   => isset($row['wunsch_tage']) ? (int) $row['wunsch_tage'] : null,
            'wunsch_plan_json'  => !empty($row['wunsch_plan_json']) ? (string) $row['wunsch_plan_json'] : null,
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

    /** @param list<array<string, mixed>> $requests */
    public static function attachSchedules(array $requests): array {
        if ($requests === []) {
            return $requests;
        }
        $ids = array_map(static fn (array $r): int => (int) ($r['id'] ?? 0), $requests);
        $byId = VacationSchedule::getByRequestIds($ids);
        foreach ($requests as &$request) {
            $id = (int) ($request['id'] ?? 0);
            $request['schedule'] = $byId[$id] ?? [];
            if (($request['schedule'] ?? []) === []) {
                $request['schedule'] = VacationSchedule::buildDefaultSegments(
                    (int) ($request['user_id'] ?? 0),
                    (string) ($request['start_date'] ?? ''),
                    (string) ($request['end_date'] ?? '')
                );
            }
        }
        unset($request);
        return $requests;
    }

    /**
     * @param mixed $rawSegments
     * @return array{0: bool, 1: list<array{date: string, from: string, to: string, full_day: bool, minutes: int}>}
     */
    public static function resolveScheduleInput(int $userId, string $startDate, string $endDate, bool $isFullDay, $rawSegments): array {
        $segments = VacationSchedule::parseSubmittedSegments($userId, $startDate, $endDate, $rawSegments, $isFullDay);
        return [$isFullDay, $segments];
    }

    public static function create($userId, $startDate, $endDate, $type = 'vacation', $deductedHours = 0, bool $isFullDay = true, ?array $segments = null) {
        $segments = $segments ?? VacationSchedule::buildDefaultSegments((int) $userId, (string) $startDate, (string) $endDate);
        $totalMinutes = VacationSchedule::totalMinutes($segments);
        if ($totalMinutes <= 0) {
            return false;
        }
        $netDays = $isFullDay
            ? self::calculateNetDays((string) $startDate, (string) $endDate)
            : VacationSchedule::minutesToDayEquivalent($totalMinutes, (int) $userId);
        if (self::hasBlockedOverlap($startDate, $endDate)) {
            return false;
        }
        if (self::hasUserVacationOverlap($userId, $startDate, $endDate)) {
            return false;
        }

        // Fenstertage-Limit prüfen (0 = deaktiviert)
        $maxFenstertage = (int) self::getSetting('max_fenstertage', '0');
        if ($maxFenstertage > 0 && self::countFenstertage((string) $startDate, (string) $endDate) > $maxFenstertage) {
            return 'fenstertage_exceeded';
        }
        if (!self::passesMinimumCoverage((int) $userId, (string) $startDate, (string) $endDate)) {
            return 'coverage_request_denied';
        }

        $db   = Database::getConnection();
        VacationSchedule::ensureSchema($db);
        $stmt = $db->prepare("
            INSERT INTO urlaub (mitarbeiter_id, beginn, ende, tage_im_urlaub, beginn_in_worten, ende_in_worten, vertretung_id, buero, buero_vertretung_id, ist_ganztag, minuten_abwesend, genehmigt)
            VALUES (?, ?, ?, ?, NULL, NULL, NULL, 0, NULL, ?, ?, 0)
        ");
        if (!$stmt->execute([(int) $userId, $startDate, $endDate, $netDays, $isFullDay ? 1 : 0, $totalMinutes])) {
            return false;
        }
        $requestId = (int) $db->lastInsertId();
        VacationSchedule::saveForRequest($requestId, $segments, $isFullDay);
        return $requestId;
    }

    public static function createAdminVacation($userId, $approverId, $startDate, $endDate, $netDays = null, $comment = null, bool $isFullDay = true, ?array $segments = null) {
        $segments = $segments ?? VacationSchedule::buildDefaultSegments((int) $userId, (string) $startDate, (string) $endDate);
        $totalMinutes = VacationSchedule::totalMinutes($segments);
        if ($totalMinutes <= 0) {
            return false;
        }
        $netDays = $isFullDay
            ? self::calculateNetDays((string) $startDate, (string) $endDate)
            : VacationSchedule::minutesToDayEquivalent($totalMinutes, (int) $userId);
        if (self::hasBlockedOverlap($startDate, $endDate)) {
            return false;
        }
        if (self::hasUserVacationOverlap($userId, $startDate, $endDate)) {
            return false;
        }

        $db   = Database::getConnection();
        VacationSchedule::ensureSchema($db);
        $stmt = $db->prepare("
            INSERT INTO urlaub (mitarbeiter_id, beginn, ende, tage_im_urlaub, beginn_in_worten, ende_in_worten, vertretung_id, buero, buero_vertretung_id, ist_ganztag, minuten_abwesend, genehmigt)
            VALUES (?, ?, ?, ?, NULL, NULL, NULL, 0, NULL, ?, ?, 1)
        ");
        if (!$stmt->execute([(int) $userId, $startDate, $endDate, $netDays, $isFullDay ? 1 : 0, $totalMinutes])) {
            return false;
        }
        $requestId = (int) $db->lastInsertId();
        VacationSchedule::saveForRequest($requestId, $segments, $isFullDay);
        return $requestId;
    }

    public static function calculateNetDays(string $startDate, string $endDate): int {
        if ($startDate === '' || $endDate === '' || $endDate < $startDate) {
            return 0;
        }

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $holidays = [];
        for ($year = (int) $start->format('Y'); $year <= (int) $end->format('Y'); $year++) {
            foreach (AustrianHolidays::getDatesForYear($year) as $holiday) {
                $holidays[$holiday] = true;
            }
        }

        $count = 0;
        $current = clone $start;
        while ($current <= $end) {
            $dow = (int) $current->format('N');
            $dateStr = $current->format('Y-m-d');
            if ($dow <= 5 && !isset($holidays[$dateStr])) {
                $count++;
            }
            $current->modify('+1 day');
        }

        return $count;
    }

    public static function decide($requestId, $approverId, $status, $comment = null, $startDate = null, $endDate = null, ?bool $isFullDay = null, ?array $segments = null) {
        $req = self::getById($requestId);
        if (!$req) {
            return false;
        }

        $status = (string) $status;
        $currentStatus = (string) ($req['status'] ?? 'pending');

        if (!in_array($status, ['approved', 'rejected', 'cancelled'], true)) {
            return false;
        }

        if (!self::canTransitionStatus($currentStatus, $status)) {
            return false;
        }

        if ($status === 'approved') {
            $start = ($startDate && $endDate) ? $startDate : (string) $req['start_date'];
            $end = ($startDate && $endDate) ? $endDate : (string) $req['end_date'];
            $userId = (int) ($req['user_id'] ?? 0);

            if ($segments === null) {
                $existing = VacationSchedule::getByRequestIds([(int) $requestId]);
                $segments = $existing[(int) $requestId] ?? VacationSchedule::buildDefaultSegments($userId, $start, $end);
                $isFullDay = $segments !== [] && array_reduce(
                    $segments,
                    static fn (bool $c, array $s): bool => $c && !empty($s['full_day']),
                    true
                );
            }
            if ($isFullDay === null) {
                $isFullDay = $segments !== [] && array_reduce(
                    $segments,
                    static fn (bool $c, array $s): bool => $c && !empty($s['full_day']),
                    true
                );
            }

            $totalMinutes = VacationSchedule::totalMinutes($segments);
            if ($totalMinutes <= 0) {
                return false;
            }

            $netDays = $isFullDay
                ? self::calculateNetDays($start, $end)
                : VacationSchedule::minutesToDayEquivalent($totalMinutes, $userId);
            if ($netDays <= 0) {
                return false;
            }

            if ($start !== $req['start_date'] || $end !== $req['end_date']) {
                if (self::hasBlockedOverlap($start, $end)) {
                    return false;
                }
                if (self::hasUserVacationOverlap($userId, $start, $end, (int) $requestId)) {
                    return false;
                }
            }

            if (!self::passesMinimumCoverage($userId, $start, $end, (int) $requestId)) {
                return false;
            }

            $db = Database::getConnection();
            VacationSchedule::ensureSchema($db);
            $upd = $db->prepare('
                UPDATE urlaub
                SET beginn = ?, ende = ?, tage_im_urlaub = ?, ist_ganztag = ?, minuten_abwesend = ?
                WHERE id = ?
            ');
            if (!$upd->execute([$start, $end, $netDays, $isFullDay ? 1 : 0, $totalMinutes, (int) $requestId])) {
                return false;
            }
            VacationSchedule::saveForRequest((int) $requestId, $segments, $isFullDay);
        }

        $db   = Database::getConnection();
        $stmt = $db->prepare('UPDATE urlaub SET genehmigt = ? WHERE id = ?');
        return $stmt->execute([self::statusToFlag($status), (int) $requestId]);
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
        $stmt->execute([(int) $id, (int) $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function requestStorno($id, $userId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("UPDATE urlaub SET genehmigt = 3 WHERE id = ? AND mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 1");
        $stmt->execute([(int) $id, (int) $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function withdrawStornoRequest($id, $userId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("UPDATE urlaub SET genehmigt = 1 WHERE id = ? AND mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 3");
        $stmt->execute([(int) $id, (int) $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function requestChange($id, $userId, $newStart, $newEnd, $netDays, ?bool $isFullDay = null, ?array $segments = null) {
        $req = self::getById($id);
        if (!$req || (int) $req['user_id'] !== (int) $userId || ($req['status'] ?? '') !== 'approved') {
            return false;
        }

        if ($newStart === $req['start_date'] && $newEnd === $req['end_date'] && $isFullDay) {
            return false;
        }
        if (self::hasBlockedOverlap($newStart, $newEnd)) {
            return 'blocked_period';
        }
        if (self::hasUserVacationOverlap((int) $userId, $newStart, $newEnd, (int) $id)) {
            return 'request_conflict';
        }

        if ($isFullDay === null || $segments === null) {
            $isFullDay = true;
            $segments = VacationSchedule::buildDefaultSegments((int) $userId, (string) $newStart, (string) $newEnd);
        }

        $newDays = $isFullDay
            ? self::calculateNetDays((string) $newStart, (string) $newEnd)
            : VacationSchedule::minutesToDayEquivalent(VacationSchedule::totalMinutes($segments), (int) $userId);
        if ($newDays <= 0) {
            return false;
        }

        $maxFenstertage = (int) self::getSetting('max_fenstertage', '0');
        if ($maxFenstertage > 0 && self::countFenstertage((string) $newStart, (string) $newEnd) > $maxFenstertage) {
            return 'fenstertage_exceeded';
        }
        if (!self::passesMinimumCoverage((int) $userId, (string) $newStart, (string) $newEnd, (int) $id)) {
            return 'coverage_request_denied';
        }

        $planJson = json_encode([
            'is_full_day' => $isFullDay,
            'segments' => $isFullDay ? [] : $segments,
        ], JSON_UNESCAPED_UNICODE);

        $db   = Database::getConnection();
        VacationSchedule::ensureSchema($db);
        $stmt = $db->prepare("
            UPDATE urlaub
            SET genehmigt = 5, wunsch_beginn = ?, wunsch_ende = ?, wunsch_tage = ?, wunsch_plan_json = ?
            WHERE id = ? AND mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 1
        ");
        $stmt->execute([(string) $newStart, (string) $newEnd, $newDays, $planJson, (int) $id, (int) $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function withdrawChangeRequest($id, $userId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE urlaub
            SET genehmigt = 1, wunsch_beginn = NULL, wunsch_ende = NULL, wunsch_tage = NULL, wunsch_plan_json = NULL
            WHERE id = ? AND mitarbeiter_id = ? AND COALESCE(genehmigt, 0) = 5
        ");
        $stmt->execute([(int) $id, (int) $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function decideChange($requestId, $approve, $startDate = null, $endDate = null, ?bool $isFullDay = null, ?array $segments = null) {
        $req = self::getById($requestId);
        if (!$req || ($req['status'] ?? '') !== 'change_requested') {
            return false;
        }

        $db = Database::getConnection();

        if (!$approve) {
            $stmt = $db->prepare("
                UPDATE urlaub
                SET genehmigt = 1, wunsch_beginn = NULL, wunsch_ende = NULL, wunsch_tage = NULL, wunsch_plan_json = NULL
                WHERE id = ?
            ");
            return $stmt->execute([(int) $requestId]);
        }

        $newStart = ($startDate && $endDate) ? $startDate : (string) ($req['wunsch_start_date'] ?? '');
        $newEnd = ($startDate && $endDate) ? $endDate : (string) ($req['wunsch_end_date'] ?? '');
        $userId = (int) ($req['user_id'] ?? 0);

        if ($segments === null) {
            $isFullDay = true;
            $segments = VacationSchedule::buildDefaultSegments($userId, $newStart, $newEnd);
            $planRaw = $req['wunsch_plan_json'] ?? null;
            if (is_string($planRaw) && $planRaw !== '') {
                $plan = json_decode($planRaw, true);
                if (is_array($plan) && !empty($plan['segments'])) {
                    [$isFullDay, $segments] = self::resolveScheduleInput($userId, $newStart, $newEnd, false, $plan['segments']);
                }
            }
        } elseif ($isFullDay === null) {
            $isFullDay = $segments !== [] && array_reduce(
                $segments,
                static fn (bool $c, array $s): bool => $c && !empty($s['full_day']),
                true
            );
        }

        $totalMinutes = VacationSchedule::totalMinutes($segments);
        $netDays = $isFullDay
            ? self::calculateNetDays($newStart, $newEnd)
            : VacationSchedule::minutesToDayEquivalent($totalMinutes, $userId);
        if ($netDays <= 0 || $totalMinutes <= 0) {
            return false;
        }

        if (self::hasBlockedOverlap($newStart, $newEnd)) {
            return false;
        }
        if (self::hasUserVacationOverlap($userId, $newStart, $newEnd, (int) $requestId)) {
            return false;
        }
        if (!self::passesMinimumCoverage($userId, $newStart, $newEnd, (int) $requestId)) {
            return false;
        }

        VacationSchedule::ensureSchema($db);
        $stmt = $db->prepare("
            UPDATE urlaub
            SET beginn = ?, ende = ?, tage_im_urlaub = ?, genehmigt = 1,
                ist_ganztag = ?, minuten_abwesend = ?,
                wunsch_beginn = NULL, wunsch_ende = NULL, wunsch_tage = NULL, wunsch_plan_json = NULL
            WHERE id = ?
        ");
        $ok = $stmt->execute([$newStart, $newEnd, $netDays, $isFullDay ? 1 : 0, $totalMinutes, (int) $requestId]);
        if (!$ok) {
            return false;
        }
        VacationSchedule::saveForRequest((int) $requestId, $segments, $isFullDay);
        return true;
    }

    public static function adminModifyVacation($requestId, $newStart, $newEnd, ?bool $isFullDay = null, ?array $segments = null) {
        $req = self::getById($requestId);
        if (!$req || !in_array($req['status'] ?? '', ['approved', 'pending', 'change_requested'], true)) {
            return false;
        }

        $userId = (int) ($req['user_id'] ?? 0);
        $datesChanged = $newStart !== ($req['start_date'] ?? '') || $newEnd !== ($req['end_date'] ?? '');

        if ($segments === null) {
            $segments = VacationSchedule::buildDefaultSegments($userId, $newStart, $newEnd);
            $isFullDay = true;
        }
        if ($isFullDay === null) {
            $isFullDay = $segments !== [] && array_reduce($segments, static fn (bool $c, array $s): bool => $c && !empty($s['full_day']), true);
        }

        $totalMinutes = VacationSchedule::totalMinutes($segments);
        if ($totalMinutes <= 0) {
            return false;
        }

        $netDays = $isFullDay
            ? self::calculateNetDays($newStart, $newEnd)
            : VacationSchedule::minutesToDayEquivalent($totalMinutes, $userId);

        if ($datesChanged) {
            if (self::hasBlockedOverlap($newStart, $newEnd)) {
                return false;
            }
            if (self::hasUserVacationOverlap($userId, $newStart, $newEnd, (int) $requestId)) {
                return false;
            }
            if (($req['status'] ?? '') === 'approved' || ($req['status'] ?? '') === 'change_requested') {
                if (!self::passesMinimumCoverage($userId, $newStart, $newEnd, (int) $requestId)) {
                    return false;
                }
            }
        }

        $flag = self::statusToFlag((string) ($req['status'] ?? 'pending'));
        if (($req['status'] ?? '') === 'change_requested') {
            $flag = 1;
        }

        $db = Database::getConnection();
        VacationSchedule::ensureSchema($db);
        $stmt = $db->prepare("
            UPDATE urlaub
            SET beginn = ?, ende = ?, tage_im_urlaub = ?, genehmigt = ?,
                ist_ganztag = ?, minuten_abwesend = ?,
                wunsch_beginn = NULL, wunsch_ende = NULL, wunsch_tage = NULL, wunsch_plan_json = NULL
            WHERE id = ?
        ");
        $ok = $stmt->execute([$newStart, $newEnd, $netDays, $flag, $isFullDay ? 1 : 0, $totalMinutes, (int) $requestId]);
        if (!$ok) {
            return false;
        }
        VacationSchedule::saveForRequest((int) $requestId, $segments, $isFullDay);
        return true;
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

        $plannedStmt = $db->prepare("
            SELECT COALESCE(SUM(
                CASE
                    WHEN COALESCE(genehmigt, 0) = 5 AND wunsch_tage IS NOT NULL AND wunsch_tage > 0
                        THEN wunsch_tage
                    ELSE tage_im_urlaub
                END
            ), 0)
            FROM urlaub
            WHERE mitarbeiter_id = ? AND COALESCE(genehmigt, 0) IN (0, 3, 5)
        ");
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

        $employeesTotalStmt = $db->query("
            SELECT COUNT(*) FROM mitarbeiter
            WHERE LOWER(COALESCE(berechtigung, '')) NOT IN ('administrator', 'admin', 'ceo')
        ");
        $employeesTotal = (int) $employeesTotalStmt->fetchColumn();

        $absentStmt = $db->prepare("
            SELECT COUNT(DISTINCT mitarbeiter_id)
            FROM urlaub
            WHERE COALESCE(genehmigt, 0) = 1
              AND beginn <= :end_date
              AND ende >= :start_date
        ");
        $absentStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $absentApproved = (int) $absentStmt->fetchColumn();

        $pendingStmt = $db->prepare("
            SELECT COUNT(DISTINCT mitarbeiter_id)
            FROM urlaub
            WHERE COALESCE(genehmigt, 0) IN (0, 3, 5)
              AND beginn <= :end_date
              AND ende >= :start_date
        ");
        $pendingStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $absentPending = (int) $pendingStmt->fetchColumn();

        return [
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'employees_total' => $employeesTotal,
            'absent_approved' => $absentApproved,
            'absent_pending'  => $absentPending,
            'available'       => max(0, $employeesTotal - $absentApproved),
            'available_if_pending' => max(0, $employeesTotal - $absentApproved - $absentPending),
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
        return Mindestbesetzung::passes((int) $requestUserId, (string) $startDate, (string) $endDate, $ignoreRequestId !== null ? (int) $ignoreRequestId : null);
    }

    /** @return list<string> */
    public static function getCoverageWarnings($requestUserId, $startDate, $endDate, $ignoreRequestId = null): array {
        return Mindestbesetzung::getWarnings((int) $requestUserId, (string) $startDate, (string) $endDate, $ignoreRequestId !== null ? (int) $ignoreRequestId : null);
    }

    /** @return list<string> */
    public static function getCoverageBlockingMessages($requestUserId, $startDate, $endDate, $ignoreRequestId = null): array {
        return Mindestbesetzung::getBlockingIssues((int) $requestUserId, (string) $startDate, (string) $endDate, $ignoreRequestId !== null ? (int) $ignoreRequestId : null);
    }
}
