<?php

namespace App\Services;

use App\Models\Request as VacationRequest;
use App\Models\User;
use App\Services\Inbox;
use App\Services\MailService;

class NotificationService {
    public static function onPasswordResetRequested(int $employeeId): void {
        $emp = User::getById($employeeId);
        if (!$emp) {
            return;
        }

        $email = trim((string) ($emp['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $token = User::createPasswordResetToken($employeeId);
        if ($token === '') {
            return;
        }

        $name = trim(($emp['firstname'] ?? '') . ' ' . ($emp['lastname'] ?? ''));
        $resetUrl = MailService::absoluteUrl('/?reset_token=' . urlencode($token));
        MailService::sendPasswordResetLink($email, $name, $resetUrl);
    }

    public static function onPasswordResetCompleted(int $employeeId, int $adminUserId): void {
        // Self-service reset only — no admin inbox flow.
    }

    public static function onVacationRequested(int $requestId, int $employeeId): void {
        $req = VacationRequest::getById($requestId);
        $emp = User::getById($employeeId);
        if (!$req || !$emp) {
            return;
        }

        $name = trim($emp['firstname'] . ' ' . $emp['lastname']);
        $range = $req['start_date'] . ' – ' . $req['end_date'];

        Inbox::send([
            'to' => 'admins',
            'title' => 'Neuer Urlaubsantrag',
            'message' => "{$name} hat Urlaub beantragt ({$range}, {$req['net_days']} Tage). Antrag #{$requestId}.",
            'category' => 'approval',
            'type' => Inbox::TYPE_TASK,
            'resolution' => Inbox::RESOLUTION_SHARED,
            'thread_id' => 'vacation_request_' . $requestId,
            'action_url' => '/?tab=operations&request_id=' . $requestId,
            'related_user_id' => $employeeId,
        ]);
    }

    public static function onVacationDecided(int $requestId, string $status, int $adminUserId = 0): void {
        $req = VacationRequest::getById($requestId);
        if (!$req) {
            return;
        }

        if ($adminUserId > 0) {
            Inbox::resolveThread('vacation_request_' . $requestId, $adminUserId);
            Inbox::resolveThread('storno_request_' . $requestId, $adminUserId);
        }

        $range = $req['start_date'] . ' – ' . $req['end_date'];
        $messages = [
            'approved'  => ['Urlaub genehmigt', "Dein Antrag #{$requestId} ({$range}) wurde genehmigt.", 'success'],
            'rejected'  => ['Urlaub abgelehnt', "Dein Antrag #{$requestId} ({$range}) wurde abgelehnt.", 'rejected'],
            'cancelled' => ['Urlaub storniert', "Dein Antrag #{$requestId} ({$range}) wurde storniert.", 'info'],
        ];
        if (!isset($messages[$status])) {
            return;
        }
        [$title, $message, $category] = $messages[$status];
        Inbox::send([
            'to' => (int) $req['user_id'],
            'title' => $title,
            'message' => $message,
            'category' => $category,
            'type' => Inbox::TYPE_INFO,
            'resolution' => Inbox::RESOLUTION_INDIVIDUAL,
            'dedupe' => false,
        ]);
    }

    public static function onStornoRequested(int $requestId, int $employeeId): void {
        $req = VacationRequest::getById($requestId);
        $emp = User::getById($employeeId);
        if (!$req || !$emp) {
            return;
        }

        $name = trim($emp['firstname'] . ' ' . $emp['lastname']);
        $range = $req['start_date'] . ' – ' . $req['end_date'];

        Inbox::send([
            'to' => 'admins',
            'title' => 'Storno angefragt',
            'message' => "{$name} möchte den genehmigten Urlaub ({$range}) stornieren. Antrag #{$requestId}.",
            'category' => 'approval',
            'type' => Inbox::TYPE_TASK,
            'resolution' => Inbox::RESOLUTION_SHARED,
            'thread_id' => 'storno_request_' . $requestId,
            'action_url' => '/?tab=operations&request_id=' . $requestId,
            'related_user_id' => $employeeId,
        ]);
    }

    public static function onStornoWithdrawn(int $requestId, int $employeeId): void {
        Inbox::resolveThread('storno_request_' . $requestId, $employeeId);
    }

    public static function onChangeRequested(int $requestId, int $employeeId): void {
        $req = VacationRequest::getById($requestId);
        $emp = User::getById($employeeId);
        if (!$req || !$emp) {
            return;
        }

        $name = trim($emp['firstname'] . ' ' . $emp['lastname']);
        $current = $req['start_date'] . ' – ' . $req['end_date'];
        $proposed = ($req['wunsch_start_date'] ?? '') . ' – ' . ($req['wunsch_end_date'] ?? '');

        Inbox::send([
            'to' => 'admins',
            'title' => 'Änderungswunsch',
            'message' => "{$name} möchte Urlaub #{$requestId} ändern: bisher {$current}, Wunsch {$proposed}.",
            'category' => 'approval',
            'type' => Inbox::TYPE_TASK,
            'resolution' => Inbox::RESOLUTION_SHARED,
            'thread_id' => 'change_request_' . $requestId,
            'action_url' => '/?tab=operations&request_id=' . $requestId,
            'related_user_id' => $employeeId,
        ]);
    }

    public static function onChangeDecided(int $requestId, bool $approved, int $adminUserId = 0): void {
        $req = VacationRequest::getById($requestId);
        if (!$req) {
            return;
        }

        if ($adminUserId > 0) {
            Inbox::resolveThread('change_request_' . $requestId, $adminUserId);
        }

        $range = $req['start_date'] . ' – ' . $req['end_date'];
        if ($approved) {
            Inbox::send([
                'to' => (int) $req['user_id'],
                'title' => 'Urlaub angepasst',
                'message' => "Dein Urlaub #{$requestId} wurde angepasst. Neuer Zeitraum: {$range}.",
                'category' => 'success',
                'type' => Inbox::TYPE_INFO,
                'resolution' => Inbox::RESOLUTION_INDIVIDUAL,
                'dedupe' => false,
            ]);
            return;
        }

        Inbox::send([
            'to' => (int) $req['user_id'],
            'title' => 'Änderungswunsch abgelehnt',
            'message' => "Dein Änderungswunsch für Antrag #{$requestId} ({$range}) wurde abgelehnt. Der bisherige Zeitraum bleibt gültig.",
            'category' => 'rejected',
            'type' => Inbox::TYPE_INFO,
            'resolution' => Inbox::RESOLUTION_INDIVIDUAL,
            'dedupe' => false,
        ]);
    }

    public static function onVacationModified(int $requestId, string $previousRange, int $adminUserId = 0): void {
        $req = VacationRequest::getById($requestId);
        if (!$req) {
            return;
        }

        $newRange = $req['start_date'] . ' – ' . $req['end_date'];
        Inbox::send([
            'to' => (int) $req['user_id'],
            'title' => 'Urlaub angepasst',
            'message' => "Dein Urlaub #{$requestId} wurde vom Admin angepasst: {$previousRange} → {$newRange}.",
            'category' => 'info',
            'type' => Inbox::TYPE_INFO,
            'resolution' => Inbox::RESOLUTION_INDIVIDUAL,
            'dedupe' => false,
        ]);
    }
}
