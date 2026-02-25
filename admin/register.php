<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Registration</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../js/bootstrap.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center">Administrator Registration</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" name="uname" required>
            </div>
            <div class="mb-3">
                <label for="passward" class="form-label">Password</label>
                <input type="password" class="form-control" name="pass" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary">Create Account</button>
        </form>
        <?php if(!empty($error ?? '')){ echo '<div class="alert alert-danger mt-3">'.htmlspecialchars($error).'</div>'; } ?>
    </div>
</body>
</html>
<?php
include 'conn.php';
if(isset($_POST['register'])){
$username = mysqli_real_escape_string($con, $_POST['uname'] ?? '');
$passward = mysqli_real_escape_string($con, $_POST['pass'] ?? '');
$sqlq = "INSERT INTO user_login (username,passward) VALUES('$username','$passward')";
$result = mysqli_query($con,$sqlq);
if($result){
    $_SESSION['is_login']=true;
    $_SESSION['username']=$username;
    header('location:index.php');
    exit;
}else{
    $error = 'Registration Failed';
}
}
?>