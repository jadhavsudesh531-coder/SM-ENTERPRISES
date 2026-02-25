<?php
include('../admin/conn.php');

function deliveryStatusLabel($rawStatus)
{
    $status = strtolower(trim((string)$rawStatus));
    if (in_array($status, ['assigned', 'out_for_delivery', 'out for delivery', 'out_for_order', 'out for order', 'shipped'], true)) {
        return 'Out for Delivery';
    }
    if ($status === '' || $status === 'pending') {
        return 'Pending';
    }
    return ucwords(str_replace('_', ' ', $status));
}

function ensureDeliveryPortalColumns($con)
{
    $checkDeliveryAgentId = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'delivery_agent_id'");
    if (!$checkDeliveryAgentId || mysqli_num_rows($checkDeliveryAgentId) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN delivery_agent_id INT NULL");
    }

    $checkAssignedAt = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'assigned_at'");
    if (!$checkAssignedAt || mysqli_num_rows($checkAssignedAt) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN assigned_at DATETIME NULL");
    }

    $checkDeliveredAt = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'delivered_at'");
    if (!$checkDeliveredAt || mysqli_num_rows($checkDeliveredAt) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN delivered_at DATETIME NULL");
    }

    $checkCanceledAt = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'canceled_at'");
    if (!$checkCanceledAt || mysqli_num_rows($checkCanceledAt) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN canceled_at DATETIME NULL");
    }

    $checkMyorderDelivered = mysqli_query($con, "SHOW COLUMNS FROM myorder LIKE 'delivered_at'");
    if (!$checkMyorderDelivered || mysqli_num_rows($checkMyorderDelivered) === 0) {
        mysqli_query($con, "ALTER TABLE myorder ADD COLUMN delivered_at DATETIME NULL");
    }

    $checkMyorderCanceled = mysqli_query($con, "SHOW COLUMNS FROM myorder LIKE 'canceled_at'");
    if (!$checkMyorderCanceled || mysqli_num_rows($checkMyorderCanceled) === 0) {
        mysqli_query($con, "ALTER TABLE myorder ADD COLUMN canceled_at DATETIME NULL");
    }
}

function syncMyorderDelivered($con, $orderId, $deliveredAt, $purchaseData = null)
{
    $updated = false;
    $stmt = mysqli_prepare($con, "UPDATE myorder SET status='delivered', delivered_at=?, canceled_at=NULL WHERE order_id=?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $deliveredAt, $orderId);
        mysqli_stmt_execute($stmt);
        $updated = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
    }

    if ($updated || empty($purchaseData)) {
        return;
    }

    $prodId = (int)($purchaseData['prod_id'] ?? 0);
    $userValue = $purchaseData['user'] ?? '';
    if ($prodId > 0 && $userValue !== '') {
        $fallbackStmt = mysqli_prepare($con, "UPDATE myorder SET status='delivered', delivered_at=?, canceled_at=NULL WHERE prod_id=? AND user=?");
        if ($fallbackStmt) {
            mysqli_stmt_bind_param($fallbackStmt, 'sis', $deliveredAt, $prodId, $userValue);
            mysqli_stmt_execute($fallbackStmt);
            mysqli_stmt_close($fallbackStmt);
        }
    }
}

ensureDeliveryPortalColumns($con);
include('header.php');

