<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
if (!isset($_GET['order_id'])) { header("Location: index.php"); exit(); }

$order_id = intval($_GET['order_id']);
$order_q = mysqli_query($conn, "SELECT o.*,rt.table_number FROM orders o JOIN restaurant_tables rt ON o.table_id=rt.id WHERE o.id=$order_id");
$order = mysqli_fetch_assoc($order_q);
if (!$order) { header("Location: index.php"); exit(); }
if ($order['status']==='paid') { header("Location: payment_success.php?order_id=$order_id"); exit(); }

$methods_q = mysqli_query($conn, "SELECT * FROM payment_methods WHERE is_enabled='yes' ORDER BY id ASC");
$methods = [];
while ($m = mysqli_fetch_assoc($methods_q)) $methods[] = $m;

$upi_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT upi_id FROM payment_methods WHERE method_name='UPI' AND is_enabled='yes' LIMIT 1"));
$upi_id = $upi_row ? ($upi_row['upi_id'] ?? '') : '';

$items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id=$order_id");
$items_arr = [];
while ($i = mysqli_fetch_assoc($items)) $items_arr[] = $i;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment — <?php echo htmlspecialchars($order['order_number']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
 --bg:#F0F2F5;--surface:#FFF;--surface2:#F8FAFC;
 --border:#E4E7EC;--text:#101828;--text2:#667085;--text3:#98A2B3;
 --primary:#C8602A;--primary-dark:#A84E20;--primary-dim:rgba(200,96,42,0.08);
 --green:#12B76A;--green-dim:rgba(18,183,106,0.1);
 --sidebar:#0D0D14;--sidebar-border:rgba(255,255,255,0.07);
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;}

/* topbar */
.topbar{background:var(--surface);border-bottom:1px solid var(--border);height:56px;padding:0 24px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 3px rgba(0,0,0,0.06);}
.back-btn{display:flex;align-items:center;gap:6px;text-decoration:none;background:var(--surface2);border:1px solid var(--border);padding:7px 14px;border-radius:10px;font-size:13px;font-weight:700;color:var(--text);transition:0.15s;}
.back-btn:hover{background:var(--border);}
.topbar-title{font-size:16px;font-weight:800;}
.step-bar{display:flex;align-items:center;gap:6px;font-size:13px;}
.step{color:var(--text3);font-weight:600;}
.step.active{color:var(--primary);font-weight:700;}
.step-sep{color:var(--text3);}

/* layout */
.page{display:grid;grid-template-columns:1fr 400px;gap:24px;padding:28px;max-width:1100px;margin:0 auto;width:100%;}

