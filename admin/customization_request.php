<?php
include('conn.php');

if (!($con instanceof mysqli)) {
    http_response_code(500);
    die('Database connection is not available.');
}

/** @var mysqli $con */

function executeStmtWithRetry($stmt, $maxRetries = 2, $delayMicros = 200000)
{
    $attempt = 0;

    while (true) {
        try {
            return mysqli_stmt_execute($stmt);
        } catch (mysqli_sql_exception $e) {
            $isLockError = in_array((int)$e->getCode(), [1205, 1213], true);
            if (!$isLockError || $attempt >= $maxRetries) {
                return false;
            }

            usleep($delayMicros * ($attempt + 1));
            $attempt++;
        }
    }
}

function ensureCustomizationPricingColumn($con)
{
    $checkUnitPrice = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE 'customization_unit_price'");
    if (!$checkUnitPrice || mysqli_num_rows($checkUnitPrice) === 0) {
        mysqli_query($con, "ALTER TABLE customization ADD COLUMN customization_unit_price DECIMAL(10,2) NULL");
    }
}

function ensureCustomizationLinkedOrderColumns($con)
{
    $checkPurchaseCustomization = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'customization_id'");
    if (!$checkPurchaseCustomization || mysqli_num_rows($checkPurchaseCustomization) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN customization_id INT NULL");
    }

    $checkMyorderCustomization = mysqli_query($con, "SHOW COLUMNS FROM myorder LIKE 'customization_id'");
    if (!$checkMyorderCustomization || mysqli_num_rows($checkMyorderCustomization) === 0) {
        mysqli_query($con, "ALTER TABLE myorder ADD COLUMN customization_id INT NULL");
    }
}

function resolveCustomizationProdId($con)
{
    $prodIdColumnRes = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'prod_id'");
    $prodIdNullable = false;
    if ($prodIdColumnRes && ($col = mysqli_fetch_assoc($prodIdColumnRes))) {
        $prodIdNullable = strtoupper((string)($col['Null'] ?? 'NO')) === 'YES';
    }

    if ($prodIdNullable) {
        return null;
    }

    $productRes = mysqli_query($con, "SELECT pid FROM product ORDER BY pid ASC LIMIT 1");
    if ($productRes && ($productRow = mysqli_fetch_assoc($productRes))) {
        return (int)($productRow['pid'] ?? 0);
    }

    return 0;
}

