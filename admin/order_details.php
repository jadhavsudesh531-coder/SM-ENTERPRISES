<?php
include('header.php');
include('conn.php');

$order_id = mysqli_real_escape_string($con, $_REQUEST['order_id'] ?? '');
if (empty($order_id)) {
    echo '<div class="container justify-content-center mt-5">';
    echo '<div class="alert alert-warning">Order ID missing.</div>';
    echo '</div>';
    include('footer.php');
    exit;
}

// Fetch order + product + customer info
$sql = "SELECT purchase.*, product.*, cl.c_name, cl.c_email, cl.c_contact, cp.address, cp.city, cp.state, cp.postal_code, cp.dob FROM purchase LEFT JOIN product ON purchase.prod_id = product.pid LEFT JOIN customer_login cl ON purchase.user = cl.c_email LEFT JOIN customer_profile cp ON cl.c_email = cp.c_email WHERE (purchase.order_id = '".mysqli_real_escape_string($con,$order_id)."' OR purchase.pid = '".mysqli_real_escape_string($con,$order_id)."') LIMIT 1";
$result = mysqli_query($con, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div class="container justify-content-center mt-5">';
    echo '<div class="alert alert-warning">Order not found.</div>';
    echo '</div>';
    include('footer.php');
    exit;
}
$row = mysqli_fetch_assoc($result);

?>
<div class="container" style="margin-top:100px; max-width: 1000px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Order #<?php echo htmlspecialchars($order_id); ?> Details</h2>
    <a href="orders.php" class="btn btn-outline-secondary btn-sm">Back to Orders</a>
  </div>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h4>Order Details</h4>
        </div>
        <div class="card-body">
          <p><strong>Order Date:</strong> <?php echo htmlspecialchars(!empty($row['pdate']) ? date('d M Y, H:i', strtotime($row['pdate'])) : (!empty($row['created_at']) ? date('d M Y, H:i', strtotime($row['created_at'])) : '')); ?></p>
          <p><strong>Delivered Date:</strong> <?php echo htmlspecialchars(!empty($row['delivered_at']) ? date('d M Y, H:i', strtotime($row['delivered_at'])) : 'Not delivered'); ?></p>
          <p><strong>Product Name:</strong> <?php echo htmlspecialchars($row['pname'] ?? ''); ?></p>
          <p><strong>Quantity:</strong> <?php echo htmlspecialchars($row['pqty'] ?? ''); ?></p>
          <p><strong>Price:</strong> <?php echo htmlspecialchars($row['pprice'] ?? ''); ?></p>
          <p><strong>Company:</strong> <?php echo htmlspecialchars($row['pcompany'] ?? ''); ?></p>
          <p><strong>Description:</strong> <?php echo htmlspecialchars($row['pdis'] ?? ''); ?></p>
          <?php $total = (float)($row['pprice'] ?? 0) * (int)($row['pqty'] ?? 0); ?>
          <hr>
          <h5>Total Amount: <?php echo htmlspecialchars($total); ?></h5>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-header bg-secondary text-white">
          <h4>Customer Info</h4>
        </div>
        <div class="card-body">
          <?php
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
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>
