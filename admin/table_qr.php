<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$tables = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE active='yes' ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Table QR Codes</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'DM Sans',sans-serif;}
body{background:#f4f6f9;padding:30px;color:#111827;}
.top-actions{margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;}
.btn{display:inline-block;padding:10px 18px;border-radius:10px;text-decoration:none;font-weight:700;border:none;cursor:pointer;}
.btn-primary{background:#C8602A;color:#fff;}
.btn-dark{background:#111827;color:#fff;}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;}
.card{background:#fff;border-radius:18px;padding:22px;box-shadow:0 8px 24px rgba(0,0,0,0.06);text-align:center;border:1px solid #e5e7eb;}
.table-title{font-size:22px;font-weight:800;margin-bottom:10px;color:#C8602A;}
.table-sub{color:#6b7280;font-size:14px;margin-bottom:15px;}
.qr-box{background:#fff;border:1px dashed #d1d5db;border-radius:14px;padding:12px;display:inline-block;}
.link-box{margin-top:15px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:10px;font-size:12px;word-break:break-all;color:#374151;}
.print-btn{margin-top:14px;background:#111827;color:#fff;padding:10px 16px;border:none;border-radius:10px;font-weight:700;cursor:pointer;width:100%;}
.download-btn{display:block;margin-top:10px;background:#16a34a;color:#fff;padding:10px 16px;border-radius:10px;font-weight:700;text-decoration:none;width:100%;}

/* ── PRINT STYLES ── */
@media print {
    /* Hide UI elements that should never print */
    .top-actions,
    .print-btn,
    .download-btn,
    .link-box {
        display: none !important;
    }

    /* General page/body reset for print */
    body {
        background: #fff !important;
        padding: 10px !important;
    }

    /* Print All: show cards in a 2-column grid */
    .grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 20px !important;
    }

    .card {
        break-inside: avoid;
        page-break-inside: avoid;
        border: 1px solid #e5e7eb !important;
        box-shadow: none !important;
        border-radius: 14px !important;
    }

    /* Print One: hide all cards except the selected one */
    body.print-one .card {
        display: none !important;
    }

    body.print-one .card.printing {
        display: block !important;
        width: 100%;
        max-width: 400px;
        margin: 60px auto;
        border: 1px solid #e5e7eb !important;
        box-shadow: none !important;
    }
}
</style>
</head>
<body>

<div class="top-actions">
    <a href="dashboard.php" class="btn btn-dark">← Back to Dashboard</a>
    <button onclick="printAll()" class="btn btn-primary">🖨 Print All QR Codes</button>
</div>

<div class="grid">
<?php while($row = mysqli_fetch_assoc($tables)) {
    $tableId = $row['id'];
    $tableNo = htmlspecialchars($row['table_number']);
    $url     = "http://localhost/POS-Cafe/customer/menu.php?table=" . $tableId;
    $qr      = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($url);
?>
    <div class="card" id="card-<?php echo $tableId; ?>">
        <div class="table-title"><?php echo $tableNo; ?></div>
        <div class="table-sub">Scan to order food</div>

        <div class="qr-box">
            <img src="<?php echo $qr; ?>" width="220" height="220" alt="QR Code">
        </div>

        <div class="link-box"><?php echo $url; ?></div>

        <button class="print-btn" onclick="printOne('card-<?php echo $tableId; ?>')">🖨 Print</button>

        <a href="<?php echo $qr; ?>" download="table_<?php echo $tableId; ?>.png" class="download-btn">
            ⬇ Download QR
        </a>
    </div>
<?php } ?>
</div>

<script>
// Print a single card only
function printOne(cardId) {
    // Remove any previously set printing classes
    document.querySelectorAll('.printing').forEach(el => el.classList.remove('printing'));

    // Mark the chosen card
    document.getElementById(cardId).classList.add('printing');

    // Tell body we're in single-card mode
    document.body.classList.add('print-one');

    window.print();

    // Clean up after print dialog closes
    setTimeout(() => {
        document.getElementById(cardId).classList.remove('printing');
        document.body.classList.remove('print-one');
    }, 1000);
}

// Print ALL cards
function printAll() {
    // Make sure no single-card mode is active
    document.querySelectorAll('.printing').forEach(el => el.classList.remove('printing'));
    document.body.classList.remove('print-one');

    window.print();
}
</script>

</body>
</html>