/* order summary */
.card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.card-title{font-size:15px;font-weight:800;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.meta-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;}
.meta-box{background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:12px 14px;}
.meta-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);margin-bottom:3px;}
.meta-val{font-size:15px;font-weight:800;}
.meta-val.orange{color:var(--primary);}
table{width:100%;border-collapse:collapse;}
th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);padding:10px 12px;text-align:left;border-bottom:2px solid var(--border);background:var(--surface2);}
td{padding:12px 12px;border-bottom:1px solid #F2F4F7;font-size:14px;}
.grand-row td{font-weight:800;font-size:15px;background:var(--primary-dim);color:var(--primary);}

/* payment panel */
.pay-panel{display:flex;flex-direction:column;gap:16px;}

/* amount card */
.amount-card{background:linear-gradient(135deg,#C8602A 0%,#A84E20 100%);border-radius:20px;padding:24px;color:white;text-align:center;position:relative;overflow:hidden;}
.amount-card::before{content:'';position:absolute;top:-40px;right:-40px;width:160px;height:160px;background:rgba(255,255,255,0.08);border-radius:50%;}
.amount-card::after{content:'';position:absolute;bottom:-30px;left:-20px;width:120px;height:120px;background:rgba(0,0,0,0.08);border-radius:50%;}
.amount-label{font-size:13px;opacity:0.85;margin-bottom:8px;font-weight:600;position:relative;z-index:1;}
.amount-value{font-size:44px;font-weight:800;letter-spacing:-1px;position:relative;z-index:1;}
.amount-order{font-size:12px;opacity:0.7;margin-top:6px;position:relative;z-index:1;}

/* method cards */
.methods-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.methods-label{font-size:13px;font-weight:800;margin-bottom:14px;color:var(--text);}
.method-option{display:flex;align-items:center;gap:14px;padding:14px 16px;border:2px solid var(--border);border-radius:14px;cursor:pointer;transition:all 0.18s;margin-bottom:10px;background:var(--surface2);}
.method-option:last-child{margin-bottom:0;}
.method-option:hover{border-color:var(--primary);background:var(--primary-dim);}
.method-option.selected{border-color:var(--primary);background:var(--primary-dim);box-shadow:0 0 0 3px rgba(200,96,42,0.12);}
.method-icon-wrap{width:44px;height:44px;background:var(--surface);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.method-info{flex:1;}
.method-name{font-size:15px;font-weight:700;}
.method-desc{font-size:12px;color:var(--text3);margin-top:2px;}
.method-radio{width:20px;height:20px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;transition:0.15s;flex-shrink:0;}
.method-option.selected .method-radio{background:var(--primary);border-color:var(--primary);}
.method-option.selected .method-radio::after{content:'';width:8px;height:8px;background:white;border-radius:50%;}

.pay-btn{width:100%;padding:16px;background:var(--primary);color:white;border:none;border-radius:14px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:800;cursor:pointer;transition:0.18s;letter-spacing:0.2px;}
.pay-btn:hover:not(:disabled){background:var(--primary-dark);transform:translateY(-2px);box-shadow:0 10px 28px rgba(200,96,42,0.3);}
.pay-btn:disabled{background:#D0D5DD;color:#98A2B3;cursor:not-allowed;transform:none;box-shadow:none;}

/* UPI QR Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:999;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:white;border-radius:28px;padding:32px;width:380px;max-width:90vw;text-align:center;animation:modalIn 0.25s ease;}
@keyframes modalIn{from{transform:scale(0.92);opacity:0;}to{transform:scale(1);opacity:1;}}
.modal-title{font-size:20px;font-weight:800;margin-bottom:4px;}
.modal-sub{font-size:14px;color:var(--text3);margin-bottom:24px;}
.qr-container{width:200px;height:200px;border:3px solid var(--border);border-radius:20px;margin:0 auto 18px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--surface2);}
.qr-icon{font-size:72px;line-height:1;}
.qr-upi-id{font-size:14px;font-weight:700;color:var(--primary);margin-bottom:4px;}
.qr-amount-display{font-size:28px;font-weight:800;margin-bottom:24px;}
.modal-note{font-size:12px;color:var(--text3);margin-bottom:22px;background:var(--surface2);padding:10px 14px;border-radius:10px;line-height:1.5;}
.modal-btns{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.btn-cancel-modal{background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:13px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;transition:0.15s;}
.btn-cancel-modal:hover{background:var(--border);}
.btn-confirm-upi{background:var(--green);border:none;border-radius:12px;padding:13px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;color:white;cursor:pointer;transition:0.15s;}
.btn-confirm-upi:hover{background:#0DA863;}

@media(max-width:768px){.page{grid-template-columns:1fr;padding:16px;}.meta-row{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>

<div class="topbar">
 <a class="back-btn" href="index.php">← Tables</a>
 <div class="topbar-title"> Payment</div>
 <div class="step-bar">
 <span class="step">Order</span>
 <span class="step-sep">›</span>
 <span class="step active">Payment</span>
 <span class="step-sep">›</span>
 <span class="step">Done</span>
 </div>
</div>

<div class="page">
 <!-- Order Summary -->
 <div class="card">
 <div class="card-title"> Order Summary</div>
 <div class="meta-row">
 <div class="meta-box">
 <div class="meta-label">Order</div>
 <div class="meta-val"><?php echo htmlspecialchars($order['order_number']); ?></div>
 </div>
 <div class="meta-box">
 <div class="meta-label">Table</div>
 <div class="meta-val"><?php echo htmlspecialchars($order['table_number']); ?></div>
 </div>
 </div>

 <table>
 <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
 <tbody>
 <?php foreach ($items_arr as $item): ?>
 <tr>
 <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
 <td><?php echo $item['quantity']; ?></td>
 <td>₹<?php echo number_format($item['price'],2); ?></td>
 <td><strong>₹<?php echo number_format($item['subtotal'],2); ?></strong></td>
 </tr>
 <?php endforeach; ?>
 <tr class="grand-row">
 <td colspan="3"><strong>Grand Total</strong></td>
 <td><strong>₹<?php echo number_format($order['total_amount'],2); ?></strong></td>
 </tr>
 </tbody>
 </table>
 </div>

 <!-- Payment Panel -->
 <div class="pay-panel">
 <div class="amount-card">
 <div class="amount-label">Amount to Collect</div>
 <div class="amount-value">₹<?php echo number_format($order['total_amount'],2); ?></div>
 <div class="amount-order"><?php echo htmlspecialchars($order['order_number']); ?> · <?php echo htmlspecialchars($order['table_number']); ?></div>
 </div>

 <div class="methods-card">
 <div class="methods-label">Select Payment Method</div>
 <?php
 $method_meta = [
 'Cash' => ['icon'=>'','desc'=>'Collect cash from customer'],
 'Digital' => ['icon'=>'','desc'=>'Card / Bank transfer / NetBanking'],
 'UPI' => ['icon'=>'','desc'=>'Show QR code for UPI scan'],
 ];
 foreach ($methods as $m):
 $meta = $method_meta[$m['method_name']] ?? ['icon'=>'','desc'=>''];
 ?>
 <div class="method-option" id="method-<?php echo $m['id']; ?>"
 onclick="selectMethod('<?php echo htmlspecialchars($m['method_name']); ?>',<?php echo $m['id']; ?>)">
 <div class="method-icon-wrap"><?php echo $meta['icon']; ?></div>
 <div class="method-info">
 <div class="method-name"><?php echo htmlspecialchars($m['method_name']); ?></div>
 <div class="method-desc"><?php echo $meta['desc']; ?></div>
 </div>
 <div class="method-radio" id="radio-<?php echo $m['id']; ?>"></div>
 </div>
 <?php endforeach; ?>
 </div>

 <button class="pay-btn" id="payBtn" disabled onclick="processPayment()">
 Select a payment method
 </button>
 </div>
</div>

<!-- UPI QR Modal -->
<div class="modal-overlay" id="qrModal">
 <div class="modal">
 <div class="modal-title"> UPI Payment</div>
 <div class="modal-sub">Scan the QR code with any UPI app</div>
<div class="qr-container">
 <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php 
 echo urlencode("upi://pay?pa=".$upi_id."&pn=POS Cafe&am=".$order['total_amount']."&cu=INR"); 
 ?>" alt="UPI QR Code">
</div>
 <div class="qr-upi-id"><?php echo htmlspecialchars($upi_id ?: 'UPI ID not configured'); ?></div>
 <div class="qr-amount-display">₹<?php echo number_format($order['total_amount'],2); ?></div>
 <div class="modal-note">Ask customer to open any UPI app (GPay, PhonePe, Paytm), scan QR and complete payment before tapping Confirmed.</div>
 <div class="modal-btns">
 <button class="btn-cancel-modal" onclick="closeModal()"> Cancel</button>
 <form action="process_payment.php" method="POST" style="display:contents;">
 <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
 <input type="hidden" name="method" value="UPI">
 <button type="submit" class="btn-confirm-upi"> Confirmed</button>
 </form>
 </div>
 </div>
</div>

<form action="process_payment.php" method="POST" id="payForm">
 <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
 <input type="hidden" name="method" id="payMethodInput" value="">
</form>

<script>
let sel='';
function selectMethod(name,id){
 document.querySelectorAll('.method-option').forEach(m=>m.classList.remove('selected'));
 document.getElementById('method-'+id).classList.add('selected');
 sel=name;
 const btn=document.getElementById('payBtn');
 btn.disabled=false;
 const icons={'Cash':'','Digital':'','UPI':''};
 btn.textContent=(icons[name]||'')+' Pay with '+name+' →';
}
function processPayment(){
 if(!sel)return;
 if(sel==='UPI'){document.getElementById('qrModal').classList.add('open');}
 else{document.getElementById('payMethodInput').value=sel;document.getElementById('payForm').submit();}
}
function closeModal(){document.getElementById('qrModal').classList.remove('open');}
</script>
</body>
</html>
