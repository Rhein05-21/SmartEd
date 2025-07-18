<?php
session_start();

require_once '../config/db_connect.php';

if (!isset($_GET['token'])) {
    header('Location: login.php');
    exit();
}

$token = $_GET['token'];
$current_time = date('Y-m-d H:i:s');

// Check if token exists and is valid
$stmt = $pdo->prepare("SELECT * FROM students WHERE reset_token = ? AND reset_token_expiry > ?");
$stmt->execute([$token, $current_time]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = "Invalid or expired reset token. Please request a new password reset.";
    header('Location: forgot_password.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Update password and clear reset token
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE students SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
        
        if ($stmt->execute([$hashed_password, $token])) {
            $_SESSION['success'] = "Password has been reset successfully. You can now login with your new password.";
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['error'] = "Failed to reset password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - e-Shelf</title>
    <link rel="icon" type="image/x-icon" href="version2.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #888;
            text-decoration: none;
        }
        .links a:hover {
            color: #00d8c7;
        }
    </style>
</head>
<body>
    <div class="reset-form">
        <h1 class="text-center mb-4">Reset <span style="color: #00d8c7;">Password</span></h1>
        <p class="text-center text-secondary mb-5">Enter your new password</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3 position-relative">
                <input type="password" name="password" class="form-control" placeholder="New password" required>
                <i class="fas fa-eye position-absolute end-0 top-50 translate-middle-y me-3" 
                   style="cursor: pointer;" 
                   onclick="togglePassword(this)"></i>
            </div>
            <div class="mb-3 position-relative">
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
                <i class="fas fa-eye position-absolute end-0 top-50 translate-middle-y me-3" 
                   style="cursor: pointer;" 
                   onclick="togglePassword(this)"></i>
            </div>
            <button type="submit" class="btn btn-reset">RESET PASSWORD</button>
        </form>

        <div class="links">
            <p>Remember your password? <a href="login.php">Login</a></p>
        </div>
    </div>

    <script>
        function togglePassword(icon) {
            const passwordInput = icon.previousElementSibling;
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>