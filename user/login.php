<?php
session_start();
include '../admin/conn.php';

if(isset($_POST['submit'])){
    // Sanitize inputs to prevent basic SQL injection
    $username = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    $sqlq = "SELECT * FROM `customer_login` WHERE c_email='$username' AND c_password='$password'";
    $result = mysqli_query($con, $sqlq);   
    
    if(mysqli_num_rows($result) > 0){ 
        $_SESSION['is_login'] = true;
        $_SESSION['username'] = $username;
        echo "<script>alert('Login successful!'); window.location.href='index.php';</script>";
        exit;
    } else {
        $error_msg = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - SM ENTERPRISES</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../js/bootstrap.min.js"></script>
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
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.3);
            padding: 45px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-section img {
            width: 70px;
            height: 70px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
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

        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #64748b;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #000000;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .register-link a:hover {
            color: #2d3748;
            text-decoration: underline;
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
            <h4>SM ENTERPRISES</h4>
            <p>Welcome back! Please login to your account.</p>
        </div>

        <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register Here</a>
        </div>
    </div>
</div>

</body>
</html>