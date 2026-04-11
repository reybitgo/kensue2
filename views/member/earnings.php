<?php $pageTitle = 'Earnings'; ?>
<?php require 'views/partials/head.php'; ?>
<?php require 'views/partials/sidebar_member.php'; ?>
<div class="main-content">
  <?php require 'views/partials/topbar.php'; ?>
  <div class="page-content">
    <?= render_flash() ?>
    <div class="row g-3 mb-3">
      <?php foreach ([
        ['Total Earned',      $summary['total_earned'],   'primary','primary'],
        ['Pairing Bonuses',   $summary['total_pairing'],  'success','success'],
        ['Direct Referral',   $summary['total_direct'],   'orange', 'warning'],
        ['Indirect Referral', $summary['total_indirect'],  'purple', 'primary'],
      ] as [$label,$val,$accent,$color]): ?>
      <div class="col-6 col-xl-3">
        <div class="card stat-card">
          <div class="stat-accent stat-accent-<?= $accent ?>"></div>
          <div class="card-body pt-4">
            <div class="stat-label"><?= $label ?></div>
            <div class="stat-value text-<?= $color ?>"><?= fmt_money((float)$val) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="card-header">
        <ul class="nav nav-pills card-header-pills gap-1">
          <?php foreach ([''=> 'All','pairing'=>'🤝 Pairing','direct_referral'=>'👥 Direct','indirect_referral'=>'🔗 Indirect'] as $val=>$label): ?>
          <li class="nav-item">
            <a class="nav-link <?= $type===$val?'active':'' ?>" href="<?= APP_URL ?>/?page=earnings&type=<?= $val ?>"><?= $label ?></a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>From</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (empty($history['data'])): ?>
            <tr><td colspan="6" class="text-center py-5 text-muted">No earnings found.</td></tr>
          <?php else: foreach ($history['data'] as $row):
            $typeName = match($row['type']) {'pairing'=>'🤝 Pairing','direct_referral'=>'👥 Direct Referral','indirect_referral'=>'🔗 Indirect Lvl '.$row['level'],default=>$row['type']};
          ?>
          <tr>
            <td class="td-muted font-mono" style="font-size:.72rem;"><?= fmt_datetime($row['created_at']) ?></td>
            <td><?= $typeName ?></td>
            <td class="text-truncate" style="max-width:200px;" title="<?= e($row['description']) ?>"><?= e($row['description']) ?></td>
            <td class="td-muted"><?= $row['source_username'] ? '@'.e($row['source_username']) : '—' ?></td>
            <td><?php if($row['status']==='credited'): ?><span class="td-green">+<?= fmt_money($row['amount']) ?></span><?php else: ?><span class="td-muted">—</span><?php endif; ?></td>
            <td><span class="badge <?= $row['status']==='credited'?'bg-success-subtle text-success':'bg-warning-subtle text-warning' ?>"><?= ucfirst($row['status']) ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($history['total_pages'] > 1): ?>
      <div class="card-footer"><?= pagination_links($history, APP_URL.'/?page=earnings&type='.urlencode($type)) ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require 'views/partials/footer.php'; ?>
