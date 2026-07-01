<?php

namespace App\Models;

use App\Core\Database;

class User {
    private static function normalizeRole(?string $role): string {
        $normalized = strtolower(trim((string) $role));
        if ($normalized === 'admin' || $normalized === 'ceo' || $normalized === 'administrator') {
            return 'CEO';
        }
        return 'Employee';
    }

    private static function mapEmployeeRow(array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'mnr' => (string) ($row['personal_id'] ?? ''),
            'firstname' => (string) ($row['vorname'] ?? ''),
            'lastname' => (string) ($row['nachname'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'role' => self::normalizeRole($row['berechtigung'] ?? null),
            'vacation_entitlement_days' => (int) ($row['urlaubsanspruch'] ?? 0),
            'overtime_hours' => isset($row['overtime_hours']) ? (float) $row['overtime_hours'] : 0.0,
            'license_classes' => [],
            'license_class_ids' => [],
            'abteilungen' => [],
            'abteilung_ids' => [],
            'standorte' => [],
            'standort_ids' => [],
            'primary_standort_id' => null,
        ];
    }

    /** @param list<array<string, mixed>> $users @return list<array<string, mixed>> */
    private static function attachAssignments(array $users): array {
        if ($users === []) {
            return $users;
        }
        $ids = array_values(array_filter(array_map(static fn (array $u): int => (int) ($u['id'] ?? 0), $users)));
        if ($ids === []) {
            return $users;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db = Database::getConnection();

        $classStmt = $db->prepare("
            SELECT mf.mitarbeiter_id, fk.id, fk.bezeichnung
            FROM mitarbeiter_fuehrerscheinklassen mf
            JOIN fuehrerscheinklassen fk ON fk.id = mf.klasse_id
            WHERE mf.mitarbeiter_id IN ({$placeholders})
            ORDER BY fk.bezeichnung ASC
        ");
        $classStmt->execute($ids);
        $classesByUser = [];
        foreach ($classStmt->fetchAll() as $row) {
            $uid = (int) ($row['mitarbeiter_id'] ?? 0);
            $classesByUser[$uid][] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['bezeichnung'] ?? ''),
            ];
        }

        $standortStmt = $db->prepare("
            SELECT ms.mitarbeiter_id, ms.basis, s.id, s.ort, s.kostenstelle
            FROM mitarbeiter_standorte ms
            JOIN standorte s ON s.id = ms.standort_id
            WHERE ms.mitarbeiter_id IN ({$placeholders})
            ORDER BY ms.basis DESC, s.ort ASC
        ");
        $standortStmt->execute($ids);
        $standorteByUser = [];
        foreach ($standortStmt->fetchAll() as $row) {
            $uid = (int) ($row['mitarbeiter_id'] ?? 0);
            $sid = (int) ($row['id'] ?? 0);
            $standorteByUser[$uid][] = [
                'id' => $sid,
                'ort' => (string) ($row['ort'] ?? ''),
                'kostenstelle' => isset($row['kostenstelle']) ? (int) $row['kostenstelle'] : null,
                'basis' => (int) ($row['basis'] ?? 0) === 1,
            ];
        }

        $abteilungStmt = $db->prepare("
            SELECT ma.mitarbeiter_id, a.id, a.bezeichnung
            FROM mitarbeiter_abteilungen ma
            JOIN abteilungen a ON a.id = ma.abteilung_id
            WHERE ma.mitarbeiter_id IN ({$placeholders})
            ORDER BY a.bezeichnung ASC
        ");
        $abteilungStmt->execute($ids);
        $abteilungenByUser = [];
        foreach ($abteilungStmt->fetchAll() as $row) {
            $uid = (int) ($row['mitarbeiter_id'] ?? 0);
            $abteilungenByUser[$uid][] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['bezeichnung'] ?? ''),
            ];
        }

        foreach ($users as &$user) {
            $uid = (int) ($user['id'] ?? 0);
            $classes = $classesByUser[$uid] ?? [];
            $standorte = $standorteByUser[$uid] ?? [];
            $abteilungen = $abteilungenByUser[$uid] ?? [];
            $user['license_classes'] = $classes;
            $user['license_class_ids'] = array_map(static fn (array $c): int => (int) $c['id'], $classes);
            $user['abteilungen'] = $abteilungen;
            $user['abteilung_ids'] = array_map(static fn (array $a): int => (int) $a['id'], $abteilungen);
            $user['standorte'] = $standorte;
            $user['standort_ids'] = array_map(static fn (array $s): int => (int) $s['id'], $standorte);
            $primary = null;
            foreach ($standorte as $standort) {
                if (!empty($standort['basis'])) {
                    $primary = (int) $standort['id'];
                    break;
                }
            }
            if ($primary === null && $standorte !== []) {
                $primary = (int) $standorte[0]['id'];
            }
            $user['primary_standort_id'] = $primary;
        }
        unset($user);

        return $users;
    }

    public static function getAll() {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT
                m.*,
                (
                    SELECT u.uebertrag_ueberstunden
                    FROM uebertrag u
                    WHERE u.mitarbeiter_id = m.id
                    ORDER BY u.datum DESC
                    LIMIT 1
                ) AS overtime_hours
            FROM mitarbeiter m
            ORDER BY m.nachname ASC, m.vorname ASC
        ");
        $rows = $stmt->fetchAll();
        $users = array_map([self::class, 'mapEmployeeRow'], $rows);
        return self::attachAssignments($users);
    }

    public static function getById($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT
                m.*,
                (
                    SELECT u.uebertrag_ueberstunden
                    FROM uebertrag u
                    WHERE u.mitarbeiter_id = m.id
                    ORDER BY u.datum DESC
                    LIMIT 1
                ) AS overtime_hours
            FROM mitarbeiter m
            WHERE m.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        $user = self::mapEmployeeRow($row);
        return self::attachAssignments([$user])[0];
    }

    /** @return list<int> */
    public static function getAdminUserIds(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT id FROM mitarbeiter
            WHERE LOWER(COALESCE(berechtigung, '')) IN ('administrator', 'admin', 'ceo')
        ");
        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $ids[] = (int) ($row['id'] ?? 0);
        }
        return array_values(array_filter($ids));
    }

