<?php
include 'header.php';
include '../admin/conn.php';

$checkUnitPriceColumn = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE 'customization_unit_price'");
if (!$checkUnitPriceColumn || mysqli_num_rows($checkUnitPriceColumn) === 0) {
    mysqli_query($con, "ALTER TABLE customization ADD COLUMN customization_unit_price DECIMAL(10,2) NULL");
}

// Get customization ID
$customization_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$customization_id) {
    echo "<script>alert('Invalid customization request!'); window.location.href='customization.php';</script>";
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
    echo "<script>alert('Customization request not found!'); window.location.href='customization.php';</script>";
    exit;
}

$customization = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$flashMessage = '';
if (!empty($_GET['msg'])) {
    $flashMessage = urldecode((string)$_GET['msg']);
}

// Price calculation: admin sets per-unit price; fallback to estimated base price
$quantity = max(1, (int)($customization['pqty'] ?? 1));
$estimated_unit_price = 5000;
$admin_unit_price = (float)($customization['customization_unit_price'] ?? 0);
$unit_price = $admin_unit_price > 0 ? $admin_unit_price : $estimated_unit_price;
$total_price = $unit_price * $quantity;
$advance_payment = $total_price * 0.5;
$is_admin_price_set = $admin_unit_price > 0;
$status = strtolower(trim((string)($customization['status'] ?? '')));
$is_approved_by_admin = in_array($status, ['accepted', 'approved'], true);

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
        border-left: 5px solid #dc3545;
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
        color: #dc3545;
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
        color: #dc3545;
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
    .sticky-alert {
        position: sticky;
        top: 80px;
        z-index: 100;
        margin-bottom: 20px;
    }
</style>

