<?php
include("../config/db.php");

$table_id = intval($_GET['table'] ?? 0);

$q = mysqli_query($conn, "
    SELECT status 
    FROM orders 
    WHERE table_id=$table_id 
    AND status NOT IN ('paid')
    ORDER BY id DESC LIMIT 1
");

$order = mysqli_fetch_assoc($q);

echo json_encode($order ?: ['status'=>'none']);