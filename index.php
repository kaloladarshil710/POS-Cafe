<?php
require_once __DIR__ . '/includes/functions.php';

$page = $_GET['page'] ?? 'login';

// Redirect logged-in users away from login
if ($page === 'login' && isLoggedIn()) {
    header('Location: index.php?page=dashboard');
    exit;
}

// Pages that don't need login
$publicPages = ['login', 'signup', 'customer_display', 'kitchen_display'];

if (!in_array($page, $publicPages)) {
    requireLogin();
}

// Map pages to files
$pageMap = [
    'login'            => 'pages/login.php',
    'signup'           => 'pages/signup.php',
    'dashboard'        => 'pages/dashboard.php',
    'products'         => 'pages/products.php',
    'categories'       => 'pages/categories.php',
    'payment_methods'  => 'pages/payment_methods.php',
    'floors'           => 'pages/floors.php',
    'tables'           => 'pages/tables.php',
    'sessions'         => 'pages/sessions.php',
    'pos'              => 'pages/pos.php',
    'kitchen_display'  => 'pages/kitchen_display.php',
    'customer_display' => 'pages/customer_display.php',
    'reports'          => 'pages/reports.php',
];

$file = $pageMap[$page] ?? 'pages/404.php';

// POS terminal runs full screen
if ($page === 'pos') {
    requireSession();
    include $file;
    exit;
}
if (in_array($page, ['kitchen_display', 'customer_display'])) {
    include $file;
    exit;
}

// All other pages use layout
include 'includes/header.php';
include $file;
include 'includes/footer.php';
