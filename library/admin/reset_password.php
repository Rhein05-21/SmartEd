<?php
session_start();
require_once 'config/db_connect.php';

$error = '';
$success = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and not expired
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "Invalid or expired reset link.";
    }
} else {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Update password and clear reset token
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $stmt->execute([$hash, $token]);
        
        $success = "Password has been reset successfully. You can now login with your new password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="icon" type="image/x-icon" href="version2.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #000;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-form {
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        .form-control {
            background-color: transparent;
            border: 1px solid #333;
            color: #fff;
            padding: 12px;
            margin-bottom: 20px;
        }
        .form-control:focus {
            background-color: transparent;
            color: #fff;
            border-color: #00d8c7;
            box-shadow: none;
        }
        .btn-reset {
            width: 100%;
            padding: 12px;
            background-color: #00d8c7;
            border: none;
            color: #000;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-form">
        <h1 class="text-center mb-4">Reset <span style="color: #00d8c7;">Password</span></h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <p class="mt-3"><a href="login.php" class="text-success">Click here to login</a></p>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-reset">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
