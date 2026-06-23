<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * Interne Inbox-API für EasyTime.
 *
 * Versand:
 * - `to`: einzelne User-ID, Liste von IDs oder `'admins'`
 *
 * Lesen / Erledigen (`resolution`):
 * - `individual`: jeder Empfänger liest und erledigt für sich (eigene Kopie)
 * - `shared`: eine Person lesen ODER erledigen reicht – gilt für alle Empfänger
 *   (z. B. Passwort-Hilfe an alle Admins)
 */
class Inbox {
    public const TYPE_INFO = 'info';
    public const TYPE_TASK = 'task';

    public const RESOLUTION_INDIVIDUAL = 'individual';
    public const RESOLUTION_SHARED = 'shared';

    /**
     * @param array{
     *   to: int|list<int>|'admins',
     *   title: string,
     *   message: string,
     *   category?: string,
     *   type?: string,
     *   resolution?: string,
     *   thread_id?: string|null,
     *   action_url?: string|null,
     *   related_user_id?: int|null,
     *   dedupe?: bool,
     * } $payload
     */
    public static function send(array $payload): ?string {
        $recipients = self::resolveRecipients($payload['to'] ?? []);
        if ($recipients === []) {
            return null;
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        if ($title === '' || $message === '') {
            return null;
        }

        $type = (string) ($payload['type'] ?? self::TYPE_INFO);
        $resolution = (string) ($payload['resolution'] ?? self::RESOLUTION_INDIVIDUAL);
        if (!in_array($resolution, [self::RESOLUTION_INDIVIDUAL, self::RESOLUTION_SHARED], true)) {
            $resolution = self::RESOLUTION_INDIVIDUAL;
        }

        $category = (string) ($payload['category'] ?? 'info');
        $actionUrl = isset($payload['action_url']) ? (string) $payload['action_url'] : null;
        $relatedUserId = isset($payload['related_user_id']) ? (int) $payload['related_user_id'] : null;
        $dedupe = (bool) ($payload['dedupe'] ?? true);

        $threadId = isset($payload['thread_id']) ? trim((string) $payload['thread_id']) : null;
        if ($resolution === self::RESOLUTION_SHARED) {
            if ($threadId === null || $threadId === '') {
                $threadId = 'thread_' . bin2hex(random_bytes(8));
            }
            if ($dedupe && $type === self::TYPE_TASK && Notification::hasOpenThread($threadId)) {
                return $threadId;
            }
        } else {
            $threadId = null;
        }

        foreach ($recipients as $userId) {
            $rowThreadId = $threadId;
            if ($resolution === self::RESOLUTION_INDIVIDUAL && $type === self::TYPE_TASK) {
                $rowThreadId = 'individual_' . bin2hex(random_bytes(6)) . '_' . $userId;
            }

            Notification::create($userId, $title, $message, $category, [
                'type' => $type,
                'resolution_mode' => $resolution,
                'thread_id' => $rowThreadId,
                'action_url' => $actionUrl,
                'related_user_id' => $relatedUserId,
            ]);
        }

        return $threadId;
    }

    public static function markRead(int $notificationId, int $userId): bool {
        $note = Notification::getByIdForUser($notificationId, $userId);
        if (!$note) {
            return false;
        }

        if (self::isShared($note)) {
            return Notification::markThreadAsRead((string) ($note['thread_id'] ?? ''), $userId);
        }

        return Notification::markAsReadSingle($notificationId, $userId);
    }

    public static function resolve(int $notificationId, int $userId): bool {
        $note = Notification::getByIdForUser($notificationId, $userId);
        if (!$note || (string) ($note['type'] ?? '') !== self::TYPE_TASK) {
            return false;
        }

        if (self::isShared($note)) {
            $threadId = (string) ($note['thread_id'] ?? '');
            return $threadId !== '' && Notification::resolveThread($threadId, $userId);
        }

        return Notification::resolveSingle($notificationId, $userId);
    }

    public static function resolveThread(string $threadId, int $userId): bool {
        if ($threadId === '') {
            return false;
        }
        return Notification::resolveThread($threadId, $userId);
    }

    public static function hasOpenThread(string $threadId): bool {
        return Notification::hasOpenThread($threadId);
    }

    /** @return list<array<string, mixed>> */
    public static function getForUser(int $userId, int $limit = 80): array {
        return Notification::getByUserId($userId, $limit);
    }

    public static function countUnread(int $userId): int {
        return Notification::countUnread($userId);
    }

    /** @param list<array<string, mixed>> $list */
    public static function computeCounts(array $list): array {
        return Notification::computeInboxCounts($list);
    }

    /** @param list<array<string, mixed>> $list
     *  @return list<array<string, mixed>>
     */
    public static function filterList(array $list, string $filter): array {
        return Notification::filterInboxList($list, $filter);
    }

    public static function isShared(array $note): bool {
        return (string) ($note['resolution_mode'] ?? self::RESOLUTION_INDIVIDUAL) === self::RESOLUTION_SHARED
            && trim((string) ($note['thread_id'] ?? '')) !== '';
    }

    /** @param int|list<int>|'admins' $to
     *  @return list<int>
     */
    private static function resolveRecipients(int|array|string $to): array {
        if ($to === 'admins') {
            return User::getAdminUserIds();
        }
        if (is_int($to)) {
            return $to > 0 ? [$to] : [];
        }
        if (!is_array($to)) {
            return [];
        }

        $ids = [];
        foreach ($to as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $ids[$intId] = $intId;
            }
        }
        return array_values($ids);
    }
}
