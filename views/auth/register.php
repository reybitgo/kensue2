<?php
$pageTitle      = 'Register Member — ' . setting('site_name', APP_NAME);
$isLoggedIn     = Auth::check();
$currentUser    = $isLoggedIn ? Auth::user() : null;
$prefillSponsor = $prefillSponsor ?? trim($_GET['sponsor'] ?? '');
// Auto-prefill sponsor with current user's username for both members AND admins
if ($isLoggedIn && !$prefillSponsor) {
    $prefillSponsor = $currentUser['username'];
}
?>
<?php if ($isLoggedIn): ?>
  <?php require 'views/partials/head.php'; ?>
  <!-- auth.css needed for step bar, position toggle, slot status -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
  <?php if (Auth::isAdmin()): ?>
    <?php require 'views/partials/sidebar_admin.php'; ?>
  <?php else: ?>
    <?php require 'views/partials/sidebar_member.php'; ?>
  <?php endif; ?>
  <div class="main-content">
    <?php require 'views/partials/topbar.php'; ?>
    <div class="page-content">
      <?= render_flash() ?>
      <div class="d-flex justify-content-center">
        <div style="width:100%;max-width:560px;">
<?php else: ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body>
<div class="auth-page">
<?php endif; ?>

  <div class="auth-card auth-card-wide <?= $isLoggedIn ? 'shadow' : '' ?>">

    <?php if ($isLoggedIn): ?>
    <!-- Logged-in header strip -->
    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom" style="background:#f8fafd;">
      <div style="width:38px;height:38px;border-radius:.625rem;background:var(--primary);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
        <img src="<?= APP_URL ?>/assets/img/logo.png" style="width:24px;height:24px;object-fit:contain;" alt="">
      </div>
      <div class="flex-grow-1">
        <div style="font-size:.875rem;font-weight:700;">Register New Member</div>
        <div style="font-size:.72rem;color:var(--muted);">Registering as <strong>@<?= e($currentUser['username']) ?></strong></div>
      </div>
      <a href="<?= Auth::isAdmin() ? APP_URL.'/?page=admin' : APP_URL.'/?page=dashboard' ?>"
         class="btn btn-sm btn-outline-secondary">✕ Cancel</a>
    </div>
    <?php else: ?>
    <!-- Guest header -->
    <div class="auth-header">
      <div class="auth-logo"><img src="<?= APP_URL ?>/assets/img/logo.png" alt="Logo"></div>
      <h1><?= e(setting('site_name', APP_NAME)) ?></h1>
      <p>Create your member account</p>
    </div>
    <?php endif; ?>

    <!-- Step bar -->
    <div class="steps-bar" id="stepsBar">
      <div class="reg-step active" id="step-ind-1"><div class="step-dot">1</div><div class="step-text">Validate Code</div></div>
      <div class="reg-step"        id="step-ind-2"><div class="step-dot">2</div><div class="step-text">Account Setup</div></div>
      <div class="reg-step"        id="step-ind-3"><div class="step-dot">3</div><div class="step-text">Confirm</div></div>
    </div>

    <div style="padding:0 2.25rem;" id="flashArea"><?= render_flash() ?></div>

    <form method="POST" action="<?= APP_URL ?>/?page=do_register" id="regForm">
      <?= csrf_field() ?>

      <!-- ── STEP 1 ── -->
      <div class="auth-body" id="step1">
        <p class="text-muted mb-3" style="font-size:.85rem;">Enter the registration code for the new member.</p>
        <div class="mb-3">
          <label class="form-label">Registration Code <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="text" id="reg_code" name="reg_code" class="form-control font-mono"
              placeholder="XXXX-XXXX-XXXX" maxlength="14"
              style="text-transform:uppercase;letter-spacing:2px;font-size:1rem;" required>
            <button type="button" class="btn btn-outline-primary" id="validateCodeBtn">Validate</button>
          </div>
          <div class="form-text" id="codeHint"></div>
        </div>
        <div id="packageInfo" class="code-verified d-none">
          <span style="font-size:1.2rem;">✅</span>
          <div>
            <div class="fw-bold" id="pkgName"></div>
            <div style="font-size:.75rem;margin-top:2px;" id="pkgDetails"></div>
          </div>
        </div>
        <input type="hidden" name="validated_code" id="validatedCode">
        <button type="button" class="btn btn-primary w-100 btn-lg" id="toStep2Btn" disabled>Continue →</button>
      </div>

      <!-- ── STEP 2 ── -->
      <div class="auth-body" id="step2" style="display:none;">
        <div class="mb-3">
          <label class="form-label">Username <span class="text-danger">*</span></label>
          <input type="text" id="username" name="username" class="form-control"
            placeholder="3–40 chars, letters/numbers/_" minlength="3" maxlength="40"
            autocomplete="off" required>
          <div class="form-text" id="usernameHint"></div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" id="password" name="password" class="form-control"
                placeholder="Min. 8 characters" minlength="8" required>
              <button type="button" class="btn btn-outline-secondary" onclick="togglePw('password',this)">👁</button>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" id="password_confirm" name="password_confirm"
                class="form-control" placeholder="Repeat password" required>
              <button type="button" class="btn btn-outline-secondary" onclick="togglePw('password_confirm',this)">👁</button>
            </div>
            <div class="form-text" id="pwMatchHint"></div>
          </div>
        </div>
        <hr class="my-3">
        <div class="mb-3">
          <label class="form-label">Sponsor Username <span class="text-danger">*</span></label>
          <?php if ($isLoggedIn && ($currentUser['role'] ?? '') === 'member'): ?>
          <!-- Member always sponsors as themselves — locked -->
          <input type="text" id="sponsor_username" name="sponsor_username"
            class="form-control" value="<?= e($prefillSponsor) ?>" readonly
            style="background:#f8fafd;font-weight:600;">
          <div class="form-text text-success">✓ Sponsor locked to your account.</div>
          <?php else: ?>
          <input type="text" id="sponsor_username" name="sponsor_username"
            class="form-control" placeholder="Sponsor's username"
            value="<?= e($prefillSponsor) ?>" autocomplete="off" required>
          <div class="form-text" id="sponsorHint"></div>
          <?php endif; ?>
        </div>
        <div class="mb-3">
          <label class="form-label">Binary Upline Username <span class="text-danger">*</span></label>
          <input type="text" id="upline_username" name="upline_username"
            class="form-control" placeholder="Upline in the binary tree"
            autocomplete="off" required>
          <div class="form-text" id="uplineHint"></div>
          <div id="slotStatus" class="slot-status d-none">
            <span id="leftSlot">↙ Left: —</span>
            <span id="rightSlot">↘ Right: —</span>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Binary Position <span class="text-danger">*</span></label>
          <div class="position-toggle">
            <div class="position-option">
              <input type="radio" id="pos_left" name="binary_position" value="left" required>
              <label class="position-label" for="pos_left">↙ Left</label>
            </div>
            <div class="position-option">
              <input type="radio" id="pos_right" name="binary_position" value="right">
              <label class="position-label" for="pos_right">↘ Right</label>
            </div>
          </div>
          <div class="form-text" id="positionHint"></div>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary" onclick="goStep(1)">← Back</button>
          <button type="button" class="btn btn-primary flex-grow-1" id="toStep3Btn">Review →</button>
        </div>
      </div>

      <!-- ── STEP 3 ── -->
      <div class="auth-body" id="step3" style="display:none;">
        <p class="text-muted mb-3" style="font-size:.85rem;">Review before completing registration.</p>
        <div class="card mb-3">
          <div class="card-header"><span class="card-title">📋 Registration Summary</span></div>
          <div class="card-body">
            <table class="info-table">
              <tr><td>Code</td>    <td><span class="reg-code" id="rev_code">—</span></td></tr>
              <tr><td>Package</td> <td id="rev_package">—</td></tr>
              <tr><td>Username</td><td id="rev_username" class="fw-bold">—</td></tr>
              <tr><td>Sponsor</td> <td id="rev_sponsor">—</td></tr>
              <tr><td>Upline</td>  <td id="rev_upline">—</td></tr>
              <tr><td>Position</td><td id="rev_position">—</td></tr>
            </table>
          </div>
        </div>
        <div class="alert alert-warning py-2 mb-3" style="font-size:.8rem;">
          ⚠️ Binary position cannot be changed after registration.
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary" onclick="goStep(2)">← Back</button>
          <button type="submit" class="btn btn-primary flex-grow-1 btn-lg" id="submitBtn">
            ✓ Complete Registration
          </button>
        </div>
      </div>

    </form>

    <?php if (!$isLoggedIn): ?>
    <div class="auth-footer">
      Already have an account? <a href="<?= APP_URL ?>/?page=login">Sign in →</a>
    </div>
    <?php endif; ?>

  </div><!-- .auth-card -->

