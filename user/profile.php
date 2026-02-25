<?php
session_start();
if(!isset($_SESSION['is_login'])){
    header('location:login.php');
}
define('page','profile');
$username = $_SESSION['username'] ?? null;
if (empty($username)) { header('Location: login.php'); exit; }
include('header.php');
include('../admin/conn.php');
$safe_email = mysqli_real_escape_string($con, $username);

// ensure profile table exists
$create_profile = "CREATE TABLE IF NOT EXISTS customer_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    c_email VARCHAR(255) NOT NULL UNIQUE,
    address TEXT NULL,
    city VARCHAR(120) NULL,
    state VARCHAR(120) NULL,
    postal_code VARCHAR(30) NULL,
    dob DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($con, $create_profile);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($con, trim($_POST['name'] ?? ''));
    $contact = mysqli_real_escape_string($con, trim($_POST['contact'] ?? ''));
    $address = mysqli_real_escape_string($con, trim($_POST['address'] ?? ''));
    $city = mysqli_real_escape_string($con, trim($_POST['city'] ?? ''));
    $state = mysqli_real_escape_string($con, trim($_POST['state'] ?? ''));
    $postal_code = mysqli_real_escape_string($con, trim($_POST['postal_code'] ?? ''));
    $dob = mysqli_real_escape_string($con, trim($_POST['dob'] ?? ''));

    // update customer_login basic fields
    mysqli_query($con, "UPDATE customer_login SET c_name='$name', c_contact='$contact' WHERE c_email='$safe_email'");

    // upsert into customer_profile
    $res = mysqli_query($con, "SELECT id FROM customer_profile WHERE c_email='$safe_email' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        mysqli_query($con, "UPDATE customer_profile SET address='$address', city='$city', state='$state', postal_code='$postal_code', dob=" . (!empty($dob) ? "'". $dob ."'" : "NULL") . " WHERE c_email='$safe_email'");
    } else {
        mysqli_query($con, "INSERT INTO customer_profile (c_email, address, city, state, postal_code, dob) VALUES ('$safe_email', '$address', '$city', '$state', '$postal_code', " . (!empty($dob) ? "'". $dob ."'" : "NULL") . ")");
    }

    $msg = 'Profile updated successfully';
}

// fetch profile
$sql = "SELECT cl.c_name, cl.c_email, cl.c_contact, cp.address, cp.city, cp.state, cp.postal_code, cp.dob FROM customer_login cl LEFT JOIN customer_profile cp ON cl.c_email = cp.c_email WHERE cl.c_email = '$safe_email' LIMIT 1";
$res = mysqli_query($con, $sql);
$row = ($res && mysqli_num_rows($res)>0) ? mysqli_fetch_assoc($res) : [];

?>
<div class="container-fluid px-2 mt-1" style="max-width: 100%;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">My Profile</h2>
    <a href="view_product.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Continue Shopping</a>
  </div>

  <!-- Toast for success -->
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1200">
    <div id="profileToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <strong class="me-auto">Profile</strong>
        <small class="text-muted">now</small>
        <button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="profileToastBody">Saved successfully</div>
    </div>
  </div>

  <div class="card shadow-sm profile-card">
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-4 text-center border-end">
          <div class="profile-avatar mb-3">
            <img src="../productimg/logo.png" alt="avatar" class="img-fluid rounded-circle" style="width:140px; height:140px; object-fit:cover;">
          </div>
          <h5 class="mb-0"><?php echo htmlspecialchars($row['c_name'] ?? ''); ?></h5>
          <p class="muted-small mb-2"><?php echo htmlspecialchars($row['c_email'] ?? $username); ?></p>
          <p class="muted-small">Member since: <?php echo !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : ''; ?></p>
          <div class="mt-3">
            <a href="myorder.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-card-list me-1"></i>My Orders</a>
            <a href="customization.php" class="btn btn-outline-secondary btn-sm ms-1"><i class="bi bi-patch-check me-1"></i>Customizations</a>
          </div>
        </div>
        <div class="col-md-8">
          <form method="post" class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['c_name'] ?? ''); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($row['c_contact'] ?? ''); ?>">
            </div>
            <div class="col-md-12">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="<?php echo htmlspecialchars($row['c_email'] ?? $username); ?>" readonly>
            </div>
            <div class="col-md-12">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($row['address'] ?? ''); ?></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">City</label>
              <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($row['city'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">State</label>
              <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($row['state'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Postal Code</label>
              <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($row['postal_code'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Date of Birth</label>
              <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($row['dob'] ?? ''); ?>">
            </div>
            <div class="col-12 mt-3 d-flex justify-content-end">
              <button type="submit" name="update_profile" class="btn btn-success"><i class="bi bi-save me-1"></i> Save Profile</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($msg)): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function(){
    const bt = new bootstrap.Toast(document.getElementById('profileToast'));
    document.getElementById('profileToastBody').textContent = <?php echo json_encode($msg); ?>;
    bt.show();
  });
</script>
<?php endif; ?>

<?php include('footer.php'); ?>