<?php
// DEPRECATED: Redirect to improved table_bill.php
if (isset($_GET['table_id'])) {
    header("Location: table_bill.php?table_id=" . intval($_GET['table_id']));
    exit();
}
header("Location: tables.php");
exit();
?>

