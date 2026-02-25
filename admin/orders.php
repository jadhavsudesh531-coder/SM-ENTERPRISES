<?php
include('conn.php');

function ensureMyorderStatusColumns($con)
{
    $checkDelivered = mysqli_query($con, "SHOW COLUMNS FROM myorder LIKE 'delivered_at'");
    if (!$checkDelivered || mysqli_num_rows($checkDelivered) === 0) {
        mysqli_query($con, "ALTER TABLE myorder ADD COLUMN delivered_at DATETIME NULL");
    }

    $checkCanceled = mysqli_query($con, "SHOW COLUMNS FROM myorder LIKE 'canceled_at'");
    if (!$checkCanceled || mysqli_num_rows($checkCanceled) === 0) {
        mysqli_query($con, "ALTER TABLE myorder ADD COLUMN canceled_at DATETIME NULL");
    }
}

function ensureDeliveryAgentColumns($con)
{
    $createAgentTableSql = "CREATE TABLE IF NOT EXISTS delivery_agents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agent_name VARCHAR(120) NOT NULL,
        username VARCHAR(80) NOT NULL UNIQUE,
        passward VARCHAR(255) NOT NULL,
        phone VARCHAR(30) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($con, $createAgentTableSql);

    $checkDeliveryAgentId = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'delivery_agent_id'");
    if (!$checkDeliveryAgentId || mysqli_num_rows($checkDeliveryAgentId) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN delivery_agent_id INT NULL");
    }

    $checkAssignedAt = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'assigned_at'");
    if (!$checkAssignedAt || mysqli_num_rows($checkAssignedAt) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN assigned_at DATETIME NULL");
    }
}

function syncMyorderStatus($con, $orderId, $status, $statusAt, $purchaseData = null)
{
    if ($status === 'delivered') {
        $sql = "UPDATE myorder SET status='delivered', delivered_at=?, canceled_at=NULL WHERE order_id=?";
    } else {
        $sql = "UPDATE myorder SET status='cancelled', canceled_at=?, delivered_at=NULL WHERE order_id=?";
    }

    $updated = false;
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $statusAt, $orderId);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            $updated = true;
        }
        mysqli_stmt_close($stmt);
    }

    if ($updated || empty($purchaseData)) {
        return;
    }

    $prodId = (int)($purchaseData['prod_id'] ?? 0);
    $userValue = $purchaseData['user'] ?? '';
    if ($prodId > 0 && $userValue !== '') {
        $fallbackSql = ($status === 'delivered')
            ? "UPDATE myorder SET status='delivered', delivered_at=?, canceled_at=NULL WHERE prod_id=? AND user=?"
            : "UPDATE myorder SET status='cancelled', canceled_at=?, delivered_at=NULL WHERE prod_id=? AND user=?";

        $fallbackStmt = mysqli_prepare($con, $fallbackSql);
        if ($fallbackStmt) {
            mysqli_stmt_bind_param($fallbackStmt, 'sis', $statusAt, $prodId, $userValue);
            mysqli_stmt_execute($fallbackStmt);
            mysqli_stmt_close($fallbackStmt);
        }
    }
}

ensureMyorderStatusColumns($con);
ensureDeliveryAgentColumns($con);

