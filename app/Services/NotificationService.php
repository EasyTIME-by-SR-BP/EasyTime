<?php

namespace App\Services;

use App\Models\Request as VacationRequest;
use App\Models\User;

class NotificationService {
    public static function onPasswordResetRequested(int $employeeId): void {
        $emp = User::getById($employeeId);
        if (!$emp) {
            return;
        }

        $name = trim($emp['firstname'] . ' ' . $emp['lastname']);
        $actionUrl = '/?tab=team&team_view=detail&team_user=' . $employeeId . '&focus=password';

        Inbox::send([
            'to' => 'admins',
            'title' => 'Passwort-Hilfe angefordert',
            'message' => "{$name} ({$emp['email']}, MNR {$emp['mnr']}) hat Passwort-Hilfe angefordert. Bitte neues Passwort setzen.",
            'category' => 'password',
            'type' => Inbox::TYPE_TASK,
            'resolution' => Inbox::RESOLUTION_SHARED,
            'thread_id' => 'pwd_reset_' . $employeeId,
            'action_url' => $actionUrl,
            'related_user_id' => $employeeId,
        ]);

        Inbox::send([
            'to' => $employeeId,
            'title' => 'Passwort-Hilfe angefordert',
            'message' => 'Deine Anfrage wurde an die Administratoren weitergeleitet. Bitte warte, bis ein Administrator dir hilft. Du findest Updates hier in der Inbox.',
            'category' => 'info',
            'type' => Inbox::TYPE_INFO,
            'resolution' => Inbox::RESOLUTION_INDIVIDUAL,
            'dedupe' => false,
        ]);
    }

    public static function onPasswordResetCompleted(int $employeeId, int $adminUserId): void {
        $threadId = 'pwd_reset_' . $employeeId;
        if (!Inbox::hasOpenThread($threadId)) {
            return;
        }

        Inbox::resolveThread($threadId, $adminUserId);

        Inbox::send([
            'to' => $employeeId,
            'title' => 'Passwort zurückgesetzt',
            'message' => 'Ein Administrator hat dein Passwort zurückgesetzt. Du kannst dich jetzt mit dem neuen Passwort anmelden.',
            'category' => 'success',
            'type' => Inbox::TYPE_INFO,
            'resolution' => Inbox::RESOLUTION_INDIVIDUAL,
            'dedupe' => false,
        ]);
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
}
