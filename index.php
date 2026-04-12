<?php
/**
 * MLM BINARY SYSTEM — Front Controller
 * All HTTP requests route through here.
 */

session_start();

require_once 'config/db.php';
require_once 'core/helpers.php';
require_once 'core/Auth.php';
require_once 'core/Commission.php';

// Auto-load models and controllers
spl_autoload_register(function (string $class): void {
    foreach (['models/', 'controllers/'] as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Maintenance mode
if (setting('maintenance_mode') === '1' && !Auth::isAdmin()) {
    $name = setting('site_name', APP_NAME);
    http_response_code(503);
    die("<!doctype html><html><head><meta charset='UTF-8'><title>Maintenance — {$name}</title>
    <style>body{font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f4f6fb;}
    .box{text-align:center;padding:40px;max-width:400px;}h1{font-size:24px;}p{color:#6b7a99;}</style>
    </head><body><div class='box'><div style='font-size:50px;margin-bottom:16px;'>🔧</div>
    <h1>{$name}</h1><p>We're performing scheduled maintenance. We'll be back shortly.</p></div></body></html>");
}

// Route table: page => [ControllerClass, method, role]
// role: 'guest' | 'member' | 'admin' | 'any'
$routes = [
    // ── Auth ──────────────────────────────────────────
    'login'              => ['AuthController',   'showLogin',       'guest'],
    'do_login'           => ['AuthController',   'doLogin',         'guest'],
    'register'           => ['AuthController',   'showRegister',    'any'],
    'do_register'        => ['AuthController',   'doRegister',      'any'],
    'validate_code'      => ['AuthController',   'ajaxValidateCode','any'],
    'check_username'     => ['AuthController',   'ajaxCheckUser',   'any'],
    'check_upline'       => ['AuthController',   'ajaxCheckUpline', 'any'],
    'logout'             => ['AuthController',   'logout',          'any'],

    // ── Member ────────────────────────────────────────
    'dashboard'          => ['MemberController', 'dashboard',       'member'],
    'profile'            => ['MemberController', 'profile',         'member'],
    'save_profile'       => ['MemberController', 'saveProfile',     'member'],
    'earnings'           => ['MemberController', 'earnings',        'member'],
    'genealogy'          => ['MemberController', 'genealogy',       'member'],
    'api_binary_tree'    => ['MemberController', 'apiBinaryTree',   'member'],
    'payout'             => ['MemberController', 'payout',          'member'],
    'request_payout'     => ['MemberController', 'requestPayout',   'member'],

    // ── Admin ─────────────────────────────────────────
    'admin'              => ['AdminController',  'dashboard',       'admin'],
    'admin_users'        => ['AdminController',  'users',           'admin'],
    'admin_user_view'    => ['AdminController',  'viewUser',        'admin'],
    'admin_toggle_user'  => ['AdminController',  'toggleUser',      'admin'],
    'admin_packages'     => ['AdminController',  'packages',        'admin'],
    'admin_save_package' => ['AdminController',  'savePackage',     'admin'],
    'admin_codes'        => ['AdminController',  'codes',           'admin'],
    'admin_gen_codes'    => ['AdminController',  'generateCodes',   'admin'],
    'admin_export_codes' => ['AdminController',  'exportCodes',     'admin'],
    'admin_payouts'      => ['AdminController',  'payouts',         'admin'],
    'admin_payout_action'=> ['AdminController',  'payoutAction',    'admin'],
    'admin_settings'     => ['AdminController',  'settings',        'admin'],
    'admin_save_settings'=> ['AdminController',  'saveSettings',    'admin'],
    'admin_manual_reset' => ['AdminController',  'manualReset',     'admin'],
];

$page = $_GET['page'] ?? 'login';

// Fall back to login for unknown pages
$route = $routes[$page] ?? null;
if (!$route) {
    if (Auth::check()) {
        redirect(Auth::isAdmin() ? '/?page=admin' : '/?page=dashboard');
    }
    $route = $routes['login'];
}

[$ctrlClass, $method, $role] = $route;

// Auth guards
// 'guest' pages redirect logged-in users away EXCEPT register (members can register new members)
if ($role === 'guest' && Auth::check() && !in_array($page, ['register', 'do_register'])) {
    redirect(Auth::isAdmin() ? '/?page=admin' : '/?page=dashboard');
}
if ($role === 'member' && !Auth::check()) {
    flash('error', 'Please log in to continue.');
    redirect('/?page=login');
}
if ($role === 'admin' && !Auth::isAdmin()) {
    flash('error', 'Access denied.');
    redirect('/?page=login');
}

// CSRF token seed
csrf_token();

// Dispatch
(new $ctrlClass())->$method();