// Handle Deliver Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deliver_order_id'])) {
    $orderId = (int)$_POST['deliver_order_id'];
    $deliveredAt = date('Y-m-d H:i:s');
    
    // First, get the order details (product ID and quantity)
    $orderQuery = mysqli_prepare($con, "SELECT prod_id, pqty, user FROM purchase WHERE order_id=?");
    if ($orderQuery) {
        mysqli_stmt_bind_param($orderQuery, 'i', $orderId);
        mysqli_stmt_execute($orderQuery);
        $orderResult = mysqli_stmt_get_result($orderQuery);
        $orderData = mysqli_fetch_assoc($orderResult);
        mysqli_stmt_close($orderQuery);
        
        if ($orderData && !empty($orderData['prod_id'])) {
            $productId = (int)$orderData['prod_id'];
            $orderQty = (int)$orderData['pqty'];
            
            // Decrease product quantity in product table
            $updateProduct = mysqli_prepare($con, "UPDATE product SET pqty = pqty - ? WHERE pid = ? AND pqty >= ?");
            if ($updateProduct) {
                mysqli_stmt_bind_param($updateProduct, 'iii', $orderQty, $productId, $orderQty);
                mysqli_stmt_execute($updateProduct);
                mysqli_stmt_close($updateProduct);
            }
        }
    }
    
    // Mark order as delivered
    $stmt = mysqli_prepare($con, "UPDATE purchase SET status='delivered', delivered_at=? WHERE order_id=?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $deliveredAt, $orderId);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            syncMyorderStatus($con, $orderId, 'delivered', $deliveredAt, $orderData ?? null);
            header('Location: orders.php?msg=' . urlencode('Order delivered and product quantity updated successfully.'));
            exit;
        }
        mysqli_stmt_close($stmt);
    }
    $error = 'Failed to mark order as delivered.';
}

// Handle Cancel Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $orderId = (int)$_POST['cancel_order_id'];
    $canceledAt = date('Y-m-d H:i:s');

    $purchaseData = null;
    $purchaseStmt = mysqli_prepare($con, "SELECT prod_id, user FROM purchase WHERE order_id=? LIMIT 1");
    if ($purchaseStmt) {
        mysqli_stmt_bind_param($purchaseStmt, 'i', $orderId);
        mysqli_stmt_execute($purchaseStmt);
        $purchaseRes = mysqli_stmt_get_result($purchaseStmt);
        $purchaseData = mysqli_fetch_assoc($purchaseRes) ?: null;
        mysqli_stmt_close($purchaseStmt);
    }
    
    $stmt = mysqli_prepare($con, "UPDATE purchase SET status='cancelled', canceled_at=? WHERE order_id=?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $canceledAt, $orderId);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            syncMyorderStatus($con, $orderId, 'cancelled', $canceledAt, $purchaseData);
            header('Location: orders.php?msg=' . urlencode('Order cancelled successfully.'));
            exit;
        }
        mysqli_stmt_close($stmt);
    }
    $error = 'Failed to cancel order.';
}

// Handle Inline Assign Delivery Agent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inline_assign_order'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $agentId = (int)($_POST['delivery_agent_id'] ?? 0);

    if ($orderId <= 0 || $agentId <= 0) {
        $error = 'Please select a valid order and delivery agent.';
    } else {
        $agentCheck = mysqli_prepare($con, "SELECT id FROM delivery_agents WHERE id=? AND is_active=1 LIMIT 1");
        if ($agentCheck) {
            mysqli_stmt_bind_param($agentCheck, 'i', $agentId);
            mysqli_stmt_execute($agentCheck);
            $agentRes = mysqli_stmt_get_result($agentCheck);
            $agentExists = ($agentRes && mysqli_num_rows($agentRes) > 0);
            mysqli_stmt_close($agentCheck);

            if (!$agentExists) {
                $error = 'Selected agent is not active.';
            } else {
                $transactionStarted = false;
                try {
                    if (mysqli_begin_transaction($con)) {
                        $transactionStarted = true;
                    }

                    $assignStmt = mysqli_prepare($con, "UPDATE purchase SET delivery_agent_id=?, assigned_at=NOW(), status='assigned' WHERE order_id=? AND (status NOT IN ('delivered','cancelled') OR status IS NULL)");
                    if (!$assignStmt) {
                        throw new Exception('Unable to prepare purchase assignment query.');
                    }

                    mysqli_stmt_bind_param($assignStmt, 'ii', $agentId, $orderId);
                    mysqli_stmt_execute($assignStmt);
                    $affected = mysqli_stmt_affected_rows($assignStmt);
                    mysqli_stmt_close($assignStmt);

                    if ($affected <= 0) {
                        throw new Exception('Assignment failed. Please try again.');
                    }

                    $syncStmt = mysqli_prepare($con, "UPDATE myorder SET status='assigned' WHERE order_id=? AND (status NOT IN ('delivered','cancelled') OR status IS NULL)");
                    if (!$syncStmt) {
                        throw new Exception('Unable to prepare myorder sync query.');
                    }

                    mysqli_stmt_bind_param($syncStmt, 'i', $orderId);
                    mysqli_stmt_execute($syncStmt);
                    mysqli_stmt_close($syncStmt);

                    if ($transactionStarted) {
                        mysqli_commit($con);
                    }

                    header('Location: orders.php?msg=' . urlencode('Order assigned to delivery agent successfully.'));
                    exit;
                } catch (Throwable $e) {
                    if ($transactionStarted) {
                        mysqli_rollback($con);
                    }
                    $error = $e->getMessage();
                }
            }
        }
    }
}

