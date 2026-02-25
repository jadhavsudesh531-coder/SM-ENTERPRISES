<?php
include('conn.php');

// Validate input
if(!isset($_POST['did']) || empty($_POST['did'])) {
    header('Location:view_product.php');
    exit();
}

$delete_id = mysqli_real_escape_string($con, $_POST['did']);

// First, fetch the product to get image filename
$fetch_sql = "SELECT pimg FROM product WHERE pid='$delete_id'";
$fetch_result = mysqli_query($con, $fetch_sql);

if($fetch_result && mysqli_num_rows($fetch_result) > 0) {
    $product = mysqli_fetch_assoc($fetch_result);
    
    // Delete image file if it exists
    if(!empty($product['pimg'])) {
        $image_path = '../productimg/' . $product['pimg'];
        if(file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete related purchase records first (foreign key constraint)
    $delete_purchases = "DELETE FROM purchase WHERE prod_id='$delete_id'";
    $purchase_result = mysqli_query($con, $delete_purchases);
    
    if(!$purchase_result) {
        echo "Error deleting related purchases: " . mysqli_error($con);
        exit();
    }
    
    // Delete product from database
    $sqlq = "DELETE FROM product WHERE pid='$delete_id'";
    $result = mysqli_query($con, $sqlq);
    
    if($result) {
        header('Location:view_product.php');
        exit();
    } else {
        echo "Error deleting product: " . mysqli_error($con);
    }
} else {
    echo "Product not found!";
}
?>