function ensureCustomizationPurchaseOrder($con, $customizationId)
{
    $customizationId = (int)$customizationId;
    if ($customizationId <= 0) {
        return false;
    }

    ensureCustomizationLinkedOrderColumns($con);

    $existsStmt = mysqli_prepare($con, "SELECT order_id, status FROM purchase WHERE customization_id=? LIMIT 1");
    if ($existsStmt) {
        mysqli_stmt_bind_param($existsStmt, 'i', $customizationId);
        mysqli_stmt_execute($existsStmt);
        $existsRes = mysqli_stmt_get_result($existsStmt);
        $existingOrder = ($existsRes && mysqli_num_rows($existsRes) > 0) ? mysqli_fetch_assoc($existsRes) : null;
        mysqli_stmt_close($existsStmt);

        if (!empty($existingOrder)) {
            $existingOrderId = (int)($existingOrder['order_id'] ?? 0);
            $existingStatus = strtolower(trim((string)($existingOrder['status'] ?? '')));

            if ($existingOrderId > 0 && in_array($existingStatus, ['delivered', 'cancelled'], true)) {
                $reactivatePurchase = mysqli_prepare($con, "UPDATE purchase SET status='confirmed', delivered_at=NULL, canceled_at=NULL WHERE order_id=?");
                if ($reactivatePurchase) {
                    mysqli_stmt_bind_param($reactivatePurchase, 'i', $existingOrderId);
                    mysqli_stmt_execute($reactivatePurchase);
                    mysqli_stmt_close($reactivatePurchase);
                }

                $reactivateMyorder = mysqli_prepare($con, "UPDATE myorder SET status='confirmed', delivered_at=NULL, canceled_at=NULL WHERE order_id=?");
                if ($reactivateMyorder) {
                    mysqli_stmt_bind_param($reactivateMyorder, 'i', $existingOrderId);
                    mysqli_stmt_execute($reactivateMyorder);
                    mysqli_stmt_close($reactivateMyorder);
                }
            }

            return true;
        }
    }

    $customStmt = mysqli_prepare($con, "SELECT id, name, email, product_type, pqty, created_at, customization_unit_price FROM customization WHERE id=? LIMIT 1");
    if (!$customStmt) {
        return false;
    }
    mysqli_stmt_bind_param($customStmt, 'i', $customizationId);
    mysqli_stmt_execute($customStmt);
    $customRes = mysqli_stmt_get_result($customStmt);
    $customization = $customRes ? mysqli_fetch_assoc($customRes) : null;
    mysqli_stmt_close($customStmt);

    if (!$customization) {
        return false;
    }

    $productType = trim((string)($customization['product_type'] ?? 'Custom Product'));
    $pname = 'Customization - ' . ($productType !== '' ? $productType : 'Custom Product');
    $userEmail = trim((string)($customization['email'] ?? ''));
    $customerName = trim((string)($customization['name'] ?? 'Customer'));
    $quantity = max(1, (int)($customization['pqty'] ?? 1));
    $estimatedPrice = 5000;
    $unitPrice = (float)($customization['customization_unit_price'] ?? 0);
    if ($unitPrice <= 0) {
        $unitPrice = $estimatedPrice;
    }
    $prodId = resolveCustomizationProdId($con);
    if ($prodId === 0) {
        return false;
    }
    $status = 'pending';
    $pdate = !empty($customization['created_at']) ? $customization['created_at'] : date('Y-m-d H:i:s');

    $insertPurchaseSql = $prodId === null
        ? "INSERT INTO purchase (pname, user, name, pprice, pqty, prod_id, customization_id, status, pdate) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?)"
        : "INSERT INTO purchase (pname, user, name, pprice, pqty, prod_id, customization_id, status, pdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $insertPurchase = mysqli_prepare($con, $insertPurchaseSql);
    if (!$insertPurchase) {
        return false;
    }

    if ($prodId === null) {
        mysqli_stmt_bind_param(
            $insertPurchase,
            'sssdisss',
            $pname,
            $userEmail,
            $customerName,
            $unitPrice,
            $quantity,
            $customizationId,
            $status,
            $pdate
        );
    } else {
        mysqli_stmt_bind_param(
            $insertPurchase,
            'sssdiiiss',
            $pname,
            $userEmail,
            $customerName,
            $unitPrice,
            $quantity,
            $prodId,
            $customizationId,
            $status,
            $pdate
        );
    }

    try {
        $executed = mysqli_stmt_execute($insertPurchase);
    } catch (mysqli_sql_exception $e) {
        $executed = false;
    }

    if (!$executed) {
        mysqli_stmt_close($insertPurchase);
        return false;
    }

    $orderId = mysqli_insert_id($con);
    mysqli_stmt_close($insertPurchase);

    $prodIdForMyorder = ($prodId === null) ? 0 : $prodId;

    $insertMyorder = mysqli_prepare($con, "INSERT INTO myorder (order_id, pname, user, name, pprice, pqty, prod_id, customization_id, status, pdate, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($insertMyorder) {
        mysqli_stmt_bind_param(
            $insertMyorder,
            'isssdiiiss',
            $orderId,
            $pname,
            $userEmail,
            $customerName,
            $unitPrice,
            $quantity,
            $prodIdForMyorder,
            $customizationId,
            $status,
            $pdate
        );
        try {
            mysqli_stmt_execute($insertMyorder);
        } catch (mysqli_sql_exception $e) {
        }
        mysqli_stmt_close($insertMyorder);
    }

    return true;
}