    public static function authenticate($emailOrMnr, $password) {
        $db = Database::getConnection();
        $normalizedStaffId = self::normalizeStaffIdentifier((string) $emailOrMnr);
        $stmt = $db->prepare("
            SELECT *
            FROM mitarbeiter
            WHERE email = ? OR personal_id = ? OR personal_id = ?
            LIMIT 1
        ");
        $stmt->execute([$emailOrMnr, $emailOrMnr, $normalizedStaffId]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }

        $storedPassword = (string) ($row['password'] ?? '');
        $isValid = $storedPassword !== '' && (
            hash_equals($storedPassword, $password) ||
            password_verify($password, $storedPassword)
        );
        if (!$isValid) {
            return false;
        }

        $user = self::mapEmployeeRow($row);
        return self::attachAssignments([$user])[0];
    }

    public static function findByEmailOrMnr(string $emailOrMnr): ?array {
        $db = Database::getConnection();
        $normalizedStaffId = self::normalizeStaffIdentifier($emailOrMnr);
        $stmt = $db->prepare("
            SELECT *
            FROM mitarbeiter
            WHERE email = ? OR personal_id = ? OR personal_id = ?
            LIMIT 1
        ");
        $stmt->execute([$emailOrMnr, $emailOrMnr, $normalizedStaffId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $user = self::mapEmployeeRow($row);
        return self::attachAssignments([$user])[0];
    }

    private static function mapRoleToSchemaValue(string $role): string {
        return strtolower($role) === 'admin' || strtolower($role) === 'ceo'
            ? 'Administrator'
            : 'Mitarbeiter';
    }

    private static function normalizeStaffIdentifier(string $mnr): string {
        $trimmed = trim($mnr);
        if ($trimmed === '') {
            return '';
        }
        return ctype_digit($trimmed) ? ('M' . str_pad($trimmed, 3, '0', STR_PAD_LEFT)) : $trimmed;
    }

    /** @param list<int|string> $classIds */
    private static function syncEmployeeLicenseClasses(int $employeeId, array $classIds): void {
        $classIds = array_values(array_unique(array_filter(array_map('intval', $classIds), static fn (int $id): bool => $id > 0)));
        $db = Database::getConnection();
        $db->prepare('DELETE FROM mitarbeiter_fuehrerscheinklassen WHERE mitarbeiter_id = ?')->execute([$employeeId]);
        if ($classIds === []) {
            return;
        }
        $stmt = $db->prepare('INSERT INTO mitarbeiter_fuehrerscheinklassen (mitarbeiter_id, klasse_id) VALUES (?, ?)');
        foreach ($classIds as $classId) {
            $stmt->execute([$employeeId, $classId]);
        }
    }

    /** @param list<int|string> $standortIds */
    private static function syncEmployeeStandorte(int $employeeId, array $standortIds, ?int $primaryStandortId): void {
        $standortIds = array_values(array_unique(array_filter(array_map('intval', $standortIds), static fn (int $id): bool => $id > 0)));
        $basisId = null;
        if ($primaryStandortId && in_array($primaryStandortId, $standortIds, true)) {
            $basisId = $primaryStandortId;
        } elseif ($standortIds !== []) {
            $basisId = $standortIds[0];
        }
        $db = Database::getConnection();
        $db->prepare('DELETE FROM mitarbeiter_standorte WHERE mitarbeiter_id = ?')->execute([$employeeId]);
        if ($standortIds === []) {
            return;
        }
        $stmt = $db->prepare('INSERT INTO mitarbeiter_standorte (mitarbeiter_id, standort_id, basis) VALUES (?, ?, ?)');
        foreach ($standortIds as $standortId) {
            $stmt->execute([$employeeId, $standortId, ($standortId === $basisId) ? 1 : 0]);
        }
    }

    /** @param list<int|string> $abteilungIds */
    private static function syncEmployeeAbteilungen(int $employeeId, array $abteilungIds): void {
        $abteilungIds = array_values(array_unique(array_filter(array_map('intval', $abteilungIds), static fn (int $id): bool => $id > 0)));
        $db = Database::getConnection();
        $db->prepare('DELETE FROM mitarbeiter_abteilungen WHERE mitarbeiter_id = ?')->execute([$employeeId]);
        if ($abteilungIds === []) {
            return;
        }
        $stmt = $db->prepare('INSERT INTO mitarbeiter_abteilungen (mitarbeiter_id, abteilung_id) VALUES (?, ?)');
        foreach ($abteilungIds as $abteilungId) {
            $stmt->execute([$employeeId, $abteilungId]);
        }
    }

    private static function upsertOvertime(int $employeeId, float $overtimeHours): void {
        $db = Database::getConnection();
        $today = date('Y-m-d');
        if (Database::isMysql()) {
            $stmt = $db->prepare("
                INSERT INTO uebertrag (mitarbeiter_id, datum, uebertrag_urlaub, uebertrag_ueberstunden, ang_wochen_std, monats_soll)
                VALUES (?, ?, 0, ?, NULL, NULL)
                ON DUPLICATE KEY UPDATE uebertrag_ueberstunden = VALUES(uebertrag_ueberstunden)
            ");
        } else {
            $stmt = $db->prepare("
                INSERT INTO uebertrag (mitarbeiter_id, datum, uebertrag_urlaub, uebertrag_ueberstunden, ang_wochen_std, monats_soll)
                VALUES (?, ?, 0, ?, NULL, NULL)
                ON CONFLICT(mitarbeiter_id, datum)
                DO UPDATE SET uebertrag_ueberstunden = excluded.uebertrag_ueberstunden
            ");
        }
        $stmt->execute([$employeeId, $today, $overtimeHours]);
    }

    public static function setMustChangePassword(int $userId, bool $mustChange): void {
        $key = 'must_change_pw_' . $userId;
        if ($mustChange) {
            \App\Core\Database::upsertAppSetting($key, '1');
            return;
        }
        $db = Database::getConnection();
        $key = 'must_change_pw_' . $userId;
        if (Database::isMysql()) {
            $stmt = $db->prepare('DELETE FROM app_settings WHERE `key` = ?');
        } else {
            $stmt = $db->prepare('DELETE FROM app_settings WHERE key = ?');
        }
        $stmt->execute([$key]);
    }

    public static function mustChangePassword(int $userId): bool {
        $db = Database::getConnection();
        $key = 'must_change_pw_' . $userId;
        if (Database::isMysql()) {
            $stmt = $db->prepare('SELECT value FROM app_settings WHERE `key` = ? LIMIT 1');
        } else {
            $stmt = $db->prepare('SELECT value FROM app_settings WHERE key = ? LIMIT 1');
        }
        $stmt->execute([$key]);
        return (string) $stmt->fetchColumn() === '1';
    }

    /** @param list<int|string>|null $licenseClassIds @param list<int|string>|null $abteilungIds @param list<int|string>|null $standortIds */
    public static function createEmployee($firstname, $lastname, $email, $mnr, $password, $role = 'Employee', $licenseClassIds = null, $abteilungIds = null, $standortIds = null, $primaryStandortId = null, $vacationDays = 25, $overtimeHours = 0, $mustChangePassword = false) {
        $db = Database::getConnection();
        $staffId = self::normalizeStaffIdentifier((string) $mnr);
        if ($staffId === '') {
            return false;
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO mitarbeiter (personal_id, vorname, nachname, email, status, password, berechtigung, urlaubsanspruch, akt_wochen_std)
                VALUES (?, ?, ?, ?, 0, ?, ?, ?, 40)
            ");
            $stmt->execute([
                $staffId,
                $firstname,
                $lastname,
                $email,
                $password,
                self::mapRoleToSchemaValue((string) $role),
                (int) $vacationDays
            ]);
            $employeeId = (int) $db->lastInsertId();

            self::syncEmployeeLicenseClasses($employeeId, is_array($licenseClassIds) ? $licenseClassIds : []);
            self::syncEmployeeAbteilungen($employeeId, is_array($abteilungIds) ? $abteilungIds : []);
            self::syncEmployeeStandorte($employeeId, is_array($standortIds) ? $standortIds : [], $primaryStandortId !== null ? (int) $primaryStandortId : null);
            self::upsertOvertime($employeeId, (float) $overtimeHours);
            if ($mustChangePassword) {
                self::setMustChangePassword($employeeId, true);
            }

            $db->commit();
            return $employeeId;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /** @param list<int|string>|null $licenseClassIds @param list<int|string>|null $abteilungIds @param list<int|string>|null $standortIds */
    public static function updateEmployee($id, $firstname, $lastname, $email, $mnr, $password = null, $role = 'Employee', $licenseClassIds = null, $abteilungIds = null, $standortIds = null, $primaryStandortId = null, $vacationDays = 25, $overtimeHours = 0) {
        $db = Database::getConnection();
        $staffId = self::normalizeStaffIdentifier((string) $mnr);
        if ($staffId === '') {
            return false;
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("
                UPDATE mitarbeiter
                SET personal_id = ?, vorname = ?, nachname = ?, email = ?, berechtigung = ?, urlaubsanspruch = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $staffId,
                $firstname,
                $lastname,
                $email,
                self::mapRoleToSchemaValue((string) $role),
                (int) $vacationDays,
                (int) $id
            ]);

            if (!empty($password)) {
                $pwStmt = $db->prepare("UPDATE mitarbeiter SET password = ? WHERE id = ?");
                $pwStmt->execute([$password, (int) $id]);
            }

            self::syncEmployeeLicenseClasses((int) $id, is_array($licenseClassIds) ? $licenseClassIds : []);
            self::syncEmployeeAbteilungen((int) $id, is_array($abteilungIds) ? $abteilungIds : []);
            self::syncEmployeeStandorte((int) $id, is_array($standortIds) ? $standortIds : [], $primaryStandortId !== null ? (int) $primaryStandortId : null);
            self::upsertOvertime((int) $id, (float) $overtimeHours);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    public static function deleteEmployee($id) {
        $db = Database::getConnection();
        $employeeId = (int) $id;
        try {
            $db->beginTransaction();

            $db->prepare("DELETE FROM urlaub_kommentar WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM urlaub WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM mitarbeiter_fuehrerscheinklassen WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM mitarbeiter_abteilungen WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM klassen WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM mitarbeiter_dokumente WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM mitarbeiter_standorte WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM eintritt WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM abmeldung WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM aenderungsmeldung WHERE mitarbeiter_id = ? OR bearbeitet_von = ?")->execute([$employeeId, $employeeId]);
            $db->prepare("DELETE FROM taetigkeit WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM uebertrag WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM zuschlag WHERE mitarbeiter_id = ?")->execute([$employeeId]);
            $db->prepare("DELETE FROM mitarbeiter WHERE id = ?")->execute([$employeeId]);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    public static function updatePassword($userId, $newPassword, $clearMustChange = false) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE mitarbeiter SET password = ? WHERE id = ?");
        $ok = $stmt->execute([$newPassword, (int) $userId]);
        if ($ok && $clearMustChange) {
            self::setMustChangePassword((int) $userId, false);
        }
        return $ok;
    }

    public static function createPasswordResetToken(int $userId): string {
        if ($userId <= 0) {
            return '';
        }

        self::clearResetToken($userId);

        $token = bin2hex(random_bytes(16));
        $expiresAt = time() + 3600;
        \App\Core\Database::upsertAppSetting('pwd_reset_' . $token, $userId . ':' . $expiresAt);
        \App\Core\Database::upsertAppSetting('pwd_reset_user_' . $userId, $token);

        return $token;
    }

    public static function verifyResetToken(string $token): int {
        $token = trim($token);
        if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
            return 0;
        }

        $db = Database::getConnection();
        $key = 'pwd_reset_' . $token;
        if (Database::isMysql()) {
            $stmt = $db->prepare('SELECT `value` FROM app_settings WHERE `key` = ? LIMIT 1');
        } else {
            $stmt = $db->prepare('SELECT value FROM app_settings WHERE key = ? LIMIT 1');
        }
        $stmt->execute([$key]);
        $raw = (string) $stmt->fetchColumn();
        if ($raw === '') {
            return 0;
        }

        [$userId, $expiresAt] = array_pad(explode(':', $raw, 2), 2, 0);
        if ((int) $userId <= 0 || (int) $expiresAt < time()) {
            return 0;
        }

        return (int) $userId;
    }

    public static function clearResetToken(int $userId): void {
        if ($userId <= 0) {
            return;
        }

        $db = Database::getConnection();
        $userKey = 'pwd_reset_user_' . $userId;
        if (Database::isMysql()) {
            $stmt = $db->prepare('SELECT `value` FROM app_settings WHERE `key` = ? LIMIT 1');
        } else {
            $stmt = $db->prepare('SELECT value FROM app_settings WHERE key = ? LIMIT 1');
        }
        $stmt->execute([$userKey]);
        $token = trim((string) $stmt->fetchColumn());

        if (Database::isMysql()) {
            $del = $db->prepare('DELETE FROM app_settings WHERE `key` = ?');
        } else {
            $del = $db->prepare('DELETE FROM app_settings WHERE key = ?');
        }
        if ($token !== '') {
            $del->execute(['pwd_reset_' . $token]);
        }
        $del->execute([$userKey]);
    }
}
