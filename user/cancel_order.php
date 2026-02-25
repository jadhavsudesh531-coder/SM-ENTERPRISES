<?php
include('../admin/conn.php');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Ensure user logged in
$username = $_SESSION['username'] ?? null;
if (empty($username)) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['order_id'])) {
    header('Location: myorder.php');
    exit;
}
$order_id = intval($_POST['order_id']);
$username_safe = mysqli_real_escape_string($con, $username);

// Try to find purchase record (support both order_id and pid)
$pk_check = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'order_id'");
$has_order_id = ($pk_check && mysqli_num_rows($pk_check) > 0);
if ($has_order_id) {
    $pres = mysqli_query($con, "SELECT * FROM purchase WHERE (order_id='$order_id' OR pid='$order_id') AND user='$username_safe' LIMIT 1");
} else {
    $pres = mysqli_query($con, "SELECT * FROM purchase WHERE (pid='$order_id' OR order_id='$order_id') AND user='$username_safe' LIMIT 1");
}
if (!$pres || mysqli_num_rows($pres) === 0) {
    header('Location: myorder.php?msg=' . urlencode('Order not found or not yours'));
    exit;
}
$purchase = mysqli_fetch_assoc($pres);

// Prevent cancelling customization orders
if (!empty($purchase['customization_id'])) {
    header('Location: myorder.php?msg=' . urlencode('Customization orders cannot be cancelled'));
    exit;
}

// Prevent cancelling already delivered or already cancelled
$existing_status = strtolower($purchase['status'] ?? '');
if ($existing_status === 'delivered' || $existing_status === 'cancelled') {
    header('Location: myorder.php?msg=' . urlencode('Order cannot be cancelled'));
    exit;
}

// Prevent cancelling once admin has assigned this order to a delivery agent
$blockedStatuses = ['assigned', 'out_for_delivery', 'out for delivery', 'shipped', 'delivered', 'cancelled'];
if (in_array($existing_status, $blockedStatuses, true)) {
    header('Location: myorder.php?msg=' . urlencode('Order cannot be cancelled after assignment to delivery agent'));
    exit;
}

$checkDeliveryAgentCol = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'delivery_agent_id'");
if ($checkDeliveryAgentCol && mysqli_num_rows($checkDeliveryAgentCol) > 0) {
    $deliveryAgentId = (int)($purchase['delivery_agent_id'] ?? 0);
    if ($deliveryAgentId > 0) {
        header('Location: myorder.php?msg=' . urlencode('Order cannot be cancelled after assignment to delivery agent'));
        exit;
    }
}

// Ensure canceled_at column exists
$col_check_purchase = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'canceled_at'");
if (!$col_check_purchase || mysqli_num_rows($col_check_purchase) === 0) {
    mysqli_query($con, "ALTER TABLE purchase ADD COLUMN canceled_at DATETIME NULL");
}
$col_check_myorder = mysqli_query($con, "SHOW COLUMNS FROM myorder LIKE 'canceled_at'");
if (!$col_check_myorder || mysqli_num_rows($col_check_myorder) === 0) {
    mysqli_query($con, "ALTER TABLE myorder ADD COLUMN canceled_at DATETIME NULL");
}
// Ensure admin_notified column exists on myorder
$col_check_admin_notified = mysqli_query($con, "SHOW COLUMNS FROM myorder LIKE 'admin_notified'");
if (!$col_check_admin_notified || mysqli_num_rows($col_check_admin_notified) === 0) {
    mysqli_query($con, "ALTER TABLE myorder ADD COLUMN admin_notified TINYINT(1) NULL DEFAULT 0");
}

// Start transaction
mysqli_begin_transaction($con);
$error = '';

// Update purchase
if ($has_order_id) {
    $pstmt = mysqli_prepare($con, "UPDATE purchase SET status='cancelled', canceled_at=NOW() WHERE order_id = ? AND user = ?");
} else {
    $pstmt = mysqli_prepare($con, "UPDATE purchase SET status='cancelled', canceled_at=NOW() WHERE pid = ? AND user = ?");
}
if ($pstmt) {
    mysqli_stmt_bind_param($pstmt, 'is', $order_id, $username);
    if (!mysqli_stmt_execute($pstmt)) {
        $error = 'Could not update purchase: ' . mysqli_stmt_error($pstmt);
    }
    mysqli_stmt_close($pstmt);
} else {
    $error = 'Could not prepare purchase update: ' . mysqli_error($con);
}

// Update or insert myorder row
if (empty($error)) {
    $mstmt = mysqli_prepare($con, "UPDATE myorder SET status='cancelled', canceled_at=NOW(), admin_notified=0 WHERE order_id = ? AND user = ?");
    if ($mstmt) {
        mysqli_stmt_bind_param($mstmt, 'is', $order_id, $username);
        if (!mysqli_stmt_execute($mstmt)) {
            $error = 'Could not update myorder: ' . mysqli_stmt_error($mstmt);
            mysqli_stmt_close($mstmt);
        } else {
            $affected = mysqli_stmt_affected_rows($mstmt);
            mysqli_stmt_close($mstmt);
            if ($affected === 0) {
                // Try prod_id + user fallback
                $prod_id = intval($purchase['prod_id'] ?? 0);
                $puser = mysqli_real_escape_string($con, $purchase['user'] ?? $username_safe);
                if ($prod_id > 0) {
                    $alt_update_sql = "UPDATE myorder SET status='cancelled', canceled_at=NOW(), admin_notified=0 WHERE prod_id = $prod_id AND user = '$puser' AND status <> 'cancelled'";
                    if (mysqli_query($con, $alt_update_sql) === false) {
                        $error = 'Could not update myorder by prod_id/user: ' . mysqli_error($con);
                    }
                }
                // If still no rows, insert a cancelled myorder
                if (empty($error)) {
                    // Check again
                    $check_sql = "SELECT order_id FROM myorder WHERE order_id = '$order_id' LIMIT 1";
                    $check_res = mysqli_query($con, $check_sql);
                    if (!$check_res || mysqli_num_rows($check_res) === 0) {
                        $pname = mysqli_real_escape_string($con, $purchase['pname'] ?? '');
                        $puser = mysqli_real_escape_string($con, $purchase['user'] ?? $username_safe);
                        $cust_name = mysqli_real_escape_string($con, $purchase['name'] ?? '');
                        $pprice = mysqli_real_escape_string($con, $purchase['pprice'] ?? '0');
                        $pqty = intval($purchase['pqty'] ?? 1);
                        $prod_id = intval($purchase['prod_id'] ?? 0);
                        $pdate = mysqli_real_escape_string($con, $purchase['pdate'] ?? date('Y-m-d H:i:s'));
                        $ins_sql = "INSERT INTO myorder (order_id, pname, name, user, pprice, pqty, prod_id, customization_id, status, pdate, canceled_at, admin_notified, created_at) VALUES ('$order_id', '$pname', '$cust_name', '$puser', '$pprice', '$pqty', '$prod_id', '0', 'cancelled', '$pdate', NOW(), 0, NOW())";
                        if (!mysqli_query($con, $ins_sql)) {
                            $error = 'Could not insert myorder: ' . mysqli_error($con);
                        }
                    }
                }
            }
        }
    } else {
        $error = 'Could not prepare myorder update: ' . mysqli_error($con);
    }
}

if (empty($error)) {
    mysqli_commit($con);
    header('Location: myorder.php?msg=' . urlencode('Order cancelled successfully'));
    exit;
} else {
    mysqli_rollback($con);
    header('Location: myorder.php?msg=' . urlencode('Could not cancel order: ' . $error));
    exit;
}

?>