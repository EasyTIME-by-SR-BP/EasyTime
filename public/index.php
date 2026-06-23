<?php
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = __DIR__ . '/../' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (file_exists($file)) require $file;
    }
});

use App\Models\User;
use App\Models\Request as VacationRequest;
use App\Models\RequestComment;
use App\Models\Department;
use App\Models\RequestEvent;
use App\Services\NotificationService;
use App\Services\Inbox;
use App\Core\I18n;
use App\Core\AustrianHolidays;

session_start();

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'login') {
            $user = User::authenticate($_POST['login'], $_POST['password']);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                header("Location: /");
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
            header('Location: /?success=password_reset_requested');
            exit;
        }

        if ($action === 'do_reset_password') {
            header('Location: /?error=invalid_request');
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
        $netDays = $_POST['net_days'] ?? null;
        if ($start && $end && $netDays) {
            $today = date('Y-m-d');
            if ($start < $today || $end < $today) {
                header("Location: /?error=past_date");
                exit;
            }
            $created = VacationRequest::create($currentUser['id'], $start, $end, $netDays);
            if ($created === 'fenstertage_exceeded') {
                header("Location: /?error=fenstertage_exceeded");
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
    }
    
    if ($action === 'withdraw_request' && $currentRole === 'Employee') {
        if (!empty($_POST['request_id'])) {
            $rid = (int) $_POST['request_id'];
            VacationRequest::withdrawRequest($rid, $currentUser['id']);
            RequestEvent::log($rid, (int) $currentUser['id'], 'withdrawn');
            header("Location: /?tab=history&request_id=" . $rid . "&success=action_success");
            exit;
        }
    }

    if ($action === 'request_storno' && $currentRole === 'Employee') {
        if (!empty($_POST['request_id'])) {
            $rid = (int) $_POST['request_id'];
            if (VacationRequest::requestStorno($rid, $currentUser['id'])) {
                NotificationService::onStornoRequested($rid, (int) $currentUser['id']);
                RequestEvent::log($rid, (int) $currentUser['id'], 'storno_requested');
            }
            header("Location: /?tab=history&request_id=" . $rid . "&success=action_success");
            exit;
        }
    }

    if ($action === 'withdraw_storno' && $currentRole === 'Employee') {
        if (!empty($_POST['request_id'])) {
            $rid = (int) $_POST['request_id'];
            if (VacationRequest::withdrawStornoRequest($rid, $currentUser['id'])) {
                NotificationService::onStornoWithdrawn($rid, (int) $currentUser['id']);
                RequestEvent::log($rid, (int) $currentUser['id'], 'storno_withdrawn');
            }
            $returnTab = ($_POST['return_tab'] ?? 'calendar') === 'history' ? 'history' : 'calendar';
            $location = '/?tab=' . $returnTab . '&success=action_success';
            if ($returnTab === 'history' || $returnTab === 'calendar') {
                $location = '/?tab=' . $returnTab . '&request_id=' . $rid . '&success=action_success';
            }
            header('Location: ' . $location);
            exit;
        }
    }

    if ($action === 'request_change' && $currentRole === 'Employee') {
        $rid = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
        $start = $_POST['new_start_date'] ?? null;
        $end = $_POST['new_end_date'] ?? null;
        if ($rid > 0 && $start && $end && $end >= $start) {
            $netDays = VacationRequest::calculateNetDays($start, $end);
            if ($netDays <= 0) {
                header('Location: /?error=invalid_request');
                exit;
            }
            $result = VacationRequest::requestChange($rid, (int) $currentUser['id'], $start, $end, $netDays);
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
            if ($result) {
                NotificationService::onChangeRequested($rid, (int) $currentUser['id']);
                RequestEvent::log($rid, (int) $currentUser['id'], 'change_requested', $start . ' – ' . $end . ' (' . $netDays . ' Tage)');
            }
            $returnTab = ($_POST['return_tab'] ?? 'history') === 'calendar' ? 'calendar' : 'history';
            header('Location: /?tab=' . $returnTab . '&request_id=' . $rid . '&success=action_success');
            exit;
        }
        header('Location: /?error=invalid_request');
        exit;
    }

    if ($action === 'withdraw_change' && $currentRole === 'Employee') {
        if (!empty($_POST['request_id'])) {
            $rid = (int) $_POST['request_id'];
            if (VacationRequest::withdrawChangeRequest($rid, (int) $currentUser['id'])) {
                RequestEvent::log($rid, (int) $currentUser['id'], 'change_withdrawn');
            }
            $returnTab = ($_POST['return_tab'] ?? 'history') === 'calendar' ? 'calendar' : 'history';
            header('Location: /?tab=' . $returnTab . '&request_id=' . $rid . '&success=action_success');
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
            $ok = VacationRequest::decideChange($requestId, $decision === 'approve', $startDate, $endDate);
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
            header('Location: /?success=decided');
            exit;
        }
        header('Location: /?error=invalid_request');
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
            $ok = VacationRequest::adminModifyVacation($requestId, $start, $end);
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
            header('Location: /?success=action_success');
            exit;
        }
        header('Location: /?error=invalid_request');
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
            $ok = VacationRequest::decide($requestId, $currentUser['id'], $status, $comment, $startDate, $endDate);
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
            header("Location: /?success=decided");
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
            ($_POST['department_id'] ?? '') !== '' ? $_POST['department_id'] : null,
            null,
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
            ($_POST['department_id'] ?? '') !== '' ? $_POST['department_id'] : null,
            null,
            isset($_POST['vacation_entitlement_days']) ? (int) $_POST['vacation_entitlement_days'] : 25,
            isset($_POST['overtime_hours']) ? (float) $_POST['overtime_hours'] : 0
        );
        if (!empty($_POST['password'])) {
            NotificationService::onPasswordResetCompleted((int) $_POST['emp_id'], (int) $currentUser['id']);
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
            header("Location: /?success=action_success");
            exit;
        }
        header("Location: /?error=invalid_request");
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
            $netDays = (int) ((strtotime($end) - strtotime($start)) / 86400) + 1;
            if ($netDays <= 0) {
                header("Location: /?error=invalid_request");
                exit;
            }
            $created = VacationRequest::createAdminVacation($userId, $currentUser['id'], $start, $end, $netDays, $comment ?: null);
            if (!$created) {
                header("Location: /?error=request_conflict");
                exit;
            }
            if ($comment !== '') {
                RequestComment::create((int) $created, (int) $currentUser['id'], $comment);
            }
            header("Location: /?success=action_success");
            exit;
        }
        header("Location: /?error=invalid_request");
        exit;
    }

    if ($action === 'delete_blocked_period' && $isAdmin) {
        $blockedId = $_POST['blocked_id'] ?? null;
        if ($blockedId) {
            VacationRequest::deleteBlockedPeriod($blockedId);
            header("Location: /?success=action_success");
            exit;
        }
        header("Location: /?error=invalid_request");
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
            $netDays = (int) ((strtotime($range['end']) - strtotime($range['start'])) / 86400) + 1;
            $ok = VacationRequest::create($currentUser['id'], $range['start'], $range['end'], $netDays);
            if (is_int($ok) && $ok > 0) {
                NotificationService::onVacationRequested($ok, (int) $currentUser['id']);
                RequestEvent::log($ok, (int) $currentUser['id'], 'created', $range['start'] . ' – ' . $range['end'] . ' (' . $netDays . ' Tage)');
                $created++;
            } else {
                $failed++;
            }
        }

        header("Location: /?tab=calendar&" . ($created > 0 ? "success=created" : "error=request_conflict"));
        exit;
    }

    header("Location: /?error=invalid_request");
    exit;
}

