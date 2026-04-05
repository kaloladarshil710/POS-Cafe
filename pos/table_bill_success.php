<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$table_id = intval($_GET['table_id'] ?? 0);
$total = htmlspecialchars($_GET['total'] ?? '0.00');
$method = htmlspecialchars($_GET['method'] ?? 'Cash');
$orders = intval($_GET['orders'] ?? 1);

$table = null;
if ($table_id) {
 $table = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE id=$table_id"));
}
$pay_icons = ['Cash'=>'','Digital'=>'','UPI'=>''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Complete — POS Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#F7F4EF;--surface:#FFF;--border:#E8E2D9;--text:#1A1410;--text2:#667085;--text3:#98A2B3;--primary:#C8602A;--green:#12B76A;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 16px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:28px;padding:40px;max-width:480px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,0.08);text-align:center;}

.success-ring{width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#12B76A,#0DA863);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;box-shadow:0 12px 32px rgba(18,183,106,0.35);}
.success-icon{font-size:48px;}

h1{font-size:26px;font-weight:800;margin-bottom:8px;color:var(--text);}
.sub{font-size:15px;color:var(--text2);margin-bottom:32px;}

.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:28px;}
.info-box{background:#F8FAFC;border:1px solid var(--border);border-radius:14px;padding:14px;}
.info-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text3);margin-bottom:4px;}
.info-val{font-size:17px;font-weight:800;color:var(--text);}
.info-val.orange{color:var(--primary);}
.info-val.green{color:var(--green);}

.method-chip{display:inline-flex;align-items:center;gap:7px;background:#DBEAFE;color:#1D4ED8;padding:8px 18px;border-radius:999px;font-size:14px;font-weight:700;margin-bottom:28px;}

.actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.btn{text-decoration:none;padding:14px;border-radius:14px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:7px;transition:0.18s;}
.btn-green{background:linear-gradient(135deg,#12B76A,#0DA863);color:white;}
.btn-green:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(18,183,106,0.3);}
.btn-outline{background:var(--bg);border:1px solid var(--border);color:var(--text);}
.btn-outline:hover{background:var(--border);}

/* confetti animation */
@keyframes fall{0%{transform:translateY(-20px) rotate(0deg);opacity:1;}100%{transform:translateY(100vh) rotate(720deg);opacity:0;}}
.confetti{position:fixed;pointer-events:none;top:0;left:0;width:100%;height:0;z-index:1000;}
.confetti span{position:absolute;top:-20px;width:10px;height:10px;border-radius:2px;animation:fall linear forwards;}
</style>
</head>
<body>

<div class="confetti" id="confetti"></div>

<div class="card">
 <div class="success-ring"><div class="success-icon"></div></div>

 <h1>Payment Complete!</h1>
 <p class="sub">
 <?php echo $table ? htmlspecialchars($table['table_number']) : 'Table'; ?>
 is now <strong>free</strong> and ready for new guests.
 </p>

 <div class="info-grid">
 <div class="info-box">
 <div class="info-label">Table</div>
 <div class="info-val"><?php echo $table ? htmlspecialchars($table['table_number']) : '—'; ?></div>
 </div>
 <div class="info-box">
 <div class="info-label">Amount Paid</div>
 <div class="info-val orange">₹<?php echo $total; ?></div>
 </div>
 <div class="info-box">
 <div class="info-label">Orders Settled</div>
 <div class="info-val green"><?php echo $orders; ?> order<?php echo $orders>1?'s':''; ?></div>
 </div>
 <div class="info-box">
 <div class="info-label">Table Status</div>
 <div class="info-val green"> Free</div>
 </div>
 </div>

 <div class="method-chip">
 <?php echo $pay_icons[$method] ?? ''; ?>
 Paid via <?php echo $method; ?>
 </div>

 <div class="actions">
 <a class="btn btn-green" href="index.php"> Next Table →</a>
 <a class="btn btn-outline" href="../admin/reports.php"> Reports</a>
 </div>
</div>

<script>
// Simple confetti burst
const colors = ['#C8602A','#22C55E','#3B82F6','#F59E0B','#8B5CF6','#EF4444'];
const c = document.getElementById('confetti');
for (let i=0;i<60;i++){
 const s = document.createElement('span');
 s.style.left = Math.random()*100+'%';
 s.style.background = colors[Math.floor(Math.random()*colors.length)];
 s.style.width = (Math.random()*10+6)+'px';
 s.style.height = (Math.random()*10+6)+'px';
 s.style.borderRadius = Math.random()>0.5?'50%':'3px';
 s.style.animationDuration = (Math.random()*2+1.5)+'s';
 s.style.animationDelay = (Math.random()*1.5)+'s';
 c.appendChild(s);
}
</script>
</body>
</html>
