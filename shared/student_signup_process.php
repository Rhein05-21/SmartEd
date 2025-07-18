<?php
require_once '../shared/db_connect.php'; // adjust path as needed
require_once '../shared/mailer_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email format.');
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die('Email already registered.');
    }

    // Check password match
    if ($password !== $confirm_password) {
        header("Location: student_login.php?signup_error=password_mismatch");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate next student ID in the format 2025-xxxx
    $year = '2025';
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE student_id LIKE ? ORDER BY student_id DESC LIMIT 1");
    $stmt->execute([$year . '-%']);
    $last = $stmt->fetchColumn();

    if ($last) {
        $last_num = intval(substr($last, 5)); // get the xxxx part
        $next_num = $last_num + 1;
    } else {
        $next_num = 1;
    }
    $student_id = sprintf('%s-%04d', $year, $next_num);

    // Insert student as verified (no verification needed)
    $stmt = $pdo->prepare("INSERT INTO students (email, password, verified, student_id) VALUES (?, ?, 1, ?)");
    $stmt->execute([$email, $hashed_password, $student_id]);

    // Send email with student_id
    $subject = "Your SmartEd Student Number";
    $message = "Your student number is: $student_id";
    $mailResult = sendMail($email, $subject, $message);
    if (!$mailResult['success']) {
        die('Failed to send student number email. ' . $mailResult['message']);
    }

    // Redirect to login with message
    header("Location: student_login.php?signup=success");
    exit();
}
?>