$agentId = (int)($_SESSION['delivery_agent_id'] ?? 0);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deliver_order_id'])) {
    $orderId = (int)($_POST['deliver_order_id'] ?? 0);

    if ($orderId <= 0) {
        $error = 'Invalid order.';
    } else {
        $orderStmt = mysqli_prepare($con, "SELECT order_id, prod_id, pqty, user, status FROM purchase WHERE order_id=? AND delivery_agent_id=? LIMIT 1");
        $orderData = null;
        if ($orderStmt) {
            mysqli_stmt_bind_param($orderStmt, 'ii', $orderId, $agentId);
            mysqli_stmt_execute($orderStmt);
            $orderRes = mysqli_stmt_get_result($orderStmt);
            $orderData = $orderRes ? mysqli_fetch_assoc($orderRes) : null;
            mysqli_stmt_close($orderStmt);
        }

        if (!$orderData) {
            $error = 'Order not found for your account.';
        } elseif (in_array(strtolower((string)$orderData['status']), ['delivered', 'cancelled'], true)) {
            $error = 'This order is already closed.';
        } else {
            $productId = (int)($orderData['prod_id'] ?? 0);
            $orderQty = (int)($orderData['pqty'] ?? 0);
            $deliveredAt = date('Y-m-d H:i:s');

            if ($productId > 0 && $orderQty > 0) {
                $updateProduct = mysqli_prepare($con, "UPDATE product SET pqty = pqty - ? WHERE pid = ? AND pqty >= ?");
                if ($updateProduct) {
                    mysqli_stmt_bind_param($updateProduct, 'iii', $orderQty, $productId, $orderQty);
                    mysqli_stmt_execute($updateProduct);
                    mysqli_stmt_close($updateProduct);
                }
            }

            $deliverStmt = mysqli_prepare($con, "UPDATE purchase SET status='delivered', delivered_at=? WHERE order_id=? AND delivery_agent_id=?");
            if ($deliverStmt) {
                mysqli_stmt_bind_param($deliverStmt, 'sii', $deliveredAt, $orderId, $agentId);
                if (mysqli_stmt_execute($deliverStmt) && mysqli_stmt_affected_rows($deliverStmt) > 0) {
                    syncMyorderDelivered($con, $orderId, $deliveredAt, $orderData);
                    $success = 'Order marked as delivered.';
                } else {
                    $error = 'Unable to update this order.';
                }
                mysqli_stmt_close($deliverStmt);
            }
        }
    }
}

$assignedOrders = [];
$assignedStmt = mysqli_prepare($con, "SELECT order_id, pname, name, user, pqty, pprice, pdate, status, assigned_at FROM purchase WHERE delivery_agent_id=? AND (status NOT IN ('delivered','cancelled') OR status IS NULL) ORDER BY order_id DESC");
if ($assignedStmt) {
    mysqli_stmt_bind_param($assignedStmt, 'i', $agentId);
    mysqli_stmt_execute($assignedStmt);
    $assignedRes = mysqli_stmt_get_result($assignedStmt);
    while ($assignedRes && $row = mysqli_fetch_assoc($assignedRes)) {
        $assignedOrders[] = $row;
    }
    mysqli_stmt_close($assignedStmt);
}

$historyOrders = [];
$historyStmt = mysqli_prepare($con, "SELECT order_id, pname, name, pqty, pdate, delivered_at, status FROM purchase WHERE delivery_agent_id=? AND status='delivered' ORDER BY delivered_at DESC");
if ($historyStmt) {
    mysqli_stmt_bind_param($historyStmt, 'i', $agentId);
    mysqli_stmt_execute($historyStmt);
    $historyRes = mysqli_stmt_get_result($historyStmt);
    while ($historyRes && $row = mysqli_fetch_assoc($historyRes)) {
        $historyOrders[] = $row;
    }
    mysqli_stmt_close($historyStmt);
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">My Assigned Orders</h3>
        <span class="badge bg-primary">Total Active: <?php echo count($assignedOrders); ?></span>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">Orders to Deliver</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assignedOrders) === 0): ?>
                            <tr><td colspan="5" class="text-center py-4">No orders assigned yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($assignedOrders as $order): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo (int)$order['order_id']; ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars(date('d M Y', strtotime($order['pdate']))); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($order['pname'] ?? 'N/A'); ?></div>
                                        <small class="text-muted">Qty: <?php echo (int)$order['pqty']; ?> | ₹<?php echo htmlspecialchars($order['pprice']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['user'] ?? ''); ?></small>
                                    </td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars(deliveryStatusLabel($order['status'] ?? 'assigned')); ?></span></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Mark this order as delivered?');">
                                            <input type="hidden" name="deliver_order_id" value="<?php echo (int)$order['order_id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="bi bi-check-circle me-1"></i>Delivered
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-success text-white">Delivered History</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Delivered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historyOrders) === 0): ?>
                            <tr><td colspan="4" class="text-center py-4">No delivered orders yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historyOrders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$order['order_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['pname'] ?? 'N/A'); ?> (<?php echo (int)$order['pqty']; ?>)</td>
                                    <td><?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(!empty($order['delivered_at']) ? date('d M Y, H:i', strtotime($order['delivered_at'])) : ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
