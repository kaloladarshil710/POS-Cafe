<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

// Fetch every active table with aggregated pending bill + order count
$tables_q = mysqli_query($conn, "
    SELECT
        rt.*,
        COALESCE(SUM(CASE WHEN o.status NOT IN ('paid') THEN o.total_amount END), 0) AS pending_total,
        COUNT(CASE WHEN o.status NOT IN ('paid') THEN 1 END)                          AS order_count,
        MIN(CASE WHEN o.status NOT IN ('paid') THEN o.id END)                         AS first_order_id
    FROM restaurant_tables rt
    LEFT JOIN orders o ON o.table_id = rt.id AND o.status NOT IN ('paid')
    WHERE rt.active = 'yes'
    GROUP BY rt.id
    ORDER BY rt.id ASC
");

$user_name      = htmlspecialchars($_SESSION['user_name']);
$free_count     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM restaurant_tables WHERE active='yes' AND status='free'"))['t'];
$occupied_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM restaurant_tables WHERE active='yes' AND status='occupied'"))['t'];
$kitchen_active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM orders WHERE status IN ('to_cook','preparing')"))['t'];
$today_sales    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) total FROM orders WHERE status='paid' AND DATE(created_at)=CURDATE()"))['total'];
$server_now     = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS Terminal — Floor View</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#0A0A0F; --surface:#12121A; --surface2:#1A1A26;
  --border:rgba(255,255,255,0.07); --border2:rgba(255,255,255,0.12);
  --primary:#F97316; --primary-dim:rgba(249,115,22,0.15);
  --green:#22C55E; --green-dim:rgba(34,197,94,0.12);
  --red:#EF4444; --red-dim:rgba(239,68,68,0.12);
  --amber:#F59E0B; --amber-dim:rgba(245,158,11,0.12);
  --text:#F1F1F5; --text2:#9999B3; --text3:#555570;
}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;}

/* TOP BAR */
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;backdrop-filter:blur(20px);}
.topbar-left{display:flex;align-items:center;gap:20px;}
.logo{font-size:18px;font-weight:800;}.logo span{color:var(--primary);}
.divider-v{width:1px;height:24px;background:var(--border2);}
.session-badge{display:flex;align-items:center;gap:7px;background:var(--green-dim);border:1px solid rgba(34,197,94,0.2);color:var(--green);padding:5px 12px;border-radius:999px;font-size:12px;font-weight:700;}
.session-dot{width:7px;height:7px;background:var(--green);border-radius:50%;animation:blink 2s infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.3;}}
.topbar-right{display:flex;align-items:center;gap:8px;}
.nav-btn{text-decoration:none;padding:8px 16px;border-radius:10px;font-size:13px;font-weight:600;color:var(--text2);display:flex;align-items:center;gap:6px;border:none;background:none;cursor:pointer;transition:0.15s;}
.nav-btn:hover{background:var(--surface2);color:var(--text);}
.nav-btn .badge{background:var(--primary);color:white;padding:1px 7px;border-radius:999px;font-size:10px;font-weight:800;}
.user-chip{display:flex;align-items:center;gap:8px;padding:6px 12px;background:var(--surface2);border:1px solid var(--border);border-radius:999px;font-size:13px;font-weight:600;}
.avatar{width:26px;height:26px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:white;}
.icon-btn{text-decoration:none;width:36px;height:36px;background:var(--surface2);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;transition:0.15s;}
.icon-btn:hover{background:var(--border2);}
.logout-btn{color:var(--red);background:var(--red-dim);border-color:rgba(239,68,68,0.2);}
.logout-btn:hover{background:var(--red);color:white;}

/* STATS BAR */
.stats-bar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 28px;height:52px;display:flex;align-items:center;gap:24px;overflow-x:auto;scrollbar-width:none;}
.stats-bar::-webkit-scrollbar{display:none;}
.stat{display:flex;align-items:center;gap:10px;white-space:nowrap;}
.stat-label{font-size:12px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;}
.stat-val{font-size:15px;font-weight:800;}
.stat-val.green{color:var(--green);}.stat-val.red{color:var(--red);}.stat-val.amber{color:var(--amber);}.stat-val.orange{color:var(--primary);}
.stats-sep{width:1px;height:24px;background:var(--border);flex-shrink:0;}

