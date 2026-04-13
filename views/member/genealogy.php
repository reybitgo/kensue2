<?php $pageTitle = $view==='referral' ? 'Referral Network' : 'Binary Tree'; ?>
<?php require 'views/partials/head.php'; ?>
<?php require 'views/partials/sidebar_member.php'; ?>
<div class="main-content">
  <?php require 'views/partials/topbar.php'; ?>
  <div class="page-content">
    <ul class="nav nav-pills mb-3">
      <li class="nav-item"><a class="nav-link <?= $view!=='referral'?'active':'' ?>" href="<?= APP_URL ?>/?page=genealogy&view=binary">🌳 Binary Tree</a></li>
      <li class="nav-item"><a class="nav-link <?= $view==='referral'?'active':'' ?>" href="<?= APP_URL ?>/?page=genealogy&view=referral">👥 Referral Network</a></li>
    </ul>

    <?php if ($view !== 'referral'): ?>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title">🌳 Binary Tree</span>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm" onclick="resetTree()">⟳ Reset</button>
          <button class="btn btn-outline-secondary btn-sm" onclick="zoomTree(-0.2)">−</button>
          <button class="btn btn-outline-secondary btn-sm" onclick="zoomTree(0.2)">+</button>
        </div>
      </div>
      <div id="treeContainer" style="overflow:auto;background:#f4f6fb;min-height:360px;position:relative;cursor:grab;">
        <div id="treeLoading" style="display:flex;align-items:center;justify-content:center;padding:3rem 1rem;color:#6b7a99;gap:.5rem;font-size:.9rem;">
          <div class="spinner-border spinner-border-sm text-primary"></div> Loading tree…
        </div>
        <canvas id="treeCanvas" style="display:none;"></canvas>
      </div>
      <div id="treeTooltip" style="display:none;position:fixed;background:#1a2035;color:#fff;border-radius:10px;padding:10px 14px;font-size:.75rem;pointer-events:none;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.3);min-width:160px;"></div>
      <div class="card-footer d-flex gap-3 flex-wrap" style="font-size:.75rem;color:var(--muted);">
        <span>🟢 Active</span><span>🔴 Suspended</span><span>⚫ Open Slot</span>
        <span class="ms-auto">Tap node to see info</span>
      </div>
    </div>

    <?php else: ?>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title">👥 Referral Network (10 Levels)</span>
        <span class="badge bg-secondary-subtle text-secondary"><?= count($indirect) ?> members</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($indirect)): ?>
          <div class="text-center py-5 text-muted"><div style="font-size:2.5rem;">👥</div><p class="mt-2 mb-0">You haven't referred anyone yet.</p></div>
        <?php else:
          $grouped = [];
          foreach ($indirect as $m) $grouped[$m['level']][] = $m;
          foreach ($grouped as $lvl => $members): ?>
        <div>
          <div class="d-flex align-items-center gap-2 px-3 py-2 cursor-pointer border-bottom"
               style="background:#f8fafd;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);"
               onclick="toggleRef(<?= $lvl ?>)">
            <span>Level <?= $lvl ?></span>
            <span class="badge bg-primary-subtle text-primary"><?= count($members) ?></span>
            <span id="refArrow<?= $lvl ?>" class="ms-auto">▼</span>
          </div>
          <div id="refLevel<?= $lvl ?>">
            <?php foreach ($members as $m): ?>
            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
              <div class="user-avatar"><?= strtoupper(substr($m['username'],0,1)) ?></div>
              <div class="flex-grow-1">
                <div class="fw-600" style="font-size:.825rem;">@<?= e($m['username']) ?><?= $m['full_name'] ? ' — '.e($m['full_name']) : '' ?></div>
                <div class="text-muted" style="font-size:.72rem;"><?= e($m['package_name']??'Member') ?> · Joined <?= fmt_date($m['joined_at']) ?></div>
              </div>
              <span class="badge <?= $m['status']==='active'?'bg-success-subtle text-success':'bg-danger-subtle text-danger' ?>"><?= ucfirst($m['status']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($view !== 'referral'): ?>
<script>
const API_URL = '<?= APP_URL ?>/?page=api_binary_tree&root=<?= Auth::id() ?>';
const canvas = document.getElementById('treeCanvas');
const ctx    = canvas.getContext('2d');
const container = document.getElementById('treeContainer');
const tooltip   = document.getElementById('treeTooltip');
let treeData=null, scale=1, offsetX=0, offsetY=0, nodeMap=[];
const NODE_R=26, V_GAP=90, COLORS={active:'#12a05c',suspended:'#e03434'};

async function loadTree() {
  const loader = document.getElementById('treeLoading');
  try {
    const res = await fetch(API_URL + '&depth=4');
    if (!res.ok) throw new Error('HTTP ' + res.status);
    treeData = await res.json();
    loader.style.display = 'none';
    canvas.style.display = 'block';
    drawTree();
  } catch(e) {
    loader.style.display = 'none';
    canvas.style.display  = 'block';
    // Show error overlay on canvas
    const cw = Math.max(container.clientWidth, 400);
    canvas.width  = cw;
    canvas.height = 160;
    ctx.clearRect(0, 0, cw, 160);
    ctx.fillStyle = '#6b7a99';
    ctx.font      = '14px Plus Jakarta Sans, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('⚠ Could not load tree. ' + (e.message || ''), cw / 2, 80);
  }
}

function calcLayout(node, x, y, spread) {
  if (!node) return;
  node._x = x; node._y = y;
  if (node.left)  calcLayout(node.left,  x - spread/4, y + V_GAP, spread/2);
  if (node.right) calcLayout(node.right, x + spread/4, y + V_GAP, spread/2);
}

function drawTree() {
  if (!treeData) return;
  const cw = Math.max(container.clientWidth, 600);
  const ch = Math.max(container.clientHeight - 20, 400);
  canvas.width = cw; canvas.height = ch;
  ctx.clearRect(0,0,cw,ch);
  nodeMap = [];
  calcLayout(treeData, cw/2 + offsetX, 60 + offsetY, cw * 0.8);
  ctx.save(); ctx.scale(scale, scale);
  drawNode(treeData);
  ctx.restore();
}

function drawNode(node) {
  if (!node) return;
  [['left','left'],['right','right']].forEach(([side]) => {
    const child = node[side];
    const cx = child ? child._x : node._x + (side==='left'?-V_GAP*.7:V_GAP*.7);
    const cy = child ? child._y : node._y + V_GAP;
    ctx.beginPath(); ctx.moveTo(node._x, node._y + NODE_R);
    ctx.lineTo(cx, cy - NODE_R);
    ctx.strokeStyle = '#dde3ef'; ctx.lineWidth = 2; ctx.stroke();
    if (!child) {
      ctx.beginPath(); ctx.arc(cx, cy, NODE_R-4, 0, Math.PI*2);
      ctx.fillStyle = '#f4f6fb'; ctx.fill();
      ctx.strokeStyle = '#dde3ef'; ctx.lineWidth = 2;
      ctx.setLineDash([4,3]); ctx.stroke(); ctx.setLineDash([]);
      ctx.fillStyle = '#9ca3af'; ctx.font = '10px Plus Jakarta Sans';
      ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      ctx.fillText(side==='left'?'↙':'↘', cx, cy-5);
      ctx.font = '8px Plus Jakarta Sans'; ctx.fillText('empty', cx, cy+7);
    }
  });
  const color = node.status==='active' ? COLORS.active : COLORS.suspended;
  ctx.shadowColor='rgba(0,0,0,.12)'; ctx.shadowBlur=10; ctx.shadowOffsetY=3;
  ctx.beginPath(); ctx.arc(node._x, node._y, NODE_R, 0, Math.PI*2);
  ctx.fillStyle = color; ctx.fill();
  ctx.shadowBlur=0; ctx.shadowOffsetY=0;
  ctx.beginPath(); ctx.arc(node._x, node._y, NODE_R+2, 0, Math.PI*2);
  ctx.strokeStyle='#fff'; ctx.lineWidth=3; ctx.stroke();
  ctx.fillStyle='#fff'; ctx.font='bold 9px Plus Jakarta Sans';
  ctx.textAlign='center'; ctx.textBaseline='middle';
  const label = node.username.length>8 ? node.username.slice(0,7)+'…' : node.username;
  ctx.fillText(label, node._x, node._y);
  ctx.fillStyle='#6b7a99'; ctx.font='8px Plus Jakarta Sans';
  ctx.fillText('L:'+node.left_count+' R:'+node.right_count, node._x, node._y+NODE_R+12);
  nodeMap.push({x:node._x, y:node._y, r:NODE_R+4, node});
  if (node.left)  drawNode(node.left);
  if (node.right) drawNode(node.right);
}

canvas.addEventListener('mousemove', function(e) {
  const rect = canvas.getBoundingClientRect();
  const mx = (e.clientX-rect.left)/scale, my = (e.clientY-rect.top)/scale;
  const hit = nodeMap.find(n => Math.hypot(mx-n.x, my-n.y) <= n.r);
  if (hit) {
    canvas.style.cursor='pointer';
    tooltip.style.display='block';
    tooltip.style.left=(e.clientX+14)+'px'; tooltip.style.top=(e.clientY-10)+'px';
    tooltip.innerHTML=`<div style="font-weight:700;margin-bottom:4px;">@${hit.node.username}</div><div style="color:rgba(255,255,255,.65);font-size:.7rem;">${hit.node.package||'—'} · ${hit.node.joined||'—'}<br>Left: ${hit.node.left_count} · Right: ${hit.node.right_count}<br>Status: <span style="color:${hit.node.status==='active'?'#4ade80':'#f87171'}">${hit.node.status}</span></div>`;
  } else { canvas.style.cursor='default'; tooltip.style.display='none'; }
});
canvas.addEventListener('mouseleave', () => { tooltip.style.display='none'; });
function zoomTree(d) { scale=Math.max(.4,Math.min(2,scale+d)); drawTree(); }
function resetTree() { scale=1; offsetX=0; offsetY=0; loadTree(); }
window.addEventListener('resize', drawTree);
loadTree();
</script>
<?php endif; ?>

<script>
function toggleRef(lvl) {
  const el    = document.getElementById('refLevel' + lvl);
  const arrow = document.getElementById('refArrow' + lvl);
  if (!el) return;
  const hidden = el.style.display === 'none';
  el.style.display    = hidden ? 'block' : 'none';
  arrow.textContent   = hidden ? '▼' : '▶';
}
</script>
<?php require 'views/partials/footer.php'; ?>
