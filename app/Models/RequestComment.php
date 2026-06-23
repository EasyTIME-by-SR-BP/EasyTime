<?php

namespace App\Models;

use App\Core\Database;

class RequestComment {
    public static function create($requestId, $userId, $comment) {
        $trimmed = trim((string) $comment);
        if ($trimmed === '') {
            return false;
        }

        $db   = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO urlaub_kommentar (urlaub_id, mitarbeiter_id, kommentar) VALUES (?, ?, ?)");
        $ok = $stmt->execute([(int) $requestId, (int) $userId, $trimmed]);
        if ($ok) {
            RequestEvent::log((int) $requestId, (int) $userId, 'comment', $trimmed);
        }
        return $ok;
    }

    public static function getByRequestId($requestId) {
        $db   = Database::getConnection();
        $stmt = $db->prepare("
            SELECT
                rc.id,
                rc.urlaub_id   AS request_id,
                rc.kommentar   AS comment,
                rc.erstellt_am AS created_at,
                m.vorname      AS firstname,
                m.nachname     AS lastname,
                m.berechtigung AS role
            FROM urlaub_kommentar rc
            JOIN mitarbeiter m ON m.id = rc.mitarbeiter_id
            WHERE rc.urlaub_id = ?
            ORDER BY rc.erstellt_am ASC
        ");
        $stmt->execute([(int) $requestId]);
        return $stmt->fetchAll();
    }

    public static function getByRequestIds(array $requestIds): array {
        $ids = array_values(array_unique(array_map('intval', $requestIds)));
        $ids = array_filter($ids, static fn($id) => $id > 0);
        if (empty($ids)) {
            return [];
        }

        $db           = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt         = $db->prepare("
            SELECT
                rc.id,
                rc.urlaub_id   AS request_id,
                rc.kommentar   AS comment,
                rc.erstellt_am AS created_at,
                m.vorname      AS firstname,
                m.nachname     AS lastname,
                m.berechtigung AS role
            FROM urlaub_kommentar rc
            JOIN mitarbeiter m ON m.id = rc.mitarbeiter_id
            WHERE rc.urlaub_id IN ($placeholders)
            ORDER BY rc.erstellt_am ASC, rc.id ASC
        ");
        $stmt->execute($ids);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['request_id']][] = $row;
        }
        return $grouped;
    }
}
