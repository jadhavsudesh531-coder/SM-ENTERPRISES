<?php
include('conn.php');
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['is_login'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$ids = [];
if (!empty($_POST['order_ids']) && is_array($_POST['order_ids'])) {
    foreach ($_POST['order_ids'] as $v) {
        $ids[] = intval($v);
    }
}
if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No order ids']);
    exit;
}
$ids_list = implode(',', $ids);
$sql = "UPDATE myorder SET admin_notified = 1 WHERE order_id IN ($ids_list)";
if (mysqli_query($con, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($con)]);
}
?>