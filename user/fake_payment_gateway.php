<?php
session_start();
include '../admin/conn.php';

if (!isset($_SESSION['is_login'])) {
    header('location:login.php');
    exit;
}

function ensureProductPaymentColumns($con)
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

function ensureCustomizationPaymentColumns($con)
{
    $columns = [
        ['column' => 'payment_txn_id', 'sql' => "ALTER TABLE customization ADD COLUMN payment_txn_id VARCHAR(120) NULL"],
        ['column' => 'payment_submitted_at', 'sql' => "ALTER TABLE customization ADD COLUMN payment_submitted_at DATETIME NULL"],
        ['column' => 'payment_verified', 'sql' => "ALTER TABLE customization ADD COLUMN payment_verified TINYINT(1) NOT NULL DEFAULT 0"],
        ['column' => 'payment_verified_at', 'sql' => "ALTER TABLE customization ADD COLUMN payment_verified_at DATETIME NULL"],
        ['column' => 'payment_screenshot', 'sql' => "ALTER TABLE customization ADD COLUMN payment_screenshot VARCHAR(255) NULL"]
    ];

    foreach ($columns as $col) {
        $check = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE '{$col['column']}'");
        if (!$check || mysqli_num_rows($check) === 0) {
            mysqli_query($con, $col['sql']);
        }
    }
}

