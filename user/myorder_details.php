<?php
define('page','myorder_details');
include('header.php');
include('../admin/conn.php');
$order_id = mysqli_real_escape_string($con, $_POST['order_id'] ?? '');
$sql = "SELECT purchase.*, product.*, cl.c_name, cl.c_email, cl.c_contact, cp.address, cp.city, cp.state, cp.postal_code, cp.dob FROM `purchase` LEFT JOIN product ON purchase.prod_id = product.pid LEFT JOIN customer_login cl ON purchase.user = cl.c_email LEFT JOIN customer_profile cp ON cl.c_email = cp.c_email WHERE purchase.order_id = '$order_id' LIMIT 1";
$result = mysqli_query($con, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div class="container justify-content-center mt-5">';
    echo '<div class="alert alert-warning">Order not found.</div>';
    echo '</div>';
    include('footer.php');
    exit;
}
$row = mysqli_fetch_assoc($result);

$rawStatus = strtolower(trim((string)($row['status'] ?? '')));
$statusLabel = 'Order Placed';
$statusBadge = 'info';

if (in_array($rawStatus, ['assigned', 'out_for_delivery', 'out for delivery', 'shipped'], true)) {
    $statusLabel = 'Out for Delivery';
    $statusBadge = 'primary';
} elseif (in_array($rawStatus, ['delivered', 'completed'], true)) {
    $statusLabel = 'Delivered';
    $statusBadge = 'success';
} elseif (in_array($rawStatus, ['cancelled', 'canceled', 'rejected'], true)) {
    $statusLabel = 'Cancelled';
    $statusBadge = 'danger';
}

echo '<div class="container justify-content-center mt-5">';
echo '<div class="row ">';        
echo '<div class="col-md-6">';
echo '<div class="card">';
echo '<div class="card-header bg-primary text-white">';
echo '<h4>Order Details</h4>';
echo '</div>';
echo '<div class="card-body">'; 
echo '<p><strong>Current Status:</strong> <span class="badge bg-'.htmlspecialchars($statusBadge).'">'.htmlspecialchars($statusLabel).'</span></p>';
echo '<p><strong>Product Date:</strong> '.htmlspecialchars(!empty($row['pdate']) ? date('d M Y, H:i', strtotime($row['pdate'])) : '').'</p>';
echo '<p><strong>Delivered Date:</strong> '.htmlspecialchars(!empty($row['delivered_at']) ? date('d M Y, H:i', strtotime($row['delivered_at'])) : 'Not delivered').'</p>';
echo '<p><strong>Product Name:</strong> '.$row['pname'].'</p>';
echo '<p><strong>Quantity:</strong> '.$row['pqty'].'</p>';   
echo '<p><strong>Price:</strong> '.$row['pprice'].'</p>';
echo '<p><strong>Company:</strong> '.$row['pcompany'].'</p>';
echo '<p><strong>Description:</strong> '.$row['pdis'].'</p>';
 $total = ($row['pprice'] ?? 0) * ($row['pqty'] ?? 0);
echo '<hr>';
echo '<h5>Total Amount: '.htmlspecialchars($total).'</h5>';
echo '</div>';
echo '</div>';  

echo '</div>'; // close product column

// Customer column
echo '<div class="col-md-6">';
echo '<div class="card">';
echo '<div class="card-header bg-secondary text-white">';
echo '<h4>Customer Info</h4>';
echo '</div>';
echo '<div class="card-body">';
$customer_name = $row['c_name'] ?? $row['name'] ?? '';
$customer_email = $row['c_email'] ?? $row['user'] ?? '';
$customer_contact = $row['c_contact'] ?? '';
$addressParts = array_filter([ $row['address'] ?? '', $row['city'] ?? '', $row['state'] ?? '', $row['postal_code'] ?? '' ]);
if (!empty($customer_name)) echo '<p><strong>Name:</strong> '.htmlspecialchars($customer_name).'</p>';
if (!empty($customer_email)) echo '<p><strong>Email:</strong> '.htmlspecialchars($customer_email).'</p>';
if (!empty($customer_contact)) echo '<p><strong>Contact:</strong> '.htmlspecialchars($customer_contact).'</p>';
if (!empty($addressParts)) {
    echo '<p><strong>Address:</strong><br>'.nl2br(htmlspecialchars(implode(', ', $addressParts))).'</p>';
} else {
    echo '<p><strong>Address:</strong> Not provided</p>';
}
if (!empty($row['dob'])) echo '<p><strong>DOB:</strong> '.htmlspecialchars($row['dob']).'</p>';
echo '</div>';
echo '</div>';
echo '</div>'; // close customer column

echo '</div>';
echo '</div>';  
include('footer.php');

?>