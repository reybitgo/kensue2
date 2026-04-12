<?php

class AuthController
{
    // ── Login ─────────────────────────────────────────────────────────────────

    public function showLogin(): void
    {
        require 'views/auth/login.php';
    }

    public function doLogin(): void
    {
        csrf_verify();

        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';

        // Rate limiting
        if (!rate_limit_check('login_' . $username, 5, 900)) {
            flash('error', 'Too many failed attempts. Please wait 15 minutes.');
            redirect('/?page=login');
        }

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            rate_limit_hit('login_' . $username);
            flash('error', 'Invalid username or password.');
            redirect('/?page=login');
        }

        if ($user['status'] === 'suspended') {
            flash('error', 'Your account has been suspended. Contact support.');
            redirect('/?page=login');
        }

        rate_limit_clear('login_' . $username);
        Auth::login($user);
        redirect($user['role'] === 'admin' ? '/?page=admin' : '/?page=dashboard');
    }

    // ── Register ──────────────────────────────────────────────────────────────

    public function showRegister(): void
    {
        // Pass pre-filled sponsor from ?sponsor= param
        $prefillSponsor = trim($_GET['sponsor'] ?? '');
        require 'views/auth/register.php';
    }

    public function doRegister(): void
    {
        csrf_verify();

        $code       = strtoupper(trim($_POST['reg_code']         ?? ''));
        $username   = strtolower(trim($_POST['username']         ?? ''));
        $password   = $_POST['password']                          ?? '';
        $passwordC  = $_POST['password_confirm']                  ?? '';
        $sponsorU   = strtolower(trim($_POST['sponsor_username'] ?? ''));
        $uplineU    = strtolower(trim($_POST['upline_username']  ?? ''));
        $position   = $_POST['binary_position']                   ?? '';

        // Validate code
        $codeRow = Code::validate($code);
        if (!$codeRow) {
            flash('error', 'Invalid or already-used registration code.');
            redirect('/?page=register');
        }

        // Validate username
        if (!is_valid_username($username)) {
            flash('error', 'Username must be 3–40 characters, letters/numbers/underscore, start with a letter.');
            redirect('/?page=register');
        }
        if (User::usernameExists($username)) {
            flash('error', 'Username is already taken.');
            redirect('/?page=register');
        }

        // Validate password
        if (strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            redirect('/?page=register');
        }
        if ($password !== $passwordC) {
            flash('error', 'Passwords do not match.');
            redirect('/?page=register');
        }

        // Validate sponsor — any existing user can be a sponsor
        $sponsor = User::findByUsername($sponsorU);
        if (!$sponsor) {
            flash('error', 'Sponsor username not found.');
            redirect('/?page=register');
        }

        // Validate binary upline — any existing user can be an upline
        $upline = User::findByUsername($uplineU);
        if (!$upline) {
            flash('error', 'Binary upline username not found.');
            redirect('/?page=register');
        }

        // Validate position
        if (!in_array($position, ['left', 'right'])) {
            flash('error', 'Invalid binary position.');
            redirect('/?page=register');
        }

        // Check slot availability
        if (!User::isSlotFree((int)$upline['id'], $position)) {
            flash('error', "The {$position} position under @{$uplineU} is already occupied.");
            redirect('/?page=register');
        }

        // Register
        $wasLoggedIn   = Auth::check();
        $prevUserId    = Auth::id();
        $prevUserRole  = $_SESSION['user_role'] ?? '';

        try {
            $newId = User::register([
                'username'         => $username,
                'password'         => $password,
                'package_id'       => (int)$codeRow['package_id'],
                'reg_code_id'      => (int)$codeRow['id'],
                'sponsor_id'       => (int)$sponsor['id'],
                'binary_parent_id' => (int)$upline['id'],
                'binary_position'  => $position,
            ]);

            if ($wasLoggedIn) {
                // Registrant was already logged in (admin or member registering someone)
                // Restore their session — do NOT switch to the new member
                $_SESSION['user_id']   = $prevUserId;
                $_SESSION['user_role'] = $prevUserRole;
                flash('success', "Account @{$username} registered successfully.");
                redirect($prevUserRole === 'admin' ? '/?page=admin_users' : '/?page=dashboard');
            } else {
                // New visitor registering themselves — log them in
                $newUser = User::find($newId);
                Auth::login($newUser);
                flash('success', 'Welcome! Your account has been created successfully.');
                redirect('/?page=dashboard');
            }

        } catch (\Exception $e) {
            flash('error', $e->getMessage());
            redirect('/?page=register' . ($wasLoggedIn ? '&sponsor=' . urlencode($sponsorU) : ''));
        }
    }

    // ── AJAX Validators ───────────────────────────────────────────────────────

    /** AJAX: validate registration code */
    public function ajaxValidateCode(): void
    {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $row  = Code::validate($code);

        if (!$row) {
            json_response(['valid' => false, 'message' => 'Code is invalid, used, or expired.']);
        }

        json_response([
            'valid'        => true,
            'package_name' => $row['package_name'],
            'entry_fee'    => fmt_money((float)$row['entry_fee']),
            'pairing_bonus'=> fmt_money((float)$row['pairing_bonus']),
            'daily_cap'    => $row['daily_pair_cap'],
        ]);
    }

    /** AJAX: check username availability */
    public function ajaxCheckUser(): void
    {
        $username = strtolower(trim($_GET['username'] ?? ''));
        if (!is_valid_username($username)) {
            json_response(['available' => false, 'message' => 'Invalid username format.']);
        }
        $taken = User::usernameExists($username);
        json_response([
            'available' => !$taken,
            'message'   => $taken ? 'Username is taken.' : 'Username is available.',
        ]);
    }

    /** AJAX: validate upline username + check slot */
    public function ajaxCheckUpline(): void
    {
        $username = strtolower(trim($_GET['username'] ?? ''));
        $position = $_GET['position'] ?? '';

        // Any existing user (member OR admin) can be a binary upline
        $user = User::findByUsername($username);
        if (!$user) {
            json_response(['valid' => false, 'message' => 'User not found.']);
        }

        $leftFree  = User::isSlotFree((int)$user['id'], 'left');
        $rightFree = User::isSlotFree((int)$user['id'], 'right');

        json_response([
            'valid'      => true,
            'username'   => $user['username'],
            'left_free'  => $leftFree,
            'right_free' => $rightFree,
            'slot_ok'    => $position ? ($position === 'left' ? $leftFree : $rightFree) : null,
            'message'    => "Found @{$user['username']} — Left: " . ($leftFree ? '✓ Free' : '✗ Taken') . ', Right: ' . ($rightFree ? '✓ Free' : '✗ Taken'),
        ]);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        Auth::logout();
    }
}