/* MAIN */
.main{flex:1;padding:28px;}
.floor-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:10px;}
.floor-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text3);}
.floor-name{display:flex;align-items:center;gap:8px;background:var(--surface2);border:1px solid var(--border);padding:6px 14px;border-radius:999px;font-size:13px;font-weight:700;}

/* TABLE GRID */
.table-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
  gap:18px;
  align-items:stretch;
}

/* TABLE CARD */
.table-card{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:20px;
  padding:18px;
  color:var(--text);
  display:flex;
  flex-direction:column;
  min-height:290px;
  transition:all 0.2s;
  position:relative;
  overflow:hidden;
}
.table-card::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 50% 0%,var(--primary-dim),transparent 70%);opacity:0;transition:opacity 0.3s;pointer-events:none;}
.table-card:hover{border-color:rgba(249,115,22,0.35);transform:translateY(-3px);box-shadow:0 16px 40px rgba(249,115,22,0.1);}
.table-card:hover::before{opacity:1;}
.table-card.occupied{border-color:rgba(239,68,68,0.22);background:rgba(239,68,68,0.03);}
.table-card.occupied::before{background:radial-gradient(circle at 50% 0%,rgba(239,68,68,0.1),transparent 70%);}
.table-card.occupied:hover{border-color:rgba(239,68,68,0.45);box-shadow:0 16px 40px rgba(239,68,68,0.1);}

/* Card header */
.tc-head{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:14px;
}
.tc-num{font-size:30px;font-weight:800;letter-spacing:-1px;line-height:1;}
.tc-status{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap;}
.status-free{background:var(--green-dim);color:var(--green);border:1px solid rgba(34,197,94,0.25);}
.status-occupied{background:var(--red-dim);color:var(--red);border:1px solid rgba(239,68,68,0.25);}

/* Card mid */
.tc-mid{
  display:flex;
  align-items:center;
  gap:12px;
  margin-bottom:14px;
  min-height:54px;
}
.tc-icon{font-size:28px;line-height:1;}
.tc-name{font-size:14px;font-weight:700;margin-bottom:2px;}
.tc-seats{font-size:12px;color:var(--text3);}