$activeAgents = [];
$activeAgentRes = mysqli_query($con, "SELECT id, agent_name, username FROM delivery_agents WHERE is_active=1 ORDER BY agent_name ASC");
if ($activeAgentRes) {
    while ($row = mysqli_fetch_assoc($activeAgentRes)) {
        $activeAgents[] = $row;
    }
}

$assignedFilter = $_GET['assigned_filter'] ?? 'all';
if (!in_array($assignedFilter, ['all', 'assigned', 'not_assigned'], true)) {
    $assignedFilter = 'all';
}

$assignmentWhere = '';
if ($assignedFilter === 'assigned') {
    $assignmentWhere = " AND purchase.delivery_agent_id IS NOT NULL";
} elseif ($assignedFilter === 'not_assigned') {
    $assignmentWhere = " AND purchase.delivery_agent_id IS NULL";
}

include('header.php');
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
        --success-gradient: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        --danger-gradient: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        --accent-color: #dc2626;
    }

    body { background-color: #eef2f7; font-family: 'Poppins', sans-serif; }
    .orders-page { margin-top: 14px; padding: 0 12px 28px; }

    /* Order Summary Cards */
    .stat-card {
        border: 1px solid #e7ebf1; border-radius: 14px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.07);
        background: #ffffff;
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12); }
    .icon-box {
        width: 50px; height: 50px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 15px;
    }

    /* Tabs Styling */
    .nav-tabs {
        border-bottom: none; gap: 10px;
        background: #ffffff; padding: 8px; border-radius: 12px;
        border: 1px solid #e7ebf1;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
    }
    .nav-link {
        border: none !important; color: #475569; font-weight: 600;
        border-radius: 10px !important; padding: 10px 22px;
        transition: 0.3s;
    }
    .nav-link.active {
        background: var(--primary-gradient) !important;
        color: white !important;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.28);
    }

    /* Table & Content Card */
    .content-card {
        background: white; border-radius: 20px;
        border: 1px solid #e7ebf1; box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        padding: 22px; margin-top: 18px;
    }
    .orders-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; padding: 10px 12px; background: #ffffff;
        border-radius: 12px; border: 1px solid #e7ebf1;
    }
    .orders-toolbar .btn-group .btn {
        border-radius: 10px !important;
        padding: 8px 14px; font-weight: 600;
    }
    .orders-toolbar small {
        background: #ffffff; padding: 6px 10px; border-radius: 999px;
        border: 1px solid #eef0f4; color: #475569;
    }
    .table {
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    .table thead th {
        background-color: #f5f7fb; border: none;
        color: #64748b; text-transform: uppercase;
        font-size: 0.72rem; letter-spacing: 1px; padding: 14px;
    }
    .table tbody tr {
        background: #ffffff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.09);
    }
    .table tbody tr td {
        background: #ffffff;
        padding: 14px; vertical-align: middle; border-color: #eef0f4;
    }
    .table tbody tr td:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
    .table tbody tr td:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
    .order-id { font-weight: 700; color: var(--accent-color); }
    .order-meta {
        font-size: 0.82rem; color: #64748b;
    }
    .agent-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 10px; border-radius: 999px;
        background: #fee2e2; color: #991b1b; font-weight: 600;
        font-size: 0.8rem;
    }
    .action-stack {
        display: flex; flex-direction: column; gap: 8px; align-items: center;
    }

    /* Buttons */
    .btn-action {
        border-radius: 8px; font-weight: 600; padding: 6px 14px; font-size: 0.85rem;
    }
    .orders-page .btn-primary {
        background: var(--primary-gradient);
        border: none;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.28);
    }
    .orders-page .btn-outline-primary {
        border-color: var(--accent-color); color: var(--accent-color);
    }
    .orders-page .btn-outline-primary:hover {
        background: var(--accent-color); color: #ffffff;
    }
    .badge-status { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; }