<?php if ($isLoggedIn): ?>
        </div><!-- max-width wrapper -->
      </div><!-- d-flex justify-content-center -->
    </div><!-- page-content -->
  </div><!-- main-content -->
<?php else: ?>
</div><!-- auth-page -->
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($isLoggedIn): ?>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<?php endif; ?>
<script>
const API           = '<?= APP_URL ?>';
const LOCKED_SPONSOR= <?= ($isLoggedIn && ($currentUser['role']??'') === 'member') ? 'true' : 'false' ?>;
const PREFILL_SPONSOR = <?= json_encode($prefillSponsor) ?>;

let codeData={}, usernameOk=false, sponsorOk=false, uplineOk=false, slotData={};

function goStep(n) {
  [1,2,3].forEach(i => { document.getElementById('step'+i).style.display = i===n?'block':'none'; });
  [1,2,3].forEach(i => {
    const el = document.getElementById('step-ind-'+i);
    el.className = 'reg-step ' + (i<n?'done':i===n?'active':'');
  });
  // Clear server flash errors when navigating — they no longer apply
  const flash = document.getElementById('flashArea');
  if (flash) flash.innerHTML = '';
  window.scrollTo({top:0,behavior:'smooth'});
}

function setHint(id, msg, ok) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.className = 'form-text' + (ok===true?' text-success':ok===false?' text-danger':'');
}

