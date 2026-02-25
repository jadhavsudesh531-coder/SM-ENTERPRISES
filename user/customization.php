<?php
// Database connection FIRST (before any output)
include '../admin/conn.php';

$error = '';

// Process POST request BEFORE including header (which outputs HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $product_type = trim($_POST['product_type']);
    $description = trim($_POST['description']);
    if (!isset($_POST['pqty']) || intval($_POST['pqty']) < 1) {
      $error = 'Please enter a valid quantity (minimum 1).';
    } else {
      $quantity = intval($_POST['pqty']);
    }

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $img = $_FILES['image'];
      $check = getimagesize($img['tmp_name']);
      if ($check !== false) {
        $targetDir = __DIR__ . '/../productimg/customization/';
        if (!is_dir($targetDir)) {
          mkdir($targetDir, 0755, true);
        }
        $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($img['tmp_name'], $targetFile)) {
          $image_path = 'productimg/customization/' . $filename;
        }
      }
    }

    // Insert into DB using prepared statement
    $status = 'pending';

    // Only attempt insert if validation passed
    if (empty($error)) {
      // Check if `quantity` column exists in customization table
      $col_check = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE 'pqty'");
      if ($col_check && mysqli_num_rows($col_check) > 0) {
        $stmt = mysqli_prepare($con, "INSERT INTO customization (name,email,phone,product_type,description,image_path,status,pqty,created_at) VALUES (?,?,?,?,?,?,?, ?, NOW())");
        if ($stmt) {
          // types: 7 strings followed by 1 integer
          mysqli_stmt_bind_param($stmt, 'sssssssi', $name, $email, $phone, $product_type, $description, $image_path, $status, $quantity);
          if (mysqli_stmt_execute($stmt)) {
            $customization_id = mysqli_insert_id($con);
            // REDIRECT to pending request page immediately - BEFORE ANY OUTPUT
            header("Location: customization_payment.php?id=$customization_id");
            exit;
          } else {
            $error = 'Database error: ' . mysqli_stmt_error($stmt);
          }
          mysqli_stmt_close($stmt);
        } else {
          $error = 'Database prepare failed: ' . mysqli_error($con);
        }
      } else {
        // Fallback if `quantity` column doesn't exist
        $stmt = mysqli_prepare($con, "INSERT INTO customization (name,email,phone,product_type,description,image_path,status,created_at) VALUES (?,?,?,?,?,?,?,NOW())");
        if ($stmt) {
          mysqli_stmt_bind_param($stmt, 'sssssss', $name, $email, $phone, $product_type, $description, $image_path, $status);
          if (mysqli_stmt_execute($stmt)) {
            $customization_id = mysqli_insert_id($con);
            // REDIRECT to pending request page immediately - BEFORE ANY OUTPUT
            header("Location: customization_payment.php?id=$customization_id");
            exit;
          } else {
            $error = 'Database error: ' . mysqli_stmt_error($stmt);
          }
          mysqli_stmt_close($stmt);
        } else {
          $error = 'Database prepare failed: ' . mysqli_error($con);
        }
      }
    }
}

// NOW include header (which outputs HTML) - ONLY AFTER processing POST
include 'header.php';
?>

