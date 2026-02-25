<?php
    session_start();
    include('../admin/conn.php');
    $pid = mysqli_real_escape_string($con, $_POST['pid'] ?? '');
    $pname = mysqli_real_escape_string($con, $_POST['pname'] ?? '');
    $pprice = mysqli_real_escape_string($con, $_POST['pprice'] ?? '');
    $qty = mysqli_real_escape_string($con, $_POST['pqty'] ?? '');
    // Accept either `username` (from form) or legacy `user` field
    $user = mysqli_real_escape_string($con, $_POST['username'] ?? $_POST['user'] ?? '');
    // Use provided name if available, otherwise fall back to username
    $name = mysqli_real_escape_string($con, $_POST['name'] ?? $user);

    $pprice_float = floatval($pprice);
    $qty_int = intval($qty);
    $total_amount = $pprice_float * $qty_int;
    
    // All orders now go through payment gateway regardless of amount
    // Orders >= 1000 require 50% advance payment
    // Orders < 1000 require full payment or COD
    
    if ($total_amount >= 1000) {
        // Order already created in purchase.php, just redirect to payment
        // Find the order_id from the most recent order for this user
        $recent_order_res = mysqli_query($con, "SELECT order_id FROM myorder WHERE user='$user' AND pname='$pname' ORDER BY order_id DESC LIMIT 1");
        if ($recent_order_res && mysqli_num_rows($recent_order_res) > 0) {
            $recent_row = mysqli_fetch_assoc($recent_order_res);
            $order_id = $recent_row['order_id'];
            header("Location: fake_payment_gateway.php?type=product&id=$order_id");
            exit;
        } else {
            echo '<script>alert("Order processing error. Please try again.");window.location.href="view_product.php";</script>';
        }
    } else {
        // For products < 1000, create order with pending_payment status and redirect to payment
        $sql = "INSERT INTO `purchase` (`pname`, `user`, `name`, `pprice`, `pqty`, `prod_id`, `status`, `pdate`) VALUES ('$pname', '$user', '$name', '$pprice', '$qty', '$pid', 'pending_payment', NOW())";
        if(mysqli_query($con, $sql)){
            $order_id = mysqli_insert_id($con);
            // Also insert into myorder table
            $myorder_sql = "INSERT INTO `myorder` (`order_id`, `pname`, `user`, `name`, `pprice`, `pqty`, `prod_id`, `status`, `pdate`, `created_at`) VALUES ('$order_id', '$pname', '$user', '$name', '$pprice', '$qty', '$pid', 'pending_payment', NOW(), NOW())";
            mysqli_query($con, $myorder_sql);
            
            // Redirect to payment gateway
            header("Location: fake_payment_gateway.php?type=product&id=$order_id");
            exit;
        }else{
            echo '<script>alert("Purchase Failed: '.mysqli_error($con).'" );window.location.href="view_product.php";</script>'; 
        }
    }
        
?>
