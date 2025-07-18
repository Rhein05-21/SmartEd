<?php
session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/mailer_config.php';

$login_error = '';
$forgot_error = '';
$forgot_success = '';
$reset_error = '';
$reset_success = '';
$show_otp_form = false;
$reset_email = '';

function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // LOGIN
    if (isset($_POST['login_admin_id'], $_POST['login_password'])) {
        $admin_id = sanitize_input($_POST['login_admin_id']);
        $password = $_POST['login_password'];
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE admin_id = ?');
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_email'] = $admin['email'];
            header('Location: choose_dashboard.php');
            exit();
        } else {
            $login_error = 'Invalid Admin ID or password.';
        }
    }
    // FORGOT PASSWORD - REQUEST OTP
    if (isset($_POST['forgot_email'])) {
        $forgot_email = sanitize_input($_POST['forgot_email']);
        if (!filter_var($forgot_email, FILTER_VALIDATE_EMAIL)) {
            $forgot_error = 'Invalid email format.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
            $stmt->execute([$forgot_email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$admin) {
                $forgot_error = 'No account found with that email.';
            } else {
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes
                $stmt = $pdo->prepare('UPDATE admins SET reset_otp = ?, reset_otp_expires = ? WHERE email = ?');
                $stmt->execute([$otp, $expires, $forgot_email]);
                $subject = 'Examrary Password Reset OTP';
                $body = 'Your OTP for password reset is: <b>' . $otp . '<br>This code will expire in 10 minutes.';
                $mailResult = sendMail($forgot_email, $subject, $body);
                if ($mailResult['success']) {
                    // Redirect to reset page with email as GET parameter
                    header('Location: admin_reset_password.php?email=' . urlencode($forgot_email));
                    exit();
                } else {
                    $forgot_error = 'Failed to send OTP: ' . $mailResult['message'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartEd - Admin Auth</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url("background.jpg");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
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
            opacity: 0;
            z-index: 1;
            transition: opacity 0.3s, z-index 0.3s;
        }
        .form.active {
            opacity: 1;
            z-index: 2;
            position: relative;
        }
        .overlay-container {
            background-image: url("smarted.jpg");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: right center;
            position: absolute;
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
        .container-auth.right-panel-active .forms-container {
            transform: translateX(100%);
        }
        .container-auth.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }
        .container-auth.right-panel-active .overlay {
            transform: translateX(50%);
        }
        .container-auth .sign-up-form {
            pointer-events: none;
        }
        .container-auth.right-panel-active .sign-up-form {
            pointer-events: auto;
        }
        .container-auth .sign-in-form {
            pointer-events: auto;
        }
        .container-auth.right-panel-active .sign-in-form {
            pointer-events: none;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="container-auth" id="container">
        <div class="forms-container">
            <form class="form sign-in-form active" method="POST" autocomplete="off">
                <h2 class="text-3xl font-bold mb-2 text-gray-600">Welcome Admin!</h2>
                <?php if ($login_error): ?>
                    <div class="text-red-500 mb-2"><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>
                <div class="w-full space-y-6 mt-6">
                    <input type="text" name="login_admin_id" placeholder="Admin ID" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-gray-400 bg-transparent text-lg focus:outline-none" required value="<?php echo isset(
                        $_POST['login_admin_id']) ? htmlspecialchars($_POST['login_admin_id']) : '' ?>">
                    <input type="password" name="login_password" placeholder="Password" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-gray-400 bg-transparent text-lg focus:outline-none" required>
                    <button type="submit" class="w-full bg-gray-300 text-gray-800 py-3 rounded-full font-semibold text-lg hover:bg-gray-800 hover:text-gray-100 transition">Log in</button>
                </div>
                <button type="button" id="show-forgot" class="mt-4 text-sm text-gray-500 hover:underline">Forgot your password?</button>
            </form>
        </div>
        
        <div class="overlay-container">
            <div class="overlay">
                <!-- Forgot Password Modal (right side, dark theme) -->
                <div id="forgotModal" class="fixed inset-0 flex items-center justify-end bg-black bg-opacity-60 z-50 hidden">
                  <div class="bg-gray-900 rounded-xl shadow-2xl p-6 w-full max-w-xs mr-8 text-center">
                    <h3 class="text-lg font-semibold mb-4 text-white">Forgot Password</h3>
                    <form method="POST" id="forgotForm">
                      <input type="email" name="forgot_email" id="forgot_email" placeholder="Enter your email" class="w-full mb-4 px-3 py-2 border border-gray-700 rounded bg-gray-800 text-white placeholder-gray-400 focus:border-purple-400 focus:bg-gray-800" required>
                      <button type="submit" class="w-full bg-gradient-to-r from-purple-900 to-blue-900 text-white py-2 rounded font-semibold">Send OTP</button>
                      <button type="button" id="closeForgot" class="w-full mt-2 py-2 rounded bg-gray-700 text-gray-200 hover:bg-gray-600">Cancel</button>
                      <div id="forgotError" class="text-red-400 mt-2"><?php if ($forgot_error) echo htmlspecialchars($forgot_error); ?></div>
                      <div id="forgotSuccess" class="text-green-400 mt-2"><?php if ($forgot_success) echo htmlspecialchars($forgot_success); ?></div>
                    </form>
                  </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const container = document.getElementById('container');
        const signUpBtn = document.getElementById('signUp');
        const signInBtn = document.getElementById('signIn');
        const signInForm = document.querySelector('.sign-in-form');
        const signUpForm = document.querySelector('.sign-up-form');

        document.addEventListener('DOMContentLoaded', function() {
            const forgotBtn = document.getElementById('show-forgot');
            const forgotModal = document.getElementById('forgotModal');
            const closeForgot = document.getElementById('closeForgot');
            if (forgotBtn && forgotModal && closeForgot) {
                forgotBtn.onclick = () => { forgotModal.classList.remove('hidden'); };
                closeForgot.onclick = () => { forgotModal.classList.add('hidden'); };
            }
        });
    </script>
</body>
</html> 