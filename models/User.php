<?php

class User
{
    // ── Registration ──────────────────────────────────────────────────────────

    public static function register(array $data): int
    {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Verify binary slot is free
            $slotCheck = $pdo->prepare("
                SELECT COUNT(*) FROM users
                WHERE binary_parent_id = ? AND binary_position = ?
            ");
            $slotCheck->execute([$data['binary_parent_id'], $data['binary_position']]);
            if ((int)$slotCheck->fetchColumn() > 0) {
                throw new RuntimeException('That binary position is already taken.');
            }

            // Insert the new member
            $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("
                INSERT INTO users
                  (username, password_hash, package_id, reg_code_id,
                   sponsor_id, binary_parent_id, binary_position)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $data['username'],
                $hash,
                $data['package_id'],
                $data['reg_code_id'],
                $data['sponsor_id'],
                $data['binary_parent_id'],
                $data['binary_position'],
            ]);
            $newId = (int)$pdo->lastInsertId();

            // Mark registration code as used
            $pdo->prepare("
                UPDATE reg_codes SET status = 'used', used_by = ?, used_at = NOW()
                WHERE id = ?
            ")->execute([$newId, $data['reg_code_id']]);

            $pdo->commit();

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        // ── Real-time commissions (outside transaction to prevent lock contention) ──
        // These each use their own atomic updates.

        // 1. Walk binary tree upward → fire pairing bonuses on every ancestor
        Commission::processBinaryPlacement($newId, $data['binary_parent_id'], $data['binary_position']);

        // 2. Direct referral bonus → sponsor
        Commission::processDirectReferral($data['sponsor_id'], $newId, $data['package_id']);

        // 3. Indirect referral bonuses → up to 10 levels in sponsor chain
        Commission::processIndirectReferral($data['sponsor_id'], $newId, $data['package_id']);

        return $newId;
    }

    // ── Finders ───────────────────────────────────────────────────────────────

    public static function find(int $id): ?array
    {
        $st = db()->prepare("
            SELECT u.*,
                   p.name          AS package_name,
                   p.pairing_bonus,
                   p.daily_pair_cap,
                   p.direct_ref_bonus,
                   sp.username     AS sponsor_username,
                   bp.username     AS binary_parent_username
            FROM   users u
            LEFT JOIN packages p ON p.id = u.package_id
            LEFT JOIN users sp   ON sp.id = u.sponsor_id
            LEFT JOIN users bp   ON bp.id = u.binary_parent_id
            WHERE  u.id = ?
        ");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        return $st->fetch() ?: null;
    }

    public static function usernameExists(string $username): bool
    {
        $st = db()->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $st->execute([$username]);
        return (bool)$st->fetchColumn();
    }

    // ── Profile Update ────────────────────────────────────────────────────────

    public static function updateProfile(int $id, array $data): bool
    {
        $allowed = ['full_name', 'email', 'mobile', 'gcash_number', 'address', 'photo'];
        $fields  = [];
        $values  = [];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;

        $values[] = $id;
        $st = db()->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        return $st->execute($values);
    }

    public static function updatePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $st   = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        return $st->execute([$hash, $id]);
    }

    public static function verifyPassword(int $id, string $password): bool
    {
        $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$id]);
        $hash = $st->fetchColumn();
        return $hash && password_verify($password, $hash);
    }

    // ── Today's Pairing Status (for dashboard widget) ─────────────────────────

    public static function todayPairingStatus(int $userId): array
    {
        $u = self::find($userId);
        if (!$u) return [];

        $cap       = (int)($u['daily_pair_cap'] ?? 3);
        $paidToday = (int)$u['pairs_paid_today'];
        $remaining = max(0, $cap - $paidToday);
        $bonus     = (float)($u['pairing_bonus'] ?? 0);

        return [
            'left_count'       => (int)$u['left_count'],
            'right_count'      => (int)$u['right_count'],
            'pairs_paid'       => (int)$u['pairs_paid'],
            'pairs_flushed'    => (int)$u['pairs_flushed'],
            'pairs_paid_today' => $paidToday,
            'daily_cap'        => $cap,
            'cap_remaining'    => $remaining,
            'cap_percent'      => $cap > 0 ? round(($paidToday / $cap) * 100) : 0,
            'pairing_bonus'    => $bonus,
            'earned_today'     => $paidToday * $bonus,
        ];
    }

    // ── Binary Slot Check ─────────────────────────────────────────────────────

    public static function isSlotFree(int $parentId, string $position): bool
    {
        $st = db()->prepare("
            SELECT COUNT(*) FROM users
            WHERE binary_parent_id = ? AND binary_position = ?
        ");
        $st->execute([$parentId, $position]);
        return (int)$st->fetchColumn() === 0;
    }

    // ── Admin Queries ─────────────────────────────────────────────────────────

    public static function allMembers(int $page = 1, string $search = '', string $status = '', int $pkgId = 0): array
    {
        $where  = "u.role = 'member'";
        $params = [];

        if ($search) {
            $where   .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $s        = "%{$search}%";
            $params   = array_merge($params, [$s, $s, $s]);
        }
        if ($status) {
            $where   .= ' AND u.status = ?';
            $params[] = $status;
        }
        if ($pkgId) {
            $where   .= ' AND u.package_id = ?';
            $params[] = $pkgId;
        }

        return paginate(
            "SELECT u.*, p.name AS package_name
             FROM   users u
             LEFT JOIN packages p ON p.id = u.package_id
             WHERE  {$where}
             ORDER BY u.joined_at DESC",
            $params, $page, 25
        );
    }

    public static function counts(): array
    {
        $row = db()->query("
            SELECT
              COUNT(*)                                                        AS total,
              COALESCE(SUM(CASE WHEN status='active'    THEN 1 ELSE 0 END),0) AS active,
              COALESCE(SUM(CASE WHEN status='suspended' THEN 1 ELSE 0 END),0) AS suspended,
              COALESCE(SUM(CASE WHEN joined_at >= CURDATE() THEN 1 ELSE 0 END),0) AS joined_today
            FROM users WHERE role = 'member'
        ")->fetch();
        return $row ?: ['total' => 0, 'active' => 0, 'suspended' => 0, 'joined_today' => 0];
    }

    // ── Referral Chain (sponsor chain, not binary) ────────────────────────────

    /**
     * Get direct recruits by this member (sponsor_id = $userId).
     */
    public static function directReferrals(int $userId, int $page = 1): array
    {
        return paginate(
            "SELECT u.*, p.name AS package_name
             FROM   users u
             LEFT JOIN packages p ON p.id = u.package_id
             WHERE  u.sponsor_id = ? AND u.role = 'member'
             ORDER BY u.joined_at DESC",
            [$userId], $page, 20
        );
    }

    /**
     * Get full indirect genealogy (up to 10 levels) for display.
     * Returns flat array with a `level` column.
     */
    public static function indirectReferralTree(int $userId, int $maxLevel = 10): array
    {
        $result = [];
        $queue  = [['id' => $userId, 'level' => 0]];
        $visited = [$userId => true];

        while (!empty($queue)) {
            $item = array_shift($queue);
            if ($item['level'] >= $maxLevel) continue;

            $st = db()->prepare("
                SELECT u.id, u.username, u.full_name, u.status,
                       u.joined_at, p.name AS package_name
                FROM   users u
                LEFT JOIN packages p ON p.id = u.package_id
                WHERE  u.sponsor_id = ? AND u.role = 'member'
            ");
            $st->execute([$item['id']]);
            $children = $st->fetchAll();

            foreach ($children as $child) {
                if (isset($visited[$child['id']])) continue;
                $visited[$child['id']] = true;
                $child['level'] = $item['level'] + 1;
                $result[] = $child;
                $queue[]  = ['id' => $child['id'], 'level' => $child['level']];
            }
        }

        return $result;
    }
}
