<?php

namespace App\Models;

use App\Core\Database;

class Abteilung {
    public static function getAll(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT id, bezeichnung
            FROM abteilungen
            ORDER BY bezeichnung ASC
        ");
        $rows = $stmt->fetchAll();
        return array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['bezeichnung'] ?? ''),
            ];
        }, $rows);
    }

    public static function create(string $name): int|false {
        $name = trim($name);
        if ($name === '') {
            return false;
        }
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare('INSERT INTO abteilungen (bezeichnung) VALUES (?)');
            $stmt->execute([$name]);
            return (int) $db->lastInsertId();
        } catch (\PDOException $e) {
            return false;
        }
    }

    public static function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM mitarbeiter_abteilungen WHERE abteilung_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }
        $del = $db->prepare('DELETE FROM abteilungen WHERE id = ?');
        return $del->execute([$id]);
    }
}
