<?php
session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/mailer_config.php';

$reset_error = '';
$reset_success = '';
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['reset_email']) ? htmlspecialchars($_POST['reset_email']) : '';
    $reset_otp = isset($_POST['reset_otp']) ? htmlspecialchars($_POST['reset_otp']) : '';
    $reset_password = isset($_POST['reset_password']) ? $_POST['reset_password'] : '';
    $reset_confirm_password = isset($_POST['reset_confirm_password']) ? $_POST['reset_confirm_password'] : '';
    // Validate
    if ($reset_password !== $reset_confirm_password) {
        $reset_error = 'Passwords do not match.';
    } elseif (
        strlen($reset_password) < 8 ||
        !preg_match('/[A-Z]/', $reset_password) ||
        !preg_match('/[0-9]/', $reset_password) ||
        !preg_match('/[^A-Za-z0-9]/', $reset_password)
    ) {
        $reset_error = 'Password must be at least 8 characters, contain at least one uppercase letter, one number, and one special character.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? AND reset_otp = ? AND reset_otp_expires > NOW()');
        $stmt->execute([$email, $reset_otp]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$admin) {
            $reset_error = 'Invalid or expired OTP.';
        } else {
            $hashed_password = password_hash($reset_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE admins SET password = ?, reset_otp = NULL, reset_otp_expires = NULL WHERE email = ?');
            $stmt->execute([$hashed_password, $email]);
            // Redirect to login page with success message
            $_SESSION['reset_success'] = 'Password reset successful! You can now log in.';
            header('Location: admin_login.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SmartEd Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url("background.jpg");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            backdrop-filter: blur(2px);
        }
        .container-auth {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
            position: relative;
            overflow: hidden;
            width: 800px;
            max-width: 100%;
            min-width: 650px;
            min-height: 500px;
        }
        .forms-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 50%;
            height: 100%;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .form {
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            width: 100%;
            max-width: 320px;
            padding: 0 30px;
            position: absolute;
            left: 0;
            right: 0;
            margin: auto;
            opacity: 1;
            z-index: 2;
        }
        .overlay-container {
            background-image: url("smarted.jpg");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: right center;
            position: absolute;
            backdrop-filter: blur(5px)
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            z-index: 100;
            transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .overlay {
            color: #fff;
            position: absolute;
            left: -100%;
            width: 200%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            transform: translateX(0);
        }
        .overlay-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 50%;
            height: 100%;
            position: absolute;
            top: 0;
        }
        .overlay-left {
            left: 0;
        }
        .overlay-right {
            right: 0;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="container-auth" id="container">
        <div class="forms-container">
            <form class="form" method="POST" autocomplete="off">
                <h2 class="text-3xl font-bold mb-2 text-gray-600">Reset Password</h2>
                <?php if ($reset_error): ?>
                    <div class="text-red-500 mb-2"><?php echo htmlspecialchars($reset_error); ?></div>
                <?php endif; ?>
                <input type="hidden" name="reset_email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="text" name="reset_otp" placeholder="Enter OTP" class="w-full mb-4 px-3 py-2 border border-gray-300 rounded bg-gray-50 focus:border-purple-400" required>
                <input type="password" name="reset_password" placeholder="New Password" class="w-full mb-4 px-3 py-2 border border-gray-300 rounded bg-gray-50 focus:border-purple-400" required minlength="8">
                <input type="password" name="reset_confirm_password" placeholder="Confirm New Password" class="w-full mb-4 px-3 py-2 border border-gray-300 rounded bg-gray-50 focus:border-purple-400" required minlength="8">
                <button type="submit" class="w-full bg-gray-800 text-gray-100 py-3 rounded-full font-semibold text-lg hover:bg-gray-300 hover:text-gray-800 transition">Reset Password</button>
                <div class="mt-4 text-center">
                    <a href="admin_login.php" class="text-sm text-gray-500 hover:underline">Back to Login</a>
                </div>
            </form>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-right">
                    
                </div>
            </div>
        </div>
    </div>
</body>
</html> 