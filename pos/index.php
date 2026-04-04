<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$tables = mysqli_query($conn, "SELECT * FROM restaurant_tables WHERE active='yes' ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Terminal - Table View</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Poppins', sans-serif;
        }

        body{
            background: linear-gradient(135deg, #0f172a, #1e293b);
            min-height:100vh;
            color:white;
            padding:30px;
        }

        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:30px;
            flex-wrap:wrap;
            gap:15px;
        }

        .topbar h1{
            font-size:30px;
            font-weight:700;
        }

        .topbar p{
            color:#cbd5e1;
            margin-top:6px;
        }

        .btn{
            text-decoration:none;
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            color:white;
            padding:12px 18px;
            border-radius:14px;
            font-weight:600;
            transition:0.2s ease;
        }

        .btn:hover{
            transform:translateY(-2px);
        }

        .grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
            gap:24px;
        }

        .table-card{
            background: rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.12);
            backdrop-filter: blur(14px);
            border-radius:24px;
            padding:28px;
            text-align:center;
            transition:0.25s ease;
            box-shadow: 0 12px 35px rgba(0,0,0,0.18);
        }

        .table-card:hover{
            transform:translateY(-6px);
            box-shadow: 0 20px 45px rgba(0,0,0,0.25);
        }

        .table-icon{
            font-size:48px;
            margin-bottom:18px;
        }

        .table-card h2{
            font-size:24px;
            margin-bottom:10px;
        }

        .table-card p{
            color:#cbd5e1;
            margin-bottom:18px;
        }

        .status{
            display:inline-block;
            padding:8px 14px;
            border-radius:999px;
            font-size:13px;
            font-weight:600;
            margin-bottom:18px;
        }

        .free{
            background:#dcfce7;
            color:#166534;
        }

        .occupied{
            background:#fee2e2;
            color:#991b1b;
        }

        .open-btn{
            display:inline-block;
            text-decoration:none;
            background:white;
            color:#0f172a;
            padding:12px 18px;
            border-radius:14px;
            font-weight:700;
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div>
            <h1>🍽 POS Terminal - Table View</h1>
            <p>Select a table to start taking orders</p>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="../admin/dashboard.php" class="btn">← Back to Admin</a>
            <a href="../auth/logout.php" class="btn">Logout</a>
        </div>
    </div>

    <div class="grid">
        <?php while($row = mysqli_fetch_assoc($tables)) { ?>
            <div class="table-card">
                <div class="table-icon">🪑</div>
                <h2><?php echo htmlspecialchars($row['table_number']); ?></h2>
                <p>Seats: <?php echo $row['seats']; ?></p>

                <div class="status <?php echo ($row['status'] == 'free') ? 'free' : 'occupied'; ?>">
                    <?php echo ucfirst($row['status']); ?>
                </div>

                <br>

                <a class="open-btn" href="order.php?table_id=<?php echo $row['id']; ?>">
                    Open Table
                </a>
            </div>
        <?php } ?>
    </div>

</body>
</html>