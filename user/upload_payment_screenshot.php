<?php
session_start();
include '../admin/conn.php';

if (!isset($_SESSION['is_login'])) {
    header('location:login.php');
    exit;
}

function ensureScreenshotColumns($con)
{
    $columns = [
        ['table' => 'myorder', 'column' => 'payment_screenshot', 'sql' => "ALTER TABLE myorder ADD COLUMN payment_screenshot VARCHAR(255) NULL"],
        ['table' => 'purchase', 'column' => 'payment_screenshot', 'sql' => "ALTER TABLE purchase ADD COLUMN payment_screenshot VARCHAR(255) NULL"],
        ['table' => 'customization', 'column' => 'payment_screenshot', 'sql' => "ALTER TABLE customization ADD COLUMN payment_screenshot VARCHAR(255) NULL"]
    ];

    foreach ($columns as $col) {
        if (!isset($col['table'])) {
            $check = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE '{$col['column']}'");
        } else {
            $check = mysqli_query($con, "SHOW COLUMNS FROM {$col['table']} LIKE '{$col['column']}'");
        }
        if (!$check || mysqli_num_rows($check) === 0) {
            mysqli_query($con, $col['sql']);
        }
    }
}

ensureScreenshotColumns($con);

$user = $_SESSION['username'] ?? '';
$type = strtolower(trim((string)($_POST['type'] ?? '')));
$id = (int)($_POST['id'] ?? 0);
$returnUrl = '';

if (!in_array($type, ['product', 'customization'], true) || $id <= 0 || $user === '') {
    header('Location: view_product.php?msg=' . urlencode('Invalid screenshot upload request.'));
    exit;
}

if ($type === 'product') {
    $returnUrl = 'purchase_payment.php?id=' . $id;
} else {
    $returnUrl = 'customization_payment.php?id=' . $id;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['payment_screenshot'])) {
    header('Location: ' . $returnUrl . '&msg=' . urlencode('No file uploaded.'));
    exit;
}

$file = $_FILES['payment_screenshot'];
$uploadDir = '../productimg/payment_screenshots/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024;

if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: ' . $returnUrl . '&msg=' . urlencode('File upload error. Please try again.'));
    exit;
}

if ($file['size'] > $maxSize) {
    header('Location: ' . $returnUrl . '&msg=' . urlencode('Screenshot size must be less than 5MB.'));
    exit;
}

if (!in_array($file['type'], $allowedTypes, true)) {
    header('Location: ' . $returnUrl . '&msg=' . urlencode('Only image files (JPEG, PNG, GIF, WEBP) are allowed.'));
    exit;
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$safeFilename = $type . '_' . $id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$targetPath = $uploadDir . $safeFilename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    header('Location: ' . $returnUrl . '&msg=' . urlencode('Could not save screenshot. Please try again.'));
    exit;
}

if ($type === 'product') {
    $checkStmt = mysqli_prepare($con, "SELECT user FROM myorder WHERE order_id=? LIMIT 1");
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, 'i', $id);
        mysqli_stmt_execute($checkStmt);
        $checkRes = mysqli_stmt_get_result($checkStmt);
        $checkRow = $checkRes ? mysqli_fetch_assoc($checkRes) : null;
        mysqli_stmt_close($checkStmt);
        
        if (!$checkRow || strtolower((string)$checkRow['user']) !== strtolower($user)) {
            header('Location: ' . $returnUrl . '&msg=' . urlencode('Unauthorized screenshot upload.'));
            exit;
        }
    }

    mysqli_begin_transaction($con);
    $safeFile = mysqli_real_escape_string($con, $safeFilename);
    $ok1 = mysqli_query($con, "UPDATE myorder SET payment_screenshot='$safeFile' WHERE order_id=" . $id);
    $ok2 = mysqli_query($con, "UPDATE purchase SET payment_screenshot='$safeFile' WHERE order_id=" . $id);

    if ($ok1 && $ok2) {
        mysqli_commit($con);
        header('Location: ' . $returnUrl . '&msg=' . urlencode('Payment screenshot uploaded successfully. Admin will verify it soon.'));
    } else {
        mysqli_rollback($con);
        header('Location: ' . $returnUrl . '&msg=' . urlencode('Screenshot uploaded but could not link to order.'));
    }
    exit;
}

$checkStmt = mysqli_prepare($con, "SELECT email FROM customization WHERE id=? LIMIT 1");
if ($checkStmt) {
    mysqli_stmt_bind_param($checkStmt, 'i', $id);
    mysqli_stmt_execute($checkStmt);
    $checkRes = mysqli_stmt_get_result($checkStmt);
    $checkRow = $checkRes ? mysqli_fetch_assoc($checkRes) : null;
    mysqli_stmt_close($checkStmt);
    
    if (!$checkRow || strtolower((string)$checkRow['email']) !== strtolower($user)) {
        header('Location: ' . $returnUrl . '&msg=' . urlencode('Unauthorized screenshot upload.'));
        exit;
    }
}

$safeFile = mysqli_real_escape_string($con, $safeFilename);
$ok = mysqli_query($con, "UPDATE customization SET payment_screenshot='$safeFile' WHERE id=" . $id);

if ($ok) {
    header('Location: ' . $returnUrl . '&msg=' . urlencode('Payment screenshot uploaded successfully. Admin will verify it soon.'));
} else {
    header('Location: ' . $returnUrl . '&msg=' . urlencode('Screenshot uploaded but could not link to customization request.'));
}
exit;