$error = '';
ensureCustomizationPricingColumn($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_id'])) {
        $acceptId = (int)$_POST['accept_id'];
        $unitPriceInput = trim((string)($_POST['unit_price'] ?? ''));
        $hasUnitPrice = $unitPriceInput !== '';
        $unitPriceValue = null;
        $stmt = null;

        if (!$hasUnitPrice) {
            $error = 'Please enter per-unit price before approving customization request.';
        } elseif (!is_numeric($unitPriceInput) || (float)$unitPriceInput <= 0) {
            $error = 'Please enter a valid per-unit price greater than 0 before approval.';
        } else {
            $unitPriceValue = round((float)$unitPriceInput, 2);
            $stmt = mysqli_prepare($con, "UPDATE customization SET status='accepted', customization_unit_price=? WHERE id=?");
        }

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'di', $unitPriceValue, $acceptId);
            if (executeStmtWithRetry($stmt)) {
                $linked = ensureCustomizationPurchaseOrder($con, $acceptId);

                if ($linked) {
                    $updatePurchasePrice = mysqli_prepare($con, "UPDATE purchase SET pprice=? WHERE customization_id=?");
                    if ($updatePurchasePrice) {
                        mysqli_stmt_bind_param($updatePurchasePrice, 'di', $unitPriceValue, $acceptId);
                        executeStmtWithRetry($updatePurchasePrice);
                        mysqli_stmt_close($updatePurchasePrice);
                    }

                    $updateMyorderPrice = mysqli_prepare($con, "UPDATE myorder SET pprice=? WHERE customization_id=?");
                    if ($updateMyorderPrice) {
                        mysqli_stmt_bind_param($updateMyorderPrice, 'di', $unitPriceValue, $acceptId);
                        executeStmtWithRetry($updateMyorderPrice);
                        mysqli_stmt_close($updateMyorderPrice);
                    }
                }

                $msg = $linked
                    ? 'Request approved and linked to delivery orders successfully.'
                    : 'Request approved, but could not create linked delivery order.';
                header('Location: customization_request.php?msg=' . urlencode($msg));
                exit;
            }
            $error = 'Unable to approve request.';
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Unable to process approval.';
        }
    }

    if (isset($_POST['cancel_id'])) {
        $cancelId = (int)$_POST['cancel_id'];
        
        // Update customization status to cancelled
        $stmt = mysqli_prepare($con, "UPDATE customization SET status='cancelled' WHERE id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $cancelId);
            if (executeStmtWithRetry($stmt)) {
                // Also cancel linked purchase/myorder records
                $updatePurchase = mysqli_prepare($con, "UPDATE purchase SET status='cancelled', canceled_at=NOW() WHERE customization_id=?");
                if ($updatePurchase) {
                    mysqli_stmt_bind_param($updatePurchase, 'i', $cancelId);
                    executeStmtWithRetry($updatePurchase);
                    mysqli_stmt_close($updatePurchase);
                }
                
                $updateMyorder = mysqli_prepare($con, "UPDATE myorder SET status='cancelled', canceled_at=NOW() WHERE customization_id=?");
                if ($updateMyorder) {
                    mysqli_stmt_bind_param($updateMyorder, 'i', $cancelId);
                    executeStmtWithRetry($updateMyorder);
                    mysqli_stmt_close($updateMyorder);
                }
                
                header('Location: customization_request.php?msg=' . urlencode('Customization order cancelled successfully.'));
                exit;
            }
            $error = 'Unable to cancel customization request.';
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Unable to process cancellation.';
        }
    }

    if (isset($_POST['reject_id'])) {
        $rejectId = (int)$_POST['reject_id'];
        $stmt = mysqli_prepare($con, "UPDATE customization SET status='rejected' WHERE id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $rejectId);
            if (executeStmtWithRetry($stmt)) {
                header('Location: customization_request.php?msg=' . urlencode('Request rejected successfully.'));
                exit;
            }
            $error = 'Unable to reject request.';
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Unable to process rejection.';
        }
    }
}

include('header.php');
?>

