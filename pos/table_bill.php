<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_GET['table_id']))    { header("Location: index.php"); exit(); }

$table_id = intval($_GET['table_id']);

$table_q = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE id=$table_id AND active='yes'");
$table   = mysqli_fetch_assoc($table_q);
if (!$table) { header("Location: index.php"); exit(); }

$orders_q   = mysqli_query($conn, "SELECT * FROM orders WHERE table_id=$table_id AND status NOT IN ('paid') ORDER BY created_at ASC");
$orders_arr = [];
while ($o = mysqli_fetch_assoc($orders_q)) $orders_arr[] = $o;

if (empty($orders_arr)) {
    header("Location: index.php?msg=no_pending");
    exit();
}

$all_items   = [];
$grand_total = 0;
$order_ids   = [];
foreach ($orders_arr as $ord) {
    $order_ids[] = $ord['id'];
    $items_q = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id={$ord['id']}");
    while ($item = mysqli_fetch_assoc($items_q)) {
        $all_items[]  = $item;
        $grand_total += floatval($item['subtotal']);
    }
}

$methods_q = mysqli_query($conn, "SELECT * FROM payment_methods WHERE is_enabled='yes' ORDER BY id ASC");
$methods   = [];
while ($m = mysqli_fetch_assoc($methods_q)) $methods[] = $m;

$upi_q  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT upi_id FROM payment_methods WHERE method_name='UPI' AND is_enabled='yes' LIMIT 1"));
$upi_id = $upi_q ? ($upi_q['upi_id'] ?? '') : '';

$server_now = time();
$elapsed    = 0;
if (!empty($table['occupied_since'])) {
    $elapsed = max(0, $server_now - strtotime($table['occupied_since']));
} else {
    $fb = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT MIN(created_at) t FROM orders WHERE table_id=$table_id AND status NOT IN ('paid')"));
    $elapsed = ($fb && $fb['t']) ? max(0, $server_now - strtotime($fb['t'])) : 0;
}
$h = floor($elapsed/3600); $m = floor(($elapsed%3600)/60);
$time_str = $h>0 ? "{$h}h {$m}m" : ($m>0 ? "{$m}m" : "<1m");

// ✅ SET YOUR DIGITAL PAYMENT PIN HERE
$digital_pin = "1234";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Table Bill — <?php echo htmlspecialchars($table['table_number']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#F0F2F5;--surface:#FFF;--surface2:#F8FAFC;
  --border:#E4E7EC;--text:#101828;--text2:#667085;--text3:#98A2B3;
  --primary:#F97316;--primary-dark:#EA6C0A;--primary-dim:rgba(249,115,22,0.08);
  --green:#12B76A;--red:#EF4444;--amber:#F59E0B;
}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;}

.topbar{background:var(--surface);border-bottom:1px solid var(--border);height:56px;padding:0 24px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 3px rgba(0,0,0,0.06);position:sticky;top:0;z-index:50;}
.back-btn{display:flex;align-items:center;gap:6px;text-decoration:none;background:var(--surface2);border:1px solid var(--border);padding:7px 14px;border-radius:10px;font-size:13px;font-weight:700;color:var(--text);transition:0.15s;}
.back-btn:hover{background:var(--border);}
.topbar-title{font-size:16px;font-weight:800;}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text3);}
.breadcrumb .active{color:var(--primary);font-weight:700;}

