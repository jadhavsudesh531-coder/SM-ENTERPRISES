<?php
include 'header.php';
include 'conn.php';

function ensureCustomizationPaymentColumnsForAdmin($con)
{
    $columns = [
        ['column' => 'payment_txn_id', 'sql' => "ALTER TABLE customization ADD COLUMN payment_txn_id VARCHAR(120) NULL"],
        ['column' => 'payment_submitted_at', 'sql' => "ALTER TABLE customization ADD COLUMN payment_submitted_at DATETIME NULL"],
        ['column' => 'payment_verified', 'sql' => "ALTER TABLE customization ADD COLUMN payment_verified TINYINT(1) NOT NULL DEFAULT 0"],
        ['column' => 'payment_verified_at', 'sql' => "ALTER TABLE customization ADD COLUMN payment_verified_at DATETIME NULL"],
        ['column' => 'customization_unit_price', 'sql' => "ALTER TABLE customization ADD COLUMN customization_unit_price DECIMAL(10,2) NULL"],
        ['column' => 'payment_screenshot', 'sql' => "ALTER TABLE customization ADD COLUMN payment_screenshot VARCHAR(255) NULL"]
    ];

    foreach ($columns as $col) {
        $check = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE '{$col['column']}'");
        if (!$check || mysqli_num_rows($check) === 0) {
            mysqli_query($con, $col['sql']);
        }
    }
}

ensureCustomizationPaymentColumnsForAdmin($con);

$message = '';
$message_type = '';

$paymentFilter = $_GET['payment_filter'] ?? 'all';
if (!in_array($paymentFilter, ['all', 'awaiting_txn', 'awaiting_verification', 'verified'], true)) {
    $paymentFilter = 'all';
}

$paymentFilterSql = '';
if ($paymentFilter === 'awaiting_txn') {
    $paymentFilterSql = " AND (payment_txn_id IS NULL OR payment_txn_id = '')";
} elseif ($paymentFilter === 'awaiting_verification') {
    $paymentFilterSql = " AND (payment_txn_id IS NOT NULL AND payment_txn_id <> '') AND (payment_verified = 0 OR payment_verified IS NULL)";
} elseif ($paymentFilter === 'verified') {
    $paymentFilterSql = " AND payment_verified = 1";
}

$filterCounts = [
    'all' => 0,
    'awaiting_txn' => 0,
    'awaiting_verification' => 0,
    'verified' => 0,
];

$countQueries = [
    'all' => "SELECT COUNT(*) AS cnt FROM customization",
    'awaiting_txn' => "SELECT COUNT(*) AS cnt FROM customization WHERE (payment_txn_id IS NULL OR payment_txn_id = '')",
    'awaiting_verification' => "SELECT COUNT(*) AS cnt FROM customization WHERE (payment_txn_id IS NOT NULL AND payment_txn_id <> '') AND (payment_verified = 0 OR payment_verified IS NULL)",
    'verified' => "SELECT COUNT(*) AS cnt FROM customization WHERE payment_verified = 1"
];

foreach ($countQueries as $key => $query) {
    $countRes = mysqli_query($con, $query);
    if ($countRes) {
        $countRow = mysqli_fetch_assoc($countRes);
        $filterCounts[$key] = (int)($countRow['cnt'] ?? 0);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_customization_payment') {
    $customization_id = (int)($_POST['customization_id'] ?? 0);

    if ($customization_id <= 0) {
        $message = 'Invalid customization request ID.';
        $message_type = 'danger';
    } else {
        $checkStmt = mysqli_prepare($con, "SELECT payment_txn_id FROM customization WHERE id=? LIMIT 1");
        $txnExists = false;
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, 'i', $customization_id);
            mysqli_stmt_execute($checkStmt);
            $checkRes = mysqli_stmt_get_result($checkStmt);
            $checkRow = $checkRes ? mysqli_fetch_assoc($checkRes) : null;
            $txnExists = !empty($checkRow['payment_txn_id']);
            mysqli_stmt_close($checkStmt);
        }

        if (!$txnExists) {
            $message = 'Transaction ID not submitted by customer yet.';
            $message_type = 'danger';
        } else {
            $verifyStmt = mysqli_prepare($con, "UPDATE customization SET payment_verified=1, payment_verified_at=NOW(), status=CASE WHEN status IN ('pending','confirmed') OR status IS NULL THEN 'partial_paid' ELSE status END WHERE id=?");
            if ($verifyStmt) {
                mysqli_stmt_bind_param($verifyStmt, 'i', $customization_id);
                if (mysqli_stmt_execute($verifyStmt)) {
                    $message = 'Customization payment verified successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Could not verify customization payment.';
                    $message_type = 'danger';
                }
                mysqli_stmt_close($verifyStmt);
            }
        }
    }
}
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 30px 0;
        margin-bottom: 30px;
    }
    .summary-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: #dc3545;
    }
    .money {
        font-weight: 700;
        color: #198754;
    }