<style>
  .custom-center {
    min-height: calc(100vh - 120px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 16px 0 24px;
  }
  .custom-shell {
    width: min(1080px, 96vw);
    background: white;
    border-radius: 16px;
    box-shadow: var(--card-shadow);
    overflow: hidden;
  }
  .custom-hero {
    padding: 22px 26px;
    background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
    color: #f8fafc;
  }
  .custom-hero h3 {
    margin: 0 0 6px;
    font-weight: 700;
    letter-spacing: 0.3px;
  }
  .custom-hero p {
    margin: 0;
    color: #e2e8f0;
    font-size: 0.95rem;
  }
  .custom-body {
    padding: 18px;
    background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
  }
  .custom-panel {
    background: white;
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    height: 100%;
  }
  .custom-panel h5 {
    margin: 0 0 10px;
    font-weight: 700;
    color: #0f172a;
  }
  .custom-panel .muted {
    color: #64748b;
    font-size: 0.9rem;
  }
  .custom-list {
    margin: 12px 0 0 0;
    padding: 0;
    list-style: none;
  }
  .custom-list li {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px dashed #e2e8f0;
  }
  .custom-list li:last-child {
    border-bottom: none;
  }
  .custom-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 6px;
    background: #dc2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.15);
  }
  .form-row { gap: 12px; }
  .small-input { padding: .5rem .8rem; }
  .payment-requirement-banner {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    padding: 16px 18px;
    border-radius: 12px;
    margin-bottom: 16px;
    border-left: 5px solid rgba(255, 255, 255, 0.7);
  }
  .payment-requirement-banner strong {
    font-size: 1.05rem;
  }
  .payment-requirement-banner ul {
    margin: 10px 0 0 18px;
    padding-left: 0;
  }
  .payment-requirement-banner li {
    margin: 6px 0;
  }
  .custom-actions .btn-primary {
    background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
    border: none;
  }
  .custom-actions .btn-primary:hover {
    filter: brightness(1.05);
  }
</style>

<div class="custom-center">
  <div class="custom-shell">
    <div class="custom-hero">
      <h3>Customization Request</h3>
      <p>Create a tailored product request with clear specs and reference images.</p>
    </div>
    <div class="custom-body">
      <div class="row g-3">
        <div class="col-lg-4">
          <div class="custom-panel">
            <h5><i class="bi bi-shield-check me-2 text-danger"></i>Advance Payment</h5>
            <p class="muted">We start crafting only after 50% advance payment is completed.</p>
            <ul class="custom-list">
              <li><span class="custom-dot"></span><span>Request stays <strong>Pending</strong> until admin sets the price.</span></li>
              <li><span class="custom-dot"></span><span>Payment form appears automatically after price approval.</span></li>
              <li><span class="custom-dot"></span><span>Remaining 50% is due before delivery.</span></li>
            </ul>
            <div class="payment-requirement-banner mt-3">
              <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>50% Advance Required</strong>
              <div class="small mt-2">Your customization is confirmed only after advance payment.</div>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="custom-panel">
            <h5>Request Details</h5>
            <p class="muted">Provide accurate details so we can quote and craft precisely.</p>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger small mb-3"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
              <div class="d-flex form-row mb-2">
                <input type="text" name="name" class="form-control small-input" placeholder="Name" required>
                <input type="email" name="email" class="form-control small-input" placeholder="Email" required>
              </div>
              <div class="d-flex form-row mb-2">
                <input type="text" name="phone" class="form-control small-input" placeholder="Phone" required>
                <select name="product_type" class="form-select small-input" required>
                  <option value="" disabled selected hidden>Select Product Type</option>
                  <option value="Trophy">Trophy</option>
                  <option value="Nameplate">Nameplate</option>
                  <option value="Plaque">Plaque</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="d-flex form-row mb-2">
                <input type="number" name="pqty" class="form-control small-input" placeholder="Quantity" min="1" required>
              </div>
              <div class="mb-2">
                <textarea name="description" class="form-control" rows="4" placeholder="Describe size, material, text, and any specific requirements" required></textarea>
              </div>
              <div class="mb-3 d-flex align-items-center">
                <input type="file" name="image" accept="image/*" class="form-control" style="max-width:60%;">
                <div class="ms-2 text-muted small">Optional reference image (JPG/PNG)</div>
              </div>
              <div class="d-flex justify-content-end custom-actions">
                <button class="btn btn-outline-secondary me-2" type="reset">Reset</button>
                <button class="btn btn-primary" type="submit">
                  <i class="bi bi-send me-1"></i>Submit Request
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
