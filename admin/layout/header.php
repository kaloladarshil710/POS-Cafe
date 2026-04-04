<?php
// ============================================================
// Admin Layout Header
// FIXED: Role-based access check MUST be at top, before any HTML output
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FIXED: Auth check before any output
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// FIXED: Role check was buried inside HTML profile-box (outputting broken HTML before redirect)
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../pos/index.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_name = htmlspecialchars($_SESSION['user_name']);
$user_role = htmlspecialchars(ucfirst($_SESSION['user_role']));

$nav_items = [
    ['file' => 'dashboard.php', 'icon' => '🏠', 'label' => 'Dashboard'],
    ['file' => 'products.php',  'icon' => '🍔', 'label' => 'Products'],
    ['file' => 'tables.php',    'icon' => '🪑', 'label' => 'Tables'],
    ['file' => 'payments.php',  'icon' => '💳', 'label' => 'Payment Methods'],
    ['file' => 'users.php',     'icon' => '👥', 'label' => 'Staff & Users'],
    ['file' => 'reports.php',   'icon' => '📊', 'label' => 'Reports'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Cafe — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #FF6B35;
            --primary-dark: #E85520;
            --sidebar-bg: #0C0C0C;
            --sidebar-border: rgba(255,255,255,0.06);
            --main-bg: #F4F5F7;
            --card-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--main-bg);
            color: var(--text-dark);
        }

        .wrapper { display: flex; min-height: 100vh; }

        /* ─── Sidebar ─── */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: white;
            padding: 24px 16px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--sidebar-border);
            z-index: 100;
        }

        .sidebar-logo {
            font-size: 22px;
            font-weight: 800;
            color: white;
            padding: 8px 12px 24px;
            border-bottom: 1px solid var(--sidebar-border);
            margin-bottom: 20px;
        }

        .sidebar-logo span { color: var(--primary); }

        .profile-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.07);
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 24px;
        }

        .profile-box h4 {
            font-size: 14px;
            font-weight: 600;
            color: white;
            margin-bottom: 4px;
        }

        .profile-box p {
            font-size: 12px;
            color: var(--primary);
            font-weight: 500;
        }

        .nav { flex: 1; }

        .nav-section {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #444;
            font-weight: 600;
            padding: 0 12px;
            margin-bottom: 8px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #aaa;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 4px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav a:hover {
            background: rgba(255,255,255,0.06);
            color: white;
        }

        .nav a.active {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        .nav a .nav-icon { font-size: 16px; }

        .sidebar-footer {
            border-top: 1px solid var(--sidebar-border);
            padding-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-pos {
            background: rgba(255,107,53,0.15);
            color: var(--primary);
            border: 1px solid rgba(255,107,53,0.3);
        }

        .btn-pos:hover { background: var(--primary); color: white; }

        .btn-kitchen {
            background: rgba(245,158,11,0.12);
            color: #F59E0B;
            border: 1px solid rgba(245,158,11,0.25);
        }

        .btn-kitchen:hover { background: #F59E0B; color: white; }

        .btn-logout {
            background: rgba(239,68,68,0.1);
            color: #EF4444;
            border: 1px solid rgba(239,68,68,0.2);
        }

        .btn-logout:hover { background: #EF4444; color: white; }

        /* ─── Main Area ─── */
        .main {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 18px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .topbar .breadcrumb {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .topbar-right { display: flex; align-items: center; gap: 16px; }

        .online-badge {
            background: #dcfce7;
            color: #166534;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .online-badge::before {
            content: '';
            width: 7px;
            height: 7px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .content { padding: 28px 32px; flex: 1; }

        /* ─── Shared Components ─── */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 22px 24px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 80px;
            height: 80px;
            border-radius: 0 20px 0 80px;
            opacity: 0.08;
        }

        .stat-card.orange::after { background: var(--primary); }
        .stat-card.green::after { background: var(--success); }
        .stat-card.blue::after { background: #3B82F6; }
        .stat-card.purple::after { background: #8B5CF6; }

        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.08); }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 14px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 34px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .panel-header h3 {
            font-size: 18px;
            font-weight: 700;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        input, select, textarea {
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            color: var(--text-dark);
            background: white;
            transition: all 0.2s ease;
            width: 100%;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255,107,53,0.12);
        }

        textarea { resize: vertical; min-height: 100px; }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 12px;
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,107,53,0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-family: 'Sora', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-danger:hover { background: #DC2626; transform: translateY(-1px); }

        .btn-secondary {
            background: #F1F5F9;
            color: var(--text-dark);
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 10px;
            font-family: 'Sora', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover { background: var(--border); }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table thead th {
            background: #F8FAFC;
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            padding: 14px 16px;
            text-align: left;
            border-bottom: 2px solid var(--border);
        }

        table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #F1F5F9;
            color: var(--text-dark);
        }

        table tbody tr:last-child td { border-bottom: none; }
        table tbody tr:hover { background: #F8FAFC; }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-green { background: #DCFCE7; color: #166534; }
        .badge-red { background: #FEE2E2; color: #991B1B; }
        .badge-blue { background: #DBEAFE; color: #1D4ED8; }
        .badge-gray { background: #F1F5F9; color: #475569; }
        .badge-orange { background: #FFEDD5; color: #9A3412; }
        .badge-yellow { background: #FEF9C3; color: #854D0E; }

        .alert {
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

        @media (max-width: 960px) {
            .sidebar { width: 100%; position: relative; flex-direction: row; flex-wrap: wrap; }
            .main { margin-left: 0; width: 100%; }
            .wrapper { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">☕ POS <span>Cafe</span></div>

        <div class="profile-box">
            <h4><?php echo $user_name; ?></h4>
            <p><?php echo $user_role; ?> Panel</p>
        </div>

        <nav class="nav">
            <div class="nav-section">Main Menu</div>
            <?php foreach ($nav_items as $item): ?>
                <a href="<?php echo $item['file']; ?>"
                   class="<?php echo ($current_page === $item['file']) ? 'active' : ''; ?>">
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <?php echo $item['label']; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="../pos/index.php" class="btn-pos">🚀 Open POS Terminal</a>
            <a href="../kitchen/kitchen.php" class="btn-kitchen">👨‍🍳 Kitchen Display</a>
            <a href="../auth/logout.php" class="btn-logout">🔓 Logout</a>
            <a href="categories.php" class="<?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>">📂 Categories</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h2><?php echo ucfirst(str_replace(['.php','_'], ['',' '], $current_page)); ?></h2>
                <div class="breadcrumb">POS Cafe › Admin › <?php echo ucfirst(str_replace('.php','',$current_page)); ?></div>
            </div>
            <div class="topbar-right">
                <div class="online-badge">System Online</div>
            </div>
        </div>
        <div class="content">