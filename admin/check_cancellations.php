<?php
include('conn.php');
header('Content-Type: application/json');
// Require admin session
session_start();
if (empty($_SESSION['is_login'])) {
    echo json_encode(['count' => 0, 'orders' => []]);
    exit;
}
// Fetch recent uncategorized cancellations not yet notified to admin
$sql = "SELECT order_id, pname, name, user, canceled_at FROM myorder WHERE status='cancelled' AND (admin_notified IS NULL OR admin_notified = 0) ORDER BY canceled_at DESC LIMIT 10";
$res = mysqli_query($con, $sql);
$orders = [];
if ($res && mysqli_num_rows($res) > 0) {
    while ($r = mysqli_fetch_assoc($res)) {
        $orders[] = [
            'order_id' => $r['order_id'],
            'pname' => $r['pname'],
            'name' => $r['name'],
            'user' => $r['user'],
            'canceled_at' => $r['canceled_at']
        ];
    }
}
echo json_encode(['count' => count($orders), 'orders' => $orders]);
?>