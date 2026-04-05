<?php
// ============================================================
// DEBUG FILE — Save as:  POS-Cafe/pos/debug_images.php
// Open in browser:       localhost/pos-cafe/pos/debug_images.php
// DELETE this file after you're done debugging!
// ============================================================
include("../config/db.php");

echo "<h2 style='font-family:sans-serif'>Image Debug</h2>";

// 1. Check if image column exists
$cols = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'image'");
if (mysqli_num_rows($cols) === 0) {
    echo "<p style='color:red;font-family:sans-serif'><b>❌ 'image' column does NOT exist in products table.</b><br>
    Run this SQL first:<br>
    <code>ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER description;</code></p>";
    exit;
} else {
    echo "<p style='color:green;font-family:sans-serif'>✅ 'image' column exists in products table.</p>";
}

// 2. Show all products with image values
$pq = mysqli_query($conn, "SELECT id, name, image FROM products ORDER BY id");
echo "<table border='1' cellpadding='8' style='font-family:sans-serif;border-collapse:collapse;'>";
echo "<tr style='background:#f0f0f0'><th>ID</th><th>Name</th><th>image (DB value)</th><th>File exists?</th><th>Preview</th></tr>";

$img_dir = __DIR__ . '/product_images/';

while ($row = mysqli_fetch_assoc($pq)) {
    $img = trim($row['image'] ?? '');
    $path = $img_dir . $img;
    $exists = ($img !== '' && file_exists($path)) ? '✅ YES' : '❌ NO';
    $color  = ($img !== '' && file_exists($path)) ? 'green' : 'red';
    $preview = ($img !== '' && file_exists($path))
        ? "<img src='product_images/" . htmlspecialchars($img) . "' style='height:50px;border-radius:6px;'>"
        : '—';

    echo "<tr>
        <td>{$row['id']}</td>
        <td>" . htmlspecialchars($row['name']) . "</td>
        <td><code>" . htmlspecialchars($img ?: 'NULL / empty') . "</code></td>
        <td style='color:$color'><b>$exists</b></td>
        <td>$preview</td>
    </tr>";
}
echo "</table>";

// 3. List all files in product_images/
echo "<hr><h3 style='font-family:sans-serif'>Files in pos/product_images/</h3>";
$files = glob($img_dir . '*');
if (!$files) {
    echo "<p style='color:red;font-family:sans-serif'>❌ Folder is empty or does not exist at: <code>$img_dir</code></p>";
} else {
    echo "<p style='font-family:sans-serif'>Found " . count($files) . " files:</p>";
    echo "<ul style='font-family:monospace;columns:3;'>";
    foreach ($files as $f) echo "<li>" . basename($f) . "</li>";
    echo "</ul>";
}
?>