/* ── LIVE TIMER ── */
.tc-timer{
  display:flex;
  align-items:center;
  gap:8px;
  background:rgba(239,68,68,0.08);
  border:1px solid rgba(239,68,68,0.16);
  border-radius:12px;
  padding:10px 12px;
  margin-bottom:10px;
}
.tc-timer-label{font-size:11px;color:var(--text3);font-weight:600;}
.tc-timer-val{font-size:14px;font-weight:800;color:var(--red);font-variant-numeric:tabular-nums;margin-left:auto;letter-spacing:-0.3px;}
.tc-timer-val.warn{color:var(--amber);}
.tc-timer-val.ok{color:#f87171;}

/* ── PENDING AMOUNT ── */
.tc-amount{
  background:rgba(245,158,11,0.10);
  border:1px solid rgba(245,158,11,0.18);
  border-radius:12px;
  padding:10px 12px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:12px;
}
.tc-amount-label{font-size:11px;color:var(--text3);font-weight:600;}
.tc-amount-val{font-size:17px;font-weight:800;color:var(--amber);}

/* ── ACTION BUTTONS ── */
.tc-actions{
  display:flex;
  flex-direction:column;
  gap:8px;
  margin-top:auto;
}

.tc-open-btn{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:7px;
  background:var(--surface2);
  border:1px solid var(--border2);
  border-radius:12px;
  padding:12px;
  font-size:14px;
  font-weight:700;
  text-decoration:none;
  color:var(--text);
  transition:all 0.15s;
  min-height:48px;
}
.table-card:hover .tc-open-btn{background:rgba(249,115,22,0.15);border-color:rgba(249,115,22,0.4);color:var(--primary);}
.table-card.occupied:hover .tc-open-btn{background:rgba(239,68,68,0.15);border-color:rgba(239,68,68,0.4);color:var(--red);}

/* PAY button */
.tc-pay-btn{display:flex;align-items:center;justify-content:center;gap:7px;background:linear-gradient(135deg,#F97316,#EA580C);border:none;border-radius:12px;padding:12px;font-size:13px;font-weight:800;text-decoration:none;color:white;cursor:pointer;transition:all 0.15s;font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 4px 14px rgba(249,115,22,0.35);}
.tc-pay-btn:hover{background:linear-gradient(135deg,#EA580C,#C2410C);transform:translateY(-1px);box-shadow:0 8px 20px rgba(249,115,22,0.45);}

/* FREE TABLE button */
.tc-free-btn{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:7px;
  background:rgba(239,68,68,0.06);
  border:1px solid rgba(239,68,68,0.18);
  border-radius:12px;
  padding:10px;
  font-size:13px;
  font-weight:700;
  text-decoration:none;
  color:var(--red);
  cursor:pointer;
  transition:all 0.15s;
  font-family:'Plus Jakarta Sans',sans-serif;
  width:100%;
  min-height:44px;
}
.tc-free-btn:hover{background:var(--red);color:white;border-color:var(--red);}

/* order count badge */
.order-badge{
  position:absolute;
  top:12px;
  right:22px;
  transform:translateY(-8px);
  background:var(--amber);
  color:#000;
  min-width:22px;
  height:22px;
  padding:0 6px;
  border-radius:999px;
  font-size:10px;
  font-weight:800;
  display:flex;
  align-items:center;
  justify-content:center;
  box-shadow:0 4px 10px rgba(245,158,11,0.35);
  z-index:3;
}
.empty{text-align:center;padding:80px 20px;color:var(--text3);}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.72);backdrop-filter:blur(8px);z-index:200;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:#1A1A26;border:1px solid rgba(255,255,255,0.1);border-radius:24px;padding:32px;width:360px;max-width:90vw;text-align:center;animation:mIn 0.22s ease;}
@keyframes mIn{from{transform:scale(0.9);opacity:0;}to{transform:scale(1);opacity:1;}}
.modal-icon{font-size:48px;margin-bottom:14px;}
.modal-title{font-size:19px;font-weight:800;margin-bottom:8px;}
.modal-sub{font-size:14px;color:var(--text2);margin-bottom:24px;line-height:1.6;}
.modal-table-name{color:var(--primary);font-weight:800;}
.modal-btns{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.modal-cancel{background:var(--surface2);border:1px solid var(--border2);border-radius:12px;padding:13px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;color:var(--text);cursor:pointer;transition:0.15s;}
.modal-cancel:hover{background:var(--border2);}
.modal-confirm{background:var(--red);border:none;border-radius:12px;padding:13px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;color:white;cursor:pointer;transition:0.15s;text-decoration:none;display:flex;align-items:center;justify-content:center;}
.modal-confirm:hover{background:#DC2626;}

@media(max-width:600px){.main,.topbar,.stats-bar{padding-left:16px;padding-right:16px;}}
</style>
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-left">
    <div class="logo">POS <span>Cafe</span></div>
    <div class="divider-v"></div>
    <div class="session-badge"><div class="session-dot"></div>Session Active</div>
  </div>
  <div class="topbar-right">
    <a class="nav-btn" href="../kitchen/kitchen.php">
      👨‍🍳 Kitchen
      <?php if($kitchen_active>0): ?><span class="badge"><?php echo $kitchen_active; ?></span><?php endif; ?>
    </a>
    <?php if ($_SESSION['user_role'] === 'admin'): ?>
      <a class="icon-btn" href="../admin/dashboard.php" title="Admin Panel">⚙️</a>
    <?php endif; ?>
    <div class="user-chip">
      <div class="avatar"><?php echo strtoupper(substr($_SESSION['user_name'],0,1)); ?></div>
      <?php echo $user_name; ?>
    </div>
    <a class="icon-btn logout-btn" href="../auth/logout.php" title="Logout">🔓</a>
  </div>
</div>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="stat"><span>🟢</span><span class="stat-label">Free</span><span class="stat-val green"><?php echo $free_count; ?></span></div>
  <div class="stats-sep"></div>
  <div class="stat"><span>🔴</span><span class="stat-label">Occupied</span><span class="stat-val red"><?php echo $occupied_count; ?></span></div>
  <div class="stats-sep"></div>
  <div class="stat"><span>🔥</span><span class="stat-label">In Kitchen</span><span class="stat-val amber"><?php echo $kitchen_active; ?></span></div>
  <div class="stats-sep"></div>
  <div class="stat"><span>💰</span><span class="stat-label">Today's Sales</span><span class="stat-val orange">₹<?php echo number_format($today_sales,2); ?></span></div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="floor-header">
    <div class="floor-title">Floor Plan</div>
    <div class="floor-name">🏢 Ground Floor</div>
  </div>

  <?php if (mysqli_num_rows($tables_q) > 0): ?>
  <div class="table-grid">
    <?php while ($row = mysqli_fetch_assoc($tables_q)):
      $occ         = ($row['status'] === 'occupied');
      $num         = preg_replace('/[^0-9]/', '', $row['table_number']);
      $pad_num     = str_pad($num ?: '?', 2, '0', STR_PAD_LEFT);
      $pending     = floatval($row['pending_total']);
      $order_count = intval($row['order_count']);

      // Elapsed seconds for timer (use occupied_since, fallback to earliest order)
      $elapsed = 0;
      if ($occ) {
          if (!empty($row['occupied_since'])) {
              $elapsed = max(0, $server_now - strtotime($row['occupied_since']));
          } else {
              $fb = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT MIN(created_at) t FROM orders WHERE table_id={$row['id']} AND status NOT IN ('paid')"));
              $elapsed = $fb && $fb['t'] ? max(0, $server_now - strtotime($fb['t'])) : 0;
          }
      }
    ?>

    <div class="table-card <?php echo $occ ? 'occupied' : ''; ?>">

      <?php if ($occ && $order_count > 0): ?>
        <div class="order-badge"><?php echo $order_count; ?></div>
      <?php endif; ?>

      <!-- Number + status pill -->
      <div class="tc-head">
        <div class="tc-num"><?php echo $pad_num; ?></div>
        <div class="tc-status <?php echo $occ ? 'status-occupied' : 'status-free'; ?>">
          <?php echo $occ ? '● Occupied' : '● Free'; ?>
        </div>
      </div>

      <!-- Icon + name + seats -->
      <div class="tc-mid">
        <div class="tc-icon"><?php echo $occ ? '🍽️' : '🪑'; ?></div>
        <div>
          <div class="tc-name"><?php echo htmlspecialchars($row['table_number']); ?></div>
          <div class="tc-seats">👥 <?php echo $row['seats']; ?> seats</div>
        </div>
      </div>

      <?php if ($occ): ?>

        <!-- LIVE OCCUPANCY TIMER -->
        <!-- <div class="tc-timer">
          <span>⏱️</span>
          <span class="tc-timer-label">Occupied for</span>
          <span class="tc-timer-val" id="tmr-<?php echo $row['id']; ?>" data-secs="<?php echo $elapsed; ?>">
            <?php
              // $h = floor($elapsed/3600); $m = floor(($elapsed%3600)/60); $s = $elapsed%60;
              // if ($h>0)     echo "{$h}h ".sprintf('%02d',$m)."m";
              // elseif ($m>0) echo "{$m}m ".sprintf('%02d',$s)."s";
              // else          echo "{$s}s";
            ?>
          </span>
        </div> -->

        <?php if ($pending > 0): ?>
        <!-- PENDING BILL TOTAL -->
        <div class="tc-amount">
          <span class="tc-amount-label">
            💰 Pending Bill<?php if($order_count>1): ?> (<?php echo $order_count; ?> orders)<?php endif; ?>
          </span>
          <span class="tc-amount-val">₹<?php echo number_format($pending,2); ?></span>
        </div>
        <?php endif; ?>

        <!-- ACTIONS: Add Items | Pay | Free Table -->
        <div class="tc-actions">
          <a class="tc-open-btn" href="order.php?table_id=<?php echo $row['id']; ?>">
            ➕ Add Items →
          </a>

          <?php if ($pending > 0): ?>
          <a class="tc-pay-btn" href="table_bill.php?table_id=<?php echo $row['id']; ?>">
            💳 Pay ₹<?php echo number_format($pending,2); ?>
          </a>
          <?php endif; ?>

          <!-- FREE TABLE — visible to ALL roles (admin + staff) -->
          <button class="tc-free-btn"
                  onclick="confirmFree(<?php echo $row['id']; ?>, '<?php echo addslashes($row['table_number']); ?>')">
            🔓 Mark as Free
          </button>
        </div>

      <?php else: ?>

        <!-- FREE TABLE: just Open -->
        <div class="tc-actions">
          <a class="tc-open-btn" href="order.php?table_id=<?php echo $row['id']; ?>">
            🍴 Open Table →
          </a>
        </div>

      <?php endif; ?>
    </div>
    <?php endwhile; ?>
  </div>

  <?php else: ?>
  <div class="empty">
    <div style="font-size:64px;margin-bottom:16px;opacity:0.4;">🪑</div>
    <p style="font-size:16px;font-weight:700;margin-bottom:8px;">No tables set up</p>
    <a href="../admin/tables.php" style="color:var(--primary);text-decoration:none;font-size:14px;">Configure tables in Admin →</a>
  </div>
  <?php endif; ?>
</div>

<!-- FREE TABLE CONFIRM MODAL -->
<div class="modal-overlay" id="freeModal">
  <div class="modal">
    <div class="modal-icon">🔓</div>
    <div class="modal-title">Mark Table as Free?</div>
    <div class="modal-sub">
      You are about to free <span class="modal-table-name" id="modalName">this table</span>.<br><br>
      ⚠️ <strong>Ensure payment is collected before proceeding.</strong>
    </div>
    <div class="modal-btns">
      <button class="modal-cancel" onclick="closeModal()">✕ Cancel</button>
      <a class="modal-confirm" id="freeLink" href="#">🔓 Yes, Free It</a>
    </div>
  </div>
</div>

<script>
// ── LIVE TIMERS: tick every second ──
function fmtSecs(t) {
  const h = Math.floor(t/3600), m = Math.floor((t%3600)/60), s = t%60;
  if (h>0) return h+'h '+String(m).padStart(2,'0')+'m';
  if (m>0) return m+'m '+String(s).padStart(2,'0')+'s';
  return s+'s';
}
document.querySelectorAll('[data-secs]').forEach(el => {
  let t = parseInt(el.dataset.secs, 10);
  function tick() {
    el.textContent = fmtSecs(t);
    // colour coding: <30min=red, 30-60min=amber, >60min=bright red pulse
    if (t >= 3600)      { el.style.color='#EF4444'; el.style.fontWeight='900'; }
    else if (t >= 1800) { el.style.color='#F59E0B'; }
    else                { el.style.color='#f87171'; }
    t++;
  }
  tick();
  setInterval(tick, 1000);
});

// ── FREE TABLE MODAL ──
function confirmFree(id, name) {
  document.getElementById('modalName').textContent = name;
  document.getElementById('freeLink').href = 'free_table.php?table_id=' + id;
  document.getElementById('freeModal').classList.add('open');
}
function closeModal() { document.getElementById('freeModal').classList.remove('open'); }
document.getElementById('freeModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal(); });
</script>
</body>
</html>