<?php

namespace App\Models;

use App\Core\Database;
use App\Services\Inbox;

class Notification {
    public static function create(int $userId, string $title, string $message, string $category = 'info', array $options = []): int {
        $db = Database::getConnection();
        $type = (string) ($options['type'] ?? Inbox::TYPE_INFO);
        $resolutionMode = (string) ($options['resolution_mode'] ?? Inbox::RESOLUTION_INDIVIDUAL);
        $threadId = $options['thread_id'] ?? null;
        $actionUrl = $options['action_url'] ?? null;
        $relatedUserId = isset($options['related_user_id']) ? (int) $options['related_user_id'] : null;

        $stmt = $db->prepare("
            INSERT INTO notifications (
                user_id, title, message, category, type, resolution_mode,
                thread_id, action_url, related_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $title,
            $message,
            $category,
            $type,
            $resolutionMode,
            $threadId,
            $actionUrl,
            $relatedUserId,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function hasOpenThread(string $threadId): bool {
        if ($threadId === '') {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE thread_id = ?
              AND type = ?
              AND is_resolved = 0
        ");
        $stmt->execute([$threadId, Inbox::TYPE_TASK]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function getByUserId(int $userId, int $limit = 80): array {
        $db = Database::getConnection();
        $resolverNameSql = Database::isMysql()
            ? "TRIM(CONCAT(COALESCE(r.vorname, ''), ' ', COALESCE(r.nachname, '')))"
            : "TRIM(COALESCE(r.vorname, '') || ' ' || COALESCE(r.nachname, ''))";
        $stmt = $db->prepare("
            SELECT
                n.*,
                {$resolverNameSql} AS resolved_by_name
            FROM notifications n
            LEFT JOIN mitarbeiter r ON r.id = n.resolved_by_user_id
            WHERE n.user_id = ?
            ORDER BY
                CASE WHEN n.type = 'task' AND n.is_resolved = 0 THEN 0 ELSE 1 END,
                n.is_read ASC,
                n.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countUnread(int $userId): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE user_id = ?
              AND (
                    (type = ? AND is_read = 0)
                 OR (type = ? AND is_resolved = 0)
              )
        ");
        $stmt->execute([$userId, Inbox::TYPE_INFO, Inbox::TYPE_TASK]);
        return (int) $stmt->fetchColumn();
    }

    public static function markAsReadSingle(int $notificationId, int $userId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$notificationId, $userId]);
    }

    public static function markThreadAsRead(string $threadId, int $readByUserId): bool {
        if ($threadId === '') {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE thread_id = ?
              AND resolution_mode = ?
              AND is_read = 0
        ");
        return $stmt->execute([$threadId, Inbox::RESOLUTION_SHARED]);
    }

    public static function getByIdForUser(int $notificationId, int $userId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$notificationId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function resolveThread(string $threadId, int $resolvedByUserId): bool {
        if ($threadId === '') {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_resolved = 1,
                resolved_at = CURRENT_TIMESTAMP,
                resolved_by_user_id = ?,
                is_read = 1
            WHERE thread_id = ?
              AND is_resolved = 0
        ");
        return $stmt->execute([$resolvedByUserId, $threadId]);
    }

    public static function resolveSingle(int $notificationId, int $userId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_resolved = 1,
                resolved_at = CURRENT_TIMESTAMP,
                resolved_by_user_id = ?,
                is_read = 1
            WHERE id = ? AND user_id = ?
              AND type = ?
              AND is_resolved = 0
        ");
        return $stmt->execute([$userId, $notificationId, $userId, Inbox::TYPE_TASK]);
    }

    /** @param list<array<string, mixed>> $list */
    public static function computeInboxCounts(array $list): array {
        $counts = [
            'all'      => count($list),
            'unread'   => 0,
            'tasks'    => 0,
            'password' => 0,
            'approval' => 0,
            'info'     => 0,
            'done'     => 0,
        ];

        foreach ($list as $note) {
            $type = (string) ($note['type'] ?? Inbox::TYPE_INFO);
            $cat = (string) ($note['category'] ?? 'info');
            $threadId = (string) ($note['thread_id'] ?? '');
            $isTask = $type === Inbox::TYPE_TASK;
            $isResolved = (int) ($note['is_resolved'] ?? 0) === 1;
            $isUnread = (int) ($note['is_read'] ?? 0) === 0;
            $isOpenTask = $isTask && !$isResolved;

            if (($isUnread && !$isResolved) || $isOpenTask) {
                $counts['unread']++;
            }
            if ($isOpenTask) {
                $counts['tasks']++;
            }
            if ($cat === 'password' || str_starts_with($threadId, 'pwd_reset_')) {
                $counts['password']++;
            }
            if ($cat === 'approval') {
                $counts['approval']++;
            }
            if ($type === Inbox::TYPE_INFO) {
                $counts['info']++;
            }
            if (($isTask && $isResolved) || ($type === Inbox::TYPE_INFO && !$isUnread)) {
                $counts['done']++;
            }
        }

        return $counts;
    }

    /** @param list<array<string, mixed>> $list
     *  @return list<array<string, mixed>>
     */
    public static function filterInboxList(array $list, string $filter): array {
        if ($filter === 'all') {
            return $list;
        }

        return array_values(array_filter($list, static function (array $note) use ($filter): bool {
            $type = (string) ($note['type'] ?? Inbox::TYPE_INFO);
            $cat = (string) ($note['category'] ?? 'info');
            $threadId = (string) ($note['thread_id'] ?? '');
            $isTask = $type === Inbox::TYPE_TASK;
            $isResolved = (int) ($note['is_resolved'] ?? 0) === 1;
            $isUnread = (int) ($note['is_read'] ?? 0) === 0;
            $isOpenTask = $isTask && !$isResolved;

            return match ($filter) {
                'unread'   => ($isUnread && !$isResolved) || $isOpenTask,
                'tasks'    => $isOpenTask,
                'password' => $cat === 'password' || str_starts_with($threadId, 'pwd_reset_'),
                'approval' => $cat === 'approval',
                'info'     => $type === Inbox::TYPE_INFO,
                'done'     => ($isTask && $isResolved) || ($type === Inbox::TYPE_INFO && !$isUnread),
                default    => true,
            };
        }));
    }
}