function togglePw(id, btn) {
  const el = document.getElementById(id);
  el.type  = el.type==='password' ? 'text' : 'password';
  btn.textContent = el.type==='password' ? '👁' : '🙈';
}

// ── Code formatting & validation ──────────────────────────────
document.getElementById('reg_code').addEventListener('input', function() {
  const clean = this.value.replace(/[^A-Z0-9]/gi,'').toUpperCase().slice(0,12);
  const parts = [clean.slice(0,4),clean.slice(4,8),clean.slice(8,12)].filter(Boolean);
  this.value  = parts.join('-');
  resetCodeState();
});

function resetCodeState() {
  document.getElementById('packageInfo').classList.add('d-none');
  document.getElementById('validatedCode').value = '';
  document.getElementById('toStep2Btn').disabled = true;
  setHint('codeHint','',null);
  codeData = {};
}

document.getElementById('validateCodeBtn').addEventListener('click', async function() {
  const code = document.getElementById('reg_code').value.trim();
  if (code.length < 14) { setHint('codeHint','Enter a complete code (XXXX-XXXX-XXXX)',false); return; }
  this.disabled=true; this.textContent='…';
  try {
    const fd = new FormData();
    fd.append('code', code);
    fd.append('csrf_token', document.querySelector('[name=csrf_token]').value);
    const data = await (await fetch(API+'/?page=validate_code',{method:'POST',body:fd})).json();
    if (data.valid) {
      codeData = data;
      document.getElementById('pkgName').textContent    = data.package_name;
      document.getElementById('pkgDetails').textContent =
        'Entry: '+data.entry_fee+' · Bonus: '+data.pairing_bonus+' · Cap: '+data.daily_cap+' pairs/day';
      document.getElementById('packageInfo').classList.remove('d-none');
      document.getElementById('validatedCode').value    = code;
      document.getElementById('toStep2Btn').disabled    = false;
      setHint('codeHint','✓ Code is valid!',true);
    } else {
      setHint('codeHint', data.message||'Invalid code.', false);
    }
  } catch(e) { setHint('codeHint','Network error.',false); }
  this.disabled=false; this.textContent='Validate';
});

