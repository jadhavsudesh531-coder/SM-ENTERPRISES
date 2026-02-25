<?php
// START SESSION FIRST - Before accessing $_SESSION
session_start();

define('page','purchase');
// Process POST FIRST before any output
include('../admin/conn.php');

$error = '';
$requires_advance_payment = false;

$pid = $_POST['pid'] ?? '';
$pname = $_POST['pname'] ?? '';
$pprice = $_POST['pprice'] ?? '';
$pqty = $_POST['qty'] ?? '';

// CRITICAL: Stock validation BEFORE processing order
if (!empty($pid) && !empty($pqty)) {
    $safe_pid = mysqli_real_escape_string($con, $pid);
    $stock_check = mysqli_query($con, "SELECT pqty, pname FROM product WHERE pid = '$safe_pid'");
    
    if ($stock_check && mysqli_num_rows($stock_check) > 0) {
        $stock_row = mysqli_fetch_assoc($stock_check);
        $available_stock = intval($stock_row['pqty']);
        $requested_qty = intval($pqty);
        
        if ($requested_qty > $available_stock) {
            $_SESSION['error_msg'] = "Insufficient stock! Only $available_stock units available for " . htmlspecialchars($stock_row['pname']);
            header('Location: view_product.php');
            exit;
        }
        
        if ($available_stock <= 0) {
            $_SESSION['error_msg'] = "Product is out of stock: " . htmlspecialchars($stock_row['pname']);
            header('Location: view_product.php');
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "Product not found";
        header('Location: view_product.php');
        exit;
    }
}

// Check if total order amount (price × quantity) >= 1000 (requires 50% advance payment)
if (!empty($pprice) && !empty($pqty) && (floatval($pprice) * intval($pqty)) >= 1000) {
    $requires_advance_payment = true;
    
    // Save order to database with pending status
    $session_email = $_SESSION['username'] ?? '';
    if (empty($session_email)) {
        header('Location: login.php');
        exit;
    }
    
    $safe_email = mysqli_real_escape_string($con, $session_email);
    $u_res = mysqli_query($con, "SELECT c_name, c_email FROM customer_login WHERE c_email = '$safe_email' LIMIT 1");
    
    $user_name = $session_email;
    if ($u_res && mysqli_num_rows($u_res) > 0) {
        $u_row = mysqli_fetch_assoc($u_res);
        if (!empty($u_row['c_name']) && !ctype_digit($u_row['c_name']) && strlen($u_row['c_name']) > 2) {
            $user_name = $u_row['c_name'];
        }
    }
    
    // Insert order with status 'pending_payment' to track advance payment requirement
    $safe_pid = mysqli_real_escape_string($con, $pid);
    $safe_pname = mysqli_real_escape_string($con, $pname);
    $safe_pprice = mysqli_real_escape_string($con, $pprice);
    $safe_pqty = mysqli_real_escape_string($con, $pqty);
    $safe_user_name = mysqli_real_escape_string($con, $user_name);
    
    $sql = "INSERT INTO `purchase` (`pname`, `user`, `name`, `pprice`, `pqty`, `prod_id`, `status`, `pdate`) 
            VALUES ('$safe_pname', '$safe_email', '$safe_user_name', '$safe_pprice', '$safe_pqty', '$safe_pid', 'pending_payment', NOW())";
    
    if(mysqli_query($con, $sql)){
        $order_id = mysqli_insert_id($con);
        // Also insert into myorder table
        $myorder_sql = "INSERT INTO `myorder` (`order_id`, `pname`, `user`, `name`, `pprice`, `pqty`, `prod_id`, `status`, `pdate`, `created_at`) 
                       VALUES ('$order_id', '$safe_pname', '$safe_email', '$safe_user_name', '$safe_pprice', '$safe_pqty', '$safe_pid', 'pending_payment', NOW(), NOW())";
        mysqli_query($con, $myorder_sql);
        
        // Redirect to payment instructions page
        header("Location: purchase_payment.php?id=$order_id");
        exit;
    } else {
        $error = 'Error creating order: ' . mysqli_error($con);
    }
}

// NOW include header (which outputs HTML)
include('header.php');

    // Email stored in session
    $session_email = $_SESSION['username'] ?? '';
    $user_email = $session_email;
    // Create a friendly display name. Prefer a non-numeric contact name if present; otherwise use the email local-part
    $display_name = $session_email;

    if (!empty($session_email)) {
        $safe_email = mysqli_real_escape_string($con, $session_email);
        // Fetch contact (phone or stored name) and email
        $u_res = mysqli_query($con, "SELECT c_name, c_email FROM customer_login WHERE c_email = '$safe_email' LIMIT 1");
        if ($u_res && mysqli_num_rows($u_res) > 0) {
            $u_row = mysqli_fetch_assoc($u_res);
            if (!empty($u_row['c_email'])) $user_email = $u_row['c_email'];
            // Start with a friendly name derived from the email local-part
            $local = explode('@', $user_email)[0];
            $local = str_replace(array('.', '_', '-'), ' ', $local);
            $display_name = ucwords($local);
            // If contact contains a non-numeric name, prefer it
            if (!empty($u_row['c_name']) && !ctype_digit($u_row['c_name']) && strlen($u_row['c_name']) > 2) {
                $display_name = $u_row['c_name'];
            }
        }
    }
?>

<style>
    .payment-required-banner {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 5px solid #fff;
    }
    .payment-required-banner strong {
        font-size: 1.1rem;
    }
</style>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Purchase Summary</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger mb-3"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <!-- Show advance payment banner for orders >= 1000 -->
                    <?php if ((floatval($pprice) * intval($pqty)) >= 1000): ?>
                        <div class="payment-required-banner">
                            <strong><i class="bi bi-exclamation-circle-fill me-2"></i>50% Advance Payment Required</strong>
                            <p class="mt-2 mb-0">For this high-value order (₹<?php echo htmlspecialchars(number_format($pprice * $pqty, 2)); ?>), a 50% advance payment is required before order confirmation.</p>
                        </div>
                    <?php endif; ?>
                    
                    <form action="purchase_order.php" method="post">
                        <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($display_name); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" class="form-control" name="pname" value="<?php echo htmlspecialchars($pname); ?>" readonly>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Price</label>
                                <input type="text" class="form-control" name="pprice" value="<?php echo htmlspecialchars($pprice); ?>" readonly>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="text" class="form-control" name="pqty" value="<?php echo htmlspecialchars($pqty); ?>" readonly>
                            </div>
                        </div>
                        <hr>
                            <hr>
                        <h4 class="text-end">Total: <strong>₹<?php echo htmlspecialchars(number_format($pprice * $pqty,2)); ?></strong></h4>
                        
                        <!-- Show advance payment info for orders >= 1000 -->
                        <?php if ((floatval($pprice) * intval($pqty)) >= 1000): ?>
                            <div class="alert alert-warning mt-3 mb-3">
                                <strong><i class="bi bi-info-circle me-2"></i>Note:</strong> This is a high-value order (≥ ₹1000). 
                                You will be redirected to payment instructions showing 50% advance payment requirement.
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-success w-100 mt-3"><i class="bi bi-check2-circle me-2"></i>Confirm Purchase</button>
                        <a href="view_product.php" class="btn btn-outline-secondary w-100 mt-2">Continue Shopping</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php   

include('footer.php');
?>