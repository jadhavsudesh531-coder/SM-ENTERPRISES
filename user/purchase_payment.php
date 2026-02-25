<?php
include 'header.php';
include '../admin/conn.php';

function ensureAdvancePaymentColumnsForUser($con)
{
    $columns = [
        ['table' => 'myorder', 'column' => 'payment_txn_id', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_txn_id VARCHAR(120) NULL"],
        ['table' => 'myorder', 'column' => 'payment_submitted_at', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_submitted_at DATETIME NULL"],
        ['table' => 'myorder', 'column' => 'payment_verified', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_verified TINYINT(1) NOT NULL DEFAULT 0"],
        ['table' => 'myorder', 'column' => 'payment_verified_at', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_verified_at DATETIME NULL"],
        ['table' => 'purchase', 'column' => 'payment_txn_id', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_txn_id VARCHAR(120) NULL"],
        ['table' => 'purchase', 'column' => 'payment_submitted_at', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_submitted_at DATETIME NULL"],
        ['table' => 'purchase', 'column' => 'payment_verified', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_verified TINYINT(1) NOT NULL DEFAULT 0"],
        ['table' => 'purchase', 'column' => 'payment_verified_at', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_verified_at DATETIME NULL"]
    ];

    foreach ($columns as $col) {
        $check = mysqli_query($con, "SHOW COLUMNS FROM {$col['table']} LIKE '{$col['column']}'");
        if (!$check || mysqli_num_rows($check) === 0) {
            mysqli_query($con, $col['sql']);
        }
    }
}

ensureAdvancePaymentColumnsForUser($con);

$message = '';
$messageType = 'success';

if (!empty($_GET['msg'])) {
    $message = urldecode((string)$_GET['msg']);
    $messageType = 'info';
}

// Get order ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    echo "<script>alert('Invalid order!'); window.location.href='view_product.php';</script>";
    exit;
}

// Fetch order details
$stmt = mysqli_prepare($con, "SELECT * FROM myorder WHERE order_id = ? LIMIT 1");
if (!$stmt) {
    die('Prepare failed: ' . mysqli_error($con));
}

mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "<script>alert('Order not found!'); window.location.href='view_product.php';</script>";
    exit;
}

$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle transaction ID submission by customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_txn_id'])) {
    $txnId = trim($_POST['payment_txn_id'] ?? '');
    $sessionUser = $_SESSION['username'] ?? '';

    if ($txnId === '' || strlen($txnId) < 5) {
        $message = 'Please enter a valid transaction ID.';
        $messageType = 'danger';
    } elseif ($sessionUser === '' || strtolower((string)$order['user']) !== strtolower((string)$sessionUser)) {
        $message = 'You are not authorized to submit payment for this order.';
        $messageType = 'danger';
    } else {
        $txnSafe = mysqli_real_escape_string($con, $txnId);
        $orderIdInt = (int)$order['order_id'];

        $u1 = mysqli_query($con, "UPDATE myorder SET payment_txn_id='$txnSafe', payment_submitted_at=NOW(), payment_verified=0, payment_verified_at=NULL WHERE order_id=$orderIdInt");
        $u2 = mysqli_query($con, "UPDATE purchase SET payment_txn_id='$txnSafe', payment_submitted_at=NOW(), payment_verified=0, payment_verified_at=NULL WHERE order_id=$orderIdInt");

        if ($u1 && $u2) {
            $message = 'Payment transaction ID submitted successfully. Admin will verify and confirm your order.';
            $messageType = 'success';
            $order['payment_txn_id'] = $txnId;
            $order['payment_submitted_at'] = date('Y-m-d H:i:s');
            $order['payment_verified'] = 0;
        } else {
            $message = 'Could not submit transaction ID. Please try again.';
            $messageType = 'danger';
        }
    }
}

// Calculate 50% advance payment and remaining amount
$total_price = floatval($order['pprice']) * intval($order['pqty']);
$advance_payment = $total_price * 0.5;
$remaining_payment = $total_price * 0.5;

$merchant_upi_id = '9076484862@ptsbi';
$merchant_name = 'SM ENTERPRISES';
$payment_note = 'Order #' . ($order['order_id'] ?? $order_id) . ' Advance 50%';
$upi_payload = 'upi://pay?pa=' . rawurlencode($merchant_upi_id)
    . '&pn=' . rawurlencode($merchant_name)
    . '&am=' . rawurlencode(number_format($advance_payment, 2, '.', ''))
    . '&cu=INR'
    . '&tn=' . rawurlencode($payment_note);
$qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($upi_payload);

?>

<style>
    .payment-container {
        min-height: calc(100vh - 120px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 0;
    }
    .payment-card {
        width: 700px;
        max-width: 96vw;
        border-left: 5px solid #ffc107;
    }
    .payment-instructions {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .instruction-item {
        padding: 10px 0;
        border-bottom: 1px solid #dee2e6;
    }
    .instruction-item:last-child {
        border-bottom: none;
    }
    .instruction-item strong {
        color: #ffc107;
    }
    .amount-box {
        background: #fff3cd;
        border: 2px solid #ffc107;
        padding: 20px;
        border-radius: 5px;
        margin: 20px 0;
        text-align: center;
    }
    .amount-box h5 {
        color: #856404;
        margin-bottom: 10px;
    }
    .amount-display {
        font-size: 2rem;
        font-weight: bold;
        color: #ffc107;
    }
    .qr-box {
        border: 2px dashed #ffc107;
        background: #fffdf5;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        margin: 20px 0;
    }
    .qr-img {
        width: 260px;
        height: 260px;
        max-width: 100%;
        border-radius: 8px;
        background: #fff;
        border: 1px solid #eee;
        padding: 8px;
    }
    .details-section {
        background: #f0f0f0;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e0e0e0;
    }
    .detail-row:last-child {
        border-bottom: none;
    }
    .detail-label {
        font-weight: 600;
        color: #333;
    }
    .detail-value {
        color: #666;
    }
</style>

<div class="payment-container">
    <div class="card payment-card shadow-lg">
        <div class="card-body">
            <h4 class="card-title mb-3">
                <i class="bi bi-credit-card me-2"></i>Payment Instructions for Product Order
            </h4>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Order Details -->
            <div class="details-section">
                <h6 class="mb-3" style="color: #333;">Your Order Details</h6>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value">#<?php echo htmlspecialchars($order['order_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Product Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['pname']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Customer Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['user']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Unit Price:</span>
                    <span class="detail-value">₹<?php echo number_format($order['pprice'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Quantity:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['pqty']); ?></span>
                </div>
                <div class="detail-row" style="border-bottom: 2px solid #999 !important;">
                    <span class="detail-label"><strong>Total Amount:</strong></span>
                    <span class="detail-value"><strong>₹<?php echo number_format($total_price, 2); ?></strong></span>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="alert alert-warning" role="alert">
                <strong><i class="bi bi-exclamation-circle me-2"></i>Important: 50% Advance Payment Required</strong>
                <p class="mt-2 mb-0">For this high-value product order, 50% advance payment is compulsory to confirm your order and proceed with processing.</p>
            </div>

            <!-- Amount Breakdown -->
            <div class="amount-box">
                <h5>Total Order Amount</h5>
                <div style="font-size: 1.2rem; color: #666; margin-bottom: 15px;">
                    ₹<?php echo number_format($total_price, 2); ?>
                </div>
                <h6 style="color: #856404; margin-bottom: 10px;">50% Advance Payment Due Now</h6>
                <div class="amount-display">₹<?php echo number_format($advance_payment, 2); ?></div>
                <p class="mt-2 mb-0" style="color: #856404; font-size: 0.95rem;">
                    Remaining: ₹<?php echo number_format($remaining_payment, 2); ?> (Due upon delivery)
                </p>
            </div>

            <!-- QR Payment Section -->
            <div class="qr-box">
                <h6 class="mb-3" style="color:#856404;"><i class="bi bi-qr-code-scan me-2"></i>Scan QR to Pay 50% Advance (Compulsory)</h6>
                <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="Advance Payment QR" class="qr-img mb-3">
                <p class="mb-1"><strong>UPI ID:</strong> <?php echo htmlspecialchars($merchant_upi_id); ?></p>
                <p class="mb-1"><strong>Amount:</strong> ₹<?php echo number_format($advance_payment, 2); ?></p>
                <p class="mb-0 text-muted small">After payment, share transaction ID with admin/support for confirmation.</p>
            </div>

            <div class="payment-instructions">
                <h6 class="mb-3" style="color:#333;"><i class="bi bi-receipt me-2"></i>Submit Payment Transaction ID</h6>
                <form method="post" class="row g-2 align-items-end">
                    <div class="col-md-9">
                        <label class="form-label">UPI Transaction ID / Reference ID</label>
                        <input type="text" class="form-control" name="payment_txn_id" placeholder="Enter UPI transaction ID" value="<?php echo htmlspecialchars($order['payment_txn_id'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" name="submit_txn_id" class="btn btn-success">
                            <i class="bi bi-send-check me-1"></i>Submit
                        </button>
                    </div>
                </form>
                <?php if (!empty($order['payment_txn_id'])): ?>
                    <p class="mt-2 mb-0 small text-success">
                        Submitted TXN: <strong><?php echo htmlspecialchars($order['payment_txn_id']); ?></strong>
                        <?php if (!empty($order['payment_submitted_at'])): ?>
                            on <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($order['payment_submitted_at']))); ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="payment-instructions">
                <h6 class="mb-3" style="color:#333;"><i class="bi bi-camera me-2"></i>Upload Payment Screenshot</h6>
                <form method="post" action="upload_payment_screenshot.php" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <input type="hidden" name="type" value="product">
                    <input type="hidden" name="id" value="<?php echo (int)$order['order_id']; ?>">
                    <div class="col-md-9">
                        <label class="form-label">Payment Screenshot / Proof (Max 5MB)</label>
                        <input type="file" class="form-control" name="payment_screenshot" accept="image/*" required>
                        <small class="text-muted">Accepted: JPG, PNG, GIF, WEBP</small>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i>Upload
                        </button>
                    </div>
                </form>
                <?php if (!empty($order['payment_screenshot'])): ?>
                    <div class="mt-2 p-2 border rounded bg-light">
                        <p class="mb-1 small text-success"><i class="bi bi-check-circle-fill me-1"></i>Screenshot uploaded</p>
                        <a href="../productimg/payment_screenshots/<?php echo htmlspecialchars($order['payment_screenshot']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>View Uploaded Screenshot
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment Instructions -->
            <div class="payment-instructions">
                <h6 class="mb-3" style="color: #333;">
                    <i class="bi bi-list-check me-2"></i>Payment Instructions
                </h6>

                <div class="instruction-item">
                    <strong><i class="bi bi-1-circle me-2"></i>Make the Advance Payment</strong>
                    <p class="mt-2 mb-0">
                        Transfer 50% of the order amount (₹<?php echo number_format($advance_payment, 2); ?>) as advance payment. 
                        This confirms your order and prioritizes processing.
                    </p>
                </div>

                <div class="instruction-item">
                    <strong><i class="bi bi-2-circle me-2"></i>Payment Methods Available</strong>
                    <p class="mt-2 mb-0">
                        • Bank Transfer / UPI Transfer<br>
                        • Credit/Debit Card (if enabled)<br>
                        • Payment Gateway (Online)<br>
                        <em style="color: #666;">Please contact our support team for exact payment details.</em>
                    </p>
                </div>

                <div class="instruction-item">
                    <strong><i class="bi bi-3-circle me-2"></i>Order Confirmation & Dispatch</strong>
                    <p class="mt-2 mb-0">
                        Once we receive your advance payment, we will:<br>
                        ✓ Confirm your order via email<br>
                        ✓ Prepare product for dispatch<br>
                        ✓ Send tracking information<br>
                        ✓ Collect remaining payment before/upon delivery
                    </p>
                </div>

                <div class="instruction-item">
                    <strong><i class="bi bi-4-circle me-2"></i>Final Payment</strong>
                    <p class="mt-2 mb-0">
                        The remaining 50% will be collected:<br>
                        • Upon delivery (Cash on Delivery)<br>
                        • Before dispatch (Online payment)<br>
                        We will notify you about payment method options.
                    </p>
                </div>

                <div class="instruction-item">
                    <strong><i class="bi bi-5-circle me-2"></i>Cancellation Policy</strong>
                    <p class="mt-2 mb-0">
                        • Cancellation after receiving advance payment will incur a 10-15% cancellation fee<br>
                        • For order modifications, please contact support within 24 hours<br>
                        • We aim to provide excellent customer service
                    </p>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="alert alert-info" role="alert">
                <strong><i class="bi bi-info-circle me-2"></i>Need Help?</strong>
                <p class="mt-2 mb-0">
                    If you have any questions about this order or need payment assistance:<br>
                    <strong>Email:</strong> support@sm-enterprises.com<br>
                    <strong>Phone:</strong> Contact our support team through the website
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2 mt-4">
                <button class="btn btn-warning btn-lg" type="button" onclick="proceedToPayment()">
                    <i class="bi bi-credit-card me-2"></i>
                    Proceed to Payment (₹<?php echo number_format($advance_payment, 2); ?>)
                </button>
                <a href="view_product.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>
                    Continue Shopping
                </a>
            </div>

            <!-- Summary Box -->
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px; border-left: 4px solid #ffc107;">
                <p class="mb-0 small" style="color: #666;">
                    <strong>Note:</strong> Your order has been saved with ID #<?php echo htmlspecialchars($order['order_id']); ?>. 
                    By proceeding with the 50% advance payment, you are confirming your product order. 
                    Once payment is received, we will begin processing and shipping your order.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function proceedToPayment() {
    window.location.href = 'fake_payment_gateway.php?type=product&id=<?php echo (int)$order['order_id']; ?>';
}
</script>

<?php include 'footer.php'; ?>
