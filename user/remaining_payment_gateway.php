<?php
session_start();
include '../admin/conn.php';

if (!isset($_SESSION['is_login'])) {
    header('location:login.php');
    exit;
}

$customization_id = isset($_POST['customization_id']) ? intval($_POST['customization_id']) : 0;
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$payment_method = strtolower(trim((string)($_POST['payment_method'] ?? 'upi')));
$user = $_SESSION['username'] ?? '';

if (!in_array($payment_method, ['upi', 'bank_transfer'], true)) {
    $payment_method = 'upi';
}

if (!$customization_id || !$order_id || empty($user)) {
    header('Location: myorder.php?msg=' . urlencode('Invalid payment request.'));
    exit;
}

// Fetch customization details for verification
$stmt = mysqli_prepare($con, "SELECT id, email, customization_unit_price, pqty FROM customization WHERE id = ? LIMIT 1");
if (!$stmt) {
    header('Location: myorder.php?msg=' . urlencode('Database error.'));
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $customization_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    header('Location: myorder.php?msg=' . urlencode('Customization not found.'));
    exit;
}

$customization = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Verify user owns this
if (strtolower((string)$customization['email']) !== strtolower($user)) {
    header('Location: myorder.php?msg=' . urlencode('Unauthorized access.'));
    exit;
}

// Generate transaction ID
$txnId = 'FINAL-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
$methodLabel = $payment_method === 'bank_transfer' ? 'Bank Transfer' : 'UPI';

// Update customization to 'confirmed' (100% paid)
mysqli_begin_transaction($con);
$safeTxn = mysqli_real_escape_string($con, $txnId);

// Update customization
$ok1 = mysqli_query($con, "UPDATE customization SET status='confirmed', payment_txn_id='$safeTxn', payment_verified=1, payment_verified_at=NOW() WHERE id=" . (int)$customization_id);

// Update purchase record (if linked)
$ok2 = mysqli_query($con, "UPDATE purchase SET status='confirmed', payment_txn_id='$safeTxn', payment_verified=1, payment_verified_at=NOW() WHERE customization_id=" . (int)$customization_id);

// Update myorder record (if linked)
$ok3 = mysqli_query($con, "UPDATE myorder SET status='confirmed', payment_txn_id='$safeTxn', payment_verified=1, payment_verified_at=NOW() WHERE customization_id=" . (int)$customization_id);

if ($ok1) {
    mysqli_commit($con);
    header('Location: myorder.php?msg=' . urlencode($methodLabel . ' payment successful for remaining 50%! Your order is confirmed. TXN: ' . $txnId));
    exit;
} else {
    mysqli_rollback($con);
    header('Location: remaining_payment.php?id=' . (int)$customization_id . '&order_id=' . (int)$order_id . '&msg=' . urlencode('Payment processing failed. Please try again.'));
    exit;
}

?>