.page{display:grid;grid-template-columns:1fr 390px;gap:24px;padding:28px;max-width:1100px;margin:0 auto;width:100%;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.card-title{font-size:15px;font-weight:800;margin-bottom:18px;display:flex;align-items:center;gap:8px;color:var(--text);}

.table-banner{background:linear-gradient(135deg,#1A1A26 0%,#0D0D14 100%);border-radius:16px;padding:18px 20px;color:white;display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.tb-left h2{font-size:22px;font-weight:800;margin-bottom:3px;}
.tb-left p{font-size:13px;color:#9999B3;}
.tb-right{text-align:right;}
.tb-timer{font-size:13px;color:#F59E0B;font-weight:700;display:flex;align-items:center;gap:6px;justify-content:flex-end;margin-bottom:3px;}
.tb-orders{font-size:12px;color:#555570;}

table{width:100%;border-collapse:collapse;}
thead th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);padding:10px 12px;text-align:left;border-bottom:2px solid var(--border);background:var(--surface2);}
tbody td{padding:12px 12px;border-bottom:1px solid #F2F4F7;font-size:14px;}
tbody tr:last-child td{border-bottom:none;}
.grand-row td{font-weight:800;font-size:15px;background:var(--primary-dim);color:var(--primary);}
.order-section{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);padding:8px 12px;background:var(--surface2);border-bottom:1px solid var(--border);}

.pay-panel{display:flex;flex-direction:column;gap:16px;}
.amount-card{background:linear-gradient(135deg,#F97316 0%,#EA580C 100%);border-radius:20px;padding:24px;color:white;text-align:center;position:relative;overflow:hidden;}
.amount-card::before{content:'';position:absolute;top:-40px;right:-40px;width:160px;height:160px;background:rgba(255,255,255,0.08);border-radius:50%;}
.amount-card::after{content:'';position:absolute;bottom:-30px;left:-20px;width:120px;height:120px;background:rgba(0,0,0,0.08);border-radius:50%;}
.amount-label{font-size:12px;opacity:0.85;margin-bottom:6px;font-weight:600;position:relative;z-index:1;text-transform:uppercase;letter-spacing:0.5px;}
.amount-value{font-size:46px;font-weight:800;letter-spacing:-1px;position:relative;z-index:1;}
.amount-sub{font-size:12px;opacity:0.7;margin-top:6px;position:relative;z-index:1;}

.methods-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:20px;}
.methods-label{font-size:13px;font-weight:800;margin-bottom:14px;}
.method-opt{display:flex;align-items:center;gap:14px;padding:13px 16px;border:2px solid var(--border);border-radius:14px;cursor:pointer;transition:0.18s;margin-bottom:9px;background:var(--surface2);}
.method-opt:last-child{margin-bottom:0;}
.method-opt:hover{border-color:var(--primary);background:var(--primary-dim);}
.method-opt.selected{border-color:var(--primary);background:var(--primary-dim);box-shadow:0 0 0 3px rgba(249,115,22,0.1);}
.m-icon{width:44px;height:44px;background:var(--surface);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.m-info{flex:1;}
.m-name{font-size:15px;font-weight:700;}
.m-desc{font-size:12px;color:var(--text3);margin-top:2px;}
.m-radio{width:20px;height:20px;border-radius:50%;border:2px solid var(--border);flex-shrink:0;transition:0.15s;display:flex;align-items:center;justify-content:center;}
.method-opt.selected .m-radio{background:var(--primary);border-color:var(--primary);}
.method-opt.selected .m-radio::after{content:'';width:8px;height:8px;background:white;border-radius:50%;}

.pay-btn{width:100%;padding:16px;background:var(--primary);color:white;border:none;border-radius:14px;font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:800;cursor:pointer;transition:0.18s;}
.pay-btn:hover{background:var(--primary-dark);transform:translateY(-2px);box-shadow:0 10px 28px rgba(249,115,22,0.3);}

/* Modals */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:999;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:white;border-radius:28px;padding:32px;width:380px;max-width:90vw;text-align:center;animation:mIn 0.25s ease;}
@keyframes mIn{from{transform:scale(0.92);opacity:0;}to{transform:scale(1);opacity:1;}}
.modal-title{font-size:20px;font-weight:800;margin-bottom:4px;}
.modal-sub{font-size:14px;color:var(--text3);margin-bottom:22px;}
.modal-note{font-size:12px;color:var(--text3);background:var(--surface2);padding:10px 14px;border-radius:10px;line-height:1.5;margin-bottom:22px;}
.modal-btns{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.btn-cancel{background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:13px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;}
.btn-cancel:hover{background:var(--border);}
.btn-confirm{background:var(--green);border:none;border-radius:12px;padding:13px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;color:white;cursor:pointer;width:100%;}
.btn-confirm:hover{background:#0DA863;}

/* Amount box inside modals */
.confirm-amount-box{background:#F8FAFC;border:1px solid #E4E7EC;border-radius:16px;padding:20px;margin-bottom:20px;}
.confirm-amount-label{font-size:13px;color:#667085;margin-bottom:6px;}
.confirm-amount-value{font-size:38px;font-weight:800;color:#F97316;}
.confirm-amount-sub{font-size:13px;color:#667085;margin-top:6px;}

/* PIN pad */
.pin-display{display:flex;justify-content:center;gap:12px;margin-bottom:24px;}
.pin-dot{width:16px;height:16px;border-radius:50%;border:2px solid var(--border);background:transparent;transition:0.2s;}
.pin-dot.filled{background:var(--primary);border-color:var(--primary);}
.pin-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;}
.pin-key{background:var(--surface2);border:1px solid var(--border);border-radius:14px;padding:16px;font-size:20px;font-weight:700;cursor:pointer;transition:0.15s;font-family:'Plus Jakarta Sans',sans-serif;}
.pin-key:hover{background:var(--primary-dim);border-color:var(--primary);color:var(--primary);}
.pin-key:active{transform:scale(0.95);}
.pin-key.del{font-size:16px;color:var(--red);}
.pin-error{color:var(--red);font-size:13px;font-weight:600;margin-bottom:12px;min-height:20px;}

/* QR */
.qr-wrap{width:210px;height:210px;margin:0 auto 16px;border:3px solid var(--border);border-radius:18px;overflow:hidden;background:#f9f9f9;display:flex;align-items:center;justify-content:center;}
.qr-wrap img{width:100%;height:100%;}
.qr-upi{font-size:14px;font-weight:700;color:var(--primary);margin-bottom:6px;}
.qr-amount{font-size:28px;font-weight:800;margin-bottom:22px;}

@media(max-width:800px){.page{grid-template-columns:1fr;padding:16px;}}
</style>
</head>
<body>

<div class="topbar">
  <a class="back-btn" href="index.php">← Floor View</a>
  <div class="topbar-title">💳 Table Bill</div>
  <div class="breadcrumb">
    <span>Tables</span><span>›</span>
    <span class="active"><?php echo htmlspecialchars($table['table_number']); ?> — Pay</span>
  </div>
</div>

<div class="page">

  <!-- LEFT -->
  <div>
    <div class="table-banner">
      <div class="tb-left">
        <h2>🪑 <?php echo htmlspecialchars($table['table_number']); ?></h2>
        <p>👥 <?php echo $table['seats']; ?> seats &nbsp;·&nbsp; Combined bill for all orders</p>
      </div>
      <div class="tb-right">
        <div class="tb-timer">⏱️ <?php echo $time_str; ?> occupied</div>
        <div class="tb-orders"><?php echo count($orders_arr); ?> order<?php echo count($orders_arr)>1?'s':''; ?> pending</div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">📋 Combined Order Items</div>
      <table>
        <thead>
          <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
          <?php
          $current_order = null;
          foreach ($all_items as $item):
            if (count($orders_arr) > 1 && $item['order_id'] !== $current_order):
              $current_order = $item['order_id'];
              $ord_obj = array_filter($orders_arr, fn($o)=>$o['id']==$current_order);
              $ord_obj = reset($ord_obj);
          ?>
          <tr>
            <td colspan="4" style="padding:0;">
              <div class="order-section">
                Order #<?php echo htmlspecialchars($ord_obj['order_number']); ?>
                — <?php echo date("h:i A", strtotime($ord_obj['created_at'])); ?>
              </div>
            </td>
          </tr>
          <?php endif; ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
            <td><?php echo $item['quantity']; ?></td>
            <td>₹<?php echo number_format($item['price'],2); ?></td>
            <td><strong>₹<?php echo number_format($item['subtotal'],2); ?></strong></td>
          </tr>
          <?php endforeach; ?>
          <tr class="grand-row">
            <td colspan="3"><strong>Grand Total</strong></td>
            <td><strong>₹<?php echo number_format($grand_total,2); ?></strong></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="pay-panel">
    <div class="amount-card">
      <div class="amount-label">Total to Collect</div>
      <div class="amount-value">₹<?php echo number_format($grand_total,2); ?></div>
      <div class="amount-sub">
        <?php echo htmlspecialchars($table['table_number']); ?>
        &nbsp;·&nbsp; <?php echo count($orders_arr); ?> order<?php echo count($orders_arr)>1?'s':''; ?>
      </div>
    </div>

    <div class="methods-card">
      <div class="methods-label">Select Payment Method</div>
      <?php
      $m_meta = [
        'Cash'    => ['icon'=>'💵', 'desc'=>'Collect cash from customer'],
        'Digital' => ['icon'=>'💳', 'desc'=>'Card / Bank transfer / NetBanking'],
        'UPI'     => ['icon'=>'📱', 'desc'=>'Show QR code for UPI scan'],
      ];
      foreach ($methods as $idx => $pm):
        $meta = $m_meta[$pm['method_name']] ?? ['icon'=>'💰','desc'=>''];
      ?>
      <div class="method-opt <?php echo $idx===0?'selected':''; ?>"
           id="mopt-<?php echo $pm['id']; ?>"
           onclick="selMethod('<?php echo addslashes($pm['method_name']); ?>', <?php echo $pm['id']; ?>)">
        <div class="m-icon"><?php echo $meta['icon']; ?></div>
        <div class="m-info">
          <div class="m-name"><?php echo htmlspecialchars($pm['method_name']); ?></div>
          <div class="m-desc"><?php echo $meta['desc']; ?></div>
        </div>
        <div class="m-radio" id="mrad-<?php echo $pm['id']; ?>"><?php echo $idx===0?'<div style="width:8px;height:8px;background:white;border-radius:50%;"></div>':''; ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <button class="pay-btn" id="payBtn" onclick="doPayment()">
      💳 Pay with <?php echo $methods[0]['method_name'] ?? 'Now'; ?> — ₹<?php echo number_format($grand_total,2); ?>
    </button>
  </div>
</div>

<!-- Hidden form -->
<form action="process_table_bill.php" method="POST" id="payForm">
  <?php foreach ($order_ids as $oid): ?>
    <input type="hidden" name="order_ids[]" value="<?php echo $oid; ?>">
  <?php endforeach; ?>
  <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
  <input type="hidden" name="total"    value="<?php echo $grand_total; ?>">
  <input type="hidden" name="method"   id="formMethod" value="">
</form>

<!-- 💳 Digital PIN Modal -->
<div class="modal-overlay" id="digitalModal">
  <div class="modal">
    <div class="modal-title">💳 Digital Payment</div>
    <div class="modal-sub">Enter PIN to confirm payment</div>

    <div class="confirm-amount-box">
      <div class="confirm-amount-label">Amount to Collect</div>
      <div class="confirm-amount-value">₹<?php echo number_format($grand_total,2); ?></div>
      <div class="confirm-amount-sub">
        <?php echo htmlspecialchars($table['table_number']); ?> &nbsp;·&nbsp;
        <?php echo count($orders_arr); ?> order<?php echo count($orders_arr)>1?'s':''; ?>
      </div>
    </div>

    <!-- PIN dots -->
    <div class="pin-display">
      <div class="pin-dot" id="pd0"></div>
      <div class="pin-dot" id="pd1"></div>
      <div class="pin-dot" id="pd2"></div>
      <div class="pin-dot" id="pd3"></div>
    </div>

    <div class="pin-error" id="pinError"></div>

    <!-- Number pad -->
    <div class="pin-grid">
      <button class="pin-key" onclick="pinPress('1')">1</button>
      <button class="pin-key" onclick="pinPress('2')">2</button>
      <button class="pin-key" onclick="pinPress('3')">3</button>
      <button class="pin-key" onclick="pinPress('4')">4</button>
      <button class="pin-key" onclick="pinPress('5')">5</button>
      <button class="pin-key" onclick="pinPress('6')">6</button>
      <button class="pin-key" onclick="pinPress('7')">7</button>
      <button class="pin-key" onclick="pinPress('8')">8</button>
      <button class="pin-key" onclick="pinPress('9')">9</button>
      <button class="pin-key" onclick="pinClear()">C</button>
      <button class="pin-key" onclick="pinPress('0')">0</button>
      <button class="pin-key del" onclick="pinDelete()">⌫</button>
    </div>

    <div class="modal-btns">
      <button class="btn-cancel" onclick="closeDigital()">✕ Cancel</button>
      <button class="btn-confirm" onclick="verifyPin()">✅ Confirm</button>
    </div>
  </div>
</div>

<!-- 💵 Cash Confirmation Modal -->
<div class="modal-overlay" id="cashModal">
  <div class="modal">
    <div class="modal-title">💵 Confirm Cash Payment</div>
    <div class="modal-sub">Please confirm before marking as paid</div>
    <div class="confirm-amount-box">
      <div class="confirm-amount-label">Amount to Collect</div>
      <div class="confirm-amount-value">₹<?php echo number_format($grand_total,2); ?></div>
      <div class="confirm-amount-sub">
        <?php echo htmlspecialchars($table['table_number']); ?> &nbsp;·&nbsp;
        <?php echo count($orders_arr); ?> order<?php echo count($orders_arr)>1?'s':''; ?>
      </div>
    </div>
    <div class="modal-note">Collect ₹<?php echo number_format($grand_total,2); ?> cash from the customer before confirming.</div>
    <div class="modal-btns">
      <button class="btn-cancel" onclick="closeCash()">✕ Cancel</button>
      <button class="btn-confirm" onclick="submitPayment('Cash')">✅ Cash Received</button>
    </div>
  </div>
</div>

<!-- 📱 UPI QR Modal -->
<div class="modal-overlay" id="upiModal">
  <div class="modal">
    <div class="modal-title">📱 UPI Payment</div>
    <div class="modal-sub">Scan with any UPI app to pay</div>
    <?php if ($upi_id): ?>
    <div class="qr-wrap">
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php
        echo urlencode("upi://pay?pa={$upi_id}&pn=POS+Cafe&am={$grand_total}&cu=INR");
      ?>" alt="UPI QR">
    </div>
    <div class="qr-upi"><?php echo htmlspecialchars($upi_id); ?></div>
    <?php else: ?>
    <div class="qr-wrap" style="font-size:48px;">📱</div>
    <div class="qr-upi" style="color:var(--red);">UPI ID not configured</div>
    <?php endif; ?>
    <div class="qr-amount">₹<?php echo number_format($grand_total,2); ?></div>
    <div class="modal-note">Ask customer to open GPay, PhonePe, Paytm or any UPI app, scan the QR and complete payment. Then tap "Payment Received".</div>
    <div class="modal-btns">
      <button class="btn-cancel" onclick="closeUpi()">✕ Cancel</button>
      <form action="process_table_bill.php" method="POST" style="display:contents;">
        <?php foreach ($order_ids as $oid): ?>
          <input type="hidden" name="order_ids[]" value="<?php echo $oid; ?>">
        <?php endforeach; ?>
        <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
        <input type="hidden" name="total"    value="<?php echo $grand_total; ?>">
        <input type="hidden" name="method"   value="UPI">
        <button type="submit" class="btn-confirm">✅ Payment Received</button>
      </form>
    </div>
  </div>
</div>

<script>
let selName = '<?php echo addslashes($methods[0]['method_name'] ?? 'Cash'); ?>';
let selId   = <?php echo $methods[0]['id'] ?? 1; ?>;
const CORRECT_PIN = '<?php echo $digital_pin; ?>';
let pinVal = '';

// ── Method selection ──
function selMethod(name, id) {
  document.querySelectorAll('.method-opt').forEach(el => {
    el.classList.remove('selected');
    el.querySelector('.m-radio').innerHTML = '';
  });
  document.getElementById('mopt-'+id).classList.add('selected');
  document.getElementById('mrad-'+id).innerHTML = '<div style="width:8px;height:8px;background:white;border-radius:50%;"></div>';
  selName = name; selId = id;
  const icons = { Cash:'💵', Digital:'💳', UPI:'📱' };
  document.getElementById('payBtn').textContent =
    (icons[name]||'💰') + ' Pay with ' + name + ' — ₹<?php echo number_format($grand_total,2); ?>';
}

// ── Pay button ──
function doPayment() {
  if      (selName === 'UPI')     { document.getElementById('upiModal').classList.add('open'); }
  else if (selName === 'Cash')    { document.getElementById('cashModal').classList.add('open'); }
  else if (selName === 'Digital') { resetPin(); document.getElementById('digitalModal').classList.add('open'); }
  else    { submitPayment(selName); }
}

// ── Submit form ──
function submitPayment(method) {
  document.getElementById('formMethod').value = method;
  document.getElementById('payForm').submit();
}

// ── PIN pad logic ──
function pinPress(d) {
  if (pinVal.length >= 4) return;
  pinVal += d;
  updateDots();
  if (pinVal.length === 4) verifyPin();
}
function pinDelete() { pinVal = pinVal.slice(0,-1); updateDots(); clearError(); }
function pinClear()  { pinVal = ''; updateDots(); clearError(); }
function updateDots() {
  for (let i=0;i<4;i++) {
    document.getElementById('pd'+i).classList.toggle('filled', i < pinVal.length);
  }
}
function clearError() { document.getElementById('pinError').textContent = ''; }

function verifyPin() {
  if (pinVal.length < 4) {
    document.getElementById('pinError').textContent = 'Please enter 4-digit PIN';
    return;
  }
  if (pinVal === CORRECT_PIN) {
    submitPayment('Digital');
  } else {
    document.getElementById('pinError').textContent = '❌ Incorrect PIN. Try again.';
    pinVal = '';
    updateDots();
  }
}

function resetPin() {
  pinVal = '';
  updateDots();
  clearError();
}

// ── Close modals ──
function closeDigital() { document.getElementById('digitalModal').classList.remove('open'); resetPin(); }
function closeCash()    { document.getElementById('cashModal').classList.remove('open'); }
function closeUpi()     { document.getElementById('upiModal').classList.remove('open'); }

// Backdrop click closes
['digitalModal','cashModal','upiModal'].forEach(id => {
  document.getElementById(id).addEventListener('click', e => {
    if (e.target === e.currentTarget) {
      document.getElementById(id).classList.remove('open');
      if (id === 'digitalModal') resetPin();
    }
  });
});
</script>
</body>
</html>