<?php if (!$is_admin_price_set || !$is_approved_by_admin): ?>
    <!-- WAITING FOR ADMIN APPROVAL -->
    <div class="payment-container">
        <div class="card payment-card shadow-lg" style="border-left: 5px solid #ffc107;">
            <div class="card-body text-center">
                <div style="font-size: 3rem; color: #ffc107; margin-bottom: 20px; animation: spin 2s linear infinite;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <h4 class="card-title mb-3" style="color: #333;">Your Customization Request is Pending</h4>
                
                <!-- Request Details -->
                <div class="details-section">
                    <h6 class="mb-3" style="color: #333;">Your Customization Details</h6>
                    <div class="detail-row">
                        <span class="detail-label">Request ID:</span>
                        <span class="detail-value">#<?php echo htmlspecialchars($customization['id']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Product Type:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($customization['product_type']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Quantity:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($customization['pqty'] ?? 1); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="badge bg-warning text-dark rounded-pill px-3">
                                <i class="bi bi-hourglass-split me-1"></i>Pending Admin Review
                            </span>
                        </span>
                    </div>
                </div>

                <div class="alert alert-info mt-4 sticky-alert" role="alert">
                    <strong><i class="bi bi-info-circle-fill me-2"></i>Waiting for Admin Quote</strong>
                    <p class="mt-2 mb-0">
                        Our admin team is reviewing your customization request and will assign a price shortly. 
                        <br><strong>This page will automatically update when the price is assigned.</strong>
                    </p>
                </div>

                <div style="background: #e3f2fd; padding: 15px; border-radius: 7px; margin: 20px 0; color: #1565c0; border-left: 4px solid #1976d2; position: sticky; top: 160px; z-index: 99;">
                    <p class="mb-0"><strong>⏱️ What's happening now?</strong></p>
                    <ul style="text-align: left; margin: 10px 0 0 20px; padding: 10px 0;">
                        <li>Admin reviewing your request (typically 24-48 hours)</li>
                        <li>Admin will assign the price per unit</li>
                        <li>Payment form will appear automatically once price is set</li>
                        <li>You can then proceed with 50% advance payment</li>
                    </ul>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button class="btn btn-outline-secondary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh Now
                    </button>
                    <a href="myorder.php" class="btn btn-secondary">
                        <i class="bi bi-list-check me-1"></i>Go to My Orders
                    </a>
                </div>

                <p class="small text-muted mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    This page auto-refreshes every 10 seconds. Payment form will appear here when admin assigns the price.
                </p>
            </div>
        </div>
    </div>

    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>

    <script>
        // Auto-refresh page every 10 seconds to check for admin price assignment
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
<?php else: ?>
    <!-- PAYMENT FORM (ADMIN HAS SET PRICE) -->
    <div class="payment-container">
    <div class="card payment-card shadow-lg">
        <div class="card-body">
            <h4 class="card-title mb-3">
                <i class="bi bi-credit-card me-2"></i>Payment Instructions for Customization
            </h4>

            <?php if ($flashMessage !== ''): ?>
                <div class="alert alert-info" role="alert">
                    <?php echo htmlspecialchars($flashMessage); ?>
                </div>
            <?php endif; ?>

            <!-- Customization Details -->
            <div class="details-section">
                <h6 class="mb-3" style="color: #333;">Your Customization Details</h6>
                <div class="detail-row">
                    <span class="detail-label">Request ID:</span>
                    <span class="detail-value">#<?php echo htmlspecialchars($customization['id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Product Type:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customization['product_type']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customization['name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customization['email']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customization['phone']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Quantity:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($customization['pqty'] ?? 1); ?></span>
                </div>
                <div class="detail-row" style="background: #e8f5e9; padding: 12px 0; margin: 0 -15px; padding: 12px 15px; border-top: 1px solid #4caf50; border-bottom: 1px solid #4caf50;">
                    <span class="detail-label" style="color: #1b5e20;">Per Unit Price (Admin Assigned):</span>
                    <span class="detail-value" style="color: #1b5e20; font-weight: 700; font-size: 1.1rem;">
                        <i class="bi bi-check-circle-fill me-1"></i>₹<?php echo number_format($unit_price, 2); ?>
                    </span>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="alert alert-danger" role="alert">
                <strong><i class="bi bi-exclamation-circle me-2"></i>Important: 50% Advance Payment Required</strong>
                <p class="mt-2 mb-0">For trophy customization orders, 50% advance payment is compulsory to confirm your order and start the customization process.</p>
            </div>

            <!-- Amount Breakdown -->
            <div class="amount-box">
                <h5 style="margin-bottom: 8px;">Total Cost (Admin Confirmed)</h5>
                <div style="font-size: 1rem; color: #666; margin-bottom: 8px;">
                    ₹<?php echo number_format($unit_price, 2); ?> × <?php echo $quantity; ?> quantity
                </div>
                <div style="font-size: 1.3rem; color: #333; margin-bottom: 15px; font-weight: 600;">
                    = ₹<?php echo number_format($total_price, 2); ?>
                </div>
                <hr style="margin: 15px 0; opacity: 0.3;">
                <h6 style="color: #856404; margin-bottom: 10px;">50% Advance Payment Required</h6>
                <div class="amount-display">₹<?php echo number_format($advance_payment, 2); ?></div>
                <p class="small text-muted mt-2">After payment, remaining 50% (₹<?php echo number_format($total_price - $advance_payment, 2); ?>) due upon completion</p>
            </div>

            <?php if (!$is_admin_price_set): ?>
                <div class="alert alert-warning" role="alert">
                    <strong><i class="bi bi-exclamation-triangle me-2"></i>Admin final quote pending</strong>
                    <p class="mt-2 mb-0">This amount is currently estimated. Once admin sets the final per-unit price, your payable 50% advance updates automatically based on quantity.</p>
                </div>
            <?php endif; ?>

            <!-- Payment Instructions -->
            <div class="payment-instructions">
                <h6 class="mb-3" style="color: #333;">
                    <i class="bi bi-list-check me-2"></i>Payment Process & Next Steps
                </h6>

                <div class="instruction-item">
                    <strong><i class="bi bi-1-circle me-2"></i>Make the 50% Advance Payment</strong>
                    <p class="mt-2 mb-0">
                        Transfer ₹<?php echo number_format($advance_payment, 2); ?> using your preferred payment method below. 
                        This secures your customization order and allows our team to begin the work.
                    </p>
                </div>

                <div class="instruction-item">
                    <strong><i class="bi bi-2-circle me-2"></i>Payment Methods Available</strong>
                    <p class="mt-2 mb-0">
                        • <strong>UPI</strong> - Instant & Recommended<br>
                        • <strong>Bank Transfer</strong> - NEFT/IMPS/RTGS<br>
                        • <strong>Cash on Delivery</strong> - Pay when order is ready<br>
                        <em style="color: #666;">Select your preferred method below.</em>
                    </p>
                </div>

                <div class="instruction-item">
                    <strong><i class="bi bi-3-circle me-2"></i>After Payment</strong>
                    <p class="mt-2 mb-0">
                        ✓ Your order will be confirmed<br>
                        ✓ You'll receive email confirmation with details<br>
                        ✓ Our team will start customization work<br>
                        ✓ You can track progress from "My Orders"
                    </p>
                </div>

                <div class="instruction-item">
                    <strong><i class="bi bi-4-circle me-2"></i>Final Payment (After Completion)</strong>
                    <p class="mt-2 mb-0">
                        Remaining 50% amount: <strong>₹<?php echo number_format($total_price - $advance_payment, 2); ?></strong><br>
                        This will be due when the customization is completed or before final delivery.
                        We will notify you with final pictures and delivery details.
                    </p>
                </div>

                <div class="instruction-item">
                    <strong><i class="bi bi-5-circle me-2"></i>Need Help?</strong>
                    <p class="mt-2 mb-0">
                        For payment issues or questions, contact our support team:<br>
                        <strong>Email:</strong> support@sm-enterprises.com<br>
                        <strong>Phone:</strong> Contact support via website
                    </p>
                </div>
            </div>
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="payment-instructions">
                <h6 class="mb-3" style="color:#333;"><i class="bi bi-camera me-2"></i>Upload Payment Screenshot</h6>
                <form method="post" action="upload_payment_screenshot.php" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <input type="hidden" name="type" value="customization">
                    <input type="hidden" name="id" value="<?php echo (int)$customization['id']; ?>">
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
                <?php if (!empty($customization['payment_screenshot'])): ?>
                    <div class="mt-2 p-2 border rounded bg-light">
                        <p class="mb-1 small text-success"><i class="bi bi-check-circle-fill me-1"></i>Screenshot uploaded</p>
                        <a href="../productimg/payment_screenshots/<?php echo htmlspecialchars($customization['payment_screenshot']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>View Uploaded Screenshot
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2 mt-4">
                <button class="btn btn-danger btn-lg" type="button" onclick="proceedToPayment()">
                    <i class="bi bi-credit-card me-2"></i>
                    Proceed to Payment (₹<?php echo number_format($advance_payment, 2); ?>)
                </button>
                <a href="customization.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>
                    Back to Customization Form
                </a>
            </div>

            <!-- Summary Box -->
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px; border-left: 4px solid #dc3545;">
                <p class="mb-0 small" style="color: #666;">
                    <strong>Note:</strong> Your customization request has been saved. By proceeding with the 50% advance payment, 
                    you are confirming your order details. Once payment is received, our team will contact you with further updates.
                </p>
            </div>
        </div>
    </div>
    </div>

<script>
function proceedToPayment() {
    window.location.href = 'fake_payment_gateway.php?type=customization&id=<?php echo (int)$customization['id']; ?>';
}
</script>

<?php endif; ?>

<?php include 'footer.php'; ?>
