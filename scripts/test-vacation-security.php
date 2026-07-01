<?php
/**
 * Urlaubs-Flow & Kontingent-Tests (CLI).
 * Usage: php scripts/test-vacation-security.php
 */

declare(strict_types=1);

spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = dirname(__DIR__) . '/' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

putenv('DB_DRIVER=sqlite');
$_ENV['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_DRIVER'] = 'sqlite';

use App\Core\Database;
use App\Core\AustrianHolidays;
use App\Models\Request as VacationRequest;

function assertTrue(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException('FAIL: ' . $msg);
    }
    echo "OK: {$msg}\n";
}

function assertEquals(mixed $expected, mixed $actual, string $msg): void {
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf('FAIL: %s (expected %s, got %s)', $msg, var_export($expected, true), var_export($actual, true)));
    }
    echo "OK: {$msg}\n";
}

function futureRange(int $offsetStart, int $workdays): array {
    $cursor = strtotime("+{$offsetStart} days");
    $start = null;
    $end = null;
    $count = 0;
    while ($count < $workdays) {
        $ymd = date('Y-m-d', $cursor);
        $dow = (int) date('N', $cursor);
        $year = (int) date('Y', $cursor);
        $isHoliday = in_array($ymd, AustrianHolidays::getDatesForYear($year), true);
        if ($dow <= 5 && !$isHoliday) {
            if ($start === null) {
                $start = $ymd;
            }
            $end = $ymd;
            $count++;
        }
        $cursor = strtotime('+1 day', $cursor);
    }
    return [$start, $end];
}

function setEntitlement(PDO $db, int $userId, int $days): void {
    $stmt = $db->prepare('UPDATE mitarbeiter SET urlaubsanspruch = ? WHERE id = ?');
    $stmt->execute([$days, $userId]);
}

function deleteUserVacations(PDO $db, int $userId): void {
    $db->prepare('DELETE FROM urlaub_ereignis WHERE urlaub_id IN (SELECT id FROM urlaub WHERE mitarbeiter_id = ?)')->execute([$userId]);
    $db->prepare('DELETE FROM urlaub WHERE mitarbeiter_id = ?')->execute([$userId]);
}

function findEmployeeId(PDO $db): int {
    $id = (int) $db->query("SELECT id FROM mitarbeiter WHERE LOWER(COALESCE(berechtigung, '')) = 'mitarbeiter' ORDER BY id LIMIT 1")->fetchColumn();
    if ($id <= 0) {
        throw new RuntimeException('No employee found – run pnpm db:seed first.');
    }
    return $id;
}

function findOtherEmployeeId(PDO $db, int $exclude): int {
    $stmt = $db->prepare("SELECT id FROM mitarbeiter WHERE LOWER(COALESCE(berechtigung, '')) = 'mitarbeiter' AND id != ? ORDER BY id LIMIT 1");
    $stmt->execute([$exclude]);
    $id = (int) $stmt->fetchColumn();
    if ($id <= 0) {
        throw new RuntimeException('Need at least two employees for IDOR test.');
    }
    return $id;
}

$db = Database::getConnection();
$employeeId = findEmployeeId($db);
$otherEmployeeId = findOtherEmployeeId($db, $employeeId);
$adminId = (int) $db->query("SELECT id FROM mitarbeiter WHERE LOWER(COALESCE(berechtigung, '')) IN ('administrator', 'admin', 'ceo') ORDER BY id LIMIT 1")->fetchColumn();

deleteUserVacations($db, $employeeId);
setEntitlement($db, $employeeId, 10);

echo "Testing employee #{$employeeId}\n\n";

// 1) Empty balance stats
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(10, $stats['remaining'], 'initial remaining = entitlement');

// 2) Create pending reserves days
[$s1, $e1] = futureRange(30, 3);
$id1 = VacationRequest::create($employeeId, $s1, $e1);
assertTrue(is_int($id1) && $id1 > 0, 'create pending 3 days');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(3, $stats['planned'], 'planned after create');
assertEquals(7, $stats['remaining'], 'remaining after create');

// 3) Reject frees days
assertTrue(VacationRequest::decide($id1, $adminId, 'rejected') === true, 'reject pending');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(0, $stats['planned'], 'planned after reject');
assertEquals(10, $stats['remaining'], 'remaining restored after reject');

// 4) Cannot approve rejected without balance check bypass – re-approve 3 days OK
assertTrue(VacationRequest::decide($id1, $adminId, 'approved') === true, 're-approve rejected request');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(3, $stats['approved'], 'approved after re-approve');
assertEquals(7, $stats['remaining'], 'remaining after approve');

// 5) Storno flow
assertTrue(VacationRequest::requestStorno($id1, $employeeId) === true, 'request storno');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(3, $stats['planned'], 'storno_requested counts as planned');
assertTrue(VacationRequest::decide($id1, $adminId, 'cancelled') === true, 'approve storno -> cancelled');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(0, $stats['approved'], 'approved cleared after cancel');
assertEquals(0, $stats['planned'], 'planned cleared after cancel');
assertEquals(10, $stats['remaining'], 'remaining fully restored after cancel');

