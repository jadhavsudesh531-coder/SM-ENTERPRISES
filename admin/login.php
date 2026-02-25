<?php
session_start();
include('conn.php');

$error_msg = '';

if(isset($_POST['submit'])){
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $passward = mysqli_real_escape_string($con, $_POST['passward']);
    
    $sqlquery = "SELECT * FROM user_login WHERE username='$username' AND passward='$passward'";
    $result = mysqli_query($con, $sqlquery);
    
    if(mysqli_num_rows($result) > 0){
        $lowStockItems = [];
        $lowStockQuery = "SELECT pname, pqty FROM product WHERE pqty <= 5 ORDER BY pqty ASC, pname ASC";
        $lowStockResult = mysqli_query($con, $lowStockQuery);
        if ($lowStockResult) {
            while ($stockRow = mysqli_fetch_assoc($lowStockResult)) {
                $lowStockItems[] = $stockRow;
            }
        }

        if (count($lowStockItems) > 0) {
            $_SESSION['low_stock_items'] = $lowStockItems;
            $_SESSION['show_low_stock_popup'] = true;
        } else {
            unset($_SESSION['low_stock_items']);
            unset($_SESSION['show_low_stock_popup']);
        }

        $_SESSION['is_login'] = true;
        $_SESSION['uname'] = $username;
        header('location:index.php');
        exit;
    } else {
        $error_msg = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SM ENTERPRISES</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, Arial;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            top: -200px;
            right: -200px;
            animation: float 20s infinite ease-in-out;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
            animation: float 15s infinite ease-in-out reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, 30px); }
        }

        .login-container {
            width: 100%;
            max-width: 460px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            padding: 45px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-section img {
            width: 70px;
            height: 70px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.4));
        }

        .logo-section h4 {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .logo-section p {
            color: #64748b;
            font-size: 0.95rem;
        }

        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            padding: 6px 16px;
            border-radius: 20px;
            color: white;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 12px;
            box-shadow: 0 3px 10px rgba(5, 150, 105, 0.3);
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #000000;
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .btn-login {
            padding: 14px;
            font-weight: 700;
            border-radius: 12px;
            margin-top: 15px;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d3748 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 14px 18px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="logo-section">
            <img src="../productimg/logo.png" alt="SM Enterprises Logo">
            <div class="admin-badge"><i class="bi bi-shield-lock-fill me-1"></i>ADMIN PORTAL</div>
            <h4>SM ENTERPRISES</h4>
            <p>Administrator access portal</p>
        </div>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" placeholder="Enter username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="passward" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-login" name="submit">
                <i class="bi bi-shield-lock-fill me-1"></i>Sign In
            </button>
        </form>
    </div>
</div>

</body>
</html>