</style>

<div class="container-fluid pb-5 orders-page">
    <?php if (!empty($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Order Management</h2>
            <p class="text-muted mb-0">Monitor, manage, and process customer orders.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-white text-dark border shadow-sm p-2 px-3 rounded-pill">
                <i class="bi bi-clock-history me-1"></i> System Status: Online
            </span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stat-card p-3">
                <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-box-seam"></i></div>
                <h6 class="text-muted small text-uppercase fw-bold">Pending Orders</h6>
                <h3 class="fw-bold mb-0"><?php 
                    $c_res = mysqli_query($con, "SELECT COUNT(*) as t FROM purchase WHERE status NOT IN ('delivered','cancelled') OR status IS NULL");
                    echo mysqli_fetch_assoc($c_res)['t'];
                ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-3 text-white" style="background: var(--primary-gradient)">
                <div class="icon-box bg-white bg-opacity-25"><i class="bi bi-currency-dollar"></i></div>
                <h6 class="small text-uppercase fw-bold">Total Items Sold</h6>
                <h3 class="fw-bold mb-0"><?php 
                    $s_res = mysqli_query($con, "SELECT SUM(pqty) as t FROM purchase WHERE status='delivered'");
                    echo mysqli_fetch_assoc($s_res)['t'] ?? 0;
                ?></h3>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="ordersTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pending">Active Orders</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#history">Order History</button></li>
    </ul>

    <div class="card content-card">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="pending">
                <div class="orders-toolbar mb-3">
                    <div class="btn-group" role="group" aria-label="Assignment Filter">
                        <a href="orders.php?assigned_filter=all" class="btn btn-sm <?php echo $assignedFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                        <a href="orders.php?assigned_filter=assigned" class="btn btn-sm <?php echo $assignedFilter === 'assigned' ? 'btn-primary' : 'btn-outline-primary'; ?>">Assigned</a>
                        <a href="orders.php?assigned_filter=not_assigned" class="btn btn-sm <?php echo $assignedFilter === 'not_assigned' ? 'btn-primary' : 'btn-outline-primary'; ?>">Unassigned</a>
                    </div>
                    <small>Filter: <?php echo htmlspecialchars(str_replace('_', ' ', $assignedFilter)); ?></small>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer and Product</th>
                                <th>Quantity</th>
                                <th>Delivery Agent</th>
                                <th>Order Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT purchase.*, COALESCE(product.pname, purchase.pname) as pname, da.agent_name, da.username as agent_username FROM purchase LEFT JOIN product ON purchase.prod_id = product.pid LEFT JOIN delivery_agents da ON purchase.delivery_agent_id = da.id WHERE (purchase.status NOT IN ('delivered','cancelled') OR purchase.status IS NULL)" . $assignmentWhere . " ORDER BY purchase.order_id DESC";
                            $res = mysqli_query($con, $sql);
                            if ($res && mysqli_num_rows($res) > 0) {
                                while ($r = mysqli_fetch_assoc($res)) {
                                    $oid = $r['order_id'] ?? $r['pid'] ?? '';
                                    ?>
                                    <tr>
                                        <td><span class="order-id">#<?php echo $oid; ?></span></td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($r['pname'] ?? 'N/A'); ?></div>
                                            <div class="order-meta"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($r['name'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border p-2 px-3 rounded-pill"><?php echo $r['pqty'] ?: 1; ?> pcs</span></td>
                                        <td>
                                            <?php if (!empty($r['agent_name'])): ?>
                                                <div class="agent-pill"><i class="bi bi-truck"></i><?php echo htmlspecialchars($r['agent_name']); ?></div>
                                                <div class="order-meta">ID: <?php echo htmlspecialchars($r['agent_username'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?php echo date('d M, Y', strtotime($r['pdate'] ?? $r['created_at'])); ?></td>
                                        <td class="text-center">
                                            <div class="action-stack">
                                            <form method="post" class="d-flex gap-1 justify-content-center">
                                                <input type="hidden" name="order_id" value="<?php echo (int)$oid; ?>">
                                                <select name="delivery_agent_id" class="form-select form-select-sm" style="max-width: 190px;" <?php echo count($activeAgents) === 0 ? 'disabled' : ''; ?> required>
                                                    <option value="">Assign Delivery Agent</option>
                                                    <?php foreach ($activeAgents as $agent): ?>
                                                        <option value="<?php echo (int)$agent['id']; ?>" <?php echo ((int)($r['delivery_agent_id'] ?? 0) === (int)$agent['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($agent['agent_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-primary btn-action btn-sm" type="submit" name="inline_assign_order" <?php echo count($activeAgents) === 0 ? 'disabled' : ''; ?>>Save</button>
                                            </form>
                                            <?php if (count($activeAgents) === 0): ?>
                                                <small class="text-muted d-block">No active delivery agents available.</small>
                                            <?php endif; ?>
                                            <?php if (empty($r['customization_id'])): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Cancel this order?');">
                                                <input type="hidden" name="cancel_order_id" value="<?php echo $oid; ?>">
                                                <button class="btn btn-outline-danger btn-action">Cancel</button>
                                            </form>
                                            <?php endif; ?>
                                            <a href="order_details.php?order_id=<?php echo $oid; ?>" class="btn btn-light btn-action ms-1"><i class="bi bi-eye"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php } } else { echo '<tr><td colspan="6" class="text-center py-5">No orders found for the selected filter.</td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="history">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Customer</th>
                                <th>Delivery Agent</th>
                                <th>Dates (Order / Final)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql2 = "SELECT purchase.*, COALESCE(product.pname, purchase.pname) as pname, da.agent_name, da.username as agent_username FROM purchase LEFT JOIN product ON purchase.prod_id = product.pid LEFT JOIN delivery_agents da ON purchase.delivery_agent_id = da.id WHERE purchase.status IN ('delivered','cancelled') ORDER BY purchase.order_id DESC";
                            $res2 = mysqli_query($con, $sql2);
                            if ($res2 && mysqli_num_rows($res2) > 0) {
                                while ($r = mysqli_fetch_assoc($res2)) {
                                    $oid = $r['order_id'] ?? $r['pid'] ?? '';
                                    $status = strtolower($r['status']);
                                    $badge_class = ($status == 'delivered') ? 'bg-success' : 'bg-danger';
                                    ?>
                                    <tr>
                                        <td><span class="order-id">#<?php echo $oid; ?></span></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($r['pname']); ?></td>
                                        <td><?php echo htmlspecialchars($r['name']); ?></td>
                                        <td>
                                            <?php if (!empty($r['agent_name'])): ?>
                                                <div class="fw-bold"><?php echo htmlspecialchars($r['agent_name']); ?></div>
                                                <small class="text-muted">ID: <?php echo htmlspecialchars($r['agent_username'] ?? ''); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <div class="text-muted">In: <?php echo date('d M', strtotime($r['pdate'])); ?></div>
                                            <div class="fw-bold text-dark">Out: <?php echo !empty($r['delivered_at']) ? date('d M', strtotime($r['delivered_at'])) : date('d M', strtotime($r['canceled_at'])); ?></div>
                                        </td>
                                        <td><span class="badge badge-status <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span></td>
                                    </tr>
                            <?php } } else { echo '<tr><td colspan="6" class="text-center py-5">No completed orders yet.</td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1200;">
    <div id="cancelToast" class="toast border-0 shadow-lg rounded-4 overflow-hidden" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white border-0">
            <strong class="me-auto"><i class="bi bi-exclamation-triangle-fill me-2"></i>Order Cancelled</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body p-3">
            <div id="cancelToastBody" class="mb-3">No details</div>
            <button id="ackCancelBtn" class="btn btn-sm btn-danger w-100 rounded-3">Acknowledge</button>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>