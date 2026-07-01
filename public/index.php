<?php
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = __DIR__ . '/../' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (file_exists($file)) require $file;
    }
});

use App\Models\User;
use App\Models\Request as VacationRequest;
use App\Models\RequestComment;
use App\Models\Abteilung;
use App\Models\Fuehrerscheinklasse;
use App\Models\Standort;
use App\Models\Mindestbesetzung;
use App\Models\VacationSchedule;
use App\Models\RequestEvent;
use App\Services\NotificationService;
use App\Services\Inbox;
use App\Services\MailService;
use App\Core\I18n;
use App\Core\AustrianHolidays;

session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    MailService::retryStaleJobs(2);
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'de';
}

$action = $_GET['action'] ?? null;

// Handle Language Switch
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = in_array($_GET['lang'], ['en', 'de']) ? $_GET['lang'] : 'de';
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); // strip query to avoid reload loop
    exit;
}

// Ensure database creates everything on first boot
\App\Core\Database::getConnection();

// --- Handle Non-Logged In Actions ---
if (!isset($_SESSION['user_id'])) {
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($requestUri !== '/' && $requestUri !== '') {
        $_SESSION['login_redirect'] = $requestUri;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'login') {
            $user = User::authenticate($_POST['login'], $_POST['password']);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $redirect = (string) ($_SESSION['login_redirect'] ?? '/');
                unset($_SESSION['login_redirect']);
                header('Location: ' . ($redirect !== '' ? $redirect : '/'));
            } else {
                header("Location: /?error=login_failed");
            }
            exit;
        }

        if ($action === 'forgot_password') {
            $login = trim((string) ($_POST['login'] ?? $_POST['email'] ?? ''));
            if ($login !== '') {
                $user = User::findByEmailOrMnr($login);
                if ($user) {
                    NotificationService::onPasswordResetRequested((int) $user['id']);
                }
            }
            header('Location: /?success=password_reset_sent');
            exit;
        }

        if ($action === 'do_reset_password') {
            $token = trim((string) ($_POST['reset_token'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
            $userId = User::verifyResetToken($token);
            if ($userId > 0 && $password !== '' && $password === $passwordConfirm && strlen($password) >= 6) {
                User::updatePassword($userId, password_hash($password, PASSWORD_DEFAULT), true);
                User::clearResetToken($userId);
                header('Location: /?success=password_reset_done');
                exit;
            }
            header('Location: /?reset_token=' . rawurlencode($token) . '&error=reset_failed');
            exit;
        }
    }

    // Default View: Login
    include __DIR__ . '/../app/Views/login.php';
    exit;
}

// --- Logged In Actions ---

if ($action === 'logout') {
    session_destroy();
    header("Location: /");
    exit;
}

// Fetch current logged in user
$currentUser = User::getById($_SESSION['user_id']);
if (!$currentUser) {
    session_destroy();
    header("Location: /");
    exit;
}

$currentRole = $currentUser['role'];
$isAdmin = in_array($currentRole, ['CEO', 'Admin'], true);

if ($action === 'coverage_warnings' && $isAdmin) {
    header('Content-Type: application/json; charset=utf-8');
    $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
    $start = trim((string) ($_GET['start'] ?? ''));
    $end = trim((string) ($_GET['end'] ?? ''));
    $ignoreRequestId = isset($_GET['ignore_request_id']) ? (int) $_GET['ignore_request_id'] : 0;
    if ($userId <= 0 || $start === '' || $end === '' || $end < $start) {
        echo json_encode(['warnings' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'warnings' => VacationRequest::getCoverageWarnings(
            $userId,
            $start,
            $end,
            $ignoreRequestId > 0 ? $ignoreRequestId : null
        ),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'coverage_check') {
    header('Content-Type: application/json; charset=utf-8');
    $start = trim((string) ($_GET['start'] ?? ''));
    $end = trim((string) ($_GET['end'] ?? ''));
    $ignoreRequestId = isset($_GET['ignore_request_id']) ? (int) $_GET['ignore_request_id'] : 0;
    $targetUserId = $isAdmin && isset($_GET['user_id'])
        ? (int) $_GET['user_id']
        : (int) $currentUser['id'];
    if ($targetUserId <= 0 || $start === '' || $end === '' || $end < $start) {
        echo json_encode(['blocked' => false, 'messages' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$isAdmin && $targetUserId !== (int) $currentUser['id']) {
        http_response_code(403);
        echo json_encode(['blocked' => false, 'messages' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $ignore = $ignoreRequestId > 0 ? $ignoreRequestId : null;
    $messages = VacationRequest::getCoverageBlockingMessages($targetUserId, $start, $end, $ignore);
    echo json_encode([
        'blocked' => $messages !== [],
        'messages' => $messages,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'schedule_preview') {
    header('Content-Type: application/json; charset=utf-8');
    $start = trim((string) ($_GET['start'] ?? ''));
    $end = trim((string) ($_GET['end'] ?? ''));
    $targetUserId = $isAdmin && isset($_GET['user_id']) && (int) $_GET['user_id'] > 0
        ? (int) $_GET['user_id']
        : ($isAdmin ? 0 : (int) $currentUser['id']);
    if ($start === '' || $end === '' || $end < $start) {
        echo json_encode(['segments' => [], 'daily_minutes' => VacationSchedule::DEFAULT_DAY_MINUTES], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$isAdmin && $targetUserId !== (int) $currentUser['id']) {
        http_response_code(403);
        echo json_encode(['segments' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'segments' => VacationSchedule::buildDefaultSegments($targetUserId, $start, $end),
        'daily_minutes' => VacationSchedule::getDailyWorkMinutes($targetUserId),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'change_password') {
    $newPw = $_POST['password'] ?? '';
    if ($newPw !== '') {
        User::updatePassword($currentUser['id'], $newPw, true);
    }
    header("Location: /?success=action_success");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_notification_read') {
    $notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;
    if ($notificationId > 0) {
        Inbox::markRead($notificationId, (int) $currentUser['id']);
    }
    $inboxFilter = preg_replace('/[^a-z_]/', '', (string) ($_POST['inbox_filter'] ?? 'all')) ?: 'all';
    header('Location: /?tab=inbox&inbox_filter=' . urlencode($inboxFilter));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'resolve_notification') {
    $notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;
    if ($notificationId > 0) {
        Inbox::resolve($notificationId, (int) $currentUser['id']);
    }
    $inboxFilter = preg_replace('/[^a-z_]/', '', (string) ($_POST['inbox_filter'] ?? 'all')) ?: 'all';
    header('Location: /?tab=inbox&inbox_filter=' . urlencode($inboxFilter));
    exit;
}

$requirePasswordChange = User::mustChangePassword((int) $currentUser['id']);

if ($action === 'calendar_ics') {
    $requestsForCalendar = ($isAdmin)
        ? VacationRequest::getAll()
        : VacationRequest::getByUserId($currentUser['id']);
    $blockedForCalendar = VacationRequest::getBlockedPeriods();

    $filterStart = $_GET['export_start'] ?? null;
    $filterEnd = $_GET['export_end'] ?? null;
    $includeApproved = isset($_GET['include_approved']) ? (bool) $_GET['include_approved'] : true;
    $includePending = isset($_GET['include_pending']) ? (bool) $_GET['include_pending'] : true;
    $includeStorno = isset($_GET['include_storno']) ? (bool) $_GET['include_storno'] : true;
    $includeBlocked = isset($_GET['include_blocked']) ? (bool) $_GET['include_blocked'] : false;

    $statusAllow = [];
    if ($includeApproved) $statusAllow[] = 'approved';
    if ($includePending) $statusAllow[] = 'pending';
    if ($includeStorno) $statusAllow[] = 'storno_requested';
    if (empty($statusAllow)) {
        $statusAllow = ['approved', 'pending', 'storno_requested'];
    }

    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//EasyTime//Vacation Calendar//EN',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH'
    ];

    foreach ($requestsForCalendar as $requestItem) {
        if (in_array($requestItem['status'], ['rejected', 'cancelled'], true)) {
            continue;
        }
        if (!in_array($requestItem['status'], $statusAllow, true)) {
            continue;
        }
        if ($filterStart && $requestItem['end_date'] < $filterStart) {
            continue;
        }
        if ($filterEnd && $requestItem['start_date'] > $filterEnd) {
            continue;
        }

        $title = ($isAdmin)
            ? ($requestItem['firstname'] . ' ' . $requestItem['lastname'] . ' - Vacation')
            : 'Vacation';

        $start = date('Ymd', strtotime($requestItem['start_date']));
        $endExclusive = date('Ymd', strtotime($requestItem['end_date'] . ' +1 day'));
        $created = gmdate('Ymd\THis\Z', strtotime($requestItem['created_at'] ?? 'now'));
        $uid = 'request-' . $requestItem['id'] . '@easytime.local';

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid;
        $lines[] = 'DTSTAMP:' . $created;
        $lines[] = 'DTSTART;VALUE=DATE:' . $start;
        $lines[] = 'DTEND;VALUE=DATE:' . $endExclusive;
        $lines[] = 'SUMMARY:' . str_replace([',', ';'], ['\,', '\;'], $title);
        $lines[] = 'DESCRIPTION:Status ' . $requestItem['status'];
        $lines[] = 'END:VEVENT';
    }

    if ($isAdmin && $includeBlocked) {
        foreach ($blockedForCalendar as $blockedItem) {
            if ($filterStart && $blockedItem['end_date'] < $filterStart) {
                continue;
            }
            if ($filterEnd && $blockedItem['start_date'] > $filterEnd) {
                continue;
            }

            $start = date('Ymd', strtotime($blockedItem['start_date']));
            $endExclusive = date('Ymd', strtotime($blockedItem['end_date'] . ' +1 day'));
            $created = gmdate('Ymd\THis\Z', strtotime($blockedItem['created_at'] ?? 'now'));
            $uid = 'blocked-' . $blockedItem['id'] . '@easytime.local';
            $label = $blockedItem['label'] ?: 'Booking blocked';

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid;
            $lines[] = 'DTSTAMP:' . $created;
            $lines[] = 'DTSTART;VALUE=DATE:' . $start;
            $lines[] = 'DTEND;VALUE=DATE:' . $endExclusive;
            $lines[] = 'SUMMARY:' . str_replace([',', ';'], ['\,', '\;'], $label);
            $lines[] = 'DESCRIPTION:Blocked booking period';
            $lines[] = 'END:VEVENT';
        }
    }

    $lines[] = 'END:VCALENDAR';

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="easytime-calendar.ics"');
    echo implode("\r\n", $lines);
    exit;
}

// Handle Data Manipulating Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_request' && $currentRole === 'Employee') {
        $start = $_POST['start_date'] ?? null;
        $end = $_POST['end_date'] ?? null;
        if ($start && $end && $end >= $start) {
            $today = date('Y-m-d');
            if ($start < $today || $end < $today) {
                header("Location: /?error=past_date");
                exit;
            }
            $netDays = VacationRequest::calculateNetDays($start, $end);
            if ($netDays <= 0) {
                header("Location: /?error=invalid_request");
                exit;
            }
            $isFullDay = ($_POST['partial_schedule'] ?? '0') !== '1';
            $rawSchedule = json_decode((string) ($_POST['schedule_json'] ?? '[]'), true);
            [, $segments] = VacationRequest::resolveScheduleInput((int) $currentUser['id'], $start, $end, $isFullDay, $rawSchedule);
            $created = VacationRequest::create((int) $currentUser['id'], $start, $end, 'vacation', 0, $isFullDay, $segments);
            if ($created === 'fenstertage_exceeded') {
                header("Location: /?error=fenstertage_exceeded");
                exit;
            }
            if ($created === 'coverage_request_denied') {
                header("Location: /?error=coverage_request_denied");
                exit;
            }
            if ($created === 'insufficient_balance') {
                header("Location: /?error=insufficient_balance");
                exit;
            }
            if ($created === false) {
                header("Location: /?error=request_conflict");
                exit;
            }
            NotificationService::onVacationRequested((int) $created, (int) $currentUser['id']);
            RequestEvent::log((int) $created, (int) $currentUser['id'], 'created', $start . ' – ' . $end . ' (' . $netDays . ' Tage)');
            header("Location: /?tab=calendar&success=created");
            exit;
        }
        header("Location: /?error=invalid_request");
        exit;
    }
    
    if ($action === 'withdraw_request' && $currentRole === 'Employee') {
        if (!empty($_POST['request_id'])) {
            $rid = (int) $_POST['request_id'];
            $returnTab = ($_POST['return_tab'] ?? 'calendar') === 'history' ? 'history' : 'calendar';
            $ok = VacationRequest::withdrawRequest($rid, $currentUser['id']);
            header('Location: /?tab=' . $returnTab . '&success=' . ($ok ? 'action_success' : 'invalid_request'));
            exit;
        }
    }

    if ($action === 'request_storno' && $currentRole === 'Employee') {
        if (!empty($_POST['request_id'])) {
            $rid = (int) $_POST['request_id'];
            $returnTab = ($_POST['return_tab'] ?? 'history') === 'calendar' ? 'calendar' : 'history';
            $ok = VacationRequest::requestStorno($rid, $currentUser['id']);
            if ($ok) {
                NotificationService::onStornoRequested($rid, (int) $currentUser['id']);
                RequestEvent::log($rid, (int) $currentUser['id'], 'storno_requested');
                header('Location: /?tab=' . $returnTab . '&request_id=' . $rid . '&success=action_success');
            } else {
                header('Location: /?tab=' . $returnTab . '&request_id=' . $rid . '&error=invalid_request');
            }
            exit;
        }
    }

    if ($action === 'withdraw_storno' && $currentRole === 'Employee') {
        if (!empty($_POST['request_id'])) {
            $rid = (int) $_POST['request_id'];
            $returnTab = ($_POST['return_tab'] ?? 'calendar') === 'history' ? 'history' : 'calendar';
            $ok = VacationRequest::withdrawStornoRequest($rid, $currentUser['id']);
            if ($ok) {
                NotificationService::onStornoWithdrawn($rid, (int) $currentUser['id']);
                RequestEvent::log($rid, (int) $currentUser['id'], 'storno_withdrawn');
            }
            $query = ($ok ? 'success=action_success' : 'error=invalid_request');
            header('Location: /?tab=' . $returnTab . '&request_id=' . $rid . '&' . $query);
            exit;
        }
    }

    if ($action === 'request_change' && $currentRole === 'Employee') {
        $rid = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        $start = $_POST['new_start_date'] ?? null;
        $end = $_POST['new_end_date'] ?? null;
        if ($rid > 0 && $start && $end && $end >= $start) {
            $reqRow = VacationRequest::getById($rid);
            $userId = (int) ($reqRow['user_id'] ?? $currentUser['id']);
            $isFullDay = ($_POST['partial_schedule'] ?? '0') !== '1';
            $rawSchedule = json_decode((string) ($_POST['schedule_json'] ?? '[]'), true);
            [$isFullDay, $segments] = VacationRequest::resolveScheduleInput($userId, $start, $end, $isFullDay, $rawSchedule);
            $netDays = $isFullDay
                ? VacationRequest::calculateNetDays($start, $end)
                : VacationSchedule::minutesToDayEquivalent(VacationSchedule::totalMinutes($segments), $userId);
            if ($netDays <= 0) {
                header('Location: /?error=invalid_request');
                exit;
            }
            $result = VacationRequest::requestChange($rid, (int) $currentUser['id'], $start, $end, $netDays, $isFullDay, $segments);
            if ($result === 'blocked_period') {
                header('Location: /?error=blocked_period');
                exit;
            }
            if ($result === 'request_conflict') {
                header('Location: /?error=request_conflict');
                exit;
            }
            if ($result === 'insufficient_balance') {
                header('Location: /?error=insufficient_balance');
                exit;
            }
            if ($result === 'fenstertage_exceeded') {
                header('Location: /?error=fenstertage_exceeded');
                exit;
            }
            if ($result === 'coverage_request_denied') {
                header('Location: /?error=coverage_request_denied');
                exit;
            }
            if ($result) {
                NotificationService::onChangeRequested($rid, (int) $currentUser['id']);
                RequestEvent::log($rid, (int) $currentUser['id'], 'change_requested', $start . ' – ' . $end . ' (' . $netDays . ' Tage)');
                $returnTab = ($_POST['return_tab'] ?? 'history') === 'calendar' ? 'calendar' : 'history';
                header('Location: /?tab=' . $returnTab . '&request_id=' . $rid . '&success=action_success');
                exit;
            }
            $returnTab = ($_POST['return_tab'] ?? 'history') === 'calendar' ? 'calendar' : 'history';
            header('Location: /?tab=' . $returnTab . '&request_id=' . $rid . '&error=invalid_request');
            exit;
        }
        header('Location: /?error=invalid_request');
        exit;
    }

    if ($action === 'withdraw_change' && $currentRole === 'Employee') {
        if (!empty($_POST['request_id'])) {
            $rid = (int) $_POST['request_id'];
            $returnTab = ($_POST['return_tab'] ?? 'history') === 'calendar' ? 'calendar' : 'history';
            $ok = VacationRequest::withdrawChangeRequest($rid, (int) $currentUser['id']);
            if ($ok) {
                RequestEvent::log($rid, (int) $currentUser['id'], 'change_withdrawn');
            }
            $query = ($ok ? 'success=action_success' : 'error=invalid_request');
            header('Location: /?tab=' . $returnTab . '&request_id=' . $rid . '&' . $query);
            exit;
        }
    }

    if ($action === 'decide_change_request' && $isAdmin) {
        $requestId = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        $decision = (string) ($_POST['decision'] ?? '');
        $start = trim((string) ($_POST['approved_start_date'] ?? ''));
        $end = trim((string) ($_POST['approved_end_date'] ?? ''));
        $comment = trim((string) ($_POST['admin_comment'] ?? ''));
        if ($requestId > 0 && in_array($decision, ['approve', 'reject'], true)) {
            $before = VacationRequest::getById($requestId);
            $startDate = ($start && $end) ? $start : null;
            $endDate = ($start && $end) ? $end : null;
            $isFullDay = null;
            $segments = null;
            if ($decision === 'approve' && $before) {
                $userId = (int) ($before['user_id'] ?? 0);
                $apStart = $startDate ?: (string) ($before['wunsch_start_date'] ?? $before['start_date']);
                $apEnd = $endDate ?: (string) ($before['wunsch_end_date'] ?? $before['end_date']);
                $isFullDay = ($_POST['partial_schedule'] ?? '0') !== '1';
                $rawSchedule = json_decode((string) ($_POST['schedule_json'] ?? '[]'), true);
                [$isFullDay, $segments] = VacationRequest::resolveScheduleInput($userId, $apStart, $apEnd, $isFullDay, $rawSchedule);
            }
            $ok = VacationRequest::decideChange($requestId, $decision === 'approve', $startDate, $endDate, $isFullDay, $segments);
            if ($ok === 'insufficient_balance') {
                header('Location: /?error=insufficient_balance');
                exit;
            }
            if (!$ok) {
                header('Location: /?error=coverage_conflict');
                exit;
            }
            if ($comment !== '') {
                RequestComment::create($requestId, (int) $currentUser['id'], $comment);
            }
            RequestEvent::log(
                $requestId,
                (int) $currentUser['id'],
                $decision === 'approve' ? 'change_approved' : 'change_rejected',
                $before ? ($before['start_date'] . ' – ' . $before['end_date']) : null
            );
            NotificationService::onChangeDecided($requestId, $decision === 'approve', (int) $currentUser['id']);
            header('Location: /?tab=operations&success=decided');
            exit;
        }
        header('Location: /?tab=operations&error=invalid_request');
        exit;
    }

    if ($action === 'admin_modify_vacation' && $isAdmin) {
        $requestId = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        $start = $_POST['start_date'] ?? null;
        $end = $_POST['end_date'] ?? null;
        $comment = trim((string) ($_POST['admin_comment'] ?? ''));
        if ($requestId > 0 && $start && $end && $end >= $start) {
            $before = VacationRequest::getById($requestId);
            $previousRange = $before ? ($before['start_date'] . ' – ' . $before['end_date']) : '';
            $userId = (int) ($before['user_id'] ?? 0);
            $isFullDay = ($_POST['partial_schedule'] ?? '0') !== '1';
            $rawSchedule = json_decode((string) ($_POST['schedule_json'] ?? '[]'), true);
            [$isFullDay, $segments] = VacationRequest::resolveScheduleInput($userId, $start, $end, $isFullDay, $rawSchedule);
            $ok = VacationRequest::adminModifyVacation($requestId, $start, $end, $isFullDay, $segments);
            if ($ok === 'insufficient_balance') {
                header('Location: /?error=insufficient_balance');
                exit;
            }
            if (!$ok) {
                header('Location: /?error=request_conflict');
                exit;
            }
            if ($comment !== '') {
                RequestComment::create($requestId, (int) $currentUser['id'], $comment);
            }
            RequestEvent::log($requestId, (int) $currentUser['id'], 'dates_adjusted', $previousRange . ' → ' . $start . ' – ' . $end);
            NotificationService::onVacationModified($requestId, $previousRange, (int) $currentUser['id']);
            header('Location: /?tab=operations&success=action_success');
            exit;
        }
        header('Location: /?tab=operations&error=invalid_request');
        exit;
    }

    if ($action === 'decide_request' && $isAdmin) {
        $requestId = $_POST['request_id'] ?? null;
        $status = $_POST['status'] ?? null;
        $comment = trim((string) ($_POST['admin_comment'] ?? ''));
        $approvedStart = trim((string) ($_POST['approved_start_date'] ?? ''));
        $approvedEnd = trim((string) ($_POST['approved_end_date'] ?? ''));
        if ($requestId && $status) {
            $before = VacationRequest::getById((int) $requestId);
            $startDate = ($approvedStart && $approvedEnd) ? $approvedStart : null;
            $endDate = ($approvedStart && $approvedEnd) ? $approvedEnd : null;
            $isFullDay = null;
            $segments = null;
            if ((string) $status === 'approved' && $before) {
                $userId = (int) ($before['user_id'] ?? 0);
                $start = $startDate ?: (string) $before['start_date'];
                $end = $endDate ?: (string) $before['end_date'];
                $isFullDay = ($_POST['partial_schedule'] ?? '0') !== '1';
                $rawSchedule = json_decode((string) ($_POST['schedule_json'] ?? '[]'), true);
                [$isFullDay, $segments] = VacationRequest::resolveScheduleInput($userId, $start, $end, $isFullDay, $rawSchedule);
            }
            $ok = VacationRequest::decide($requestId, $currentUser['id'], $status, $comment, $startDate, $endDate, $isFullDay, $segments);
            if ($ok === 'insufficient_balance') {
                header('Location: /?error=insufficient_balance');
                exit;
            }
            if (!$ok) {
                header("Location: /?error=coverage_conflict");
                exit;
            }
            if ($comment !== '') {
                RequestComment::create((int) $requestId, (int) $currentUser['id'], $comment);
            }
            $eventType = match ((string) $status) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                'cancelled' => 'cancelled',
                default => 'updated',
            };
            if ($before && (string) $status === 'approved' && $startDate && $endDate
                && ($before['start_date'] !== $startDate || $before['end_date'] !== $endDate)) {
                RequestEvent::log((int) $requestId, (int) $currentUser['id'], 'dates_adjusted', $before['start_date'] . ' – ' . $before['end_date'] . ' → ' . $startDate . ' – ' . $endDate);
            }
            RequestEvent::log((int) $requestId, (int) $currentUser['id'], $eventType);
            if (in_array((string) $status, ['approved', 'rejected', 'cancelled'], true)) {
                NotificationService::onVacationDecided((int) $requestId, (string) $status, (int) $currentUser['id']);
            }
            header('Location: /?tab=operations&success=decided');
            exit;
        }
    }

    if ($action === 'create_employee' && $isAdmin) {
        if (!isset($_POST['mnr']) || trim((string) $_POST['mnr']) === '') {
            header("Location: /?error=invalid_mnr");
            exit;
        }

        $createdId = User::createEmployee(
            $_POST['firstname'],
            $_POST['lastname'],
            $_POST['email'],
            $_POST['mnr'],
            $_POST['password'],
            $_POST['role'] ?? 'Employee',
            $_POST['license_class_ids'] ?? [],
            $_POST['abteilung_ids'] ?? [],
            $_POST['standort_ids'] ?? [],
            isset($_POST['primary_standort_id']) && $_POST['primary_standort_id'] !== '' ? (int) $_POST['primary_standort_id'] : null,
            isset($_POST['vacation_entitlement_days']) ? (int) $_POST['vacation_entitlement_days'] : 25,
            isset($_POST['overtime_hours']) ? (float) $_POST['overtime_hours'] : 0,
            !empty($_POST['must_change_password'])
        );
        header("Location: /?tab=team&success=" . ($createdId ? "employee_created" : "employee_failed"));
        exit;
    }

    if ($action === 'edit_employee' && $isAdmin) {
        if (!isset($_POST['mnr']) || trim((string) $_POST['mnr']) === '') {
            header("Location: /?error=invalid_mnr");
            exit;
        }
        User::updateEmployee(
            $_POST['emp_id'],
            $_POST['firstname'],
            $_POST['lastname'],
            $_POST['email'],
            $_POST['mnr'],
            $_POST['password'] ?? null,
            $_POST['role'] ?? 'Employee',
            $_POST['license_class_ids'] ?? [],
            $_POST['abteilung_ids'] ?? [],
            $_POST['standort_ids'] ?? [],
            isset($_POST['primary_standort_id']) && $_POST['primary_standort_id'] !== '' ? (int) $_POST['primary_standort_id'] : null,
            isset($_POST['vacation_entitlement_days']) ? (int) $_POST['vacation_entitlement_days'] : 25,
            isset($_POST['overtime_hours']) ? (float) $_POST['overtime_hours'] : 0
        );
        if (!empty($_POST['password'])) {
            // Admin password change — no inbox notification.
        }
        header("Location: /?success=action_success");
        exit;
    }

    if ($action === 'delete_employee' && $isAdmin) {
        $employeeIdToDelete = isset($_POST['emp_id']) ? (int) $_POST['emp_id'] : 0;
        if ($employeeIdToDelete === (int) $currentUser['id']) {
            header("Location: /?error=self_delete_forbidden");
            exit;
        }
        User::deleteEmployee($employeeIdToDelete);
        header("Location: /?success=action_success");
        exit;
    }

    if ($action === 'create_blocked_period' && $isAdmin) {
        $start = $_POST['start_date'] ?? null;
        $end = $_POST['end_date'] ?? null;
        $label = trim($_POST['label'] ?? '');
        $confirmPast = !empty($_POST['confirm_past']);
        $today = date('Y-m-d');
        if ($start && $end && $end >= $start) {
            if (!$confirmPast && ($start < $today || $end < $today)) {
                header("Location: /?error=past_date");
                exit;
            }
            $createdBlocked = VacationRequest::createBlockedPeriod($start, $end, $label ?: null, $currentUser['id']);
            if (!$createdBlocked) {
                header("Location: /?error=blocked_exists");
                exit;
            }
            header('Location: /?tab=operations&success=action_success');
            exit;
        }
        header('Location: /?tab=operations&error=invalid_request');
        exit;
    }

    if ($action === 'admin_create_vacation' && $isAdmin) {
        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $start = $_POST['start_date'] ?? null;
        $end = $_POST['end_date'] ?? null;
        $comment = trim($_POST['admin_comment'] ?? '');
        $confirmPast = !empty($_POST['confirm_past']);
        if ($userId > 0 && $start && $end && $end >= $start) {
            $today = date('Y-m-d');
            if (!$confirmPast && ($start < $today || $end < $today)) {
                header("Location: /?error=past_date");
                exit;
            }
            $netDays = VacationRequest::calculateNetDays($start, $end);
            if ($netDays <= 0) {
                header('Location: /?tab=operations&error=invalid_request');
                exit;
            }
            $isFullDay = ($_POST['partial_schedule'] ?? '0') !== '1';
            $rawSchedule = json_decode((string) ($_POST['schedule_json'] ?? '[]'), true);
            [, $segments] = VacationRequest::resolveScheduleInput($userId, $start, $end, $isFullDay, $rawSchedule);
            $created = VacationRequest::createAdminVacation($userId, $currentUser['id'], $start, $end, null, $comment ?: null, $isFullDay, $segments);
            if ($created === 'insufficient_balance') {
                header('Location: /?error=insufficient_balance');
                exit;
            }
            if (!$created) {
                header('Location: /?error=request_conflict');
                exit;
            }
            if ($comment !== '') {
                RequestComment::create((int) $created, (int) $currentUser['id'], $comment);
            }
            header('Location: /?tab=operations&success=action_success');
            exit;
        }
        header('Location: /?tab=operations&error=invalid_request');
        exit;
    }

    if ($action === 'delete_blocked_period' && $isAdmin) {
        $blockedId = $_POST['blocked_id'] ?? null;
        if ($blockedId) {
            VacationRequest::deleteBlockedPeriod($blockedId);
            header('Location: /?tab=operations&success=action_success');
            exit;
        }
        header('Location: /?tab=operations&error=invalid_request');
        exit;
    }

    if ($action === 'add_request_comment') {
        $requestId = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $request = $requestId > 0 ? VacationRequest::getById($requestId) : false;
        $canComment = $request && ($isAdmin || (int) $request['user_id'] === (int) $currentUser['id']);
        if ($canComment && $comment !== '') {
            RequestComment::create($requestId, (int) $currentUser['id'], $comment);
            $returnTab = ($_POST['return_tab'] ?? '') === 'history'
                ? 'history'
                : (($currentRole === 'Employee') ? 'history' : 'operations');
            $location = '/?tab=' . $returnTab;
            if ($returnTab === 'history') {
                $location .= '&request_id=' . $requestId;
            }
            header('Location: ' . $location . '&success=action_success');
            exit;
        }
        header("Location: /?error=invalid_request");
        exit;
    }

    if ($action === 'update_min_staff' && $isAdmin) {
        $val = max(0, (int) ($_POST['min_staff_available'] ?? 1));
        VacationRequest::setSetting('min_staff_available', (string) $val);
        header("Location: /?tab=settings&success=action_success");
        exit;
    }

    if ($action === 'update_max_fenstertage' && $isAdmin) {
        $val = max(0, (int) ($_POST['max_fenstertage'] ?? 0));
        VacationRequest::setSetting('max_fenstertage', (string) $val);
        header("Location: /?tab=settings&success=action_success");
        exit;
    }

    if ($action === 'update_coverage_rules' && $isAdmin) {
        VacationRequest::setSetting('min_staff_available', (string) max(0, (int) ($_POST['min_staff_available'] ?? 0)));
        VacationRequest::setSetting('max_fenstertage', (string) max(0, (int) ($_POST['max_fenstertage'] ?? 0)));
        Mindestbesetzung::saveStandortRules(is_array($_POST['standort_coverage'] ?? null) ? $_POST['standort_coverage'] : []);
        Mindestbesetzung::saveAbteilungRules(is_array($_POST['abteilung_coverage'] ?? null) ? $_POST['abteilung_coverage'] : []);
        header('Location: /?tab=settings&success=action_success');
        exit;
    }

    if ($action === 'create_license_class' && $isAdmin) {
        $created = Fuehrerscheinklasse::create(trim((string) ($_POST['name'] ?? '')));
        header('Location: /?tab=settings&' . ($created ? 'success=action_success' : 'error=settings_pool_failed'));
        exit;
    }

    if ($action === 'delete_license_class' && $isAdmin) {
        $classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
        $ok = $classId > 0 && Fuehrerscheinklasse::delete($classId);
        header('Location: /?tab=settings&' . ($ok ? 'success=action_success' : 'error=settings_pool_in_use'));
        exit;
    }

    if ($action === 'create_abteilung' && $isAdmin) {
        $created = Abteilung::create(trim((string) ($_POST['name'] ?? '')));
        header('Location: /?tab=settings&' . ($created ? 'success=action_success' : 'error=settings_pool_failed'));
        exit;
    }

    if ($action === 'delete_abteilung' && $isAdmin) {
        $abteilungId = isset($_POST['abteilung_id']) ? (int) $_POST['abteilung_id'] : 0;
        $ok = $abteilungId > 0 && Abteilung::delete($abteilungId);
        header('Location: /?tab=settings&' . ($ok ? 'success=action_success' : 'error=settings_pool_in_use'));
        exit;
    }

    if ($action === 'create_standort' && $isAdmin) {
        $created = Standort::create(
            trim((string) ($_POST['ort'] ?? '')),
            ($_POST['kostenstelle'] ?? '') !== '' ? (int) $_POST['kostenstelle'] : null,
            trim((string) ($_POST['strasse'] ?? '')),
            trim((string) ($_POST['hausnummer'] ?? '')),
            ($_POST['plz'] ?? '') !== '' ? (int) $_POST['plz'] : null
        );
        header('Location: /?tab=settings&' . ($created ? 'standort_view=detail&standort_id=' . (int) $created . '&success=action_success' : 'standort_view=create&error=settings_pool_failed'));
        exit;
    }

    if ($action === 'update_standort' && $isAdmin) {
        $standortId = isset($_POST['standort_id']) ? (int) $_POST['standort_id'] : 0;
        $ok = $standortId > 0 && Standort::update(
            $standortId,
            trim((string) ($_POST['ort'] ?? '')),
            ($_POST['kostenstelle'] ?? '') !== '' ? (int) $_POST['kostenstelle'] : null,
            trim((string) ($_POST['strasse'] ?? '')),
            trim((string) ($_POST['hausnummer'] ?? '')),
            ($_POST['plz'] ?? '') !== '' ? (int) $_POST['plz'] : null
        );
        header('Location: /?tab=settings&standort_view=detail&standort_id=' . $standortId . '&' . ($ok ? 'success=action_success' : 'error=settings_pool_failed'));
        exit;
    }

    if ($action === 'delete_standort' && $isAdmin) {
        $standortId = isset($_POST['standort_id']) ? (int) $_POST['standort_id'] : 0;
        $ok = $standortId > 0 && Standort::delete($standortId);
        header('Location: /?tab=settings&' . ($ok ? 'success=action_success' : 'error=settings_pool_in_use'));
        exit;
    }

    if ($action === 'create_multi_request' && $currentRole === 'Employee') {
        $datesJson = $_POST['multi_dates'] ?? '';
        $dates     = json_decode($datesJson, true);
        if (!is_array($dates) || empty($dates)) {
            header("Location: /?error=invalid_request");
            exit;
        }
        sort($dates);
        $today   = date('Y-m-d');
        $created = 0;
        $failed  = 0;
        $insufficient = false;

        // Aufeinanderfolgende Daten zu Zeiträumen zusammenfassen
        $ranges = [];
        $start  = $dates[0];
        $prev   = $dates[0];
        for ($i = 1; $i < count($dates); $i++) {
            $diff = (strtotime($dates[$i]) - strtotime($prev)) / 86400;
            if ($diff <= 1) {
                $prev = $dates[$i];
            } else {
                $ranges[] = ['start' => $start, 'end' => $prev];
                $start = $dates[$i];
                $prev  = $dates[$i];
            }
        }
        $ranges[] = ['start' => $start, 'end' => $prev];

        foreach ($ranges as $range) {
            if ($range['start'] < $today) { $failed++; continue; }
            $netDays = VacationRequest::calculateNetDays($range['start'], $range['end']);
            $ok = VacationRequest::create($currentUser['id'], $range['start'], $range['end']);
            if ($ok === 'insufficient_balance') {
                $insufficient = true;
                $failed++;
                continue;
            }
            if ($ok === 'fenstertage_exceeded') {
                $failed++;
                continue;
            }
            if (is_int($ok) && $ok > 0) {
                NotificationService::onVacationRequested($ok, (int) $currentUser['id']);
                RequestEvent::log($ok, (int) $currentUser['id'], 'created', $range['start'] . ' – ' . $range['end'] . ' (' . $netDays . ' Tage)');
                $created++;
            } else {
                $failed++;
            }
        }

        if ($insufficient && $created === 0) {
            header('Location: /?tab=calendar&error=insufficient_balance');
            exit;
        }
        header('Location: /?tab=calendar&' . ($created > 0 ? 'success=created' : 'error=request_conflict'));
        exit;
    }

    header("Location: /?error=invalid_request");
    exit;
}

// Data fetching for Views
if ($isAdmin) {
    $requests = VacationRequest::getAll();
    foreach ($requests as &$requestRow) {
        $requestRow['coverage_warnings'] = VacationRequest::getCoverageWarnings(
            (int) ($requestRow['user_id'] ?? 0),
            (string) ($requestRow['start_date'] ?? ''),
            (string) ($requestRow['end_date'] ?? ''),
            (int) ($requestRow['id'] ?? 0)
        );
    }
    unset($requestRow);
    $blockedPeriods = VacationRequest::getBlockedPeriods();
    $employees = User::getAll(); // To show in the team dashboard
    $licenseClassesPool = Fuehrerscheinklasse::getAll();
    $abteilungenPool = Abteilung::getAll();
    $standortePool = Standort::getAll();

    $resolvedTab = $_GET['tab'] ?? ($isAdmin ? 'operations' : 'calendar');
    $settingsStandortView = $resolvedTab === 'settings' ? (string) ($_GET['standort_view'] ?? '') : '';
    $selectedSettingsStandort = null;
    if ($settingsStandortView === 'detail') {
        $settingsStandortId = isset($_GET['standort_id']) ? (int) $_GET['standort_id'] : 0;
        if ($settingsStandortId > 0) {
            $selectedSettingsStandort = Standort::getById($settingsStandortId);
        }
    }

    $selectedTeamUserId = isset($_GET['team_user']) ? (int) $_GET['team_user'] : 0;
    if ($selectedTeamUserId <= 0 && !empty($employees)) {
        $selectedTeamUserId = (int) $employees[0]['id'];
    }
    $selectedTeamUser = null;
    foreach ($employees as $empCandidate) {
        if ((int) $empCandidate['id'] === $selectedTeamUserId) {
            $selectedTeamUser = $empCandidate;
            break;
        }
    }
    if (!$selectedTeamUser && !empty($employees)) {
        $selectedTeamUser = $employees[0];
        $selectedTeamUserId = (int) $selectedTeamUser['id'];
    }

    $selectedTeamUserRequests = [];
    $selectedTeamUserUsedDays = 0;
    $selectedTeamUserStats = ['entitlement' => 0, 'approved' => 0, 'planned' => 0, 'remaining' => 0];
    if ($selectedTeamUser) {
        $selectedTeamUserStats = VacationRequest::calculateUserVacationStats((int) $selectedTeamUser['id']);
        $selectedTeamUserUsedDays = (int) ($selectedTeamUserStats['approved'] ?? 0);
        foreach ($requests as $reqRow) {
            if ((int) $reqRow['user_id'] !== (int) $selectedTeamUser['id']) {
                continue;
            }
            $selectedTeamUserRequests[] = $reqRow;
        }
    }
} else {
    $requests = VacationRequest::getByUserId($currentUser['id']);
    $blockedPeriods = VacationRequest::getBlockedPeriods();
}

$requests = VacationRequest::attachSchedules($requests);

$notificationListAll = Inbox::getForUser((int) $currentUser['id'], 80);
$notificationUnreadCount = Inbox::countUnread((int) $currentUser['id']);
$activeTab = $_GET['tab'] ?? ($isAdmin ? 'operations' : 'calendar');
if (!$isAdmin) {
    if ($activeTab === 'plan') {
        $activeTab = 'calendar';
    }
    if (in_array($activeTab, ['overview', 'comments'], true)) {
        $activeTab = 'history';
    }
}
$inboxFilter = 'all';
$inboxCounts = ['all' => 0, 'unread' => 0, 'tasks' => 0, 'password' => 0, 'approval' => 0, 'info' => 0, 'done' => 0];
if ($isAdmin) {
    if (!in_array($activeTab, ['operations', 'history', 'team', 'settings', 'inbox'], true)) {
        $activeTab = 'operations';
    }
} else {
    if (!in_array($activeTab, ['calendar', 'history', 'inbox'], true)) {
        $activeTab = 'calendar';
    }
}
$selectedHistoryRequestId = ($activeTab === 'history' && isset($_GET['request_id']))
    ? (int) $_GET['request_id']
    : 0;
$highlightNotificationId = ($activeTab === 'inbox' && isset($_GET['notification_id']))
    ? (int) $_GET['notification_id']
    : 0;
if ($activeTab === 'inbox') {
    $allowedInboxFilters = $isAdmin
        ? ['all', 'unread', 'tasks', 'password', 'approval', 'info', 'done']
        : ['all', 'unread', 'info', 'done'];
    $inboxFilter = (string) ($_GET['inbox_filter'] ?? 'all');
    if (!in_array($inboxFilter, $allowedInboxFilters, true)) {
        $inboxFilter = 'all';
    }
    if ($highlightNotificationId > 0) {
        foreach ($notificationListAll as $noteRow) {
            if ((int) ($noteRow['id'] ?? 0) === $highlightNotificationId) {
                $inboxFilter = 'all';
                break;
            }
        }
    }
    $inboxCounts = Inbox::computeCounts($notificationListAll);
}
$notificationList = ($activeTab === 'inbox')
    ? Inbox::filterList($notificationListAll, $inboxFilter)
    : $notificationListAll;
$userVacationStats = $currentRole === 'Employee'
    ? null
    : VacationRequest::calculateUserVacationStats($currentUser['id']);
$minStaffAvailable = (int) VacationRequest::getSetting('min_staff_available', '1');
$maxFenstertage    = (int) VacationRequest::getSetting('max_fenstertage', '0');
$standortCoverageRules = $isAdmin ? Mindestbesetzung::getStandortRules() : [];
$abteilungCoverageRules = $isAdmin ? Mindestbesetzung::getAbteilungRules() : [];
if ($isAdmin) {
    $y = (int) date('Y');
    AustrianHolidays::warmCache([$y, $y + 1]);
}
$requestCommentsById = RequestComment::getByRequestIds(array_column($requests, 'id'));
$requestEventsById = RequestEvent::getGroupedByRequestIds(array_column($requests, 'id'));
$recentAuditLogs = [];
$capacitySummary = $isAdmin ? VacationRequest::getCapacitySummary(date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))) : null;

// Prepare FullCalendar events
$requestStatusEventStyles = [
    'approved'         => ['bg' => '#16A34A', 'text' => '#ffffff'],
    'pending'          => ['bg' => '#F59E0B', 'text' => '#1a1a1a'],
    'storno_requested' => ['bg' => '#EA580C', 'text' => '#ffffff'],
    'change_requested' => ['bg' => '#7C3AED', 'text' => '#ffffff'],
    'rejected'         => ['bg' => '#DC2626', 'text' => '#ffffff'],
    'cancelled'        => ['bg' => '#9CA3AF', 'text' => '#ffffff'],
];

$fcEvents = [];
foreach ($requests as $r) {
    $status = (string) ($r['status'] ?? 'pending');
    $style = $requestStatusEventStyles[$status] ?? $requestStatusEventStyles['pending'];

    if ($isAdmin) {
        $title = $r['firstname'] . ' ' . $r['lastname'];
        $statusTitle = match ($status) {
            'approved'         => I18n::get('emp.status_approved'),
            'pending'          => I18n::get('emp.status_pending'),
            'storno_requested' => I18n::get('emp.status_storno_requested'),
            'change_requested' => I18n::get('emp.status_change_requested'),
            'rejected'         => I18n::get('emp.status_rejected'),
            'cancelled'        => I18n::get('emp.status_cancelled'),
            default            => $status,
        };
        $title .= ' (' . $statusTitle . ')';
    } else {
        $title = match ($status) {
            'approved'         => I18n::get('emp.status_approved'),
            'pending'          => I18n::get('emp.status_pending'),
            'storno_requested' => I18n::get('emp.status_storno_requested'),
            'change_requested' => I18n::get('emp.status_change_requested'),
            'rejected'         => I18n::get('emp.status_rejected'),
            'cancelled'        => I18n::get('emp.status_cancelled'),
            default            => I18n::get('emp.plan'),
        };
    }

    // FullCalendar end bounds are exclusive
    $endDateStr = date('Y-m-d', strtotime($r['end_date'] . ' +1 day'));

    $fcEvents[] = [
        'id' => $r['id'],
        'title' => $title,
        'start' => $r['start_date'],
        'end' => $endDateStr,
        'backgroundColor' => $style['bg'],
        'borderColor' => $style['bg'],
        'textColor' => $style['text'],
        'allDay' => true,
        'extendedProps' => [
            'status' => $status,
            'requestId' => $r['id'],
            'userId' => (int) ($r['user_id'] ?? 0),
        ],
    ];
}

foreach ($blockedPeriods as $b) {
    $endDateStr = date('Y-m-d', strtotime($b['end_date'] . ' +1 day'));
    $fcEvents[] = [
        'id' => 'blocked-' . $b['id'],
        'title' => $b['label'] ?: 'Booking blocked',
        'start' => $b['start_date'],
        'end' => $endDateStr,
        'display' => 'background',
        'backgroundColor' => 'rgba(107, 114, 128, 0.12)',
        'borderColor' => 'rgba(107, 114, 128, 0.35)',
        'allDay' => true,
        'extendedProps' => [
            'isBlocked' => true,
            'blockedId' => $b['id'],
            'blockedLabel' => $b['label'] ?? ''
        ]
    ];
}

include __DIR__ . '/../app/Views/layout.php';