document.getElementById('toStep2Btn').addEventListener('click', () => {
  if (document.getElementById('validatedCode').value) {
    goStep(2);
    // Auto-validate pre-filled sponsor (locked member or prefilled from URL)
    if (PREFILL_SPONSOR) {
      const sField = document.getElementById('sponsor_username');
      if (LOCKED_SPONSOR) {
        sponsorOk = true; // locked to self — already validated server-side
      } else if (sField.value) {
        checkSponsor(sField.value);
      }
    }
  }
});

// ── Username ──────────────────────────────────────────────────
let uTimer;
document.getElementById('username').addEventListener('input', function() {
  usernameOk=false; clearTimeout(uTimer);
  const v = this.value.trim();
  if (v.length < 3) { setHint('usernameHint','At least 3 characters required.',null); return; }
  setHint('usernameHint','Checking…',null);
  uTimer = setTimeout(async () => {
    const data = await (await fetch(API+'/?page=check_username&username='+encodeURIComponent(v))).json();
    usernameOk = data.available;
    setHint('usernameHint', data.message, data.available);
  }, 600);
});

// ── Password confirm ──────────────────────────────────────────
document.getElementById('password_confirm').addEventListener('input', function() {
  const ok = document.getElementById('password').value === this.value;
  setHint('pwMatchHint', this.value ? (ok?'✓ Passwords match.':'✗ Passwords do not match.') : '', this.value?ok:null);
});

// ── Sponsor ───────────────────────────────────────────────────
let sTimer;
if (!LOCKED_SPONSOR) {
  document.getElementById('sponsor_username').addEventListener('input', function() {
    sponsorOk=false; clearTimeout(sTimer);
    const v = this.value.trim();
    if (!v) { setHint('sponsorHint','',null); return; }
    setHint('sponsorHint','Checking…',null);
    sTimer = setTimeout(() => checkSponsor(v), 600);
  });
  // Auto-trigger if pre-filled from URL
  if (PREFILL_SPONSOR) {
    setTimeout(() => {
      const el = document.getElementById('sponsor_username');
      if (el && el.value) checkSponsor(el.value);
    }, 800);
  }
}

async function checkSponsor(v) {
  const data = await (await fetch(API+'/?page=check_username&username='+encodeURIComponent(v))).json();
  sponsorOk = !data.available; // exists = not available as a new username
  setHint('sponsorHint', sponsorOk ? '✓ Sponsor @'+v+' found.' : '✗ Sponsor not found.', sponsorOk);
}

// ── Upline + slot ─────────────────────────────────────────────
let upTimer;
document.getElementById('upline_username').addEventListener('input', function() {
  uplineOk=false; slotData={}; clearTimeout(upTimer);
  const v = this.value.trim();
  if (!v) {
    setHint('uplineHint','',null);
    document.getElementById('slotStatus').classList.add('d-none');
    resetPosBtns();
    return;
  }
  setHint('uplineHint','Checking…',null);
  upTimer = setTimeout(() => checkUpline(v), 600);
});

document.querySelectorAll('[name=binary_position]').forEach(r => {
  r.addEventListener('change', function() { checkPos(this.value); });
});

