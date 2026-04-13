<?php
// === Load the correct user based on ?id= (for admin view) ===
if (isset($_GET['page']) && $_GET['page'] === 'admin_user_view' && !empty($_GET['id']) && Auth::isAdmin()) {
  $user = getUserById((int)$_GET['id']);

  if (!$user) {
    flash('error', 'User not found.');
    redirect('/?page=admin_users');
  }
} else {
  // Fallback: should not happen if routed correctly
  $user = Auth::user();
}
$topbarBalance = fmt_money($user['ewallet_balance'] ?? 0);
$initials      = strtoupper(substr($user['username'] ?? 'U', 0, 1));
$isMember      = ($user['role'] ?? '') === 'member';
?>
<div class="topbar-wrapper no-print">
  <!-- Hamburger (mobile only — triggers offcanvas) -->
  <button class="btn btn-sm btn-light d-lg-none me-1 border-0"
    type="button"
    data-bs-toggle="offcanvas"
    data-bs-target="#mobileSidebar"
    aria-controls="mobileSidebar"
    style="font-size:1.2rem;padding:.3rem .55rem;">
    ☰
  </button>

  <div class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></div>

  <div class="d-flex align-items-center gap-2">
    <?php if ($isMember): ?>
      <div class="topbar-balance d-none d-sm-flex">
        <span class="bal-label">Balance</span>
        <span class="bal-amount" id="topbarBalance"><?= $topbarBalance ?></span>
      </div>
    <?php endif; ?>

    <a href="<?= APP_URL ?>/?page=<?= Auth::isAdmin() ? 'admin' : 'profile' ?>"
      class="topbar-avatar" title="<?= e($user['username'] ?? '') ?>">
      <?php if (!empty($user['photo'])): ?>
        <img src="<?= APP_URL ?>/uploads/<?= e($user['photo']) ?>" alt="">
      <?php else: ?>
        <?= $initials ?>
      <?php endif; ?>
    </a>
  </div>
</div>