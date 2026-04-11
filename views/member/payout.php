<?php
$pageTitle        = 'Payouts';
$minPayout        = (float)setting('min_payout','500');
$availableBalance = (float)$user['ewallet_balance'];
$hasPending       = false;
foreach ($history['data'] as $h) { if ($h['status']==='pending') { $hasPending=true; break; } }
?>
<?php require 'views/partials/head.php'; ?>
<?php require 'views/partials/sidebar_member.php'; ?>
<div class="main-content">
  <?php require 'views/partials/topbar.php'; ?>
  <div class="page-content">
    <?= render_flash() ?>
    <div class="row g-3 mb-3">
      <!-- Balance hero -->
      <div class="col-12 col-md-6">
        <div class="card h-100" style="background:linear-gradient(135deg,#1a3a8f,#3b6ff0);border:none;">
          <div class="card-body text-white">
            <div style="font-size:.68rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;opacity:.7;margin-bottom:.5rem;">Available Balance</div>
            <div style="font-size:2.2rem;font-weight:800;font-family:var(--font-mono);line-height:1;"><?= fmt_money($availableBalance) ?></div>
            <div style="font-size:.75rem;opacity:.6;margin-top:.5rem;">Minimum withdrawal: <?= fmt_money($minPayout) ?></div>
          </div>
        </div>
      </div>
      <!-- Request form -->
      <div class="col-12 col-md-6">
        <div class="card h-100">
          <div class="card-header"><span class="card-title">💳 Request Payout</span></div>
          <div class="card-body">
            <?php if ($hasPending): ?>
              <div class="alert alert-warning mb-0">⏳ You already have a pending payout request.</div>
            <?php elseif ($availableBalance < $minPayout): ?>
              <div class="alert alert-info mb-0">ℹ Minimum payout is <?= fmt_money($minPayout) ?>. Insufficient balance.</div>
            <?php else: ?>
            <form method="POST" action="<?= APP_URL ?>/?page=request_payout" id="payoutForm">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Amount <span class="text-danger">*</span></label>
                <input type="number" name="amount" class="form-control" inputmode="numeric"
                  min="<?= $minPayout ?>" max="<?= $availableBalance ?>" step="1" required
                  placeholder="Min <?= fmt_money($minPayout) ?>" oninput="checkAmount(this.value)">
                <div class="form-text" id="amountHint">Max: <?= fmt_money($availableBalance) ?></div>
              </div>
              <div class="mb-3">
                <label class="form-label">GCash Number <span class="text-danger">*</span></label>
                <input type="tel" name="gcash_number" class="form-control" inputmode="numeric"
                  value="<?= e($user['gcash_number'] ?? '') ?>" placeholder="09XXXXXXXXX" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Submit Payout Request</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">📋 Payout History</span></div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Requested</th><th>Amount</th><th>GCash</th><th>Status</th><th>Processed</th><th>Note</th></tr></thead>
          <tbody>
          <?php if (empty($history['data'])): ?>
            <tr><td colspan="6" class="text-center py-5 text-muted">No payout requests yet.</td></tr>
          <?php else: foreach ($history['data'] as $row): ?>
          <tr>
            <td class="td-muted" style="font-size:.75rem;"><?= fmt_datetime($row['requested_at']) ?></td>
            <td class="font-mono fw-bold"><?= fmt_money($row['amount']) ?></td>
            <td class="td-muted font-mono"><?= e($row['gcash_number']) ?></td>
            <td><?php
              $b = match($row['status']){'pending'=>'bg-warning-subtle text-warning','approved'=>'bg-info-subtle text-info','completed'=>'bg-success-subtle text-success','rejected'=>'bg-danger-subtle text-danger',default=>'bg-secondary-subtle text-secondary'};
            ?><span class="badge <?= $b ?>"><?= ucfirst($row['status']) ?></span></td>
            <td class="td-muted" style="font-size:.75rem;"><?= $row['processed_at'] ? fmt_datetime($row['processed_at']) : '—' ?></td>
            <td class="td-muted" style="font-size:.75rem;"><?= $row['admin_note'] ? e($row['admin_note']) : '—' ?></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($history['total_pages'] > 1): ?>
      <div class="card-footer"><?= pagination_links($history, APP_URL.'/?page=payout') ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
function checkAmount(v) {
  const el  = document.getElementById('amountHint');
  const n   = parseFloat(v) || 0;
  const max = <?= $availableBalance ?>;
  const min = <?= $minPayout ?>;
  if (n > max) el.innerHTML = '<span class="text-danger">Exceeds balance of <?= fmt_money($availableBalance) ?></span>';
  else if (n < min && n > 0) el.innerHTML = '<span class="text-danger">Minimum is <?= fmt_money($minPayout) ?></span>';
  else el.textContent = 'Max: <?= fmt_money($availableBalance) ?>';
}
</script>
<?php require 'views/partials/footer.php'; ?>
