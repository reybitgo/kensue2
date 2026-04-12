<?php

class Commission
{
    // ══════════════════════════════════════════════════════════════════════════
    //  BINARY PLACEMENT ENGINE
    //  Called immediately after a new member is inserted.
    //  Walks the binary tree upward, updating leg counts on every ancestor and
    //  firing pairing bonuses in real time for each ancestor that earns one.
    // ══════════════════════════════════════════════════════════════════════════

    public static function processBinaryPlacement(
        int $newUserId,
        int $parentId,
        string $position          // 'left' | 'right' — which side of $parentId
    ): void {
        $pdo  = db();
        $cur  = $parentId;
        $side = $position;

        while ($cur !== null) {

            // 1. Increment the correct leg count on this ancestor
            $col = ($side === 'left') ? 'left_count' : 'right_count';
            $pdo->prepare("UPDATE users SET {$col} = {$col} + 1 WHERE id = ?")
                ->execute([$cur]);

            // 2. Read fresh state (after increment) with package info
            $st = $pdo->prepare("
                SELECT u.id, u.left_count, u.right_count,
                       u.pairs_paid, u.pairs_flushed, u.pairs_paid_today,
                       p.pairing_bonus, p.daily_pair_cap
                FROM   users u
                LEFT JOIN packages p ON p.id = u.package_id
                WHERE  u.id = ? AND u.status = 'active'
                  AND  p.pairing_bonus IS NOT NULL
            ");
            $st->execute([$cur]);
            $ancestor = $st->fetch();

            if ($ancestor) {
                $processed = $ancestor['pairs_paid'] + $ancestor['pairs_flushed'];
                $available = min($ancestor['left_count'], $ancestor['right_count']);
                $newPairs  = $available - $processed;

                if ($newPairs > 0) {
                    $capRemaining = (int)$ancestor['daily_pair_cap'] - (int)$ancestor['pairs_paid_today'];
                    $payNow       = min($newPairs, max(0, $capRemaining));
                    $flushNow     = $newPairs - $payNow;

                    // Credit earned pairs immediately
                    if ($payNow > 0) {
                        $bonus = $payNow * (float)$ancestor['pairing_bonus'];
                        self::creditPairing($cur, $bonus, $payNow, $newUserId);
                    }

                    // Record flushed pairs (money permanently lost — audit only)
                    if ($flushNow > 0) {
                        self::recordFlush($cur, $flushNow, $newUserId);
                    }

                    // Update counters in one atomic statement
                    $pdo->prepare("
                        UPDATE users
                        SET pairs_paid       = pairs_paid       + :pay,
                            pairs_flushed    = pairs_flushed    + :flush,
                            pairs_paid_today = pairs_paid_today + :pay2
                        WHERE id = :id
                    ")->execute([
                        ':pay'   => $payNow,
                        ':flush' => $flushNow,
                        ':pay2'  => $payNow,
                        ':id'    => $cur,
                    ]);
                }
            }

            // 3. Move to this ancestor's own parent
            $upRow = $pdo->prepare(
                'SELECT binary_parent_id, binary_position FROM users WHERE id = ?'
            );
            $upRow->execute([$cur]);
            $up = $upRow->fetch();

            $side = $up['binary_position'] ?? null;
            $cur  = isset($up['binary_parent_id']) ? (int)$up['binary_parent_id'] : null;
            if (!$cur) break;
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  DIRECT REFERRAL BONUS
    //  Fires immediately to the sponsor when their direct recruit registers.
    // ══════════════════════════════════════════════════════════════════════════

    public static function processDirectReferral(
        int $sponsorId,
        int $newUserId,
        int $packageId
    ): void {
        $pkg = Package::find($packageId);
        if (!$pkg || (float)$pkg['direct_ref_bonus'] <= 0) return;

        $bonus = (float)$pkg['direct_ref_bonus'];

        $pdo = db();
        $pdo->prepare("
            INSERT INTO commissions
              (user_id, type, amount, source_user_id, description, status)
            VALUES (?, 'direct_referral', ?, ?, 'Direct referral bonus', 'credited')
        ")->execute([$sponsorId, $bonus, $newUserId]);

        $commId = (int)$pdo->lastInsertId();
        Ewallet::credit($sponsorId, $bonus, $commId, 'commission', 'Direct referral bonus');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  UNILEVEL GENERATIONAL REFERRAL BONUSES 
    //  Pure Sponsor Chain - No Binary Tree involvement at all
    //  Level 1 = Direct sponsor of the new member
    //  Level 2 = Sponsor of Level 1
    //  Level 3 = Sponsor of Level 2 ... up to 10 levels
    // ══════════════════════════════════════════════════════════════════════════

    public static function processIndirectReferral(
        int $directSponsorId,
        int $newUserId,
        int $packageId
    ): void {
        $levels = Package::getIndirectLevels($packageId);
        if (empty($levels)) return;

        $pdo = db();
        $cur = $directSponsorId;
        $visited = [$directSponsorId => true];

        for ($lvl = 1; $lvl <= 10; $lvl++) {

            $bonus = (float)($levels[$lvl] ?? 0);

            if ($bonus > 0) {
                $pdo->prepare("
                    INSERT INTO commissions
                      (user_id, type, amount, source_user_id, level, description, status)
                    VALUES (?, 'indirect_referral', ?, ?, ?, ?, 'credited')
                ")->execute([
                    $cur,
                    $bonus,
                    $newUserId,
                    $lvl,
                    "Unilevel Level {$lvl} Bonus"
                ]);

                $commId = (int)$pdo->lastInsertId();

                Ewallet::credit(
                    $cur,
                    $bonus,
                    $commId,
                    'commission',
                    "Unilevel Level {$lvl} Bonus"
                );
            }

            // Move up using ONLY sponsor_id
            $row = $pdo->prepare('SELECT sponsor_id FROM users WHERE id = ?');
            $row->execute([$cur]);
            $upRow = $row->fetch();

            if (!$upRow || empty($upRow['sponsor_id'])) {
                break;
            }

            $next = (int)$upRow['sponsor_id'];

            if (isset($visited[$next])) {
                break;
            }

            $visited[$next] = true;
            $cur = $next;
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    private static function creditPairing(
        int $userId,
        float $amount,
        int $pairs,
        int $sourceId
    ): void {
        $pdo = db();
        $perPair = $pairs > 0 ? fmt_money($amount / $pairs) : '₱0.00';

        $pdo->prepare("
            INSERT INTO commissions
              (user_id, type, amount, source_user_id, pairs_count, description, status)
            VALUES (?, 'pairing', ?, ?, ?, ?, 'credited')
        ")->execute([
            $userId,
            $amount,
            $sourceId,
            $pairs,
            "{$pairs} pair(s) × {$perPair}"
        ]);

        $commId = (int)$pdo->lastInsertId();
        Ewallet::credit($userId, $amount, $commId, 'commission', "Pairing bonus — {$pairs} pair(s)");
    }

    private static function recordFlush(int $userId, int $pairs, int $sourceId): void
    {
        db()->prepare("
            INSERT INTO commissions
              (user_id, type, amount, source_user_id, pairs_count, description, status)
            VALUES (?, 'pairing', 0.00, ?, ?, ?, 'flushed')
        ")->execute([
            $userId,
            $sourceId,
            $pairs,
            "{$pairs} pair(s) flushed — daily cap reached"
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  QUERY HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    public static function summary(int $userId): array
    {
        $st = db()->prepare("
            SELECT
              COALESCE(SUM(CASE WHEN type='pairing'           AND status='credited' THEN amount END), 0) AS total_pairing,
              COALESCE(SUM(CASE WHEN type='direct_referral'   AND status='credited' THEN amount END), 0) AS total_direct,
              COALESCE(SUM(CASE WHEN type='indirect_referral' AND status='credited' THEN amount END), 0) AS total_indirect,
              COALESCE(SUM(CASE WHEN status='credited'                              THEN amount END), 0) AS total_earned,
              COALESCE(SUM(CASE WHEN type='pairing' AND status='flushed' THEN pairs_count END), 0)       AS total_flushed_pairs
            FROM commissions
            WHERE user_id = ?
        ");
        $st->execute([$userId]);
        return $st->fetch();
    }

    public static function recent(int $userId, int $limit = 10): array
    {
        $st = db()->prepare("
            SELECT c.*,
                   u.username AS source_username
            FROM   commissions c
            LEFT JOIN users u ON u.id = c.source_user_id
            WHERE  c.user_id = ?
            ORDER BY c.created_at DESC
            LIMIT  {$limit}
        ");
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public static function history(int $userId, int $page = 1, int $perPage = 20, string $type = ''): array
    {
        $where  = 'c.user_id = ?';
        $params = [$userId];

        if ($type && in_array($type, ['pairing', 'direct_referral', 'indirect_referral'])) {
            $where  .= ' AND c.type = ?';
            $params[] = $type;
        }

        return paginate(
            "SELECT c.*, u.username AS source_username
             FROM   commissions c
             LEFT JOIN users u ON u.id = c.source_user_id
             WHERE  {$where}
             ORDER BY c.created_at DESC",
            $params,
            $page,
            $perPage
        );
    }
}
