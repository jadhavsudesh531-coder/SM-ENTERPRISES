<?php
include 'header.php';
include '../admin/conn.php';

$username = $_SESSION['username'] ?? null;
if (empty($username)) {
    header('Location: login.php');
    exit;
}

$customization_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$customization_id || !$order_id) {
    echo "<script>alert('Invalid request!'); window.location.href='myorder.php';</script>";
    exit;
}

// Fetch customization details
$stmt = mysqli_prepare($con, "SELECT * FROM customization WHERE id = ? LIMIT 1");
if (!$stmt) {
    die('Prepare failed: ' . mysqli_error($con));
}

mysqli_stmt_bind_param($stmt, 'i', $customization_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "<script>alert('Customization not found!'); window.location.href='myorder.php';</script>";
    exit;
}

$customization = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Verify user owns this customization
if (strtolower((string)$customization['email']) !== strtolower($username)) {
    echo "<script>alert('Unauthorized access!'); window.location.href='myorder.php';</script>";
    exit;
}

// Calculate remaining amount
$quantity = max(1, (int)($customization['pqty'] ?? 1));
$unit_price = (float)($customization['customization_unit_price'] ?? 0);
if ($unit_price <= 0) {
    $unit_price = 5000;
}
$total_price = $unit_price * $quantity;
$advance_paid = $total_price * 0.5;
$remaining_balance = $total_price - $advance_paid;

$flashMessage = '';
if (!empty($_GET['msg'])) {
    $flashMessage = urldecode((string)$_GET['msg']);
}

?>

<style>
    .payment-container {
        min-height: calc(100vh - 120px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 0;
    }
    .remaining-payment-card {
        width: 700px;
        max-width: 96vw;
        border-left: 5px solid #ff9800;
    }
    .progress-indicator {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    .progress-step {
        flex: 1;
        text-align: center;
        position: relative;
    }
    .progress-step::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 3px;
        background: #e0e0e0;
        top: 20px;
        left: 50%;
        z-index: 0;
    }
    .progress-step:last-child::after {
        display: none;
    }
    .progress-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e0e0e0;
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
        font-weight: bold;
        color: #666;
    }
    .progress-step.completed .progress-circle {
        background: #28a745;
        color: white;
    }
    .progress-step.active .progress-circle {
        background: #ff9800;
        color: white;
        box-shadow: 0 0 0 6px rgba(255, 152, 0, 0.2);
    }
    .progress-label {
        font-size: 0.85rem;
        color: #666;
        margin-top: 8px;
    }
    .amount-display-large {
        font-size: 2.5rem;
        font-weight: bold;
        color: #ff9800;
        margin: 20px 0;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #eee;
    }
    .summary-row:last-child {
        border-bottom: none;
    }
    .highlight-box {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        border-left: 4px solid #ff9800;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }
</style>

<div class="payment-container">
    <div class="card remaining-payment-card shadow-lg">
        <div class="card-body">
            <h4 class="card-title mb-4">
                <i class="bi bi-credit-card me-2" style="color: #ff9800;"></i>Complete Your Customization Payment
            </h4>

            <!-- Progress Indicator -->
            <div class="progress-indicator">
                <div class="progress-step completed">
                    <div class="progress-circle">✓</div>
                    <div class="progress-label">50% Paid</div>
                </div>
                <div class="progress-step active">
                    <div class="progress-circle">2</div>
                    <div class="progress-label">Remaining 50%</div>
                </div>
                <div class="progress-step">
                    <div class="progress-circle">3</div>
                    <div class="progress-label">Completed</div>
                </div>
            </div>

            <?php if ($flashMessage !== ''): ?>
                <div class="alert alert-info" role="alert">
                    <?php echo htmlspecialchars($flashMessage); ?>
                </div>
            <?php endif; ?>

            <!-- Payment Summary -->
            <div class="highlight-box">
                <h6 style="color: #e65100; margin-bottom: 15px;"><i class="bi bi-info-circle me-2"></i>Payment Summary</h6>
                <div class="summary-row">
                    <span>Total Order Value:</span>
                    <span style="font-weight: 600;">₹<?php echo number_format($total_price, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>50% Already Paid:</span>
                    <span style="font-weight: 600; color: #28a745;">₹<?php echo number_format($advance_paid, 2); ?></span>
                </div>
                <div class="summary-row" style="border: none; padding-top: 15px; margin-top: 10px; border-top: 2px solid #ff9800;">
                    <span style="font-size: 1.1rem; font-weight: 600;">Remaining Balance Due:</span>
                    <span class="amount-display-large">₹<?php echo number_format($remaining_balance, 2); ?></span>
                </div>
            </div>

            <!-- Order Details -->
            <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h6 style="color: #333; margin-bottom: 12px;">Order Details</h6>
                <div class="summary-row" style="border-bottom: 1px solid #ddd;">
                    <span>Order ID:</span>
                    <strong>#<?php echo htmlspecialchars($order_id); ?></strong>
                </div>
                <div class="summary-row" style="border-bottom: 1px solid #ddd;">
                    <span>Request ID:</span>
                    <strong>#<?php echo htmlspecialchars($customization_id); ?></strong>
                </div>
                <div class="summary-row" style="border-bottom: 1px solid #ddd;">
                    <span>Product:</span>
                    <strong><?php echo htmlspecialchars($customization['product_type']); ?></strong>
                </div>
                <div class="summary-row">
                    <span>Quantity:</span>
                    <strong><?php echo htmlspecialchars($customization['pqty'] ?? 1); ?></strong>
                </div>
            </div>

            <!-- Payment Instructions -->
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                <strong style="color: #1565c0;"><i class="bi bi-exclamation-circle me-2"></i>Complete Your Payment</strong>
                <p style="margin-top: 10px; margin-bottom: 0; font-size: 0.95rem; color: #424242;">
                    Your customization work is almost complete. Pay the remaining ₹<?php echo number_format($remaining_balance, 2); ?> now to finalize your order and receive the final product.
                </p>
            </div>

            <!-- Payment Method Selection -->
            <form method="post" action="remaining_payment_gateway.php" class="mt-4">
                <input type="hidden" name="customization_id" value="<?php echo (int)$customization_id; ?>">
                <input type="hidden" name="order_id" value="<?php echo (int)$order_id; ?>">

                <label class="form-label fw-semibold">Choose Payment Method</label>
                <div class="border rounded p-3 mb-4">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="methodUpi" value="upi" checked>
                        <label class="form-check-label" for="methodUpi">
                            <strong>UPI</strong> <span class="text-muted">(Instant & Recommended)</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="methodBank" value="bank_transfer">
                        <label class="form-check-label" for="methodBank">
                            <strong>Bank Transfer</strong> <span class="text-muted">(NEFT/IMPS/RTGS)</span>
                        </label>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-warning btn-lg" style="background: linear-gradient(135deg, #ff9800, #f57c00); border: none; color: white; font-weight: 600;">
                        <i class="bi bi-credit-card me-2"></i>Proceed to Payment - ₹<?php echo number_format($remaining_balance, 2); ?>
                    </button>
                    <a href="myorder.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to My Orders
                    </a>
                </div>
            </form>

            <!-- Note -->
            <p class="small text-muted mt-4 text-center">
                <i class="bi bi-shield-check me-1"></i>Your payment is secure and encrypted. After successful payment, your order will be confirmed for delivery.
            </p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
