<?php

class Package
{
    public static function find(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM packages WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM packages';
        if ($activeOnly) $sql .= " WHERE status = 'active'";
        $sql .= ' ORDER BY entry_fee ASC';
        return db()->query($sql)->fetchAll();
    }

    public static function getIndirectLevels(int $packageId): array
    {
        $st = db()->prepare(
            'SELECT level, bonus FROM package_indirect_levels WHERE package_id = ? ORDER BY level'
        );
        $st->execute([$packageId]);
        $rows   = $st->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r['level']] = (float)$r['bonus'];
        }
        return $result;
    }

    public static function save(array $data, ?int $id = null): int
    {
        $pdo = db();

        $fields = [
            'name'             => $data['name'],
            'entry_fee'        => $data['entry_fee'],
            'pairing_bonus'    => $data['pairing_bonus'],
            'daily_pair_cap'   => $data['daily_pair_cap'],
            'direct_ref_bonus' => $data['direct_ref_bonus'],
            'status'           => $data['status'] ?? 'active',
        ];

        if ($id) {
            $sets = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($fields)));
            $st   = $pdo->prepare("UPDATE packages SET {$sets} WHERE id = ?");
            $st->execute([...array_values($fields), $id]);
        } else {
            $cols = implode(', ', array_keys($fields));
            $phs  = implode(', ', array_fill(0, count($fields), '?'));
            $st   = $pdo->prepare("INSERT INTO packages ({$cols}) VALUES ({$phs})");
            $st->execute(array_values($fields));
            $id   = (int)$pdo->lastInsertId();
        }

        // Rebuild indirect levels (delete + re-insert)
        $pdo->prepare('DELETE FROM package_indirect_levels WHERE package_id = ?')->execute([$id]);
        $levels = $data['indirect_levels'] ?? []; // array [1=>300, 2=>200, ...]
        $st = $pdo->prepare(
            'INSERT INTO package_indirect_levels (package_id, level, bonus) VALUES (?, ?, ?)'
        );
        for ($lvl = 1; $lvl <= 10; $lvl++) {
            $bonus = (float)($levels[$lvl] ?? 0);
            $st->execute([$id, $lvl, $bonus]);
        }

        return $id;
    }

    public static function delete(int $id): bool
    {
        // Only allow deletion if no members use this package
        $inUse = db()->query("SELECT COUNT(*) FROM users WHERE package_id = {$id}")->fetchColumn();
        if ($inUse > 0) return false;
        db()->prepare('DELETE FROM packages WHERE id = ?')->execute([$id]);
        return true;
    }

    public static function withLevels(int $id): ?array
    {
        $pkg = self::find($id);
        if (!$pkg) return null;
        $pkg['indirect_levels'] = self::getIndirectLevels($id);
        return $pkg;
    }
}
