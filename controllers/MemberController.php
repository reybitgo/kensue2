<?php

class MemberController
{
    public function dashboard(): void
    {
        Auth::guard('member');
        $user    = Auth::user();
        $summary = Commission::summary($user['id']);
        $status  = User::todayPairingStatus($user['id']);
        $recent  = Commission::recent($user['id'], 8);
        require 'views/member/dashboard.php';
    }

    public function profile(): void
    {
        Auth::guard('member');
        $user = Auth::user();
        require 'views/member/profile.php';
    }

    public function saveProfile(): void
    {
        Auth::guard('member');
        csrf_verify();
        $id   = Auth::id();
        $user = Auth::user();

        $data = [
            'full_name'    => trim($_POST['full_name']    ?? ''),
            'email'        => trim($_POST['email']        ?? ''),
            'mobile'       => trim($_POST['mobile']       ?? ''),
            'gcash_number' => trim($_POST['gcash_number'] ?? ''),
            'address'      => trim($_POST['address']      ?? ''),
        ];

        // Handle photo upload
        if (!empty($_FILES['photo']['tmp_name'])) {
            $file = $_FILES['photo'];

            // Verify MIME type
            $mime    = mime_content_type($file['tmp_name']);
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowed)) {
                flash('error', 'Photo must be JPEG, PNG, or WebP.');
                redirect('/?page=profile');
            }
            if ($file['size'] > 2 * 1024 * 1024) { // 2 MB — phone photos can be large
                flash('error', 'Photo must be under 2MB.');
                redirect('/?page=profile');
            }

            // Use absolute path so it works regardless of PHP's working directory.
            // dirname(__DIR__) = the project root (parent of /controllers/)
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

            // Create directory if missing
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
            $name = 'photo_' . $id . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $name;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                flash('error', 'Failed to save photo. Check that the uploads/ folder exists and is writable.');
                redirect('/?page=profile');
            }

            // Delete old photo file if it exists
            if (!empty($user['photo'])) {
                $old = $uploadDir . $user['photo'];
                if (file_exists($old)) @unlink($old);
            }

            $data['photo'] = $name;
        }

        // Password change (optional)
        $newPw = $_POST['new_password'] ?? '';
        if ($newPw) {
            if (strlen($newPw) < 8) {
                flash('error', 'New password must be at least 8 characters.');
                redirect('/?page=profile');
            }
            if (!User::verifyPassword($id, $_POST['current_password'] ?? '')) {
                flash('error', 'Current password is incorrect.');
                redirect('/?page=profile');
            }
            if ($newPw !== ($_POST['new_password_confirm'] ?? '')) {
                flash('error', 'New passwords do not match.');
                redirect('/?page=profile');
            }
            User::updatePassword($id, $newPw);
        }

        User::updateProfile($id, $data);
        flash('success', 'Profile updated successfully.');
        redirect('/?page=profile');
    }

    public function earnings(): void
    {
        Auth::guard('member');
        $userId  = Auth::id();
        $type    = $_GET['type'] ?? '';
        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $summary = Commission::summary($userId);
        $history = Commission::history($userId, $page, 20, $type);
        require 'views/member/earnings.php';
    }

    public function genealogy(): void
    {
        Auth::guard('member');
        $user     = Auth::user();
        $view     = $_GET['view'] ?? 'binary'; // 'binary' | 'referral'
        $indirect = [];
        if ($view === 'referral') {
            $indirect = User::indirectReferralTree($user['id']);
        }
        require 'views/member/genealogy.php';
    }

    public function apiBinaryTree(): void
    {
        Auth::guard('member');
        $rootId = isset($_GET['root']) ? (int)$_GET['root'] : Auth::id();
        $depth  = min(4, max(1, (int)($_GET['depth'] ?? 3)));
        json_response(self::buildTreeNode($rootId, $depth));
    }

    private static function buildTreeNode(int $id, int $depth): array
    {
        $u = User::find($id);
        if (!$u) return [];

        $node = [
            'id'          => (int)$u['id'],
            'username'    => $u['username'],
            'full_name'   => $u['full_name'] ?: $u['username'],
            'status'      => $u['status'],
            'package'     => $u['package_name'] ?? '—',
            'joined'      => fmt_date($u['joined_at']),
            'left_count'  => (int)$u['left_count'],
            'right_count' => (int)$u['right_count'],
            'left'        => null,
            'right'       => null,
        ];

        if ($depth > 0) {
            $pdo = db();
            $st  = $pdo->prepare(
                "SELECT id FROM users WHERE binary_parent_id = ? AND binary_position = ?"
            );
            $st->execute([$id, 'left']);
            $lc = $st->fetchColumn();
            if ($lc) $node['left'] = self::buildTreeNode((int)$lc, $depth - 1);

            $st->execute([$id, 'right']);
            $rc = $st->fetchColumn();
            if ($rc) $node['right'] = self::buildTreeNode((int)$rc, $depth - 1);
        }

        return $node;
    }

    public function payout(): void
    {
        Auth::guard('member');
        $userId  = Auth::id();
        $user    = Auth::user();
        $history = Payout::forUser($userId);
        require 'views/member/payout.php';
    }

    public function requestPayout(): void
    {
        Auth::guard('member');
        csrf_verify();

        $amount = (float)($_POST['amount'] ?? 0);
        $gcash  = trim($_POST['gcash_number'] ?? '');

        if (!$gcash) {
            flash('error', 'Please enter your GCash number.');
            redirect('/?page=payout');
        }

        $result = Payout::request(Auth::id(), $amount, $gcash);
        if ($result['ok']) {
            flash('success', 'Payout request submitted. Admin will process it shortly.');
        } else {
            flash('error', $result['error']);
        }
        redirect('/?page=payout');
    }
}
