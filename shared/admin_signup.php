<?php
session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/mailer_config.php';

$signup_error = '';
$signup_success = '';

function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['signup_email'], $_POST['signup_password'], $_POST['signup_confirm_password'])) {
        $email = sanitize_input($_POST['signup_email']);
        $password = $_POST['signup_password'];
        $confirm_password = $_POST['signup_confirm_password'];
        // Backend validation
        if ($email === '' || $password === '' || $confirm_password === '') {
            $signup_error = 'All fields are required.';
        } elseif (preg_match('/^\s*$/', $email) || preg_match('/^\s*$/', $password) || preg_match('/^\s*$/', $confirm_password)) {
            $signup_error = 'Fields cannot be empty or spaces only.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $signup_error = 'Invalid email format.';
        } elseif ($password !== $confirm_password) {
            $signup_error = 'Passwords do not match.';
        } elseif (
            strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            $signup_error = 'Password must be at least 8 characters, contain at least one uppercase letter, one number, and one special character.';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $signup_error = 'Email already registered.';
            } else {
                // Generate a unique admin_id: ADMIN-<randomnumber>
                $base_admin_id = 'ADMIN-' . rand(1000, 99999);
                $admin_id = $base_admin_id;
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE admin_id = ?');
                while (true) {
                    $stmt->execute([$admin_id]);
                    if ($stmt->fetchColumn() == 0) break;
                    $admin_id = $base_admin_id . rand(1, 99);
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO admins (firstname, middlename, lastname, email, admin_id, password, created_at, updated_at) VALUES (NULL, NULL, NULL, ?, ?, ?, NOW(), NOW())');
                if ($stmt->execute([$email, $admin_id, $hashed_password])) {
                    // Send email with admin ID
                    $subject = 'Your Examrary Admin Account';
                    $body = 'Welcome to Examrary!<br>Your Admin ID is: <b>' . htmlspecialchars($admin_id) . '</b><br>Please always remember this.<br>Use this Admin ID to log in.';
                    $mailResult = sendMail($email, $subject, $body);
                    if ($mailResult['success']) {
                        $signup_success = 'Account created! Your Admin ID has been sent to your email.';
                    } else {
                        $signup_error = 'Account created, but failed to send email: ' . $mailResult['message'];
                    }
                } else {
                    $signup_error = 'Signup failed. Please try again.';
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
    <title>SmartEd - Admin Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #d1c4e9 0%, #bbdefb 100%);
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
        .form {
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            width: 100%;
            max-width: 320px;
            padding: 0 30px;
            margin: auto;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="container-auth" id="container">
        <form class="form" method="POST" autocomplete="off" id="signupForm" novalidate>
            <h2 class="text-3xl font-bold mb-2 text-purple-700">Create Admin Account</h2>
            <?php if ($signup_error): ?>
                <div class="text-red-500 mb-2"><?php echo htmlspecialchars($signup_error); ?></div>
                <?php if (strpos($signup_error, 'Email already registered.') !== false): ?>
                    <script>alert('Email already registered. Please use a different email.');</script>
                <?php endif; ?>
            <?php elseif ($signup_success): ?>
                <div class="text-green-500 mb-2"><?php echo htmlspecialchars($signup_success); ?></div>
            <?php endif; ?>
            <div class="w-full space-y-6 mt-6">
                <input type="email" name="signup_email" id="signup_email" placeholder="Email address" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                <div class="relative w-full">
                    <input type="password" name="signup_password" id="signup_password" placeholder="Password" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none pr-10" required minlength="8" pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$">
                    <button type="button" id="togglePassword" class="absolute right-2 top-2 text-gray-400" tabindex="-1">
                        <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592m3.1-2.727A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.965 9.965 0 01-4.293 5.411M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 6L6 6" /></svg>
                    </button>
                </div>
                <input type="password" name="signup_confirm_password" id="signup_confirm_password" placeholder="Confirm Password" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required minlength="8">
                <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-3 rounded-full font-semibold text-lg hover:from-purple-600 hover:to-blue-600 transition">Sign Up</button>
                <div id="signupError" class="text-red-500 mt-2 hidden"></div>
            </div>
        </form>
    </div>
    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('signup_password');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');
        togglePassword.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        });
        // Frontend validation
        const signupForm = document.getElementById('signupForm');
        const signupError = document.getElementById('signupError');
        signupForm.addEventListener('submit', function(e) {
            signupError.classList.add('hidden');
            let email = document.getElementById('signup_email').value.trim();
            let password = document.getElementById('signup_password').value;
            let confirmPassword = document.getElementById('signup_confirm_password').value;
            let error = '';
            if (!email || !password || !confirmPassword) {
                error = 'All fields are required.';
            } else if (/^\s*$/.test(email) || /^\s*$/.test(password) || /^\s*$/.test(confirmPassword)) {
                error = 'Fields cannot be empty or spaces only.';
            } else if (!/^\S+@\S+\.\S+$/.test(email)) {
                error = 'Invalid email format.';
            } else if (password !== confirmPassword) {
                error = 'Passwords do not match.';
            } else if (
                password.length < 8 ||
                !/[A-Z]/.test(password) ||
                !/[0-9]/.test(password) ||
                !/[^A-Za-z0-9]/.test(password)
            ) {
                error = 'Password must be at least 8 characters, contain at least one uppercase letter, one number, and one special character.';
            } else if (/<|>|script/i.test(email) || /<|>|script/i.test(password)) {
                error = 'Invalid characters detected.';
            }
            if (error) {
                signupError.textContent = error;
                signupError.classList.remove('hidden');
                e.preventDefault();
            }
        });
    </script>
</body>
</html> 