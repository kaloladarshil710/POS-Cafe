<?php
include("../config/db.php");

$table_id = intval($_GET['table'] ?? 0);

$table_q = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE id=$table_id AND active='yes'");
$table = mysqli_fetch_assoc($table_q);
if (!$table) die("Invalid table.");

/*
|--------------------------------------------------------------------------
| STEP 1: FETCH ALL UNPAID ORDERS FOR THIS TABLE
|--------------------------------------------------------------------------
*/
$all_orders_q = mysqli_query($conn, "
    SELECT * FROM orders
    WHERE table_id=$table_id
      AND status NOT IN ('paid','cancelled')
    ORDER BY created_at ASC
");

$all_orders = [];
while ($o = mysqli_fetch_assoc($all_orders_q)) {
    $all_orders[] = $o;
}

/*
|--------------------------------------------------------------------------
| STEP 2: FETCH ONLY READY / COMPLETED ORDERS FOR PAYMENT
|--------------------------------------------------------------------------
*/
$orders_q = mysqli_query($conn, "
    SELECT * FROM orders 
    WHERE table_id=$table_id 
      AND status IN ('completed','ready')
    ORDER BY created_at ASC
");

$orders_arr = [];
while ($o = mysqli_fetch_assoc($orders_q)) {
    $orders_arr[] = $o;
}

$has_ready_orders = count($orders_arr) > 0;

/*
|--------------------------------------------------------------------------
| STEP 3: BUILD BILL IF READY ORDERS EXIST
|--------------------------------------------------------------------------
*/
$all_items = [];
$grand_total = 0;
$order_ids = [];

if ($has_ready_orders) {
    foreach ($orders_arr as $ord) {
        $order_ids[] = $ord['id'];

        $items_q = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id={$ord['id']}");
        while ($item = mysqli_fetch_assoc($items_q)) {
            $all_items[] = $item;
            $grand_total += floatval($item['subtotal']);
        }
    }
}

/*
|--------------------------------------------------------------------------
| STEP 4: PAYMENT METHODS
|--------------------------------------------------------------------------
*/
$methods_q = mysqli_query($conn, "SELECT * FROM payment_methods ORDER BY id ASC");
$methods = [];
while ($m = mysqli_fetch_assoc($methods_q)) $methods[] = $m;

if (empty($methods)) {
    $methods = [
        ['id'=>1, 'method_name'=>'Cash', 'upi_id'=>null],
        ['id'=>2, 'method_name'=>'Digital', 'upi_id'=>null],
        ['id'=>3, 'method_name'=>'UPI', 'upi_id'=>'7567521999@sbi'],
    ];
}

$upi_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT upi_id FROM payment_methods WHERE method_name='UPI' LIMIT 1"));
$upi_id = $upi_q ? ($upi_q['upi_id'] ?? '') : '7567521999@sbi';

/*
|--------------------------------------------------------------------------
| STEP 5: OCCUPIED TIME
|--------------------------------------------------------------------------
*/
$server_now = time();
$elapsed = 0;

if (!empty($table['occupied_since'])) {
    $elapsed = max(0, $server_now - strtotime($table['occupied_since']));
} else {
    $fb = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT MIN(created_at) t FROM orders WHERE table_id=$table_id
    "));
    $elapsed = ($fb && $fb['t']) ? max(0, $server_now - strtotime($fb['t'])) : 0;
}

$h = floor($elapsed/3600);
$m = floor(($elapsed%3600)/60);
$time_str = $h > 0 ? "{$h}h {$m}m" : ($m > 0 ? "{$m}m" : "<1m");

/*
|--------------------------------------------------------------------------
| STEP 6: LIVE STATUS COUNTS
|--------------------------------------------------------------------------
*/
$status_counts = [
    'to_cook' => 0,
    'preparing' => 0,
    'ready' => 0,
    'completed' => 0
];

foreach ($all_orders as $ord) {
    $st = strtolower(trim($ord['status']));
    if (isset($status_counts[$st])) $status_counts[$st]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment — <?php echo htmlspecialchars($table['table_number']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
 --bg:#F3F5F8;
 --surface:#FFF;
 --surface2:#F8FAFC;
 --border:#E4E7EC;
 --text:#101828;
 --text2:#667085;
 --text3:#98A2B3;
 --primary:#C8602A;
 --primary-dark:#A84E20;
 --primary-dim:rgba(200,96,42,0.08);
 --green:#12B76A;
 --red:#EF4444;
 --amber:#F59E0B;
 --blue:#2563EB;
 --dark:#0D0D14;
}
body{
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
}
.topbar{
    background:rgba(255,255,255,0.88);
    backdrop-filter:blur(10px);
    border-bottom:1px solid var(--border);
    height:68px;
    padding:0 22px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    position:sticky;
    top:0;
    z-index:50;
}
.back-btn{
    display:flex;align-items:center;gap:8px;text-decoration:none;
    background:var(--surface2);border:1px solid var(--border);
    padding:10px 14px;border-radius:12px;font-size:13px;font-weight:700;color:var(--text);
    transition:.2s;
}
.back-btn:hover{background:#eef2f7;}
.topbar-title{font-size:17px;font-weight:800;}
.topbar-sub{font-size:13px;color:var(--text3);font-weight:700;}

.page{
    max-width:1240px;
    margin:0 auto;
    padding:28px;
}
.card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:24px;
    padding:24px;
    box-shadow:0 10px 30px rgba(16,24,40,.04);
}
.table-banner{
    background:linear-gradient(135deg,#1A1A26 0%,#0D0D14 100%);
    border-radius:22px;
    padding:22px 24px;
    color:white;
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:22px;
    flex-wrap:wrap;
    gap:14px;
}
.tb-left h2{font-size:26px;font-weight:800;margin-bottom:4px;}
.tb-left p{font-size:13px;color:#A1A1BE;}
.tb-right{text-align:right;}
.tb-timer{
    font-size:13px;color:#F59E0B;font-weight:800;margin-bottom:4px;
}
.tb-orders{font-size:12px;color:#70708A;}

.wait-layout{
    display:grid;
    grid-template-columns:1.3fr .8fr;
    gap:24px;
}
.wait-box{
    padding:40px 28px;
    text-align:center;
}
.wait-emoji{
    font-size:70px;
    margin-bottom:18px;
    animation:bob 2s ease-in-out infinite;
}
@keyframes bob{
    0%,100%{transform:translateY(0);}
    50%{transform:translateY(-8px);}
}
.wait-title{
    font-size:30px;
    font-weight:800;
    margin-bottom:10px;
}
.wait-sub{
    color:var(--text2);
    font-size:15px;
    line-height:1.7;
    max-width:680px;
    margin:0 auto 26px;
}
.live-pill-wrap{
    display:flex;
    justify-content:center;
    gap:12px;
    flex-wrap:wrap;
    margin-top:12px;
}
.live-pill{
    background:var(--surface2);
    border:1px solid var(--border);
    border-radius:999px;
    padding:12px 16px;
    min-width:120px;
}
.live-pill strong{
    display:block;
    font-size:18px;
    font-weight:800;
}
.live-pill span{
    font-size:12px;
    color:var(--text3);
    font-weight:700;
}
.info-stack{
    display:flex;
    flex-direction:column;
    gap:18px;
}
.mini-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:20px;
}
.mini-title{
    font-size:14px;
    font-weight:800;
    margin-bottom:14px;
}
.progress-list{
    display:flex;
    flex-direction:column;
    gap:12px;
}
.progress-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    font-size:14px;
    font-weight:700;
}
.dot{
    width:10px;height:10px;border-radius:50%;
    display:inline-block;margin-right:10px;
}
.dot.orange{background:#F59E0B;}
.dot.blue{background:#2563EB;}
.dot.green{background:#12B76A;}
.dot.gray{background:#98A2B3;}
.refresh-note{
    margin-top:18px;
    padding:14px 16px;
    background:var(--primary-dim);
    border:1px solid rgba(200,96,42,.15);
    color:var(--primary);
    border-radius:14px;
    font-size:13px;
    font-weight:700;
}

.bill-layout{
    display:grid;
    grid-template-columns:1fr 390px;
    gap:24px;
}
.card-title{
    font-size:16px;
    font-weight:800;
    margin-bottom:18px;
    display:flex;
    align-items:center;
    gap:8px;
}
table{width:100%;border-collapse:collapse;}
thead th{
    font-size:11px;font-weight:700;text-transform:uppercase;
    color:var(--text3);padding:10px 12px;text-align:left;
    border-bottom:2px solid var(--border);background:var(--surface2);
}
tbody td{
    padding:12px 12px;border-bottom:1px solid #F2F4F7;font-size:14px;
}
tbody tr:last-child td{border-bottom:none;}
.grand-row td{
    font-weight:800;font-size:15px;background:var(--primary-dim);color:var(--primary);
}
.order-section{
    font-size:11px;font-weight:700;text-transform:uppercase;
    color:var(--text3);padding:8px 12px;background:var(--surface2);
}
.pay-panel{display:flex;flex-direction:column;gap:16px;}
.amount-card{
    background:linear-gradient(135deg,#C8602A 0%,#A84E20 100%);
    border-radius:24px;padding:28px;color:white;text-align:center;position:relative;overflow:hidden;
}
.amount-card::before{
    content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;
    background:rgba(255,255,255,0.08);border-radius:50%;
}
.amount-label{font-size:12px;opacity:0.85;margin-bottom:6px;font-weight:700;position:relative;z-index:1;}
.amount-value{font-size:48px;font-weight:800;letter-spacing:-1px;position:relative;z-index:1;}
.amount-sub{font-size:12px;opacity:0.75;margin-top:6px;position:relative;z-index:1;}
.methods-card{
    background:var(--surface);border:1px solid var(--border);border-radius:24px;padding:20px;
}
.methods-label{font-size:13px;font-weight:800;margin-bottom:14px;}
.method-opt{
    display:flex;align-items:center;gap:14px;padding:13px 16px;border:2px solid var(--border);
    border-radius:16px;cursor:pointer;transition:0.18s;margin-bottom:10px;background:var(--surface2);
}
.method-opt:hover{border-color:var(--primary);background:var(--primary-dim);}
.method-opt.selected{
    border-color:var(--primary);background:var(--primary-dim);box-shadow:0 0 0 3px rgba(200,96,42,0.1);
}
.m-icon{
    width:48px;height:48px;background:var(--surface);border:1px solid var(--border);
    border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;
}
.m-info{flex:1;}
.m-name{font-size:15px;font-weight:800;}
.m-desc{font-size:12px;color:var(--text3);margin-top:2px;}
.pay-btn{
    width:100%;padding:17px;background:var(--primary);color:white;border:none;border-radius:16px;
    font-size:15px;font-weight:800;cursor:pointer;transition:0.18s;
}
.pay-btn:hover{background:var(--primary-dark);transform:translateY(-1px);}
.modal-overlay{
    display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);
    backdrop-filter:blur(6px);z-index:999;align-items:center;justify-content:center;
}
.modal-overlay.open{display:flex;}
.modal{
    background:white;border-radius:28px;padding:32px;width:380px;max-width:90vw;text-align:center;
}
.modal-title{font-size:20px;font-weight:800;margin-bottom:4px;}
.modal-sub{font-size:14px;color:var(--text3);margin-bottom:22px;}
.modal-note{
    font-size:12px;color:var(--text3);background:var(--surface2);
    padding:10px 14px;border-radius:10px;line-height:1.5;margin-bottom:22px;
}
.modal-btns{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.btn-cancel{
    background:var(--surface2);border:1px solid var(--border);border-radius:12px;
    padding:13px;font-size:14px;font-weight:700;cursor:pointer;
}
.btn-confirm{
    background:var(--green);border:none;border-radius:12px;
    padding:13px;font-size:14px;font-weight:700;color:white;cursor:pointer;width:100%;
}
.qr-wrap{
    width:220px;height:220px;margin:0 auto 16px;border:3px solid var(--border);
    border-radius:20px;overflow:hidden;background:#f9f9f9;display:flex;align-items:center;justify-content:center;
}
.qr-wrap img{width:100%;height:100%;}
.qr-upi{font-size:14px;font-weight:700;color:var(--primary);margin-bottom:6px;}
.qr-amount{font-size:28px;font-weight:800;margin-bottom:22px;}

@media(max-width:980px){
    .wait-layout,.bill-layout{grid-template-columns:1fr;}
    .page{padding:16px;}
}
</style>
</head>
<body>

<div class="topbar">
    <a class="back-btn" href="menu.php?table=<?php echo $table_id; ?>">← Back to Menu</a>
    <div class="topbar-title">Customer Payment</div>
    <div class="topbar-sub"><?php echo htmlspecialchars($table['table_number']); ?></div>
</div>

<div class="page">

    <div class="table-banner">
        <div class="tb-left">
            <h2>🪑 <?php echo htmlspecialchars($table['table_number']); ?></h2>
            <p><?php echo $table['seats']; ?> seats · QR ordering payment</p>
        </div>
        <div class="tb-right">
            <div class="tb-timer">⏱️ <?php echo $time_str; ?> occupied</div>
            <div class="tb-orders"><?php echo count($all_orders); ?> total order<?php echo count($all_orders)>1?'s':''; ?></div>
        </div>
    </div>

    <?php if (!$has_ready_orders): ?>
        <!-- WAITING UI -->
        <div class="wait-layout">
            <div class="card wait-box">
                <div class="wait-emoji">👨‍🍳</div>
                <div class="wait-title">Your food is being prepared</div>
                <div class="wait-sub">
                    Payment will appear here automatically once your order is marked
                    <strong>Ready</strong> or <strong>Completed</strong> by the kitchen.
                    No need to refresh manually.
                </div>

                <div class="live-pill-wrap">
                    <div class="live-pill">
                        <strong><?php echo $status_counts['to_cook']; ?></strong>
                        <span>Queued</span>
                    </div>
                    <div class="live-pill">
                        <strong><?php echo $status_counts['preparing']; ?></strong>
                        <span>Preparing</span>
                    </div>
                    <div class="live-pill">
                        <strong><?php echo $status_counts['ready']; ?></strong>
                        <span>Ready</span>
                    </div>
                    <div class="live-pill">
                        <strong><?php echo $status_counts['completed']; ?></strong>
                        <span>Completed</span>
                    </div>
                </div>

                <div class="refresh-note">
                    🔄 Checking live order status every 5 seconds...
                </div>
            </div>

            <div class="info-stack">
                <div class="mini-card">
                    <div class="mini-title">📦 Order Progress</div>
                    <div class="progress-list">
                        <div class="progress-item">
                            <span><span class="dot orange"></span> Order Received</span>
                            <span><?php echo $status_counts['to_cook']; ?></span>
                        </div>
                        <div class="progress-item">
                            <span><span class="dot blue"></span> In Kitchen</span>
                            <span><?php echo $status_counts['preparing']; ?></span>
                        </div>
                        <div class="progress-item">
                            <span><span class="dot green"></span> Ready to Serve</span>
                            <span><?php echo $status_counts['ready'] + $status_counts['completed']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="mini-card">
                    <div class="mini-title">ℹ Payment Unlock</div>
                    <div style="font-size:14px;color:var(--text2);line-height:1.8;">
                        As soon as any order for this table is marked
                        <strong>Ready</strong> or <strong>Completed</strong>,
                        this page will automatically switch to the bill & payment view.
                    </div>
                </div>
            </div>
        </div>

        <script>
            setTimeout(() => {
                window.location.reload();
            }, 5000);
        </script>

    <?php else: ?>
        <!-- BILL UI -->
        <div class="bill-layout">
            <!-- LEFT -->
            <div class="card">
                <div class="card-title">📋 Ready Order Items</div>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $current_order = null;
                    foreach ($all_items as $item):
                        if (count($orders_arr) > 1 && $item['order_id'] !== $current_order):
                            $current_order = $item['order_id'];
                            $ord_obj = array_filter($orders_arr, fn($o) => $o['id'] == $current_order);
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

            <!-- RIGHT -->
            <div class="pay-panel">
                <div class="amount-card">
                    <div class="amount-label">Total to Pay</div>
                    <div class="amount-value">₹<?php echo number_format($grand_total,2); ?></div>
                    <div class="amount-sub">
                        <?php echo htmlspecialchars($table['table_number']); ?> · <?php echo count($orders_arr); ?> ready order<?php echo count($orders_arr)>1?'s':''; ?>
                    </div>
                </div>

                <div class="methods-card">
                    <div class="methods-label">Select Payment Method</div>

                    <?php
                    $m_meta = [
                        'Cash' => ['icon'=>'💵','desc'=>'Pay cash at counter'],
                        'Digital' => ['icon'=>'💳','desc'=>'Card / Digital payment'],
                        'UPI' => ['icon'=>'📱','desc'=>'Scan and pay with any UPI app'],
                        'cash' => ['icon'=>'💵','desc'=>'Pay cash at counter'],
                        'card' => ['icon'=>'💳','desc'=>'Card / Digital payment'],
                        'upi' => ['icon'=>'📱','desc'=>'Scan and pay with any UPI app'],
                    ];

                    foreach ($methods as $idx => $pm):
                        $name = $pm['method_name'];
                        $meta = $m_meta[$name] ?? ['icon'=>'💰','desc'=>'Payment'];
                    ?>
                        <div class="method-opt <?php echo $idx===0?'selected':''; ?>"
                             id="mopt-<?php echo $idx; ?>"
                             onclick="selMethod('<?php echo addslashes($name); ?>', <?php echo $idx; ?>)">
                            <div class="m-icon"><?php echo $meta['icon']; ?></div>
                            <div class="m-info">
                                <div class="m-name"><?php echo htmlspecialchars($name); ?></div>
                                <div class="m-desc"><?php echo $meta['desc']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="pay-btn" id="payBtn" onclick="doPayment()">
                    Pay with <?php echo htmlspecialchars($methods[0]['method_name'] ?? 'Now'); ?> — ₹<?php echo number_format($grand_total,2); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($has_ready_orders): ?>
<!-- Hidden Form -->
<form action="process_payment.php" method="POST" id="payForm">
    <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
    <input type="hidden" name="payment_method" id="formMethod" value="">
</form>

<!-- Cash Modal -->
<div class="modal-overlay" id="cashModal">
    <div class="modal">
        <div class="modal-title">💵 Cash Payment</div>
        <div class="modal-sub">Please pay cash at counter</div>
        <div class="modal-note">After receiving cash, restaurant staff will confirm your payment.</div>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeCash()">Cancel</button>
            <button class="btn-confirm" onclick="submitPayment('Cash')">Continue</button>
        </div>
    </div>
</div>

<!-- UPI Modal -->
<div class="modal-overlay" id="upiModal">
    <div class="modal">
        <div class="modal-title">📱 UPI Payment</div>
        <div class="modal-sub">Scan with any UPI app</div>

        <div class="qr-wrap">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php echo urlencode("upi://pay?pa={$upi_id}&pn=POS+Cafe&am={$grand_total}&cu=INR"); ?>" alt="UPI QR">
        </div>

        <div class="qr-upi"><?php echo htmlspecialchars($upi_id); ?></div>
        <div class="qr-amount">₹<?php echo number_format($grand_total,2); ?></div>
        <div class="modal-note">Pay using GPay, PhonePe, Paytm or any UPI app.</div>

        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeUpi()">Cancel</button>
            <button class="btn-confirm" onclick="submitPayment('UPI')">I Have Paid</button>
        </div>
    </div>
</div>

<!-- Digital Modal -->
<div class="modal-overlay" id="digitalModal">
    <div class="modal">
        <div class="modal-title">💳 Digital Payment</div>
        <div class="modal-sub">Proceed with card / digital payment</div>
        <div class="modal-note">Restaurant staff will verify and confirm payment.</div>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeDigital()">Cancel</button>
            <button class="btn-confirm" onclick="submitPayment('Digital')">Continue</button>
        </div>
    </div>
</div>

<script>
let selName = '<?php echo addslashes($methods[0]['method_name'] ?? 'Cash'); ?>';

function selMethod(name, idx) {
    document.querySelectorAll('.method-opt').forEach(el => el.classList.remove('selected'));
    document.getElementById('mopt-' + idx).classList.add('selected');
    selName = name;
    document.getElementById('payBtn').textContent = 'Pay with ' + name + ' — ₹<?php echo number_format($grand_total,2); ?>';
}

function doPayment() {
    let n = selName.toLowerCase();
    if (n === 'upi') {
        document.getElementById('upiModal').classList.add('open');
    } else if (n === 'cash') {
        document.getElementById('cashModal').classList.add('open');
    } else {
        document.getElementById('digitalModal').classList.add('open');
    }
}

function submitPayment(method) {
    document.getElementById('formMethod').value = method;
    document.getElementById('payForm').submit();
}

function closeCash(){ document.getElementById('cashModal').classList.remove('open'); }
function closeUpi(){ document.getElementById('upiModal').classList.remove('open'); }
function closeDigital(){ document.getElementById('digitalModal').classList.remove('open'); }

['cashModal','upiModal','digitalModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', e => {
        if (e.target === e.currentTarget) {
            document.getElementById(id).classList.remove('open');
        }
    });
});
</script>
<?php endif; ?>

</body>
</html>