// Data fetching for Views
if ($isAdmin) {
    $requests = VacationRequest::getAll();
    $blockedPeriods = VacationRequest::getBlockedPeriods();
    $employees = User::getAll(); // To show in the team dashboard
    $departments = Department::getAll();

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
    if ($selectedTeamUser) {
        foreach ($requests as $reqRow) {
            if ((int) $reqRow['user_id'] !== (int) $selectedTeamUser['id']) {
                continue;
            }
            $selectedTeamUserRequests[] = $reqRow;
            if ($reqRow['status'] === 'approved') {
                $selectedTeamUserUsedDays += (int) $reqRow['net_days'];
            }
        }
    }
} else {
    $requests = VacationRequest::getByUserId($currentUser['id']);
    $blockedPeriods = VacationRequest::getBlockedPeriods();
}

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
if ($activeTab === 'inbox') {
    $allowedInboxFilters = $isAdmin
        ? ['all', 'unread', 'tasks', 'password', 'approval', 'info', 'done']
        : ['all', 'unread', 'info', 'done'];
    $inboxFilter = (string) ($_GET['inbox_filter'] ?? 'all');
    if (!in_array($inboxFilter, $allowedInboxFilters, true)) {
        $inboxFilter = 'all';
    }
    $inboxCounts = Inbox::computeCounts($notificationListAll);
}
$notificationList = ($activeTab === 'inbox')
    ? Inbox::filterList($notificationListAll, $inboxFilter)
    : $notificationListAll;
$userVacationStats = VacationRequest::calculateUserVacationStats($currentUser['id']);
$minStaffAvailable = (int) VacationRequest::getSetting('min_staff_available', '1');
$maxFenstertage    = (int) VacationRequest::getSetting('max_fenstertage', '0');
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
