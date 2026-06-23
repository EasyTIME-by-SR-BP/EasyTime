<?php

namespace App\Models;

use App\Core\Database;

class RequestEvent {
    public static function log(int $requestId, int $actorUserId, string $eventType, ?string $message = null): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO urlaub_ereignis (urlaub_id, mitarbeiter_id, ereignis, nachricht)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $requestId,
            $actorUserId,
            $eventType,
            $message !== null && $message !== '' ? $message : null,
        ]);
    }

    /** @return array<string, list<array<string, mixed>>> */
    public static function getGroupedByRequestIds(array $requestIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $requestIds), static fn($id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $db = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $nameSql = Database::isMysql()
            ? "TRIM(CONCAT(COALESCE(m.vorname, ''), ' ', COALESCE(m.nachname, '')))"
            : "TRIM(COALESCE(m.vorname, '') || ' ' || COALESCE(m.nachname, ''))";
        $stmt = $db->prepare("
            SELECT
                e.id,
                e.urlaub_id AS request_id,
                e.ereignis AS event_type,
                e.nachricht AS message,
                e.erstellt_am AS created_at,
                e.mitarbeiter_id AS actor_user_id,
                {$nameSql} AS actor_name,
                m.berechtigung AS actor_role
            FROM urlaub_ereignis e
            LEFT JOIN mitarbeiter m ON m.id = e.mitarbeiter_id
            WHERE e.urlaub_id IN ($placeholders)
            ORDER BY e.erstellt_am ASC, e.id ASC
        ");
        $stmt->execute($ids);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['request_id']][] = $row;
        }
        return $grouped;
    }

    /** @return list<array<string, mixed>> */
    public static function getTimeline(int $requestId): array {
        return self::getGroupedByRequestIds([$requestId])[$requestId] ?? [];
    }
}
