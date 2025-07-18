<?php
session_start();
require_once '../shared/db_connect.php';
require_once '../shared/mailer_config.php'; // Adjust path if needed

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_number'], $_POST['password'])) {
    $student_number = trim($_POST['student_number']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND verified = 1");
    $stmt->execute([$student_number]);
    $student = $stmt->fetch();

    if ($student && password_verify($password, $student['password'])) {
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['student_email'] = $student['email'];
        header("Location: student_choose_dashboard.php");
        exit();
    } else {
        $login_error = "Invalid student number, password, or account not verified.";
    }
}

$forgot_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Send OTP
    if (isset($_POST['send_otp'], $_POST['forgot_email'])) {
        $email = trim($_POST['forgot_email']);
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch();
        if ($student) {
            $otp = rand(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $stmt = $pdo->prepare("UPDATE students SET reset_otp = ?, reset_otp_expires = ? WHERE email = ?");
            $stmt->execute([$otp, $expires, $email]);
            // Send OTP to email
            $to = $student['email'];
            $subject = "SmartEd Password Reset OTP";
            $body = "Your OTP for password reset is: $otp<br>This code will expire in 10 minutes.";
            sendMail($to, $subject, $body);
            $forgot_message = 'An OTP has been sent to your email.';
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    openForgotModal();
                    showStep2();
                    document.getElementById('forgot-message').textContent = '';
                    document.getElementById('forgot-success').style.display = 'block';
                    document.getElementById('forgot-success').textContent = '$forgot_message';
                    document.getElementById('hidden_forgot_email').value = '$email';
                });
            </script>";
        } else {
            $forgot_message = 'Email not found.';
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    openForgotModal();
                    document.getElementById('forgot-message').textContent = 'Email not found.';
                    document.getElementById('forgot-step1').classList.remove('hidden');
                    document.getElementById('forgot-step2').classList.add('hidden');
                });
            </script>";
        }
    }
    // Step 2: Reset Password
    if (isset($_POST['reset_password'], $_POST['otp'], $_POST['new_password'], $_POST['confirm_new_password'], $_POST['forgot_email'])) {
        $email = trim($_POST['forgot_email']);
        $otp = trim($_POST['otp']);
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch();
        if ($student && $student['reset_otp'] === $otp && strtotime($student['reset_otp_expires']) > time()) {
            if ($new_password === $confirm_new_password && strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE students SET password = ?, reset_otp = NULL, reset_otp_expires = NULL WHERE email = ?");
                $stmt->execute([$hashed, $email]);
                $forgot_message = 'Password reset successful! You can now log in.';
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        closeForgotModal();
                        document.getElementById('global-success').style.display = 'block';
                        document.getElementById('global-success').textContent = 'The password has been reset successfully, please try to login with your new password.';
                    });
                </script>";
            } else {
                $forgot_message = 'Passwords do not match or are too short.';
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        openForgotModal();
                        document.getElementById('forgot-step1').classList.add('hidden');
                        document.getElementById('forgot-step2').classList.remove('hidden');
                        document.getElementById('forgot-message').textContent = '$forgot_message';
                    });
                </script>";
            }
        } else {
            $forgot_message = 'Invalid or expired OTP.';
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    openForgotModal();
                    document.getElementById('forgot-step1').classList.add('hidden');
                    document.getElementById('forgot-step2').classList.remove('hidden');
                    document.getElementById('forgot-message').textContent = '$forgot_message';
                });
            </script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartEd</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg,rgb(176, 230, 188) 0%,rgb(198, 251, 187) 100%);
        }
        .container-auth {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
            position: relative;
            overflow: hidden;
            width: 800px;
            max-width: 100%;
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
            background: linear-gradient(135deg, #008037 0%, #00b050 100%);
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
        /* Main action buttons: white background, green text, green on hover */
        .bg-gradient-to-r.from-purple-500.to-blue-500 {
            background: #fff !important;
            color: #008037 !important;
            border: 2px solid #008037 !important;
            transition: background 0.3s, color 0.3s;
        }
        .bg-gradient-to-r.from-purple-500.to-blue-500:hover,
        .hover\:from-purple-600:hover, .hover\:to-blue-600:hover {
            background: #008037 !important;
            color: #fff !important;
        }
        /* Green border for overlay buttons */
        .border-white {
            border-color: #008037 !important;
        }
        .hover\:bg-white:hover {
            background: #008037 !important;
            color: #fff !important;
        }
        /* Input focus border green */
        .focus\:border-purple-400:focus {
            border-color: #008037 !important;
        }
        /* Text color adjustments for green theme */
        .text-purple-700 {
            color: #008037 !important;
        }
        .text-purple-500 {
            color: #008037 !important;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="container-auth" id="container">
        <div id="global-success" class="mb-4 px-4 py-2 rounded bg-green-100 text-green-800 text-center font-semibold" style="display:none;"></div>
        <div class="forms-container">
            <form class="form sign-in-form active" method="POST" action="">
                <?php if ($login_error): ?>
                    <div class="mb-4 px-4 py-2 rounded bg-red-100 text-red-800 text-center font-semibold">
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
                    <div class="mb-4 px-4 py-2 rounded bg-green-100 text-green-800 text-center font-semibold">
                        Registration successful! Please check your email to verify your account before logging in.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['signup_error']) && $_GET['signup_error'] === 'password_mismatch'): ?>
                    <div class="mb-4 px-4 py-2 rounded bg-red-100 text-red-800 text-center font-semibold">
                        Passwords do not match. Please re-enter your password.
                    </div>
                <?php endif; ?>
                <h2 class="text-3xl font-bold mb-2 text-purple-700">Welcome back!</h2>
                <div class="w-full space-y-6 mt-6">
                    <input type="text" name="student_number" placeholder="Student Number" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <input type="password" name="password" placeholder="Password" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-3 rounded-full font-semibold text-lg hover:from-purple-600 hover:to-blue-600 transition">Log in</button>
                </div>
                <button type="button" id="show-forgot" class="mt-4 text-sm text-purple-500 hover:underline">Forgot your password?</button>
            </form>
            <form class="form sign-up-form" method="POST" action="student_signup_process.php">
                <h2 class="text-3xl font-bold mb-2 text-purple-700">Create account</h2>
                <div class="w-full space-y-6 mt-6">
                    <input type="email" name="email" placeholder="Email address" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <input type="password" name="password" placeholder="Password" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-3 rounded-full font-semibold text-lg hover:from-purple-600 hover:to-blue-600 transition">SIGN UP</button>
                </div>
            </form>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h2 class="text-3xl font-bold mb-2">Already have an account ?</h2>
                    <p class="mb-6">Login with your email & password</p>
                    <button class="border border-white px-8 py-2 rounded-full font-semibold hover:bg-white hover:text-blue-700 transition text-lg" id="signIn">Log in</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h2 class="text-3xl font-bold mb-2">Don't have an account ?</h2>
                    <p class="mb-6">Register here as a Student
                    </p>
                    <button class="border border-white px-8 py-2 rounded-full font-semibold hover:bg-white hover:text-blue-700 transition text-lg" id="signUp">SIGN UP</button>
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

        signUpBtn.onclick = () => {
            container.classList.add('right-panel-active');
            signInForm.classList.remove('active');
            signUpForm.classList.add('active');
        };
        signInBtn.onclick = () => {
            container.classList.remove('right-panel-active');
            signUpForm.classList.remove('active');
            signInForm.classList.add('active');
        };
        document.getElementById('show-forgot').onclick = () => {
            alert('Forgot password functionality goes here.');
        };
    </script>
    <script>
        // Remove ?signup=success from URL after showing the message
        if (window.location.search.includes('signup=success')) {
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('signup');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        }
    </script>
    <!-- Forgot Password Modal -->
    <div id="forgotModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
      <div class="bg-white rounded-lg p-6 w-full max-w-xs" style="position: absolute; left: 24%; top: 50%; transform: translateY(-50%);">
        <h2 class="text-xl font-bold mb-4 text-green-700">Forgot Password</h2>
        <div id="forgot-success" class="text-center text-green-600 mb-2" style="display:none;"></div>
        <form id="forgotForm" method="POST">
          <div id="forgot-step1">
            <input type="email" name="forgot_email" placeholder="Enter your Email" class="w-full mb-4 px-3 py-2 border rounded" required>
            <button type="submit" name="send_otp" class="w-full bg-green-600 text-white py-2 rounded">Send OTP</button>
          </div>
          <div id="forgot-step2" class="hidden">
            <input type="text" name="otp" placeholder="Enter OTP" class="w-full mb-4 px-3 py-2 border rounded" required>
            <input type="password" name="new_password" placeholder="New Password" class="w-full mb-4 px-3 py-2 border rounded" required>
            <input type="password" name="confirm_new_password" placeholder="Confirm New Password" class="w-full mb-4 px-3 py-2 border rounded" required>
            <input type="hidden" name="forgot_email" id="hidden_forgot_email">
            <button type="submit" name="reset_password" class="w-full bg-green-600 text-white py-2 rounded">Reset Password</button>
          </div>
          <div id="forgot-message" class="text-center text-red-600 mt-2"></div>
          <button type="button" onclick="closeForgotModal()" class="mt-4 text-gray-500 hover:underline w-full">Cancel</button>
        </form>
      </div>
    </div>
    <script>
function openForgotModal() {
  document.getElementById('forgotModal').classList.remove('hidden');
  document.getElementById('forgot-step1').classList.remove('hidden');
  document.getElementById('forgot-step2').classList.add('hidden');
  document.getElementById('forgot-message').textContent = '';
  document.getElementById('forgot-success').style.display = 'none';
  document.querySelectorAll('#forgot-step1 input').forEach(function(input) {
    input.disabled = false;
    if (input.name === 'forgot_email') input.required = true;
  });
  document.querySelectorAll('#forgot-step2 input').forEach(function(input) {
    input.disabled = true;
    input.required = false;
  });
}
function closeForgotModal() {
  document.getElementById('forgotModal').classList.add('hidden');
}
document.getElementById('show-forgot').onclick = openForgotModal;
// Set hidden email field when moving to step 2
if (document.getElementById('forgotForm')) {
  document.getElementById('forgotForm').addEventListener('submit', function(e) {
    // If step 1 is visible, disable step 2 fields
    if (!document.getElementById('forgot-step1').classList.contains('hidden')) {
      document.querySelectorAll('#forgot-step2 input').forEach(function(input) {
        input.disabled = true;
        input.required = false;
      });
      document.querySelectorAll('#forgot-step1 input').forEach(function(input) {
        input.disabled = false;
        if (input.name === 'forgot_email') input.required = true;
      });
    }
    // If step 2 is visible, disable step 1 fields
    if (!document.getElementById('forgot-step2').classList.contains('hidden')) {
      document.querySelectorAll('#forgot-step1 input').forEach(function(input) {
        input.disabled = true;
        input.required = false;
      });
      document.querySelectorAll('#forgot-step2 input').forEach(function(input) {
        input.disabled = false;
        input.required = true;
      });
    }
    if (e.submitter && e.submitter.name === 'send_otp') {
      var email = this.forgot_email.value;
      setTimeout(function() {
        var hiddenEmail = document.getElementById('hidden_forgot_email');
        if (hiddenEmail) hiddenEmail.value = email;
      }, 100);
    }
  });
}
// When showing step 2, re-enable its fields
function showStep2() {
  document.getElementById('forgot-step1').classList.add('hidden');
  document.getElementById('forgot-step2').classList.remove('hidden');
  document.querySelectorAll('#forgot-step2 input').forEach(function(input) {
    input.disabled = false;
    input.required = true;
  });
  document.querySelectorAll('#forgot-step1 input').forEach(function(input) {
    input.disabled = true;
    input.required = false;
  });
}
</script>
</body>
</html> 