function fakeTxnId()
{
    return 'FAKE-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

$type = strtolower(trim((string)($_GET['type'] ?? $_POST['type'] ?? '')));
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$user = $_SESSION['username'] ?? '';
$pageError = '';

if (!in_array($type, ['product', 'customization'], true) || $id <= 0 || $user === '') {
    $pageError = 'Invalid fake payment request.';
}

$record = null;
$amount = 0.0;
$returnUrl = '';
$upiId = '9076484862@ptsbi';
$bankName = 'Pranav Sambhaji Patil';
$bankAccountNo = '43850664226';
$bankIfsc = 'SBIN0018360';

if ($pageError === '' && $type === 'product') {
    ensureProductPaymentColumns($con);

    $stmt = mysqli_prepare($con, "SELECT order_id, pqty, pprice, user, status FROM myorder WHERE order_id=? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $record = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }

    if (!$record || strtolower((string)$record['user']) !== strtolower($user)) {
        $pageError = 'Order not found or unauthorized.';
    }

    if ($pageError === '') {
        $totalAmount = (float)$record['pprice'] * (int)$record['pqty'];
        // Orders >= 1000 require 50% advance, orders < 1000 require full payment
        if ($totalAmount >= 1000) {
            $amount = $totalAmount * 0.5;
        } else {
            $amount = $totalAmount;
        }
        $returnUrl = 'purchase_payment.php?id=' . $id;
    }
} elseif ($pageError === '') {
    ensureCustomizationPaymentColumns($con);

    $stmt = mysqli_prepare($con, "SELECT id, email, pqty, customization_unit_price, status FROM customization WHERE id=? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $record = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }

    if (!$record || strtolower((string)$record['email']) !== strtolower($user)) {
        $pageError = 'Customization request not found or unauthorized.';
    }

    if ($pageError === '') {
        $unitPrice = (float)($record['customization_unit_price'] ?? 0);
        if ($unitPrice <= 0) {
            $unitPrice = 5000;
        }
        $amount = ($unitPrice * max(1, (int)($record['pqty'] ?? 1))) * 0.5;
        $returnUrl = 'customization_payment.php?id=' . $id;
    }
}

if ($pageError === '' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fake_action'])) {
    $action = strtolower(trim((string)$_POST['fake_action']));
    $paymentMethod = strtolower(trim((string)($_POST['payment_method'] ?? 'upi')));

    if (!in_array($paymentMethod, ['upi', 'bank_transfer', 'cod'], true)) {
        $paymentMethod = 'upi';
    }

    if ($action === 'cancel') {
        header('Location: ' . $returnUrl . '&msg=' . urlencode('Payment cancelled.'));
        exit;
    }

    if ($action === 'cod') {
        $codTxnId = 'COD-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));

        if ($type === 'product') {
            mysqli_begin_transaction($con);
            $safeTxn = mysqli_real_escape_string($con, $codTxnId);
            $ok1 = mysqli_query($con, "UPDATE myorder SET payment_txn_id='$safeTxn', payment_submitted_at=NOW(), payment_verified=0, payment_verified_at=NULL, status='confirmed' WHERE order_id=" . (int)$id);
            $ok2 = mysqli_query($con, "UPDATE purchase SET payment_txn_id='$safeTxn', payment_submitted_at=NOW(), payment_verified=0, payment_verified_at=NULL, status='confirmed' WHERE order_id=" . (int)$id);

            if ($ok1 && $ok2) {
                mysqli_commit($con);
                header('Location: ' . $returnUrl . '&msg=' . urlencode('Cash on Delivery selected. Please pay at delivery time.'));
            } else {
                mysqli_rollback($con);
                header('Location: ' . $returnUrl . '&msg=' . urlencode('Could not save COD selection. Please try again.'));
            }
            exit;
        }
        // COD not allowed for customization - redirect back
        header('Location: ' . $returnUrl . '&msg=' . urlencode('Payment method not available for customization orders.'));
        exit;
    }

    if ($action === 'pay') {
        $txnId = fakeTxnId();
        $methodLabel = $paymentMethod === 'bank_transfer' ? 'Bank Transfer' : 'UPI';

        if ($type === 'product') {
            mysqli_begin_transaction($con);
            $ok1 = mysqli_query($con, "UPDATE myorder SET payment_txn_id='" . mysqli_real_escape_string($con, $txnId) . "', payment_submitted_at=NOW(), payment_verified=1, payment_verified_at=NOW(), status='confirmed' WHERE order_id=" . (int)$id);
            $ok2 = mysqli_query($con, "UPDATE purchase SET payment_txn_id='" . mysqli_real_escape_string($con, $txnId) . "', payment_submitted_at=NOW(), payment_verified=1, payment_verified_at=NOW(), status='confirmed' WHERE order_id=" . (int)$id);

            if ($ok1 && $ok2) {
                mysqli_commit($con);
                header('Location: ' . $returnUrl . '&msg=' . urlencode($methodLabel . ' payment successful. TXN: ' . $txnId));
            } else {
                mysqli_rollback($con);
                header('Location: ' . $returnUrl . '&msg=' . urlencode('Payment completed but could not save status.'));
            }
            exit;
        }

        mysqli_begin_transaction($con);
        $safeTxn = mysqli_real_escape_string($con, $txnId);
        $ok1 = mysqli_query($con, "UPDATE customization SET status='partial_paid', payment_txn_id='$safeTxn', payment_submitted_at=NOW(), payment_verified=1, payment_verified_at=NOW() WHERE id=" . (int)$id);
        $ok2 = mysqli_query($con, "UPDATE purchase SET status='confirmed', payment_txn_id='$safeTxn', payment_submitted_at=NOW(), payment_verified=1, payment_verified_at=NOW() WHERE customization_id=" . (int)$id);
        $ok3 = mysqli_query($con, "UPDATE myorder SET status='confirmed', payment_txn_id='$safeTxn', payment_submitted_at=NOW(), payment_verified=1, payment_verified_at=NOW() WHERE customization_id=" . (int)$id);

        if ($ok1) {
            mysqli_commit($con);
            header('Location: ' . $returnUrl . '&msg=' . urlencode($methodLabel . ' payment successful. TXN: ' . $txnId));
        } else {
            mysqli_rollback($con);
            header('Location: ' . $returnUrl . '&msg=' . urlencode('Payment completed but could not save status.'));
        }
        exit;
    }
}
?>

<?php include 'header.php'; ?>

<?php
$upiPayUrl = 'upi://pay?pa=' . $upiId
    . '&pn=' . rawurlencode($bankName)
    . '&cu=INR'
    . '&am=' . number_format($amount, 2, '.', '')
    . '&tn=' . rawurlencode('SM Enterprises Advance Ref #' . (int)$id);
$upiQrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode($upiPayUrl);
?>

<style>
.copy-btn.copied {
    border-color: #198754;
    color: #198754;
    transform: scale(1.04);
    transition: all 0.18s ease;
}
.copy-btn.copied .copied-icon {
    display: inline-block;
    animation: copiedPop 0.22s ease;
}
@keyframes copiedPop {
    0% { transform: scale(0.75); opacity: 0.6; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<div class="container py-5" style="max-width: 700px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Secure Payment</h5>
        </div>
        <div class="card-body">
            <?php if ($pageError !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($pageError); ?></div>
                <a href="view_product.php" class="btn btn-secondary">Back</a>
            <?php else: ?>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <small class="text-muted d-block">Order Type</small>
                        <strong><?php echo htmlspecialchars(ucfirst($type)); ?></strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <small class="text-muted d-block">Reference</small>
                        <strong>#<?php echo (int)$id; ?></strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <small class="text-muted d-block">Payable Now</small>
                        <strong class="text-success">₹<?php echo number_format($amount, 2); ?></strong>
                        <?php if ($type === 'product' && isset($record)): ?>
                            <?php 
                            $totalAmount = (float)$record['pprice'] * (int)$record['pqty'];
                            if ($totalAmount >= 1000): ?>
                                <div class="small text-muted">50% Advance</div>
                            <?php else: ?>
                                <div class="small text-muted">Full Payment</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <form method="post" class="mt-3">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                <div id="copyFeedback" class="alert alert-success py-2 mb-3" style="display:none;"></div>

                <label class="form-label fw-semibold">Choose Payment Method</label>
                <div class="border rounded p-3 mb-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="methodUpi" value="upi" checked>
                        <label class="form-check-label" for="methodUpi">
                            <strong>UPI</strong> <span class="text-muted">(Instant payment)</span>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="methodBank" value="bank_transfer">
                        <label class="form-check-label" for="methodBank">
                            <strong>Bank Transfer</strong> <span class="text-muted">(NEFT/IMPS/RTGS)</span>
                        </label>
                    </div>
                    <?php if ($type === 'product'): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="methodCod" value="cod">
                        <label class="form-check-label" for="methodCod">
                            <strong>Cash on Delivery</strong> <span class="text-muted">(Pay when order is delivered)</span>
                        </label>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="upiDetails" class="border rounded p-3 mb-3 bg-light">
                    <div class="fw-semibold mb-2">Pay with UPI</div>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4 text-center">
                            <img src="<?php echo htmlspecialchars($upiQrImage); ?>" alt="UPI QR Code" class="img-fluid border rounded p-1 bg-white" style="max-width: 180px;">
                        </div>
                        <div class="col-md-8">
                            <div class="small text-muted mb-1">Scan QR or use UPI ID</div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="fw-semibold"><?php echo htmlspecialchars($upiId); ?></div>
                                <button type="button" class="btn btn-outline-secondary btn-sm copy-btn" data-copy="<?php echo htmlspecialchars($upiId); ?>" data-label="UPI ID">Copy</button>
                            </div>
                            <div class="small text-muted mt-2">Account Name: <?php echo htmlspecialchars($bankName); ?></div>
                        </div>
                    </div>
                </div>

                <div id="bankDetails" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                    <div class="fw-semibold mb-2">Bank Transfer Details</div>
                    <div class="row g-2">
                        <div class="col-sm-4 text-muted">Banking Name</div>
                        <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($bankName); ?></div>
                        <div class="col-sm-4 text-muted">Account No</div>
                        <div class="col-sm-8 fw-semibold d-flex align-items-center gap-2 flex-wrap">
                            <span><?php echo htmlspecialchars($bankAccountNo); ?></span>
                            <button type="button" class="btn btn-outline-secondary btn-sm copy-btn" data-copy="<?php echo htmlspecialchars($bankAccountNo); ?>" data-label="Account Number">Copy</button>
                        </div>
                        <div class="col-sm-4 text-muted">IFSC</div>
                        <div class="col-sm-8 fw-semibold d-flex align-items-center gap-2 flex-wrap">
                            <span><?php echo htmlspecialchars($bankIfsc); ?></span>
                            <button type="button" class="btn btn-outline-secondary btn-sm copy-btn" data-copy="<?php echo htmlspecialchars($bankIfsc); ?>" data-label="IFSC">Copy</button>
                        </div>
                    </div>
                </div>

                <div id="codDetails" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                    <div class="fw-semibold mb-1">Cash on Delivery</div>
                    <div class="text-muted small">No advance collection now. Please pay at the time of delivery.</div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" name="fake_action" value="pay" class="btn btn-success">
                        Continue Payment
                    </button>
                    <button type="submit" name="fake_action" value="cod" class="btn btn-primary">
                        Confirm COD
                    </button>
                    <button type="submit" name="fake_action" value="cancel" class="btn btn-outline-secondary">
                        Cancel
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var methodUpi = document.getElementById('methodUpi');
    var methodBank = document.getElementById('methodBank');
    var methodCod = document.getElementById('methodCod');
    var upiDetails = document.getElementById('upiDetails');
    var bankDetails = document.getElementById('bankDetails');
    var codDetails = document.getElementById('codDetails');
    var copyFeedback = document.getElementById('copyFeedback');

    function syncPaymentDetails() {
        if (!upiDetails || !bankDetails || !codDetails) {
            return;
        }

        upiDetails.style.display = methodUpi && methodUpi.checked ? 'block' : 'none';
        bankDetails.style.display = methodBank && methodBank.checked ? 'block' : 'none';
        codDetails.style.display = methodCod && methodCod.checked ? 'block' : 'none';
    }

    [methodUpi, methodBank, methodCod].forEach(function (radio) {
        if (radio) {
            radio.addEventListener('change', syncPaymentDetails);
        }
    });

    function showCopyMessage(text) {
        if (!copyFeedback) {
            return;
        }
        copyFeedback.textContent = text;
        copyFeedback.style.display = 'block';
        window.clearTimeout(showCopyMessage._timer);
        showCopyMessage._timer = window.setTimeout(function () {
            copyFeedback.style.display = 'none';
        }, 1800);
    }

    function copyText(value, label) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
                showCopyMessage(label + ' copied');
            }).catch(function () {
                showCopyMessage('Unable to copy. Please copy manually.');
            });
            return;
        }

        var temp = document.createElement('textarea');
        temp.value = value;
        temp.style.position = 'fixed';
        temp.style.left = '-9999px';
        document.body.appendChild(temp);
        temp.select();
        try {
            document.execCommand('copy');
            showCopyMessage(label + ' copied');
        } catch (e) {
            showCopyMessage('Unable to copy. Please copy manually.');
        }
        document.body.removeChild(temp);
    }

    function animateCopyButton(button) {
        if (!button) {
            return;
        }

        if (!button.dataset.originalLabel) {
            button.dataset.originalLabel = button.innerHTML;
        }

        button.classList.add('copied');
        button.innerHTML = '<i class="bi bi-check-circle-fill copied-icon me-1"></i>Copied';

        window.clearTimeout(button._copiedTimer);
        button._copiedTimer = window.setTimeout(function () {
            button.classList.remove('copied');
            button.innerHTML = button.dataset.originalLabel;
        }, 1200);
    }

    document.querySelectorAll('.copy-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            copyText(button.getAttribute('data-copy') || '', button.getAttribute('data-label') || 'Value');
            animateCopyButton(button);
        });
    });

    syncPaymentDetails();
});
</script>

<?php include 'footer.php'; ?>
