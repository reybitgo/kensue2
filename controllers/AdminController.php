<?php

class AdminController
{
    public function dashboard(): void
    {
        Auth::guard('admin');
        $memberCounts  = User::counts();
        $codeStat      = Code::stats();
        $pendingPayout = Payout::pendingTotal();
        $totalPaid     = Payout::totalPaid();
        $pendingList   = Payout::all(1, 'pending')['data'];
        require 'views/admin/dashboard.php';
    }

    public function users(): void
    {
        Auth::guard('admin');
        $page     = max(1, (int)($_GET['pg'] ?? 1));
        $search   = trim($_GET['q']      ?? '');
        $status   = $_GET['status']      ?? '';
        $pkgId    = (int)($_GET['pkg']   ?? 0);
        $packages = Package::all();
        $result   = User::allMembers($page, $search, $status, $pkgId);
        require 'views/admin/users.php';
    }

    public function viewUser(): void
    {
        Auth::guard('admin');
        $id   = (int)($_GET['id'] ?? 0);
        $user = User::find($id);
        if (!$user) {
            flash('error', 'User not found.');
            redirect('/?page=admin_users');
        }

        $summary  = Commission::summary($id);
        $payouts  = Payout::forUser($id);
        $commHist = Commission::history($id, 1, 20);
        $ledger   = Ewallet::ledger($id, 1);
        $pairingStatus = User::todayPairingStatus($id);
        require 'views/admin/user_view.php';
    }

    public function toggleUser(): void
    {
        Auth::guard('admin');
        csrf_verify();

        $id   = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Invalid user ID.');
            redirect('/?page=admin_users');
            return;
        }

        $user = User::find($id);
        if (!$user || $user['role'] === 'admin') {
            flash('error', 'Invalid user or cannot modify administrator account.');
            redirect('/?page=admin_users');
            return;
        }

        $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';

        $pdo = db();
        $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $success = $stmt->execute([$newStatus, $id]);

        if ($success) {
            $action = ($newStatus === 'active') ? 'activated' : 'suspended';
            flash('success', "User @{$user['username']} has been {$action} successfully.");
        } else {
            flash('error', 'Failed to update user status. Please try again.');
        }

        // Return JSON only for AJAX requests
        if (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            json_response(['ok' => $success, 'status' => $newStatus]);
        }

        // ── Smart Redirect Logic ─────────────────────────────────────
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        // If came from user view page → stay on that user's view
        if (strpos($referer, 'admin_user_view') !== false && strpos($referer, "id={$id}") !== false) {
            redirect("/?page=admin_user_view&id={$id}");
        }

