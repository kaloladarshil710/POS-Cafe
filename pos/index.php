<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$tables = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE active='yes' ORDER BY id ASC");
$user_name = htmlspecialchars($_SESSION['user_name']);

$free_count     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM restaurant_tables WHERE active='yes' AND status='free'"))['t'];
$occupied_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM restaurant_tables WHERE active='yes' AND status='occupied'"))['t'];
$kitchen_active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM orders WHERE status IN ('to_cook','preparing')"))['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Terminal — Table View</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        :root{--primary:#FF6B35;--primary-dark:#E85520;--bg:#0C0C0C;--card:rgba(255,255,255,0.05);--border:rgba(255,255,255,0.08);--text:#F5F5F5;--muted:#888;}
        body{font-family:'Sora',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
        body::before{content:'';position:fixed;top:-20%;left:-10%;width:50%;height:50%;background:radial-gradient(ellipse,rgba(255,107,53,0.08) 0%,transparent 65%);pointer-events:none;}

        .topbar{background:rgba(255,255,255,0.03);border-bottom:1px solid var(--border);padding:18px 32px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;position:sticky;top:0;z-index:50;backdrop-filter:blur(14px);}
        .topbar h1{font-size:22px;font-weight:800;}
        .topbar p{font-size:13px;color:var(--muted);margin-top:3px;}
        .topbar-right{display:flex;gap:10px;flex-wrap:wrap;}

        .btn{text-decoration:none;padding:10px 16px;border-radius:12px;font-family:'Sora',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all 0.2s;display:inline-flex;align-items:center;gap:7px;}
        .btn-outline{background:rgba(255,255,255,0.06);color:var(--text);border:1px solid var(--border);}
        .btn-outline:hover{background:rgba(255,255,255,0.1);}
        .btn-kitchen{background:rgba(245,158,11,0.15);color:#F59E0B;border:1px solid rgba(245,158,11,0.25);}
        .btn-kitchen:hover{background:#F59E0B;color:white;}
        .btn-red{background:rgba(239,68,68,0.1);color:#EF4444;border:1px solid rgba(239,68,68,0.2);}
        .btn-red:hover{background:#EF4444;color:white;}

        .stats-bar{display:flex;gap:12px;padding:14px 32px;border-bottom:1px solid var(--border);flex-wrap:wrap;}
        .stat-pill{background:var(--card);border:1px solid var(--border);border-radius:999px;padding:8px 18px;font-size:13px;display:flex;align-items:center;gap:8px;}

        .content{padding:28px 32px;}
        .section-title{font-size:12px;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);font-weight:700;margin-bottom:20px;}

        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(195px,1fr));gap:16px;}

        .table-card{background:var(--card);border:1px solid var(--border);border-radius:22px;padding:22px;text-align:center;transition:all 0.25s;cursor:pointer;text-decoration:none;color:var(--text);display:block;}
        .table-card:hover{transform:translateY(-6px);border-color:var(--primary);box-shadow:0 20px 40px rgba(255,107,53,0.18);}
        .table-card.occupied{border-color:rgba(239,68,68,0.25);background:rgba(239,68,68,0.04);}
        .table-card.occupied:hover{border-color:#EF4444;box-shadow:0 20px 40px rgba(239,68,68,0.15);}

        .t-icon{font-size:38px;margin-bottom:14px;}
        .t-name{font-size:18px;font-weight:700;margin-bottom:5px;}
        .t-seats{font-size:13px;color:var(--muted);margin-bottom:14px;}
        .t-status{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:999px;font-size:12px;font-weight:700;}
        .s-free{background:rgba(34,197,94,0.15);color:#4ade80;}
        .s-occupied{background:rgba(239,68,68,0.15);color:#f87171;}
        .t-btn{margin-top:14px;display:block;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:10px;font-size:13px;font-weight:700;transition:0.2s;}
        .table-card:hover .t-btn{background:var(--primary);border-color:var(--primary);}

        .empty{text-align:center;padding:60px;color:var(--muted);}

        @media(max-width:600px){.content,.topbar,.stats-bar{padding-left:16px;padding-right:16px;}}
    </style>
</head>
<body>

<div class="topbar">
    <div>
        <h1>☕ POS Terminal</h1>
        <p>Welcome, <?php echo $user_name; ?> — Select a table to begin ordering</p>
    </div>
    <div class="topbar-right">
        <a href="../kitchen/kitchen.php" class="btn btn-kitchen">👨‍🍳 Kitchen</a>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <a href="../admin/dashboard.php" class="btn btn-outline">⚙️ Admin</a>
        <?php endif; ?>
        <a href="../auth/logout.php" class="btn btn-red">🔓 Logout</a>
    </div>
</div>

<div class="stats-bar">
    <div class="stat-pill">🟢 <strong><?php echo $free_count; ?></strong> Free Tables</div>
    <div class="stat-pill">🔴 <strong><?php echo $occupied_count; ?></strong> Occupied</div>
    <div class="stat-pill">🔥 <strong><?php echo $kitchen_active; ?></strong> In Kitchen</div>
</div>

<div class="content">
    <div class="section-title">🍽️ Floor Plan — All Tables</div>
    <?php if (mysqli_num_rows($tables) > 0): ?>
    <div class="grid">
        <?php while ($row = mysqli_fetch_assoc($tables)): ?>
        <a class="table-card <?php echo $row['status'] === 'occupied' ? 'occupied' : ''; ?>"
           href="order.php?table_id=<?php echo $row['id']; ?>">
            <div class="t-icon"><?php echo $row['status'] === 'occupied' ? '🍽️' : '🪑'; ?></div>
            <div class="t-name"><?php echo htmlspecialchars($row['table_number']); ?></div>
            <div class="t-seats">👥 <?php echo $row['seats']; ?> seats</div>
            <div class="t-status <?php echo $row['status'] === 'free' ? 's-free' : 's-occupied'; ?>">
                <?php echo $row['status'] === 'free' ? '● Free' : '● Occupied'; ?>
            </div>
            <div class="t-btn"><?php echo $row['status'] === 'occupied' ? 'Add Items →' : 'Open Table →'; ?></div>
        </a>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty">
        <div style="font-size:52px;margin-bottom:14px;">🪑</div>
        <p>No tables configured. <a href="../admin/tables.php" style="color:var(--primary);">Add tables in Admin →</a></p>
    </div>
    <?php endif; ?>
</div>

</body>
</html>