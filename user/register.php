<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - SM ENTERPRISES</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, Arial;
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 40px 15px;
        }

        .register-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .register-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.08);
            padding: 40px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section img {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }

        .logo-section h4 {
            font-weight: 700;
            color: #212529;
            margin-bottom: 8px;
        }

        .logo-section p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }

        .btn-register {
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }

        .login-link a {
            color: #198754;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .optional-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="logo-section">
                <img src="../productimg/logo.png" alt="SM Enterprises Logo">
                <h4>SM ENTERPRISES</h4>
                <p>Create your account to get started.</p>
            </div>

            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                
                <div class="mb-3">
                    <label for="contact" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contact" name="contact" placeholder="Enter your contact number" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="addAddressToggle">
                    <label class="form-check-label" for="addAddressToggle">Add address details (optional)</label>
                </div>

                <div id="addressFields" class="optional-section" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Enter your address"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" placeholder="City">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" placeholder="State">
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Postal Code</label>
                            <input type="text" class="form-control" name="postal_code" placeholder="Postal Code">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob">
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" name="register" class="btn btn-success btn-register">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </div>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Sign In</a>
            </div>

            <script>
                document.getElementById('addAddressToggle').addEventListener('change', function(e){
                    document.getElementById('addressFields').style.display = this.checked ? 'block' : 'none';
                });
            </script>
        </div>
    </div>
</body>
</html>
<?php
if(isset($_POST['register'])){
    // sanitize inputs
    include('../admin/conn.php');
    $name = mysqli_real_escape_string($con, trim($_POST['name'] ?? ''));
    $contact = mysqli_real_escape_string($con, trim($_POST['contact'] ?? ''));
    $email = mysqli_real_escape_string($con, trim($_POST['email'] ?? ''));
    $password = mysqli_real_escape_string($con, trim($_POST['password'] ?? ''));

    // basic validation
    if (empty($name) || empty($email) || empty($password)) {
        echo "<div class='alert alert-danger'>Please fill required fields</div>";
        exit;
    }

    // collect optional address fields
    $address = mysqli_real_escape_string($con, trim($_POST['address'] ?? ''));
    $city = mysqli_real_escape_string($con, trim($_POST['city'] ?? ''));
    $state = mysqli_real_escape_string($con, trim($_POST['state'] ?? ''));
    $postal_code = mysqli_real_escape_string($con, trim($_POST['postal_code'] ?? ''));
    $dob = mysqli_real_escape_string($con, trim($_POST['dob'] ?? ''));

    // check existing
    $sqlq = "SELECT * FROM customer_login WHERE c_email='$email' LIMIT 1";
    $result = mysqli_query($con, $sqlq);
    if(mysqli_num_rows($result) > 0){
        echo "<script>alert('Email already registered!'); window.location.href='register.php';</script>";
        exit;
    }

    // ensure created_at column exists on customer_login
    $col_check_created = mysqli_query($con, "SHOW COLUMNS FROM customer_login LIKE 'created_at'");
    if (!$col_check_created || mysqli_num_rows($col_check_created) === 0) {
        mysqli_query($con, "ALTER TABLE customer_login ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    }

    // insert user
    $sql = "INSERT INTO customer_login (c_name, c_email, c_contact, c_password, created_at) VALUES ('$name', '$email', '$contact', '$password', NOW())";
    if (!mysqli_query($con, $sql)){
        echo "<div class='alert alert-danger'>Could not register: " . htmlspecialchars(mysqli_error($con)) . "</div>";
        exit;
    }

    // create profile table if not exists
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

    // insert profile row only if user provided address or related fields
    $addressProvided = (!empty($address) || !empty($city) || !empty($state) || !empty($postal_code) || !empty($dob));
    if ($addressProvided) {
        $ins_profile = "INSERT INTO customer_profile (c_email, address, city, state, postal_code, dob) VALUES ('$email', '$address', '$city', '$state', '$postal_code', " . (!empty($dob) ? "'". $dob ."'" : "NULL") . ")";
        mysqli_query($con, $ins_profile);
    }

    mysqli_close($con);

    echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
    exit;
}


?>