        // Otherwise (from members list or anywhere else) → go back to members list
        redirect('/?page=admin_users');
    }

    // ── Packages ──────────────────────────────────────────────────────────────

    public function packages(): void
    {
        Auth::guard('admin');
        $packages = Package::all();
        $editPkg  = null;
        if (isset($_GET['edit'])) {
            $editPkg = Package::withLevels((int)$_GET['edit']);
        }
        require 'views/admin/packages.php';
    }

    public function savePackage(): void
    {
        Auth::guard('admin');
        csrf_verify();

        $id   = (int)($_POST['package_id'] ?? 0);
        $data = [
            'name'             => trim($_POST['name']             ?? ''),
            'entry_fee'        => (float)($_POST['entry_fee']      ?? 0),
            'pairing_bonus'    => (float)($_POST['pairing_bonus']  ?? 0),
            'daily_pair_cap'   => (int)($_POST['daily_pair_cap']   ?? 3),
            'direct_ref_bonus' => (float)($_POST['direct_ref_bonus'] ?? 0),
            'status'           => $_POST['status'] ?? 'active',
            'indirect_levels'  => [],
        ];

        for ($lvl = 1; $lvl <= 10; $lvl++) {
            $data['indirect_levels'][$lvl] = (float)($_POST["indirect_{$lvl}"] ?? 0);
        }

        if (!$data['name'] || $data['entry_fee'] <= 0) {
            flash('error', 'Package name and entry fee are required.');
            redirect('/?page=admin_packages');
        }

        Package::save($data, $id ?: null);
        flash('success', $id ? 'Package updated.' : 'Package created.');
        redirect('/?page=admin_packages');
    }

    // ── Registration Codes ────────────────────────────────────────────────────

    public function codes(): void
    {
        Auth::guard('admin');
        $page     = max(1, (int)($_GET['pg']  ?? 1));
        $status   = $_GET['status']            ?? '';
        $pkgId    = (int)($_GET['pkg']         ?? 0);
        $packages = Package::all(true);
        $codes    = Code::all($page, $status, $pkgId);
        $stats    = Code::stats();
        require 'views/admin/codes.php';
    }

    public function generateCodes(): void
    {
        Auth::guard('admin');
        csrf_verify();

        $pkgId    = (int)($_POST['package_id'] ?? 0);
        $qty      = min(500, max(1, (int)($_POST['quantity'] ?? 1)));
        $price    = (float)($_POST['price']    ?? 0);
        $expires  = trim($_POST['expires_at']  ?? '');

        if (!$pkgId || $price <= 0) {
            flash('error', 'Package and price are required.');
            redirect('/?page=admin_codes');
        }

        $generated = Code::generate($pkgId, $qty, $price, $expires ?: null, Auth::id());
        flash('success', count($generated) . ' code(s) generated successfully.');
        redirect('/?page=admin_codes');
    }

    public function exportCodes(): void
    {
        Auth::guard('admin');
        $status = $_GET['status'] ?? '';
        $pkgId  = (int)($_GET['pkg'] ?? 0);
        Code::exportCSV($status, $pkgId);
    }

    // ── Payouts ───────────────────────────────────────────────────────────────

    public function payouts(): void
    {
        Auth::guard('admin');
        $page   = max(1, (int)($_GET['pg']     ?? 1));
        $status = $_GET['status']               ?? 'pending';
        $result = Payout::all($page, $status);
        require 'views/admin/payouts.php';
    }

    public function payoutAction(): void
    {
        Auth::guard('admin');
        csrf_verify();

        $action   = $_POST['action']    ?? '';
        $id       = (int)($_POST['id']  ?? 0);
        $note     = trim($_POST['note'] ?? '');
        $adminId  = Auth::id();

        switch ($action) {
            case 'approve':
                $ok = Payout::approve($id, $adminId);
                flash($ok ? 'success' : 'error', $ok ? 'Payout approved.' : 'Could not approve.');
                break;
            case 'reject':
                $ok = Payout::reject($id, $adminId, $note);
                flash($ok ? 'success' : 'error', $ok ? 'Payout rejected.' : 'Could not reject.');
                break;
            case 'complete':
                $result = Payout::complete($id, $adminId, $note);
                flash(
                    $result['ok'] ? 'success' : 'error',
                    $result['ok'] ? 'Payout marked as completed. E-wallet deducted.' : $result['error']
                );
                break;
            default:
                flash('error', 'Unknown action.');
        }
        redirect('/?page=admin_payouts');
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function settings(): void
    {
        Auth::guard('admin');
        require 'views/admin/settings.php';
    }

    public function saveSettings(): void
    {
        Auth::guard('admin');
        csrf_verify();

        $allowed = ['site_name', 'site_tagline', 'min_payout', 'contact_email', 'maintenance_mode'];
        $pdo = db();
        $st  = $pdo->prepare('UPDATE settings SET value = ? WHERE key_name = ?');

        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $st->execute([trim($_POST[$key]), $key]);
            }
        }

        flash('success', 'Settings saved.');
        redirect('/?page=admin_settings');
    }

    public function manualReset(): void
    {
        Auth::guard('admin');
        csrf_verify();

        $affected = db()->exec("UPDATE users SET pairs_paid_today = 0 WHERE role = 'member'");
        db()->prepare("UPDATE settings SET value = ? WHERE key_name = 'last_reset'")
            ->execute([date('Y-m-d H:i:s')]);

        flash('success', "Daily pair counter reset for {$affected} member(s).");
        redirect('/?page=admin_settings');
    }
}