</style>

<div class="page-header">
    <div class="container-fluid">
        <h2><i class="bi bi-cash-coin me-2"></i>Customization Payment Verification</h2>
        <p class="mb-0">Track and verify 50% advance payments for customization orders.</p>
    </div>
</div>

<div class="container-fluid">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="btn-group" role="group" aria-label="Payment Filter">
            <a href="customization_advance_payments.php?payment_filter=all" class="btn btn-sm <?php echo $paymentFilter === 'all' ? 'btn-danger' : 'btn-outline-danger'; ?>">All (<?php echo $filterCounts['all']; ?>)</a>
            <a href="customization_advance_payments.php?payment_filter=awaiting_txn" class="btn btn-sm <?php echo $paymentFilter === 'awaiting_txn' ? 'btn-danger' : 'btn-outline-danger'; ?>">Awaiting Transaction (<?php echo $filterCounts['awaiting_txn']; ?>)</a>
            <a href="customization_advance_payments.php?payment_filter=awaiting_verification" class="btn btn-sm <?php echo $paymentFilter === 'awaiting_verification' ? 'btn-danger' : 'btn-outline-danger'; ?>">Awaiting Verification (<?php echo $filterCounts['awaiting_verification']; ?>)</a>
            <a href="customization_advance_payments.php?payment_filter=verified" class="btn btn-sm <?php echo $paymentFilter === 'verified' ? 'btn-danger' : 'btn-outline-danger'; ?>">Verified (<?php echo $filterCounts['verified']; ?>)</a>
        </div>
        <small class="text-muted">Current Filter: <?php echo htmlspecialchars(str_replace('_', ' ', $paymentFilter)); ?></small>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <?php
            $sumSql = "SELECT SUM(COALESCE(customization_unit_price,0) * COALESCE(pqty,1)) AS total_amount FROM customization";
            $sumRes = mysqli_query($con, $sumSql);
            $sumRow = $sumRes ? mysqli_fetch_assoc($sumRes) : ['total_amount' => 0];
            $totalAmount = (float)($sumRow['total_amount'] ?? 0);
            ?>
            <div class="summary-value">Estimated Total Quoted Value: ₹<?php echo number_format($totalAmount, 2); ?></div>
            <div class="text-muted">Expected 50% advance overall: ₹<?php echo number_format($totalAmount * 0.5, 2); ?></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>50% Advance</th>
                            <th>TXN ID</th>
                            <th>Screenshot</th>
                            <th>Verify</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM customization WHERE 1=1" . $paymentFilterSql . " ORDER BY id DESC";
                        $res = mysqli_query($con, $sql);

                        if ($res && mysqli_num_rows($res) > 0) {
                            while ($row = mysqli_fetch_assoc($res)) {
                                $qty = max(1, (int)($row['pqty'] ?? 1));
                                $unit = (float)($row['customization_unit_price'] ?? 0);
                                if ($unit <= 0) {
                                    $unit = 5000;
                                }
                                $total = $unit * $qty;
                                $advance = $total * 0.5;
                                ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$row['id']; ?></strong></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($row['name'] ?? ''); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['email'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['product_type'] ?? 'Custom'); ?></td>
                                    <td><?php echo $qty; ?></td>
                                    <td>₹<?php echo number_format($unit, 2); ?></td>
                                    <td class="money">₹<?php echo number_format($total, 2); ?></td>
                                    <td class="money">₹<?php echo number_format($advance, 2); ?></td>
                                    <td>
                                        <?php if (!empty($row['payment_txn_id'])): ?>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($row['payment_txn_id']); ?></span>
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
                                            <form method="post">
                                                <input type="hidden" name="action" value="verify_customization_payment">
                                                <input type="hidden" name="customization_id" value="<?php echo (int)$row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Verify</button>
                                            </form>
                                        <?php elseif (!empty($row['payment_txn_id'])): ?>
                                            <span class="badge bg-warning text-dark">Awaiting Screenshot</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Awaiting TXN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', (string)($row['status'] ?? 'pending')))); ?></span>
                                    </td>
                                    <td><?php echo !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '-'; ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="11" class="text-center py-4 text-muted">No customization payment records found for this filter.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
