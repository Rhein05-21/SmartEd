<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/db_connect.php';
require_once '../utils/notification_handler.php';

$student_id = $_SESSION['student_id'];
$name = $_SESSION['name'];

// Get the internal student ID
$stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    // Handle error, e.g., redirect or show message
    die("Student not found.");
}

$internal_student_id = $student['id'];

// Get all notifications for the student
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE student_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$internal_student_id]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications - e-Shelf</title>
    <link rel="icon" type="image/x-icon" href="version2.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #1c2331;
            --secondary-bg: #252d3b;
            --accent-color: #00d8c7;
            --text-color: #ffffff;
            --muted-text: #cbd5e0;
            --border-color: #2d3748;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-color);
            min-height: 100vh;
        }

        .notification-card {
            background-color: var(--secondary-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s;
        }

        .notification-card:hover {
            transform: translateX(5px);
        }

        .notification-card.unread {
            border-left: 4px solid var(--accent-color);
        }

        .notification-time {
            color: var(--muted-text);
            font-size: 0.9em;
        }

        .back-button {
            background-color: var(--accent-color);
            color: var(--primary-bg);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background-color: #00a699;
            color: var(--text-color);
        }

        .notification-content {
            margin: 10px 0;
        }

        .no-notifications {
            text-align: center;
            padding: 40px;
            color: var(--muted-text);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>

        <h2 class="mb-4">All Notifications</h2>

        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                    <div class="notification-content">
                        <?php echo htmlspecialchars($notification['message']); ?>
                    </div>
                    <div class="notification-time">
                        <i class="far fa-clock me-1"></i>
                        <?php echo date('F d, Y h:i A', strtotime($notification['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications">
                <i class="far fa-bell fa-3x mb-3"></i>
                <h4>No notifications yet</h4>
                <p>You haven't received any notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
