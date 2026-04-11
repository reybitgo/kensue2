<?php

class Ewallet
{
    /**
     * Credit amount to user's e-wallet and log to ledger.
     */
    public static function credit(
        int $userId,
        float $amount,
        int $refId,
        string $refType,
        string $note = ''
    ): void {
        $pdo = db();
        $pdo->prepare('UPDATE users SET ewallet_balance = ewallet_balance + ? WHERE id = ?')
            ->execute([$amount, $userId]);

        $bal = (float) $pdo->query("SELECT ewallet_balance FROM users WHERE id = {$userId}")
                           ->fetchColumn();

        $pdo->prepare("
            INSERT INTO ewallet_ledger
              (user_id, type, amount, reference_id, ref_type, balance_after, note)
            VALUES (?, 'credit', ?, ?, ?, ?, ?)
        ")->execute([$userId, $amount, $refId, $refType, $bal, $note]);
    }

    /**
     * Debit amount from user's e-wallet.
     * Returns false if balance is insufficient.
     */
    public static function debit(
        int $userId,
        float $amount,
        int $refId,
        string $refType,
        string $note = ''
    ): bool {
        $pdo = db();

        // Lock the row and check balance atomically
        $st = $pdo->prepare('SELECT ewallet_balance FROM users WHERE id = ? FOR UPDATE');
        $st->execute([$userId]);
        $bal = (float) $st->fetchColumn();

        if ($bal < $amount) return false;

        $pdo->prepare('UPDATE users SET ewallet_balance = ewallet_balance - ? WHERE id = ?')
            ->execute([$amount, $userId]);

        $newBal = $bal - $amount;

        $pdo->prepare("
            INSERT INTO ewallet_ledger
              (user_id, type, amount, reference_id, ref_type, balance_after, note)
            VALUES (?, 'debit', ?, ?, ?, ?, ?)
        ")->execute([$userId, $amount, $refId, $refType, $newBal, $note]);

        return true;
    }

    /**
     * Get paginated ledger entries for a user.
     */
    public static function ledger(int $userId, int $page = 1, int $perPage = 20): array
    {
        return paginate(
            "SELECT * FROM ewallet_ledger WHERE user_id = ? ORDER BY created_at DESC",
            [$userId], $page, $perPage
        );
    }

    /**
     * Current balance — always reads fresh from DB.
     */
    public static function balance(int $userId): float
    {
        return (float) db()
            ->query("SELECT ewallet_balance FROM users WHERE id = {$userId}")
            ->fetchColumn();
    }
}
