<?php $pageTitle = 'Profile & Settings'; ?>
<?php require 'views/partials/head.php'; ?>
<?php require 'views/partials/sidebar_member.php'; ?>
<div class="main-content">
  <?php require 'views/partials/topbar.php'; ?>
  <div class="page-content">
    <?= render_flash() ?>
    <form method="POST" action="<?= APP_URL ?>/?page=save_profile" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="row g-3">
        <!-- Left col -->
        <div class="col-12 col-lg-4 d-flex flex-column gap-3">
          <!-- Photo -->
          <div class="card">
            <div class="card-header"><span class="card-title">🖼 Profile Photo</span></div>
            <div class="card-body d-flex align-items-center gap-3 flex-wrap">
              <div id="avatarWrap" style="width:72px;height:72px;border-radius:50%;overflow:hidden;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.75rem;font-weight:700;color:var(--primary);border:3px solid #dde3ef;flex-shrink:0;">
                <?php if($user['photo']): ?><img id="photoPreview" src="<?= APP_URL ?>/uploads/<?= e($user['photo']) ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><span id="photoInitial"><?= strtoupper(substr($user['username'],0,1)) ?></span><?php endif; ?>
              </div>
              <div>
                <label class="btn btn-outline-primary btn-sm cursor-pointer">📷 Change Photo
                  <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewPhoto(this)">
                </label>
                <p class="text-muted mb-0 mt-1" style="font-size:.72rem;">JPEG/PNG/WebP · Max 2MB</p>
              </div>
            </div>
          </div>
          <!-- Account info (read-only) -->
          <div class="card">
            <div class="card-header"><span class="card-title">ℹ Account Info</span></div>
            <div class="card-body">
              <table class="info-table">
                <tr><td>Username</td><td><strong>@<?= e($user['username']) ?></strong></td></tr>
                <tr><td>Package</td><td><?= e($user['package_name'] ?? '—') ?></td></tr>
                <tr><td>Sponsor</td><td><?= isset($user['sponsor_username']) && $user['sponsor_username'] ? '@'.e($user['sponsor_username']) : '—' ?></td></tr>
                <tr><td>Upline</td><td><?php $bpu=$user['binary_parent_username']??null; echo $bpu ? '@'.e($bpu).' ('.e($user['binary_position']??'').')' : '—'; ?></td></tr>
                <tr><td>Joined</td><td><?= fmt_datetime($user['joined_at']) ?></td></tr>
              </table>
            </div>
          </div>
        </div>
        <!-- Right col -->
        <div class="col-12 col-lg-8 d-flex flex-column gap-3">
          <div class="card">
            <div class="card-header"><span class="card-title">👤 Personal Information</span></div>
            <div class="card-body">
              <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']??'') ?>" placeholder="Your full name"></div>
              <div class="row g-3">
                <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($user['email']??'') ?>" placeholder="email@example.com"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Mobile</label><input type="tel" name="mobile" class="form-control" value="<?= e($user['mobile']??'') ?>" placeholder="09XXXXXXXXX"></div>
              </div>
              <div class="mb-0"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2" placeholder="Your address"><?= e($user['address']??'') ?></textarea></div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><span class="card-title">💳 Payout Information</span></div>
            <div class="card-body">
              <label class="form-label">GCash Number</label>
              <input type="tel" name="gcash_number" class="form-control" inputmode="numeric" value="<?= e($user['gcash_number']??'') ?>" placeholder="09XXXXXXXXX">
              <div class="form-text">This number will receive your payouts via GCash.</div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><span class="card-title">🔒 Change Password</span></div>
            <div class="card-body">
              <div class="mb-3"><label class="form-label">Current Password</label>
                <div class="input-group">
                  <input type="password" name="current_password" id="cur_pw" class="form-control" placeholder="Current password" autocomplete="current-password">
                  <button type="button" class="btn btn-outline-secondary" onclick="togglePw('cur_pw',this)">👁</button>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-md-6 mb-3"><label class="form-label">New Password</label>
                  <div class="input-group">
                    <input type="password" name="new_password" id="new_pw" class="form-control" minlength="8" placeholder="Min. 8 characters" autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePw('new_pw',this)">👁</button>
                  </div>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Confirm New</label>
                  <div class="input-group">
                    <input type="password" name="new_password_confirm" id="confirm_pw" class="form-control" placeholder="Repeat password" autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePw('confirm_pw',this)">👁</button>
                  </div>
                </div>
              </div>
              <p class="form-text mb-0">Leave blank to keep your current password.</p>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-lg w-100">💾 Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
function previewPhoto(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => { document.getElementById('avatarWrap').innerHTML = '<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover;">'; };
  reader.readAsDataURL(input.files[0]);
}

function togglePw(id, btn) {
  const el = document.getElementById(id);
  el.type  = el.type === 'password' ? 'text' : 'password';
  btn.textContent = el.type === 'password' ? '👁' : '🙈';
}
</script>
<?php require 'views/partials/footer.php'; ?>