// 6) Employee create is not blocked by app-side remaining balance
[$s2, $e2] = futureRange(60, 11);
$result = VacationRequest::create($employeeId, $s2, $e2);
assertTrue(is_int($result) && $result > 0, 'create allowed even when exceeding remaining in app stats');
VacationRequest::withdrawRequest((int) $result, $employeeId);

// 7) IDOR withdraw
[$s3, $e3] = futureRange(90, 2);
$id3 = VacationRequest::create($employeeId, $s3, $e3);
assertTrue(is_int($id3) && $id3 > 0, 'create for IDOR test');
assertTrue(VacationRequest::withdrawRequest($id3, $otherEmployeeId) === false, 'other employee cannot withdraw');
assertTrue(VacationRequest::withdrawRequest($id3, $employeeId) === true, 'owner can withdraw');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(10, $stats['remaining'], 'remaining after withdraw delete');

// 8) Tampered net_days ignored – create always uses date range
[$s4, $e4] = futureRange(120, 5);
$id4 = VacationRequest::create($employeeId, $s4, $e4);
assertTrue(is_int($id4) && $id4 > 0, 'create 5-day range');
$req = VacationRequest::getById($id4);
assertEquals(5, (int) $req['net_days'], 'stored days match calculated range');

// 9) Full lifecycle: pending -> approve -> storno -> decline storno
assertTrue(VacationRequest::decide($id4, $adminId, 'approved') === true, 'approve pending');
assertTrue(VacationRequest::requestStorno($id4, $employeeId) === true, 'storno on approved');
assertTrue(VacationRequest::decide($id4, $adminId, 'approved') === true, 'decline storno');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(5, $stats['approved'], 'still approved after declined storno');
assertEquals(5, $stats['remaining'], 'remaining correct after declined storno');

// 10) Invalid transition blocked
[$s5, $e5] = futureRange(150, 2);
$id5 = VacationRequest::create($employeeId, $s5, $e5);
assertTrue(VacationRequest::decide($id5, $adminId, 'cancelled') === false, 'cannot cancel pending directly');

// 11) IDOR storno / change
deleteUserVacations($db, $employeeId);
setEntitlement($db, $employeeId, 10);
[$s6, $e6] = futureRange(180, 4);
$id6 = VacationRequest::create($employeeId, $s6, $e6);
assertTrue(is_int($id6) && $id6 > 0, 'create for storno IDOR');
assertTrue(VacationRequest::decide($id6, $adminId, 'approved') === true, 'approve for storno IDOR');
assertTrue(VacationRequest::requestStorno($id6, $otherEmployeeId) === false, 'other employee cannot request storno');
assertTrue(VacationRequest::requestStorno($id6, $employeeId) === true, 'owner can request storno');
assertTrue(VacationRequest::withdrawStornoRequest($id6, $otherEmployeeId) === false, 'other cannot withdraw storno');
assertTrue(VacationRequest::withdrawStornoRequest($id6, $employeeId) === true, 'owner can withdraw storno');

// 12) Change request reserves wunsch days while pending
deleteUserVacations($db, $employeeId);
setEntitlement($db, $employeeId, 10);
[$s7, $e7] = futureRange(200, 3);
[$s7b, $e7b] = futureRange(210, 6);
$id7 = VacationRequest::create($employeeId, $s7, $e7);
assertTrue(VacationRequest::decide($id7, $adminId, 'approved') === true, 'approve for change test');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(7, $stats['remaining'], 'remaining before change request');
assertTrue(VacationRequest::requestChange($id7, $employeeId, $s7b, $e7b, 6) === true, 'request longer change');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(6, $stats['planned'], 'change_requested reserves wunsch days');
assertEquals(4, $stats['remaining'], 'remaining reduced while change pending');
assertTrue(VacationRequest::decideChange($id7, false) === true, 'reject change restores balance');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(0, $stats['planned'], 'planned cleared after change reject');
assertEquals(7, $stats['remaining'], 'remaining restored after change reject');

// 13) Approve change applies new dates
assertTrue(VacationRequest::requestChange($id7, $employeeId, $s7b, $e7b, 6) === true, 'request change again');
assertTrue(VacationRequest::decideChange($id7, true) === true, 'approve change');
$stats = VacationRequest::calculateUserVacationStats($employeeId);
assertEquals(6, $stats['approved'], 'approved days updated after change');
assertEquals(4, $stats['remaining'], 'remaining after approved change');

// 14) Change request ignores tampered day count
[$s8, $e8] = futureRange(240, 2);
$id8 = VacationRequest::create($employeeId, $s8, $e8);
assertTrue(VacationRequest::decide($id8, $adminId, 'approved') === true, 'approve for tamper test');
[$s8b, $e8b] = futureRange(250, 4);
assertTrue(VacationRequest::requestChange($id8, $employeeId, $s8b, $e8b, 1) === true, 'change with tampered net_days param');
$req8 = VacationRequest::getById($id8);
assertEquals(4, (int) $req8['wunsch_net_days'], 'wunsch days match calculated range');

deleteUserVacations($db, $employeeId);

echo "\nAll vacation security tests passed.\n";
