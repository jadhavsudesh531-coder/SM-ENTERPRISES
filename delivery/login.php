<?php
session_start();
include('../admin/conn.php');

$createAgentTableSql = "CREATE TABLE IF NOT EXISTS delivery_agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_name VARCHAR(120) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    passward VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($con, $createAgentTableSql);

$error = '';

if (isset($_SESSION['delivery_agent_id'])) {
    header('location:index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $passward = trim($_POST['passward'] ?? '');

    if ($username === '' || $passward === '') {
        $error = 'Username and password are required.';
    } else {
        $stmt = mysqli_prepare($con, "SELECT id, agent_name, username FROM delivery_agents WHERE username=? AND passward=? AND is_active=1 LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $username, $passward);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $agent = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if ($agent) {
                $_SESSION['delivery_agent_id'] = (int)$agent['id'];
                $_SESSION['delivery_agent_name'] = $agent['agent_name'];
                $_SESSION['delivery_agent_username'] = $agent['username'];
                header('location:index.php');
                exit;
            }
            $error = 'Invalid agent ID or password.';
        } else {
            $error = 'Unable to login right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Login - SM ENTERPRISES</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h4 class="mb-1">Delivery Agent Login</h4>
                            <p class="text-muted mb-0">SM ENTERPRISES delivery portal</p>
                        </div>

                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Agent ID</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="passward" class="form-control" required>
                            </div>
                            <button type="submit" name="submit" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </button>
                        </form>

                        <a href="../admin/login.php" class="btn btn-link w-100 mt-2">Back to Admin</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