async function checkUpline(v) {
  const pos  = document.querySelector('[name=binary_position]:checked')?.value || '';
  const data = await (await fetch(API+'/?page=check_upline&username='+encodeURIComponent(v)+'&position='+pos)).json();
  if (!data.valid) {
    setHint('uplineHint','✗ '+data.message,false);
    document.getElementById('slotStatus').classList.add('d-none');
    uplineOk=false; return;
  }
  slotData=data; uplineOk=true;
  setHint('uplineHint','✓ Found @'+data.username,true);

  const ls = document.getElementById('leftSlot');
  const rs = document.getElementById('rightSlot');
  ls.textContent = '↙ Left: '  + (data.left_free  ? '✓ Free' : '✗ Taken');
  rs.textContent = '↘ Right: ' + (data.right_free ? '✓ Free' : '✗ Taken');
  ls.className   = data.left_free  ? 'slot-free' : 'slot-taken';
  rs.className   = data.right_free ? 'slot-free' : 'slot-taken';
  document.getElementById('slotStatus').classList.remove('d-none');

  document.getElementById('pos_left').disabled  = !data.left_free;
  document.getElementById('pos_right').disabled = !data.right_free;

  const cur = document.querySelector('[name=binary_position]:checked')?.value;
  if (cur==='left'  && !data.left_free  && data.right_free) document.getElementById('pos_right').checked=true;
  if (cur==='right' && !data.right_free && data.left_free)  document.getElementById('pos_left').checked=true;
  checkPos(document.querySelector('[name=binary_position]:checked')?.value || '');
}

function checkPos(pos) {
  if (!slotData.username) { setHint('positionHint','',null); return; }
  if (!pos)               { setHint('positionHint','Please select a position.',null); return; }
  const free = pos==='left' ? slotData.left_free : slotData.right_free;
  setHint('positionHint',
    (free?'✓ ':'✗ ') + pos.charAt(0).toUpperCase()+pos.slice(1) + ' slot is ' + (free?'available.':'taken.'),
    free);
}

function resetPosBtns() {
  document.getElementById('pos_left').disabled  = false;
  document.getElementById('pos_right').disabled = false;
  setHint('positionHint','',null);
}

// ── Step 2 → 3 ───────────────────────────────────────────────
document.getElementById('toStep3Btn').addEventListener('click', function() {
  const pw  = document.getElementById('password').value;
  const pwc = document.getElementById('password_confirm').value;
  const pos = document.querySelector('[name=binary_position]:checked')?.value;
  const sponsorVal = document.getElementById('sponsor_username').value.trim();

  if (!usernameOk)  { setHint('usernameHint','Please choose a valid, available username.',false); return; }
  if (pw.length < 8){ alert('Password must be at least 8 characters.'); return; }
  if (pw !== pwc)   { setHint('pwMatchHint','Passwords do not match.',false); return; }
  if (!LOCKED_SPONSOR && !sponsorOk) { setHint('sponsorHint','Please enter a valid sponsor.',false); return; }
  if (LOCKED_SPONSOR) sponsorOk = true;
  if (!uplineOk)    { setHint('uplineHint','Please enter a valid upline.',false); return; }
  if (!pos)         { setHint('positionHint','Please select a position.',false); return; }
  const free = pos==='left' ? slotData.left_free : slotData.right_free;
  if (!free)        { setHint('positionHint','Selected position is taken. Choose another.',false); return; }

  document.getElementById('rev_code').textContent     = document.getElementById('validatedCode').value;
  document.getElementById('rev_package').textContent  = codeData.package_name || '—';
  document.getElementById('rev_username').textContent = '@' + document.getElementById('username').value;
  document.getElementById('rev_sponsor').textContent  = '@' + sponsorVal;
  document.getElementById('rev_upline').textContent   = '@' + document.getElementById('upline_username').value;
  document.getElementById('rev_position').textContent = pos.charAt(0).toUpperCase() + pos.slice(1);
  goStep(3);
});

// ── Submit ────────────────────────────────────────────────────
document.getElementById('regForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating account…';
});

// ── If locked sponsor, mark as ok immediately ─────────────────
if (LOCKED_SPONSOR) { sponsorOk = true; }
</script>
<?php if (!$isLoggedIn): ?>
</body>
</html>
<?php else: ?>
<?php require 'views/partials/footer.php'; ?>
<?php endif; ?>
