<?php $pageTitle = 'Member: @' . $user['username']; ?>
<?php require 'views/partials/head.php'; ?>
<?php require 'views/partials/sidebar_admin.php'; ?>
<div class="main-content">
  <?php require 'views/partials/topbar.php'; ?>
  <div class="page-content">
    <?= render_flash() ?>

    <!-- Header -->
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
      <a href="<?= APP_URL ?>/?page=admin_users" class="btn btn-outline-secondary btn-sm">← Back</a>
      <div class="flex-grow-1">
        <h4 class="mb-0 fw-800">@<?= e($user['username']) ?></h4>
        <p class="text-muted mb-0" style="font-size:.78rem;">Member since <?= fmt_datetime($user['joined_at']) ?></p>
      </div>
      <?php $b = $user['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>
      <span class="badge <?= $b ?>" style="font-size:.8rem;padding:.4em .9em;"><?= ucfirst($user['status']) ?></span>
      <form method="POST" action="<?= APP_URL ?>/?page=admin_toggle_user" class="m-0">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $user['id'] ?>">
        <?php $isSuspend = $user['status'] === 'active'; ?>
        <button type="button" class="btn btn-sm <?= $isSuspend ? 'btn-outline-danger' : 'btn-outline-success' ?>"
          onclick="showConfirm({title:'<?= $isSuspend ? 'Suspend' : 'Activate' ?> Member',message:'<?= $isSuspend ? 'Suspend' : 'Activate' ?> <strong>@<?= e($user['username']) ?></strong>?',confirmText:'<?= $isSuspend ? 'Suspend' : 'Activate' ?>',confirmClass:'<?= $isSuspend ? 'btn-danger' : 'btn-success' ?>',onConfirm:()=>this.closest(\'form\').submit()})">
          <?= $isSuspend ? '🔒 Suspend' : '✅ Activate' ?>
        </button>
      </form>
    </div>

    <!-- KPI row -->
    <div class="row g-3 mb-3">
      <?php foreach (
        [
          ['E-Wallet Balance', fmt_money($user['ewallet_balance']),              'primary', 'primary'],
          ['Total Earned',     fmt_money($summary['total_earned']),              'success', 'success'],
          ['Pairs Paid / Today', $pairingStatus['pairs_paid'] . ' / ' . $pairingStatus['pairs_paid_today'], 'orange', 'warning'],
          ['Pairs Flushed',    number_format($pairingStatus['pairs_flushed']),   'danger', 'danger'],
        ] as [$label, $val, $accent, $color]
      ): ?>
        <div class="col-6 col-xl-3">
          <div class="card stat-card">
            <div class="stat-accent stat-accent-<?= $accent ?>"></div>
            <div class="card-body pt-4">
              <div class="stat-label"><?= $label ?></div>
              <div class="stat-value text-<?= $color ?>" style="font-size:1.25rem;"><?= $val ?></div>
              <?php if ($label === 'Pairs Paid / Today'): ?><div class="stat-sub">Cap: <?= $pairingStatus['daily_cap'] ?> / day</div><?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="row g-3 mb-3">
      <!-- Profile -->
      <div class="col-12 col-md-6">
        <div class="card h-100">
          <div class="card-header"><span class="card-title">👤 Profile</span></div>
          <div class="card-body">
            <table class="info-table">
              <tr>
                <td>Full Name</td>
                <td><?= e($user['full_name'] ?? '—') ?></td>
              </tr>
              <tr>
                <td>Email</td>
                <td><?= e($user['email'] ?? '—') ?></td>
              </tr>
              <tr>
                <td>Mobile</td>
                <td><?= e($user['mobile'] ?? '—') ?></td>
              </tr>
              <tr>
                <td>GCash</td>
                <td><strong><?= e($user['gcash_number'] ?? '—') ?></strong></td>
              </tr>
              <tr>
                <td>Address</td>
                <td><?= e($user['address'] ?? '—') ?></td>
              </tr>
              <tr>
                <td>Last Login</td>
                <td><?= $user['last_login'] ? fmt_datetime($user['last_login']) : 'Never' ?></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
      <!-- Binary -->
      <div class="col-12 col-md-6">
        <div class="card h-100">
          <div class="card-header"><span class="card-title">🌳 Binary Placement</span></div>
          <div class="card-body">
            <table class="info-table">
              <tr>
                <td>Package</td>
                <td><span class="badge bg-primary-subtle text-primary"><?= e($user['package_name'] ?? '—') ?></span></td>
              </tr>
              <tr>
                <td>Sponsor</td>
                <td><?= ($user['sponsor_username'] ?? null) ? '<a href="' . APP_URL . '/?page=admin_user_view&id=' . $user['sponsor_id'] . '">@' . e($user['sponsor_username']) . '</a>' : '—' ?></td>
              </tr>
              <tr>
                <td>Upline</td>
                <td><?= ($user['binary_parent_username'] ?? null) ? '@' . e($user['binary_parent_username']) . ' (' . $user['binary_position'] . ')' : '—' ?></td>
              </tr>
              <tr>
                <td>Pairing Bonus</td>
                <td><?= fmt_money($user['pairing_bonus'] ?? 0) ?> / pair</td>
              </tr>
              <tr>
                <td>Daily Cap</td>
                <td><?= $user['daily_pair_cap'] ?? 0 ?> pairs / day</td>
              </tr>
            </table>
            <div class="row g-2 mt-2">
              <div class="col-6">
                <div class="leg-box text-center">
                  <div class="leg-label">↙ Left</div>
                  <div class="leg-count"><?= number_format($user['left_count']) ?></div>
                </div>
              </div>
              <div class="col-6">
                <div class="leg-box text-center">
                  <div class="leg-label">↘ Right</div>
                  <div class="leg-count"><?= number_format($user['right_count']) ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <?php $tab = $_GET['tab'] ?? 'commissions'; ?>
    <div class="card">
      <div class="card-header">
        <ul class="nav nav-pills card-header-pills gap-1">
          <?php foreach (['commissions' => '💰 Commissions', 'ledger' => '📒 E-Wallet Ledger', 'payouts' => '💳 Payouts'] as $t => $label): ?>
            <li class="nav-item"><a class="nav-link <?= $tab === $t ? 'active' : '' ?>" href="<?= APP_URL ?>/?page=admin_user_view&id=<?= $user['id'] ?>&tab=<?= $t ?>"><?= $label ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <?php if ($tab === 'commissions'): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>From</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($commHist['data'])): ?><tr>
                  <td colspan="6" class="text-center py-4 text-muted">No commissions.</td>
                </tr>
                <?php else: foreach ($commHist['data'] as $c):
                  $tn = match ($c['type']) {
                    'pairing' => '🤝 Pairing',
                    'direct_referral' => '👥 Direct',
                    'indirect_referral' => '🔗 Indirect Lvl ' . $c['level'],
                    default => $c['type']
                  };
                ?>
                  <tr>
                    <td class="td-muted" style="font-size:.72rem;"><?= fmt_datetime($c['created_at']) ?></td>
                    <td><?= $tn ?></td>
                    <td class="text-truncate" style="max-width:180px;font-size:.8rem;"><?= e($c['description']) ?></td>
                    <td class="td-muted"><?= $c['source_username'] ? '@' . e($c['source_username']) : '—' ?></td>
                    <td class="<?= $c['status'] === 'credited' ? 'td-green' : ' td-muted' ?> font-mono"><?= $c['status'] === 'credited' ? '+' . fmt_money($c['amount']) : '—' ?></td>
                    <td><span class="badge <?= $c['status'] === 'credited' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>"><?= ucfirst($c['status']) ?></span></td>
                  </tr>
              <?php endforeach;
              endif; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($tab === 'ledger'): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Balance After</th>
                <th>Note</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($ledger['data'])): ?><tr>
                  <td colspan="5" class="text-center py-4 text-muted">No ledger entries.</td>
                </tr>
                <?php else: foreach ($ledger['data'] as $l): ?>
                  <tr>
                    <td class="td-muted" style="font-size:.72rem;"><?= fmt_datetime($l['created_at']) ?></td>
                    <td><span class="badge <?= $l['type'] === 'credit' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>"><?= ucfirst($l['type']) ?></span></td>
                    <td class="font-mono <?= $l['type'] === 'credit' ? 'td-green' : 'td-red' ?>"><?= ($l['type'] === 'credit' ? '+' : '-') . fmt_money($l['amount']) ?></td>
                    <td class="font-mono fw-bold"><?= fmt_money($l['balance_after']) ?></td>
                    <td class="td-muted" style="font-size:.78rem;"><?= e($l['note'] ?? '—') ?></td>
                  </tr>
              <?php endforeach;
              endif; ?>
            </tbody>
          </table>
        </div>

      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Requested</th>
                <th>Amount</th>
                <th>GCash</th>
                <th>Status</th>
                <th>Processed</th>
                <th>Note</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($payouts['data'])): ?><tr>
                  <td colspan="6" class="text-center py-4 text-muted">No payout history.</td>
                </tr>
                <?php else: foreach ($payouts['data'] as $pr): ?>
                  <tr>
                    <td class="td-muted" style="font-size:.72rem;"><?= fmt_datetime($pr['requested_at']) ?></td>
                    <td class="font-mono fw-bold"><?= fmt_money($pr['amount']) ?></td>
                    <td class="td-muted font-mono"><?= e($pr['gcash_number']) ?></td>
                    <td><?php $b = match ($pr['status']) {
                          'pending' => 'bg-warning-subtle text-warning',
                          'approved' => 'bg-info-subtle text-info',
                          'completed' => 'bg-success-subtle text-success',
                          'rejected' => 'bg-danger-subtle text-danger',
                          default => 'bg-secondary-subtle'
                        }; ?><span class="badge <?= $b ?>"><?= ucfirst($pr['status']) ?></span></td>
                    <td class="td-muted" style="font-size:.72rem;"><?= $pr['processed_at'] ? fmt_datetime($pr['processed_at']) : '—' ?></td>
                    <td class="td-muted" style="font-size:.78rem;"><?= e($pr['admin_note'] ?? '—') ?></td>
                  </tr>
              <?php endforeach;
              endif; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require 'views/partials/footer.php'; ?>