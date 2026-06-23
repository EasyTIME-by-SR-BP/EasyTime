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
            'department_id' => isset($row['department_id']) ? (int) $row['department_id'] : null,
            'department_name' => $row['department_name'] ?? null,
            'department_color' => null,
        ];
    }

    public static function getAll() {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT
                m.*,
                (
                    SELECT k.id
                    FROM klassen k
                    WHERE k.mitarbeiter_id = m.id
                    ORDER BY k.id ASC
                    LIMIT 1
                ) AS department_id,
                (
                    SELECT k.klasse
                    FROM klassen k
                    WHERE k.mitarbeiter_id = m.id
                    ORDER BY k.id ASC
                    LIMIT 1
                ) AS department_name,
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
        return array_map([self::class, 'mapEmployeeRow'], $rows);
    }

    public static function getById($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT
                m.*,
                (
                    SELECT k.id
                    FROM klassen k
                    WHERE k.mitarbeiter_id = m.id
                    ORDER BY k.id ASC
                    LIMIT 1
                ) AS department_id,
                (
                    SELECT k.klasse
                    FROM klassen k
                    WHERE k.mitarbeiter_id = m.id
                    ORDER BY k.id ASC
                    LIMIT 1
                ) AS department_name,
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
        return self::mapEmployeeRow($row);
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

        return self::mapEmployeeRow($row);
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
        return self::mapEmployeeRow($row);
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

    private static function upsertClassForEmployee(int $employeeId, ?string $className): void {
        $trimmed = trim((string) $className);
        if ($trimmed === '') {
            return;
        }

        $db = Database::getConnection();
        $existingStmt = $db->prepare("SELECT id FROM klassen WHERE mitarbeiter_id = ? LIMIT 1");
        $existingStmt->execute([$employeeId]);
        $existingId = $existingStmt->fetchColumn();
        if ($existingId) {
            $updateStmt = $db->prepare("UPDATE klassen SET klasse = ? WHERE id = ?");
            $updateStmt->execute([$trimmed, $existingId]);
            return;
        }

        $insertStmt = $db->prepare("INSERT INTO klassen (klasse, mitarbeiter_id) VALUES (?, ?)");
        $insertStmt->execute([$trimmed, $employeeId]);
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

    public static function createEmployee($firstname, $lastname, $email, $mnr, $password, $role = 'Employee', $departmentId = null, $customColor = null, $vacationDays = 25, $overtimeHours = 0, $mustChangePassword = false) {
        $db = Database::getConnection();
        $staffId = self::normalizeStaffIdentifier((string) $mnr);
        if ($staffId === '') {
            return false;
        }

        $className = null;
        if ($departmentId !== null && $departmentId !== '') {
            $classStmt = $db->prepare("SELECT klasse FROM klassen WHERE id = ? LIMIT 1");
            $classStmt->execute([(int) $departmentId]);
            $className = $classStmt->fetchColumn() ?: null;
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

            self::upsertClassForEmployee($employeeId, $className);
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

    public static function updateEmployee($id, $firstname, $lastname, $email, $mnr, $password = null, $role = 'Employee', $departmentId = null, $customColor = null, $vacationDays = 25, $overtimeHours = 0) {
        $db = Database::getConnection();
        $staffId = self::normalizeStaffIdentifier((string) $mnr);
        if ($staffId === '') {
            return false;
        }

        $className = null;
        if ($departmentId !== null && $departmentId !== '') {
            $classStmt = $db->prepare("SELECT klasse FROM klassen WHERE id = ? LIMIT 1");
            $classStmt->execute([(int) $departmentId]);
            $className = $classStmt->fetchColumn() ?: null;
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

            self::upsertClassForEmployee((int) $id, $className);
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

    public static function generateResetToken($email) {
        return false;
    }

    public static function verifyResetToken($token) {
        return false;
    }

    public static function clearResetToken($userId) {
        return true;
    }
}
