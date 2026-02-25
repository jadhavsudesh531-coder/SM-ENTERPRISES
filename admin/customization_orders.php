<?php
include 'header.php';
include 'conn.php';

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
    $status = 'confirmed';
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

// Check if user is admin (you may need to adjust based on your auth system)
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

ensureCustomizationPricingColumn($con);

$message = '';
$message_type = '';

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $customization_id = intval($_POST['customization_id'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? '';
    $unit_price_input = trim((string)($_POST['unit_price'] ?? ''));
    $unit_price_value = null;

    if ($unit_price_input !== '') {
        if (!is_numeric($unit_price_input) || (float)$unit_price_input <= 0) {
            $message = "Please enter a valid per-unit price greater than 0.";
            $message_type = 'danger';
        } else {
            $unit_price_value = round((float)$unit_price_input, 2);
        }
    }
    
    if ($message === '' && $customization_id && in_array($payment_status, ['pending', 'partial_paid', 'confirmed', 'in_progress', 'ready_for_pickup', 'completed', 'cancelled'])) {
        if ($unit_price_input !== '') {
            $stmt = mysqli_prepare($con, "UPDATE customization SET status = ?, customization_unit_price = ? WHERE id = ?");
        } else {
            $stmt = mysqli_prepare($con, "UPDATE customization SET status = ? WHERE id = ?");
        }
        if ($stmt) {
            if ($unit_price_input !== '') {
                mysqli_stmt_bind_param($stmt, 'sdi', $payment_status, $unit_price_value, $customization_id);
            } else {
                mysqli_stmt_bind_param($stmt, 'si', $payment_status, $customization_id);
            }
            if (mysqli_stmt_execute($stmt)) {
                $syncStatuses = ['partial_paid', 'confirmed', 'in_progress', 'ready_for_pickup', 'completed'];
                $shouldSync = in_array($payment_status, $syncStatuses, true);
                if ($shouldSync) {
                    $linked = ensureCustomizationPurchaseOrder($con, $customization_id);
                    if ($linked && $unit_price_input !== '') {
                        $updatePurchasePrice = mysqli_prepare($con, "UPDATE purchase SET pprice=? WHERE customization_id=?");
                        if ($updatePurchasePrice) {
                            mysqli_stmt_bind_param($updatePurchasePrice, 'di', $unit_price_value, $customization_id);
                            mysqli_stmt_execute($updatePurchasePrice);
                            mysqli_stmt_close($updatePurchasePrice);
                        }

                        $updateMyorderPrice = mysqli_prepare($con, "UPDATE myorder SET pprice=? WHERE customization_id=?");
                        if ($updateMyorderPrice) {
                            mysqli_stmt_bind_param($updateMyorderPrice, 'di', $unit_price_value, $customization_id);
                            mysqli_stmt_execute($updateMyorderPrice);
                            mysqli_stmt_close($updateMyorderPrice);
                        }
                    }

                    if ($linked) {
                        $message = "Payment status updated and linked to delivery orders successfully!";
                        $message_type = 'success';
                    } else {
                        $message = "Payment status updated, but failed to create linked delivery order.";
                        $message_type = 'warning';
                    }
                } else {
                    $message = "Payment status updated successfully!";
                    $message_type = 'success';
                }
            } else {
                $message = "Error updating status: " . mysqli_stmt_error($stmt);
                $message_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px 0;
        margin-bottom: 30px;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .status-pending { background: #ffc107; color: #333; }
    .status-partial_paid { background: #17a2b8; color: white; }
    .status-confirmed { background: #28a745; color: white; }
    .status-in_progress { background: #007bff; color: white; }
    .status-ready_for_pickup { background: #6f42c1; color: white; }
    .status-completed { background: #20c997; color: white; }
    .status-cancelled { background: #dc3545; color: white; }
    .customization-table { font-size: 0.95rem; }
    .action-buttons { white-space: nowrap; }
    .card-header-custom {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        padding: 15px;
    }
</style>

<div class="page-header">
    <div class="container-fluid">
        <h2><i class="bi bi-pencil-square me-2"></i>Customization Order Management</h2>
        <p class="mb-0">Manage customization orders and payment status.</p>
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
        $statuses = ['pending', 'partial_paid', 'confirmed', 'in_progress', 'completed'];
        $status_labels = [
            'pending' => 'Awaiting Payment',
            'partial_paid' => '50% Paid',
            'confirmed' => 'Confirmed',
            'in_progress' => 'In Progress',
            'completed' => 'Completed'
        ];

        foreach ($statuses as $status) {
            $count_query = "SELECT COUNT(*) as count FROM customization WHERE status = '$status'";
            $result = mysqli_query($con, $count_query);
            $count = mysqli_fetch_assoc($result)['count'];
            $icons = [
                'pending' => 'clock',
                'partial_paid' => 'credit-card',
                'confirmed' => 'check-circle',
                'in_progress' => 'hourglass-split',
                'completed' => 'check2-all'
            ];
        ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-<?php echo $icons[$status]; ?>" style="font-size: 2rem; color: #667eea;"></i>
                        <h6 class="mt-3 mb-1"><?php echo $status_labels[$status]; ?></h6>
                        <h3 class="text-primary"><?php echo $count; ?></h3>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Customization Orders Table -->
    <div class="card shadow-sm">
        <div class="card-header card-header-custom">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>All Customization Requests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover customization-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Request ID</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Product Type</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Status</th>
                            <th>Submitted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM customization ORDER BY created_at DESC";
                        $result = mysqli_query($con, $sql);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $status_class = 'status-' . str_replace(' ', '_', $row['status']);
                        ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($row['id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['pqty'] ?? 1); ?></td>
                                    <td>
                                        <?php if (!empty($row['customization_unit_price']) && (float)$row['customization_unit_price'] > 0): ?>
                                            ₹<?php echo number_format((float)$row['customization_unit_price'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $row['id']; ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $row['id']; ?>">
                                            <i class="bi bi-pencil"></i> Update
                                        </button>
                                    </td>
                                </tr>

                                <!-- Details Modal -->
                                <div class="modal fade" id="detailsModal<?php echo $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title">Customization Request #<?php echo htmlspecialchars($row['id']); ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Customer Name:</strong><br><?php echo htmlspecialchars($row['name']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Phone:</strong><br><?php echo htmlspecialchars($row['phone']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Email:</strong><br><?php echo htmlspecialchars($row['email']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Product Type:</strong><br><?php echo htmlspecialchars($row['product_type']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Quantity:</strong><br><?php echo htmlspecialchars($row['pqty'] ?? 1); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Current Status:</strong><br>
                                                            <span class="status-badge status-<?php echo str_replace(' ', '_', $row['status']); ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <p><strong>Description/Requirements:</strong></p>
                                                    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
                                                        <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                                                    </div>
                                                </div>
                                                <?php if (!empty($row['image_path'])): ?>
                                                    <div class="mb-3">
                                                        <p><strong>Reference Image:</strong></p>
                                                        <img src="<?php echo htmlspecialchars('../' . $row['image_path']); ?>" alt="Reference" style="max-width: 100%; max-height: 300px; border-radius: 5px; border: 1px solid #ddd;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mb-3">
                                                    <p><strong>Submitted on:</strong><br><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></p>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $row['id']; ?>" data-bs-dismiss="modal">
                                                    Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Update Status Modal -->
                                <div class="modal fade" id="updateModal<?php echo $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title">Update Status - Request #<?php echo htmlspecialchars($row['id']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-info mb-3">
                                                        <strong>Payment Tracking:</strong> This customization requires 50% advance payment before work begins.
                                                    </div>
                                                    <input type="hidden" name="action" value="update_payment">
                                                    <input type="hidden" name="customization_id" value="<?php echo $row['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label"><strong>Update Status to:</strong></label>
                                                        <select name="payment_status" class="form-select" required>
                                                            <option value="">-- Select Status --</option>
                                                            <option value="pending" <?php echo $row['status'] === 'pending' ? 'selected' : ''; ?>>
                                                                Pending (Awaiting Payment)
                                                            </option>
                                                            <option value="partial_paid" <?php echo $row['status'] === 'partial_paid' ? 'selected' : ''; ?>>
                                                                Partial Paid (50% Received)
                                                            </option>
                                                            <option value="confirmed" <?php echo $row['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                                                Confirmed (Ready to Start)
                                                            </option>
                                                            <option value="in_progress" <?php echo $row['status'] === 'in_progress' ? 'selected' : ''; ?>>
                                                                In Progress (Being Customized)
                                                            </option>
                                                            <option value="ready_for_pickup" <?php echo $row['status'] === 'ready_for_pickup' ? 'selected' : ''; ?>>
                                                                Ready for Pickup
                                                            </option>
                                                            <option value="completed" <?php echo $row['status'] === 'completed' ? 'selected' : ''; ?>>
                                                                Completed (Delivered)
                                                            </option>
                                                            <option value="cancelled" <?php echo $row['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                                Cancelled
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label"><strong>Per Product Price (₹):</strong></label>
                                                        <input type="number" class="form-control" name="unit_price" min="1" step="0.01" value="<?php echo htmlspecialchars((string)($row['customization_unit_price'] ?? '')); ?>" placeholder="Enter per-unit customization price">
                                                        <small class="text-muted">Total amount shown to user = Unit price × Quantity. Advance shown = 50% of total.</small>
                                                    </div>
                                                    <div class="alert alert-warning">
                                                        <small><strong>Note:</strong> When status changes to "Partial Paid" or higher, the 50% advance payment should have been verified.</small>
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
                            echo '<tr><td colspan="8" class="text-center text-muted py-4">No customization requests found</td></tr>';
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
                        <p><strong>Partial Paid:</strong> 50% advance payment has been received</p>
                        <p><strong>Confirmed:</strong> Ready to start customization work</p>
                        <p><strong>In Progress:</strong> Currently being customized</p>
                        <p><strong>Ready for Pickup:</strong> Custom item is complete and ready</p>
                        <p><strong>Completed:</strong> Delivered to customer</p>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-exclamation-circle me-2"></i>Important Notes</h6>
                    <small>
                        <p>✓ 50% advance payment is <strong>compulsory</strong> for all customization orders</p>
                        <p>✓ Verify payment receipt before starting customization work</p>
                        <p>✓ Send regular updates to customers about their order status</p>
                        <p>✓ Collect remaining 50% before final delivery</p>
                        <p>✓ Use the "View" button to see all order details and reference images</p>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