<style>
    :root {
        --primary-blue: #4361ee;
        --soft-bg: #f8f9fc;
        --accent-teal: #2dd4bf;
    }

    body { background-color: var(--soft-bg); font-family: 'Inter', sans-serif; }
    .container-fluid.page-wrap { margin-top: 10px !important; padding-left: 12px; padding-right: 12px; }

    /* Summary Stats */
    .summary-card {
        background: white; border: none; border-radius: 16px;
        transition: 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .icon-circle {
        width: 45px; height: 45px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }

    /* Tab Styling */
    .nav-pills .nav-link {
        color: #64748b; font-weight: 600; padding: 10px 20px;
        border-radius: 10px; transition: 0.3s;
    }
    .nav-pills .nav-link.active {
        background-color: var(--primary-blue);
        color: white;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }

    /* Table Styling */
    .table-container {
        background: white; border-radius: 20px;
        padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        border: none;
    }
    .custom-table thead th {
        background-color: transparent; border-bottom: 2px solid #f1f5f9;
        color: #94a3b8; text-transform: uppercase; font-size: 0.75rem;
        letter-spacing: 0.5px; padding: 15px;
    }
    .custom-table tbody td {
        padding: 18px 15px; border-bottom: 1px solid #f8fafc;
        vertical-align: middle; color: #334155; font-size: 0.9rem;
    }
    
    .prod-thumb {
        width: 60px; height: 60px; object-fit: cover;
        border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-action {
        width: 38px; height: 38px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        transition: 0.2s; border: none;
    }
    .btn-accept { background-color: #dcfce7; color: #166534; }
    .btn-accept:hover { background-color: #22c55e; color: white; }
    .btn-reject { background-color: #fee2e2; color: #991b1b; }
    .btn-reject:hover { background-color: #ef4444; color: white; }
</style>

<div class="container-fluid page-wrap pb-4">
    <div class="row align-items-center mb-3">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-1">Customization Management</h2>
            <p class="text-muted small mb-0">Review and manage custom product requests from clients.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-inline-flex gap-2">
                <div class="summary-card p-3 d-flex align-items-center gap-3">
                    <div class="icon-circle bg-primary text-white bg-opacity-75"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="small text-muted">Awaiting</div>
                        <div class="fw-bold fs-5">
                            <?php 
                            $c_res = mysqli_query($con, "SELECT COUNT(*) as t FROM customization WHERE status IS NULL OR status='pending'");
                            echo mysqli_fetch_assoc($c_res)['t'];
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4"><?php echo htmlspecialchars(urldecode($_GET['msg'])); ?></div>
    <?php endif; ?>

    <ul class="nav nav-pills mb-3 gap-2" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pending">Pending Requests</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#accepted">Approved</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cancelled">Cancelled</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rejected">History / Rejected</button></li>
    </ul>

    <div class="table-container shadow-sm border-0 card">
        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="pending">
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Client Details</th>
                                <th>Request Info</th>
                                <th class="text-center">Design</th>
                                <th class="text-end">Process</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pending = mysqli_query($con, "SELECT * FROM customization WHERE status IS NULL OR status='pending' ORDER BY created_at DESC");
                            if ($pending && mysqli_num_rows($pending) > 0) {
                                while ($r = mysqli_fetch_assoc($pending)) {
                                    $img = !empty($r['image_path']) ? ('../' . $r['image_path']) : '';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['name'] ?? ''); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($r['email'] ?? ''); ?></div>
                                            <div class="text-muted small"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($r['phone'] ?? ''); ?></div>
                                        </td>
                                        <td>
                                            <div class="badge bg-light text-primary mb-1"><?php echo htmlspecialchars($r['product_type'] ?? 'Custom'); ?></div>
                                            <div class="small text-dark fw-medium">Qty: <?php echo htmlspecialchars($r['pqty'] ?? '1'); ?></div>
                                            <div class="small text-muted">Unit Price: <?php echo (!empty($r['customization_unit_price']) && (float)$r['customization_unit_price'] > 0) ? ('₹' . number_format((float)$r['customization_unit_price'], 2)) : 'Not set'; ?></div>
                                            <div class="text-muted x-small" style="max-width: 250px;"><?php echo htmlspecialchars($r['description'] ?? ''); ?></div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($img): ?>
                                                <img src="<?php echo htmlspecialchars($img); ?>" class="prod-thumb" alt="custom design">
                                            <?php else: ?>
                                                <div class="text-muted small"><i class="bi bi-image-alt"></i> No Image</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="accept_id" value="<?php echo $r['id']; ?>">
                                                <input type="number" name="unit_price" min="1" step="0.01" class="form-control form-control-sm d-inline-block me-1" style="width: 130px;" placeholder="Unit ₹" value="<?php echo htmlspecialchars((string)($r['customization_unit_price'] ?? '')); ?>">
                                                <button title="Accept Request" class="btn-action btn-accept me-1" type="submit"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Reject this request?');">
                                                <input type="hidden" name="reject_id" value="<?php echo $r['id']; ?>">
                                                <button title="Reject Request" class="btn-action btn-reject" type="submit"><i class="bi bi-x-lg"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                            <?php } } else { echo '<tr><td colspan="4" class="text-center py-5">No requests awaiting review.</td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="accepted">
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $acc = mysqli_query($con, "SELECT * FROM customization WHERE status='accepted' ORDER BY created_at DESC");
                            while ($r = mysqli_fetch_assoc($acc)) { ?>
                                <tr>
                                    <td><div class="fw-bold"><?php echo $r['name']; ?></div><small><?php echo $r['email']; ?></small></td>
                                    <td><?php echo $r['product_type']; ?></td>
                                    <td><?php echo $r['pqty']; ?></td>
                                    <td><?php echo (!empty($r['customization_unit_price']) && (float)$r['customization_unit_price'] > 0) ? ('₹' . number_format((float)$r['customization_unit_price'], 2)) : '<span class="text-muted">Not set</span>'; ?></td>
                                    <td><?php $unit = (float)($r['customization_unit_price'] ?? 0); $qty = max(1, (int)($r['pqty'] ?? 1)); echo ($unit > 0) ? ('₹' . number_format($unit * $qty, 2)) : '<span class="text-muted">-</span>'; ?></td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Approved</span></td>
                                    <td class="text-muted"><?php echo date('d M, Y', strtotime($r['created_at'])); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="accepted">
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $acc = mysqli_query($con, "SELECT * FROM customization WHERE status='accepted' ORDER BY created_at DESC");
                            if ($acc && mysqli_num_rows($acc) > 0) {
                                while ($r = mysqli_fetch_assoc($acc)) { ?>
                                    <tr>
                                        <td><div class="fw-bold"><?php echo $r['name']; ?></div><small><?php echo $r['email']; ?></small></td>
                                        <td><?php echo $r['product_type']; ?></td>
                                        <td><?php echo $r['pqty']; ?></td>
                                        <td><?php echo (!empty($r['customization_unit_price']) && (float)$r['customization_unit_price'] > 0) ? ('₹' . number_format((float)$r['customization_unit_price'], 2)) : '<span class="text-muted">Not set</span>'; ?></td>
                                        <td><?php $unit = (float)($r['customization_unit_price'] ?? 0); $qty = max(1, (int)($r['pqty'] ?? 1)); echo ($unit > 0) ? ('₹' . number_format($unit * $qty, 2)) : '<span class="text-muted">-</span>'; ?></td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Approved</span></td>
                                        <td class="text-muted"><?php echo date('d M, Y', strtotime($r['created_at'])); ?></td>
                                        <td>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Cancel this customization order? This action cannot be undone.');">
                                                <input type="hidden" name="cancel_id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Order">
                                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php }
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-5">No approved requests.</td></tr>';
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="cancelled">
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cancelled = mysqli_query($con, "SELECT * FROM customization WHERE status='cancelled' ORDER BY created_at DESC");
                            if ($cancelled && mysqli_num_rows($cancelled) > 0) {
                                while ($r = mysqli_fetch_assoc($cancelled)) { ?>
                                    <tr>
                                        <td><div class="fw-bold"><?php echo htmlspecialchars($r['name'] ?? ''); ?></div><small><?php echo htmlspecialchars($r['email'] ?? ''); ?></small></td>
                                        <td><?php echo htmlspecialchars($r['product_type'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['pqty'] ?? '1'); ?></td>
                                        <td><?php echo (!empty($r['customization_unit_price']) && (float)$r['customization_unit_price'] > 0) ? ('₹' . number_format((float)$r['customization_unit_price'], 2)) : '<span class="text-muted">Not set</span>'; ?></td>
                                        <td><span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Cancelled</span></td>
                                        <td class="text-muted"><?php echo !empty($r['created_at']) ? date('d M, Y', strtotime($r['created_at'])) : '-'; ?></td>
                                    </tr>
                                <?php }
                            } else {
                                echo '<tr><td colspan="6" class="text-center py-5">No cancelled orders.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rej = mysqli_query($con, "SELECT * FROM customization WHERE status IN ('rejected','reject') ORDER BY created_at DESC");
                            if ($rej && mysqli_num_rows($rej) > 0) {
                                while ($r = mysqli_fetch_assoc($rej)) { ?>
                                    <tr>
                                        <td><div class="fw-bold"><?php echo htmlspecialchars($r['name'] ?? ''); ?></div><small><?php echo htmlspecialchars($r['email'] ?? ''); ?></small></td>
                                        <td><?php echo htmlspecialchars($r['product_type'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['pqty'] ?? '1'); ?></td>
                                        <td><span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Rejected</span></td>
                                        <td class="text-muted"><?php echo !empty($r['created_at']) ? date('d M, Y', strtotime($r['created_at'])) : '-'; ?></td>
                                    </tr>
                                <?php }
                            } else {
                                echo '<tr><td colspan="5" class="text-center py-5">No rejected requests found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>