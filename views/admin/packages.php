<?php $pageTitle = 'Packages'; ?>
<?php require 'views/partials/head.php'; ?>
<?php require 'views/partials/sidebar_admin.php'; ?>
<div class="main-content">
  <?php require 'views/partials/topbar.php'; ?>
  <div class="page-content">
    <?= render_flash() ?>
    <div class="row g-3">
      <!-- Package list -->
      <div class="col-12 col-lg-5">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="card-title">📦 All Packages</span>
            <a href="<?= APP_URL ?>/?page=admin_packages" class="btn btn-primary btn-sm">+ New</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead><tr><th>Name</th><th>Entry Fee</th><th>Pair Bonus</th><th>Cap</th><th>Status</th><th></th></tr></thead>
              <tbody>
              <?php if (empty($packages)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No packages yet.</td></tr>
              <?php else: foreach ($packages as $pkg): ?>
              <tr>
                <td class="fw-bold"><?= e($pkg['name']) ?></td>
                <td class="font-mono"><?= fmt_money($pkg['entry_fee']) ?></td>
                <td class="td-green font-mono"><?= fmt_money($pkg['pairing_bonus']) ?></td>
                <td class="td-muted"><?= $pkg['daily_pair_cap'] ?></td>
                <td><span class="badge <?= $pkg['status']==='active'?'bg-success-subtle text-success':'bg-secondary-subtle text-secondary' ?>"><?= ucfirst($pkg['status']) ?></span></td>
                <td><a href="<?= APP_URL ?>/?page=admin_packages&edit=<?= $pkg['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
              </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Create / Edit form -->
      <div class="col-12 col-lg-7">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="card-title"><?= $editPkg ? '✏️ Edit Package' : '➕ New Package' ?></span>
            <?php if ($editPkg): ?><a href="<?= APP_URL ?>/?page=admin_packages" class="btn btn-sm btn-outline-secondary">✕ Cancel</a><?php endif; ?>
          </div>
          <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/?page=admin_save_package">
              <?= csrf_field() ?>
              <?php if ($editPkg): ?><input type="hidden" name="package_id" value="<?= $editPkg['id'] ?>"><?php endif; ?>

              <div class="mb-3">
                <label class="form-label">Package Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($editPkg['name']??'') ?>" placeholder="e.g. Starter, Pro, Elite" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Entry Fee (₱) <span class="text-danger">*</span></label>
                  <input type="number" name="entry_fee" class="form-control" inputmode="decimal" min="0" step="0.01" value="<?= e($editPkg['entry_fee']??'') ?>" placeholder="10000.00" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Pairing Bonus (₱) <span class="text-danger">*</span></label>
                  <input type="number" name="pairing_bonus" class="form-control" inputmode="decimal" min="0" step="0.01" value="<?= e($editPkg['pairing_bonus']??'') ?>" placeholder="2000.00" required>
                  <div class="form-text">Per pair paid out</div>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Daily Pair Cap <span class="text-danger">*</span></label>
                  <input type="number" name="daily_pair_cap" class="form-control" inputmode="numeric" min="1" max="100" value="<?= e($editPkg['daily_pair_cap']??3) ?>" required>
                  <div class="form-text">Flush-out limit per member per day</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Direct Referral Bonus (₱)</label>
                  <input type="number" name="direct_ref_bonus" class="form-control" inputmode="decimal" min="0" step="0.01" value="<?= e($editPkg['direct_ref_bonus']??0) ?>" placeholder="500.00">
                  <div class="form-text">Paid once to sponsor on join</div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <option value="active"   <?= ($editPkg['status']??'active')==='active'  ?'selected':'' ?>>Active</option>
                  <option value="inactive" <?= ($editPkg['status']??'')==='inactive'       ?'selected':'' ?>>Inactive</option>
                </select>
              </div>

              <!-- Indirect Referral Levels -->
              <div class="mb-3">
                <label class="form-label fw-bold">🔗 Indirect Referral Bonuses (10 Levels)</label>
                <div class="row g-2">
                  <?php $lvls = $editPkg['indirect_levels'] ?? [];
                  for ($lvl = 1; $lvl <= 10; $lvl++): ?>
                  <div class="col-6 col-md-4 col-lg-6 col-xl-4">
                    <label class="form-label" style="font-size:.72rem;">Level <?= $lvl ?></label>
                    <div class="input-group input-group-sm">
                      <span class="input-group-text">₱</span>
                      <input type="number" name="indirect_<?= $lvl ?>" class="form-control" inputmode="decimal" min="0" step="0.01" value="<?= e($lvls[$lvl]??0) ?>" placeholder="0.00">
                    </div>
                  </div>
                  <?php endfor; ?>
                </div>
                <div class="form-text mt-1">Set 0 to disable a level. Paid once to each upline sponsor on member join.</div>
              </div>

              <button type="submit" class="btn btn-primary w-100 btn-lg">
                <?= $editPkg ? '💾 Update Package' : '➕ Create Package' ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require 'views/partials/footer.php'; ?>
