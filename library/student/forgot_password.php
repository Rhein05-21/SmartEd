<?php
session_start();

require_once '../config/db_connect.php';
require_once '../admin/config/mailer_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Validate email
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email exists in database
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch();
        
        if ($student) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            // Store token in database
            $stmt = $pdo->prepare("UPDATE students SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
            if ($stmt->execute([$token, $expiry, $email])) {
                // Send reset email
                $reset_link = "http://{$_SERVER['HTTP_HOST']}/e-Shelfv2/student/reset_password.php?token=" . $token;
                $subject = "Password Reset Request - e-Shelf";
                $body = "<p>Hello {$student['firstname']},</p>
                        <p>We received a request to reset your password. Click the link below to reset your password:</p>
                        <p><a href='{$reset_link}'>Reset Password</a></p>
                        <p>This link will expire in 5 minutes.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <p>Best regards,<br>e-Shelf Team</p>";
                
                $mail_result = sendMail($email, $subject, $body);
                
                if ($mail_result['success']) {
                    $_SESSION['success'] = "Password reset link has been sent to your email.";
                } else {
                    $_SESSION['error'] = "Failed to send reset email. Please try again.";
                }
            } else {
                $_SESSION['error'] = "Failed to process reset request. Please try again.";
            }
        } else {
            $_SESSION['error'] = "No account found with this email address.";
        }
    } else {
        $_SESSION['error'] = "Please enter a valid email address.";
    }
    
    header('Location: forgot_password.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - e-Shelf</title>
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
        .forgot-form {
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
    <div class="forgot-form">
        <h1 class="text-center mb-4">Forgot <span style="color: #00d8c7;">Password</span></h1>
        <p class="text-center text-secondary mb-5">Enter your email to receive password reset instructions</p>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-reset">SEND RESET LINK</button>
        </form>

        <div class="links">
            <p>Remember your password? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>
</html>