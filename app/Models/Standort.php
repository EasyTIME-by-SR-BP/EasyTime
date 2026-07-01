<?php

namespace App\Models;

use App\Core\Database;

class Standort {
    public static function getAll(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT id, ort, kostenstelle, strasse, hausnummer, plz
            FROM standorte
            ORDER BY ort ASC
        ");
        $rows = $stmt->fetchAll();
        return array_map([self::class, 'mapRow'], $rows);
    }

    private static function mapRow(array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'ort' => (string) ($row['ort'] ?? ''),
            'kostenstelle' => isset($row['kostenstelle']) && $row['kostenstelle'] !== '' ? (int) $row['kostenstelle'] : null,
            'strasse' => (string) ($row['strasse'] ?? ''),
            'hausnummer' => (string) ($row['hausnummer'] ?? ''),
            'plz' => isset($row['plz']) && $row['plz'] !== '' ? (int) $row['plz'] : null,
        ];
    }

    public static function getById(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT id, ort, kostenstelle, strasse, hausnummer, plz
            FROM standorte
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::mapRow($row) : null;
    }

    public static function create(string $ort, ?int $kostenstelle = null, ?string $strasse = null, ?string $hausnummer = null, ?int $plz = null): int|false {
        $ort = trim($ort);
        if ($ort === '') {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('
            INSERT INTO standorte (ort, kostenstelle, strasse, hausnummer, plz)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $ort,
            $kostenstelle,
            $strasse !== null && trim($strasse) !== '' ? trim($strasse) : null,
            $hausnummer !== null && trim($hausnummer) !== '' ? trim($hausnummer) : null,
            $plz,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, string $ort, ?int $kostenstelle = null, ?string $strasse = null, ?string $hausnummer = null, ?int $plz = null): bool {
        $ort = trim($ort);
        if ($id <= 0 || $ort === '') {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE standorte
            SET ort = ?, kostenstelle = ?, strasse = ?, hausnummer = ?, plz = ?
            WHERE id = ?
        ');
        return $stmt->execute([
            $ort,
            $kostenstelle,
            $strasse !== null && trim($strasse) !== '' ? trim($strasse) : null,
            $hausnummer !== null && trim($hausnummer) !== '' ? trim($hausnummer) : null,
            $plz,
            $id,
        ]);
    }

    public static function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM mitarbeiter_standorte WHERE standort_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }
        $eventStmt = $db->prepare('SELECT COUNT(*) FROM event WHERE standort_id = ?');
        $eventStmt->execute([$id]);
        if ((int) $eventStmt->fetchColumn() > 0) {
            return false;
        }
        $del = $db->prepare('DELETE FROM standorte WHERE id = ?');
        return $del->execute([$id]);
    }
}
