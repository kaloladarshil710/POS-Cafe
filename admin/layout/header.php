<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Cafe Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Poppins', sans-serif;
        }

        body{
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            color:#1f2937;
        }

        .wrapper{
            display:flex;
            min-height:100vh;
        }

        .sidebar{
            width:260px;
            background: linear-gradient(180deg, #0f172a, #1e293b);
            color:white;
            padding:25px 18px;
            position:fixed;
            top:0;
            left:0;
            bottom:0;
            box-shadow: 8px 0 30px rgba(15, 23, 42, 0.18);
        }

        .brand{
            font-size:24px;
            font-weight:700;
            margin-bottom:30px;
            text-align:center;
            color:#fff;
            letter-spacing:0.5px;
        }

        .brand span{
            color:#38bdf8;
        }

        .profile-box{
            background: rgba(255,255,255,0.06);
            border:1px solid rgba(255,255,255,0.08);
            padding:15px;
            border-radius:16px;
            margin-bottom:25px;
        }

        .profile-box h4{
            font-size:16px;
            margin-bottom:5px;
        }

        .profile-box p{
            font-size:13px;
            color:#cbd5e1;
        }

        .menu a{
            display:flex;
            align-items:center;
            gap:12px;
            padding:14px 16px;
            text-decoration:none;
            color:#e2e8f0;
            border-radius:14px;
            margin-bottom:10px;
            transition:0.25s ease;
            font-weight:500;
        }

        .menu a:hover,
        .menu a.active{
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            color:white;
            transform:translateX(4px);
        }

        .main{
            margin-left:260px;
            width:calc(100% - 260px);
            padding:28px;
        }

        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(14px);
            padding:18px 24px;
            border-radius:20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            margin-bottom:28px;
            border:1px solid rgba(255,255,255,0.4);
        }

        .topbar h2{
            font-size:24px;
            font-weight:700;
        }

        .topbar .right{
            display:flex;
            align-items:center;
            gap:15px;
        }

        .badge{
            background:#dcfce7;
            color:#166534;
            padding:8px 14px;
            border-radius:999px;
            font-size:13px;
            font-weight:600;
        }

        .logout-btn{
            text-decoration:none;
            background: linear-gradient(90deg, #ef4444, #f97316);
            color:white;
            padding:10px 18px;
            border-radius:12px;
            font-size:14px;
            font-weight:600;
            transition:0.2s ease;
        }

        .logout-btn:hover{
            transform: translateY(-2px);
            box-shadow:0 8px 18px rgba(239, 68, 68, 0.25);
        }

        .card-grid{
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap:20px;
            margin-bottom:28px;
        }

        .card{
            background: rgba(255,255,255,0.85);
            border:1px solid rgba(255,255,255,0.5);
            backdrop-filter: blur(12px);
            padding:24px;
            border-radius:24px;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.06);
            transition:0.25s ease;
        }

        .card:hover{
            transform:translateY(-5px);
        }

        .card h3{
            font-size:16px;
            color:#64748b;
            margin-bottom:10px;
        }

        .card p{
            font-size:32px;
            font-weight:700;
            color:#0f172a;
        }

        .panel{
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            border:1px solid rgba(255,255,255,0.5);
            border-radius:24px;
            padding:24px;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.06);
            margin-bottom:25px;
        }

        .panel h3{
            margin-bottom:18px;
            font-size:22px;
            font-weight:700;
        }

        .form-grid{
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap:16px;
        }

        input, select, textarea, button{
            width:100%;
            padding:14px 16px;
            border-radius:14px;
            border:1px solid #dbeafe;
            outline:none;
            font-size:14px;
            background:white;
        }

        textarea{
            resize:none;
            min-height:110px;
        }

        input:focus, select:focus, textarea:focus{
            border-color:#3b82f6;
            box-shadow:0 0 0 4px rgba(59,130,246,0.12);
        }

        .btn-primary{
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            color:white;
            border:none;
            font-weight:600;
            cursor:pointer;
            transition:0.2s ease;
        }

        .btn-primary:hover{
            transform:translateY(-2px);
            box-shadow:0 10px 22px rgba(37,99,235,0.22);
        }

        .table-wrap{
            overflow-x:auto;
        }

        table{
            width:100%;
            border-collapse:collapse;
            background:white;
            border-radius:16px;
            overflow:hidden;
        }

        table th{
            background:#eff6ff;
            color:#1e3a8a;
            padding:16px;
            text-align:left;
            font-size:14px;
        }

        table td{
            padding:15px 16px;
            border-bottom:1px solid #f1f5f9;
            font-size:14px;
        }

        table tr:hover{
            background:#f8fafc;
        }

        .action-btn{
            display:inline-block;
            text-decoration:none;
            padding:8px 14px;
            border-radius:10px;
            color:white;
            font-size:13px;
            font-weight:600;
        }

        .delete-btn{
            background: linear-gradient(90deg, #ef4444, #f97316);
        }

        .status-free{
            background:#dcfce7;
            color:#166534;
            padding:6px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:600;
        }

        .status-occupied{
            background:#fee2e2;
            color:#991b1b;
            padding:6px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:600;
        }

        .status-yes{
            background:#dbeafe;
            color:#1d4ed8;
            padding:6px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:600;
        }

        .status-no{
            background:#e5e7eb;
            color:#374151;
            padding:6px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:600;
        }

        .msg-success{
            background:#dcfce7;
            color:#166534;
            padding:14px 16px;
            border-radius:14px;
            margin-bottom:18px;
            font-weight:500;
        }

        .msg-error{
            background:#fee2e2;
            color:#991b1b;
            padding:14px 16px;
            border-radius:14px;
            margin-bottom:18px;
            font-weight:500;
        }

        @media(max-width:900px){
            .sidebar{
                width:100%;
                position:relative;
                height:auto;
            }

            .main{
                margin-left:0;
                width:100%;
            }

            .wrapper{
                flex-direction:column;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <div class="brand">POS <span>Cafe</span></div>

        <div class="profile-box">
            <h4><?php echo $_SESSION['user_name']; ?></h4>
            <p><?php echo ucfirst($_SESSION['user_role']); ?> Panel</p>
            // Restrict only admin
<?php if ($_SESSION['user_role'] != 'admin') {
    header("Location: ../pos/index.php");
    exit();
} ?>
        </div>

        <div class="menu">
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">🏠 Dashboard</a>
            <a href="products.php" class="<?php echo ($current_page == 'products.php') ? 'active' : ''; ?>">🍔 Products</a>
            <a href="tables.php" class="<?php echo ($current_page == 'tables.php') ? 'active' : ''; ?>">🪑 Tables</a>
            <a href="payments.php" class="<?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>">💳 Payments</a>
            <a href="users.php" class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">👥 Users</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h2>Admin Control Panel</h2>
            <div class="right">
                <div class="badge">System Online</div>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>