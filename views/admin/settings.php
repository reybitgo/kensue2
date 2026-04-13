<?php $pageTitle = 'System Settings'; ?>
<?php require 'views/partials/head.php'; ?>
<?php require 'views/partials/sidebar_admin.php'; ?>
<div class="main-content">
  <?php require 'views/partials/topbar.php'; ?>
  <div class="page-content">
    <?= render_flash() ?>
    <div class="row g-3">
      <!-- Left col -->
      <div class="col-12 col-lg-6 d-flex flex-column gap-3">
        <div class="card">
          <div class="card-header"><span class="card-title">🌐 General Settings</span></div>
          <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/?page=admin_save_settings">
              <?= csrf_field() ?>
              <div class="mb-3"><label class="form-label">Site Name</label><input type="text" name="site_name" class="form-control" value="<?= e(setting('site_name')) ?>"></div>
              <div class="mb-3"><label class="form-label">Site Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?= e(setting('site_tagline')) ?>"></div>
              <div class="mb-3"><label class="form-label">Contact Email</label><input type="email" name="contact_email" class="form-control" value="<?= e(setting('contact_email')) ?>"></div>
              <div class="mb-3">
                <label class="form-label">Minimum Payout (₱)</label>
                <input type="number" name="min_payout" class="form-control" min="0" step="0.01" value="<?= e(setting('min_payout','500')) ?>">
                <div class="form-text">Members cannot request below this amount</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Maintenance Mode</label>
                <select name="maintenance_mode" class="form-select">
                  <option value="0" <?= setting('maintenance_mode')==='0'?'selected':'' ?>>Off — Site is live</option>
                  <option value="1" <?= setting('maintenance_mode')==='1'?'selected':'' ?>>On — Members see maintenance page</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary w-100">💾 Save Settings</button>
            </form>
          </div>
        </div>

        <!-- Admin password -->
        <div class="card">
          <div class="card-header"><span class="card-title">🔒 Change Admin Password</span></div>
          <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/?page=save_profile">
              <?= csrf_field() ?>
              <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" autocomplete="current-password"></div>
              <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" minlength="8" autocomplete="new-password"></div>
              <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="new_password_confirm" class="form-control" autocomplete="new-password"></div>
              <button type="submit" class="btn btn-primary w-100">🔒 Update Password</button>
            </form>
          </div>
        </div>
      </div>

      <!-- Right col -->
      <div class="col-12 col-lg-6 d-flex flex-column gap-3">
        <!-- Daily reset -->
        <div class="card">
          <div class="card-header"><span class="card-title">⏱️ Daily Pair Cap Reset</span></div>
          <div class="card-body">
            <p class="text-muted mb-3" style="font-size:.85rem;line-height:1.7;">
              The midnight cron resets <code>pairs_paid_today = 0</code> for all members, clearing the daily pairing cap so they can earn again tomorrow.
            </p>
            <div class="rounded p-3 mb-3" style="background:#f4f6fb;">
              <div class="text-muted mb-1" style="font-size:.68rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">Last Reset</div>
              <div class="fw-600 font-mono" style="font-size:.875rem;"><?= setting('last_reset') ? fmt_datetime(setting('last_reset')) : 'Never run' ?></div>
            </div>
            <div class="rounded p-3 mb-3 font-mono" style="background:#f4f6fb;font-size:.75rem;color:var(--muted);">
              Crontab:<br><strong style="color:#111;">0 0 * * * php /path/to/kensue/cron/midnight_reset.php</strong>
            </div>
            <form method="POST" action="<?= APP_URL ?>/?page=admin_manual_reset" class="m-0">
              <?= csrf_field() ?>
              <button type="button" class="btn btn-outline-warning w-100"
                onclick="showConfirm({title:'Run Daily Reset',message:'Reset <code>pairs_paid_today = 0</code> for ALL active members now? This simulates the midnight cron.',confirmText:'⟳ Run Reset',confirmClass:'btn-warning',formId:'manualResetForm'})">
                ⟳ Run Daily Reset Now
              </button>
            </form>
          </div>
        </div>

        <!-- System info -->
        <div class="card">
          <div class="card-header"><span class="card-title">ℹ System Info</span></div>
          <div class="card-body">
            <table class="info-table">
              <tr><td>PHP Version</td><td class="font-mono"><?= PHP_VERSION ?></td></tr>
              <tr><td>MySQL Version</td><td class="font-mono"><?= db()->query('SELECT VERSION()')->fetchColumn() ?></td></tr>
              <tr><td>Server Time</td><td class="font-mono"><?= date('Y-m-d H:i:s') ?></td></tr>
              <tr><td>App URL</td><td class="font-mono" style="font-size:.72rem;word-break:break-all;"><?= APP_URL ?></td></tr>
              <tr><td>Environment</td><td><span class="badge <?= APP_ENV==='production'?'bg-success-subtle text-success':'bg-warning-subtle text-warning' ?>"><?= APP_ENV ?></span></td></tr>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require 'views/partials/footer.php'; ?>
