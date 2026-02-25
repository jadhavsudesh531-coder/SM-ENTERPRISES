<?php
include 'header.php';
include 'conn.php';

function ensureAdvancePaymentColumnsForAdmin($con)
{
    $columns = [
        ['table' => 'myorder', 'column' => 'payment_txn_id', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_txn_id VARCHAR(120) NULL"],
        ['table' => 'myorder', 'column' => 'payment_submitted_at', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_submitted_at DATETIME NULL"],
        ['table' => 'myorder', 'column' => 'payment_verified', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_verified TINYINT(1) NOT NULL DEFAULT 0"],
        ['table' => 'myorder', 'column' => 'payment_verified_at', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_verified_at DATETIME NULL"],
        ['table' => 'myorder', 'column' => 'payment_screenshot', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_screenshot VARCHAR(255) NULL"],
        ['table' => 'purchase', 'column' => 'payment_txn_id', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_txn_id VARCHAR(120) NULL"],
        ['table' => 'purchase', 'column' => 'payment_submitted_at', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_submitted_at DATETIME NULL"],
        ['table' => 'purchase', 'column' => 'payment_verified', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_verified TINYINT(1) NOT NULL DEFAULT 0"],
        ['table' => 'purchase', 'column' => 'payment_verified_at', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_verified_at DATETIME NULL"],
        ['table' => 'purchase', 'column' => 'payment_screenshot', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_screenshot VARCHAR(255) NULL"]
    ];

    foreach ($columns as $col) {
        $check = mysqli_query($con, "SHOW COLUMNS FROM {$col['table']} LIKE '{$col['column']}'");
        if (!$check || mysqli_num_rows($check) === 0) {
            mysqli_query($con, $col['sql']);
        }
    }
}

ensureAdvancePaymentColumnsForAdmin($con);

// Check if user is admin
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

$paymentFilter = $_GET['payment_filter'] ?? 'all';
if (!in_array($paymentFilter, ['all', 'awaiting_txn', 'awaiting_verification', 'verified'], true)) {
    $paymentFilter = 'all';
}

$paymentFilterSql = '';
if ($paymentFilter === 'awaiting_txn') {
    $paymentFilterSql = " AND (myorder.payment_txn_id IS NULL OR myorder.payment_txn_id = '')";
} elseif ($paymentFilter === 'awaiting_verification') {
    $paymentFilterSql = " AND (myorder.payment_txn_id IS NOT NULL AND myorder.payment_txn_id <> '') AND (myorder.payment_verified = 0 OR myorder.payment_verified IS NULL)";
} elseif ($paymentFilter === 'verified') {
    $paymentFilterSql = " AND myorder.payment_verified = 1";
}

$filterCounts = [
    'all' => 0,
    'awaiting_txn' => 0,
    'awaiting_verification' => 0,
    'verified' => 0,
];

$countQueries = [
    'all' => "SELECT COUNT(*) AS cnt FROM myorder WHERE (pprice * pqty) >= 1000",
    'awaiting_txn' => "SELECT COUNT(*) AS cnt FROM myorder WHERE (pprice * pqty) >= 1000 AND (payment_txn_id IS NULL OR payment_txn_id = '')",
    'awaiting_verification' => "SELECT COUNT(*) AS cnt FROM myorder WHERE (pprice * pqty) >= 1000 AND (payment_txn_id IS NOT NULL AND payment_txn_id <> '') AND (payment_verified = 0 OR payment_verified IS NULL)",
    'verified' => "SELECT COUNT(*) AS cnt FROM myorder WHERE (pprice * pqty) >= 1000 AND payment_verified = 1"
];

foreach ($countQueries as $key => $query) {
    $countRes = mysqli_query($con, $query);
    if ($countRes) {
        $countRow = mysqli_fetch_assoc($countRes);
        $filterCounts[$key] = (int)($countRow['cnt'] ?? 0);
    }
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $order_status = $_POST['order_status'] ?? '';
    
    if ($order_id && in_array($order_status, ['pending_payment', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        // Update both purchase and myorder tables
        $stmt = mysqli_prepare($con, "UPDATE purchase SET status = ? WHERE pid = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $order_status, $order_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        $stmt = mysqli_prepare($con, "UPDATE myorder SET status = ? WHERE order_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $order_status, $order_id);
            if (mysqli_stmt_execute($stmt)) {
                // If status is changed to 'delivered', deduct the quantity from product inventory
                if ($order_status === 'delivered') {
                    // Get order details (product_id and quantity)
                    $order_query = mysqli_prepare($con, "SELECT prod_id, pqty FROM myorder WHERE order_id = ? LIMIT 1");
                    if ($order_query) {
                        mysqli_stmt_bind_param($order_query, 'i', $order_id);
                        mysqli_stmt_execute($order_query);
                        $order_result = mysqli_stmt_get_result($order_query);
                        
                        if ($order_result && mysqli_num_rows($order_result) > 0) {
                            $order_data = mysqli_fetch_assoc($order_result);
                            $prod_id = intval($order_data['prod_id'] ?? 0);
                            $pqty = intval($order_data['pqty'] ?? 0);
                            
                            // Deduct quantity from product inventory
                            if ($prod_id > 0 && $pqty > 0) {
                                $update_product = mysqli_prepare($con, "UPDATE product SET pqty = pqty - ? WHERE pid = ? AND pqty >= ?");
                                if ($update_product) {
                                    mysqli_stmt_bind_param($update_product, 'iii', $pqty, $prod_id, $pqty);
                                    mysqli_stmt_execute($update_product);
                                    mysqli_stmt_close($update_product);
                                }
                            }
                        }
                        mysqli_stmt_close($order_query);
                    }
                }
                
                $message = "Order status updated successfully!";
                $message_type = 'success';
            } else {
                $message = "Error updating status: " . mysqli_stmt_error($stmt);
                $message_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle advance payment verification (auto confirm)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_advance_payment') {
    $order_id = intval($_POST['order_id'] ?? 0);

    if ($order_id > 0) {
        $checkStmt = mysqli_prepare($con, "SELECT payment_txn_id FROM myorder WHERE order_id = ? LIMIT 1");
        $txnExists = false;
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, 'i', $order_id);
            mysqli_stmt_execute($checkStmt);
            $checkRes = mysqli_stmt_get_result($checkStmt);
            $checkRow = $checkRes ? mysqli_fetch_assoc($checkRes) : null;
            $txnExists = !empty($checkRow['payment_txn_id']);
            mysqli_stmt_close($checkStmt);
        }

        if (!$txnExists) {
            $message = "Transaction ID not submitted yet by customer.";
            $message_type = 'danger';
        } else {
            $ok1 = false;
            $ok2 = false;

            $pstmt = mysqli_prepare($con, "UPDATE purchase SET status='confirmed', payment_verified=1, payment_verified_at=NOW() WHERE order_id = ?");
            if ($pstmt) {
                mysqli_stmt_bind_param($pstmt, 'i', $order_id);
                $ok1 = mysqli_stmt_execute($pstmt);
                mysqli_stmt_close($pstmt);
            }

            $mstmt = mysqli_prepare($con, "UPDATE myorder SET status='confirmed', payment_verified=1, payment_verified_at=NOW() WHERE order_id = ?");
            if ($mstmt) {
                mysqli_stmt_bind_param($mstmt, 'i', $order_id);
                $ok2 = mysqli_stmt_execute($mstmt);
                mysqli_stmt_close($mstmt);
            }

            if ($ok1 && $ok2) {
                $message = "Advance payment verified and order confirmed successfully.";
                $message_type = 'success';
            } else {
                $message = "Could not verify payment right now.";
                $message_type = 'danger';
            }
        }
    }
}

?>

<style>
    .page-header {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: white;
        padding: 30px 0;
        margin-bottom: 30px;
    }
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .status-pending_payment { background: #dc3545; color: white; }
    .status-confirmed { background: #28a745; color: white; }
    .status-processing { background: #007bff; color: white; }
    .status-shipped { background: #6f42c1; color: white; }
    .status-delivered { background: #20c997; color: white; }
    .status-cancelled { background: #6c757d; color: white; }
    .orders-table { font-size: 0.95rem; }
    .action-buttons { white-space: nowrap; }
    .card-header-custom {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        padding: 15px;
    }
    .price-highlight {
        font-weight: bold;
        color: #ffc107;
    }
    .advance-amount {
        color: #dc3545;
        font-weight: bold;
        font-size: 1.1rem;
    }
</style>

<div class="page-header">
    <div class="container-fluid">
        <h2><i class="bi bi-bag-check me-2"></i>Product Advance Payment Management</h2>
        <p class="mb-0">Manage product orders with a 50% advance payment requirement (Price ≥ ₹1000).</p>
    </div>
</div>

<div class="container-fluid">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
            <strong><?php echo ucfirst($message_type); ?>!</strong> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <?php
        $statuses = ['pending_payment', 'confirmed', 'shipped', 'delivered'];
        $status_labels = [
            'pending_payment' => 'Awaiting 50% Payment',
            'confirmed' => 'Payment Confirmed',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered'
        ];
        $icons = [
            'pending_payment' => 'clock-history',
            'confirmed' => 'check-circle',
            'shipped' => 'box-seam',
            'delivered' => 'check2-all'
        ];

        foreach ($statuses as $status) {
            $count_query = "SELECT COUNT(*) as count FROM myorder WHERE status = '$status' AND (pprice * pqty) >= 1000";
            $result = mysqli_query($con, $count_query);
            $count = mysqli_fetch_assoc($result)['count'];
        ?>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-<?php echo $icons[$status]; ?>" style="font-size: 2rem; color: #ffc107;"></i>
                        <h6 class="mt-3 mb-1"><?php echo $status_labels[$status]; ?></h6>
                        <h3 class="text-warning"><?php echo $count; ?></h3>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Total Revenue Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-currency-rupee me-2"></i>Total Order Value (≥ ₹1000)</h6>
                            <h3 class="text-warning">
                                ₹<?php 
                                $total_query = "SELECT SUM(pprice * pqty) as total FROM myorder WHERE (pprice * pqty) >= 1000";
                                $result = mysqli_query($con, $total_query);
                                $total_row = mysqli_fetch_assoc($result);
                                echo number_format($total_row['total'] ?? 0, 2);
                                ?>
                            </h3>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-exclamation-circle me-2"></i>Pending Advance Payment</h6>
                            <h3 class="text-danger">
                                ₹<?php 
                                $pending_query = "SELECT SUM((pprice * pqty) * 0.5) as pending FROM myorder WHERE status = 'pending_payment' AND (pprice * pqty) >= 1000";
                                $result = mysqli_query($con, $pending_query);
                                $pending_row = mysqli_fetch_assoc($result);
                                echo number_format($pending_row['pending'] ?? 0, 2);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Orders Table -->
    <div class="card shadow-sm">
        <div class="card-header card-header-custom">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>All Product Orders with Advance Payment</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="btn-group" role="group" aria-label="Payment Filter">
                    <a href="product_advance_payments.php?payment_filter=all" class="btn btn-sm <?php echo $paymentFilter === 'all' ? 'btn-warning' : 'btn-outline-warning'; ?>">All (<?php echo $filterCounts['all']; ?>)</a>
                    <a href="product_advance_payments.php?payment_filter=awaiting_txn" class="btn btn-sm <?php echo $paymentFilter === 'awaiting_txn' ? 'btn-warning' : 'btn-outline-warning'; ?>">Awaiting TXN (<?php echo $filterCounts['awaiting_txn']; ?>)</a>
                    <a href="product_advance_payments.php?payment_filter=awaiting_verification" class="btn btn-sm <?php echo $paymentFilter === 'awaiting_verification' ? 'btn-warning' : 'btn-outline-warning'; ?>">Awaiting Verification (<?php echo $filterCounts['awaiting_verification']; ?>)</a>
                    <a href="product_advance_payments.php?payment_filter=verified" class="btn btn-sm <?php echo $paymentFilter === 'verified' ? 'btn-warning' : 'btn-outline-warning'; ?>">Verified (<?php echo $filterCounts['verified']; ?>)</a>
                </div>
                <small class="text-muted">Current: <?php echo htmlspecialchars(str_replace('_', ' ', $paymentFilter)); ?></small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover orders-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>50% Advance</th>
                            <th>TXN ID</th>
                            <th>Screenshot</th>
                            <th>Payment Verify</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM myorder WHERE (pprice * pqty) >= 1000" . $paymentFilterSql . " ORDER BY pdate DESC";
                        $result = mysqli_query($con, $sql);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $total_amount = floatval($row['pprice']) * intval($row['pqty']);
                                $advance_amount = $total_amount * 0.5;
                                $status_class = 'status-' . str_replace(' ', '_', $row['status']);
                        ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($row['order_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['pname']); ?></td>
                                    <td>₹<?php echo number_format($row['pprice'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['pqty']); ?></td>
                                    <td><span class="price-highlight">₹<?php echo number_format($total_amount, 2); ?></span></td>
                                    <td><span class="advance-amount">₹<?php echo number_format($advance_amount, 2); ?></span></td>
                                    <td>
                                        <?php if (!empty($row['payment_txn_id'])): ?>
                                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($row['payment_txn_id']); ?></span>
                                            <?php if (!empty($row['payment_submitted_at'])): ?>
                                                <div class="small text-muted"><?php echo date('d M Y, H:i', strtotime($row['payment_submitted_at'])); ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not submitted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['payment_screenshot'])): ?>
                                            <a href="../productimg/payment_screenshots/<?php echo htmlspecialchars($row['payment_screenshot']); ?>" target="_blank" class="btn btn-sm btn-info">
                                                <i class="bi bi-image me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Not uploaded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['payment_verified']) && (int)$row['payment_verified'] === 1): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php elseif (!empty($row['payment_txn_id']) && !empty($row['payment_screenshot'])): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Verify this payment and confirm order?');">
                                                <input type="hidden" name="action" value="verify_advance_payment">
                                                <input type="hidden" name="order_id" value="<?php echo (int)$row['order_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Verify Payment</button>
                                            </form>
                                        <?php elseif (!empty($row['payment_txn_id'])): ?>
                                            <span class="badge bg-warning text-dark">Awaiting Screenshot</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Awaiting TXN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($row['pdate'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $row['order_id']; ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $row['order_id']; ?>">
                                            <i class="bi bi-pencil"></i> Update
                                        </button>
                                    </td>
                                </tr>

                                <!-- Details Modal -->
                                <div class="modal fade" id="detailsModal<?php echo $row['order_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title">Order #<?php echo htmlspecialchars($row['order_id']); ?> Details</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Customer Name:</strong><br><?php echo htmlspecialchars($row['name']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Email:</strong><br><?php echo htmlspecialchars($row['user']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Product Name:</strong><br><?php echo htmlspecialchars($row['pname']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Quantity:</strong><br><?php echo htmlspecialchars($row['pqty']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4">
                                                        <p><strong>Unit Price:</strong><br>₹<?php echo number_format($row['pprice'], 2); ?></p>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <p><strong>Total Amount:</strong><br><span class="price-highlight">₹<?php echo number_format($total_amount, 2); ?></span></p>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <p><strong>50% Advance:</strong><br><span class="advance-amount">₹<?php echo number_format($advance_amount, 2); ?></span></p>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <p><strong>Current Status:</strong><br>
                                                        <span class="status-badge status-<?php echo str_replace(' ', '_', $row['status']); ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                                <div class="mb-3">
                                                    <p><strong>Order Date:</strong><br><?php echo date('d M Y H:i', strtotime($row['pdate'])); ?></p>
                                                </div>
                                                
                                                <!-- Payment Status Info -->
                                                <?php if ($row['status'] === 'pending_payment'): ?>
                                                    <div class="alert alert-danger">
                                                        <strong>⚠️ Awaiting 50% Advance Payment</strong>
                                                        <p class="mt-2 mb-0">
                                                            Amount Due: <span class="advance-amount">₹<?php echo number_format($advance_amount, 2); ?></span><br>
                                                            Customer needs to make the advance payment before order processing begins.
                                                        </p>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-success">
                                                        <strong>✓ 50% Advance Payment Confirmed</strong>
                                                        <p class="mt-2 mb-0">
                                                            Advance Received: <span class="advance-amount">₹<?php echo number_format($advance_amount, 2); ?></span><br>
                                                            Remaining Due: <span class="advance-amount">₹<?php echo number_format($advance_amount, 2); ?></span>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $row['order_id']; ?>" data-bs-dismiss="modal">
                                                    Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Update Status Modal -->
                                <div class="modal fade" id="updateModal<?php echo $row['order_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title">Update Order Status #<?php echo htmlspecialchars($row['order_id']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-info mb-3">
                                                        <strong>Advance Payment Due: ₹<?php echo number_format($advance_amount, 2); ?></strong><br>
                                                        Verify payment before moving status forward.
                                                    </div>
                                                    <input type="hidden" name="action" value="update_order_status">
                                                    <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label"><strong>Update Status to:</strong></label>
                                                        <select name="order_status" class="form-select" required>
                                                            <option value="">-- Select Status --</option>
                                                            <option value="pending_payment" <?php echo $row['status'] === 'pending_payment' ? 'selected' : ''; ?>>
                                                                Pending (Awaiting 50% Payment)
                                                            </option>
                                                            <option value="confirmed" <?php echo $row['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                                                Confirmed (50% Payment Received)
                                                            </option>
                                                            <option value="processing" <?php echo $row['status'] === 'processing' ? 'selected' : ''; ?>>
                                                                Processing
                                                            </option>
                                                            <option value="shipped" <?php echo $row['status'] === 'shipped' ? 'selected' : ''; ?>>
                                                                Shipped
                                                            </option>
                                                            <option value="delivered" <?php echo $row['status'] === 'delivered' ? 'selected' : ''; ?>>
                                                                Delivered
                                                            </option>
                                                            <option value="cancelled" <?php echo $row['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                                Cancelled
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="alert alert-warning">
                                                        <small>
                                                            <strong>Note:</strong><br>
                                                            • Only move to "Confirmed" after verifying 50% payment receipt<br>
                                                            • Collect remaining 50% before/upon delivery<br>
                                                            • Send customer and tracking updates
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-warning">Update Status</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="12" class="text-center text-muted py-4">No product orders with advance payment found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Information Panel -->
    <div class="row mt-4">
        <div class="col-md-6 mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle me-2"></i>Payment Status Guide</h6>
                    <small>
                        <p><strong>Pending:</strong> Awaiting 50% advance payment from customer</p>
                        <p><strong>Confirmed:</strong> 50% advance payment received, ready to process</p>
                        <p><strong>Processing:</strong> Order being prepared</p>
                        <p><strong>Shipped:</strong> Order dispatched to customer</p>
                        <p><strong>Delivered:</strong> Order delivered to customer</p>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-exclamation-circle me-2"></i>Important Notes</h6>
                    <small>
                        <p>✓ 50% advance payment is <strong>required</strong> for products ≥ ₹1000</p>
                        <p>✓ Verify payment receipt before confirming orders</p>
                        <p>✓ Send regular updates to customers</p>
                        <p>✓ Collect remaining 50% before/upon delivery</p>
                        <p>✓ Keep payment records for accounting</p>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
