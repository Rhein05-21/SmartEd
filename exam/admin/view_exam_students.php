<?php
session_start();
require_once '../../shared/db_connect.php';
require_once '../includes/functions.php';
require_once '../config/timezone.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /finalProject/login.php');
    exit();
}

if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    header('Location: exams.php');
    exit();
}

$exam_id = intval($_GET['exam_id']);

// Fetch exam details
$stmt = $pdo->prepare("SELECT e.*, c.course_name FROM exams e JOIN courses c ON e.course_id = c.course_id WHERE e.exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header('Location: exams.php');
    exit();
}

// Fetch students for this exam
$stmt = $pdo->prepare("
    SELECT s.*, 
           CONCAT(s.firstname, ' ', s.lastname) AS full_name,
           IFNULL(ea.status, 'Not Taken') AS exam_status,
           ea.score,
           ea.end_time,
           re.reopen_start_date,
           re.reopen_end_date
    FROM students s
    JOIN enrollments en ON s.student_id = en.student_id AND en.course_id = ? AND en.is_approved = 1
    LEFT JOIN (
        SELECT *
        FROM exam_attempts
        WHERE exam_id = ?
        AND status = 'finished'
        ORDER BY end_time DESC
    ) ea ON s.student_id = ea.student_id
    LEFT JOIN reopened_exams re ON s.student_id = re.student_id AND re.exam_id = ?
");
$stmt->execute([$exam['course_id'], $exam_id, $exam_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch admin info for sidebar
$stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Exam Students';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ExaMatrix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/finalProject/assets/css/styles.css">
</head>
<body class="dashboard-layout">
    <div class="sidebar">
        <div class="brand-section">
            <a href="../../shared/choose_dashboard.php" class="btn btn-light ms-3" style="background: #fff; color: rgba(66, 65, 65, 0.99); border: none; font-weight: bold; box-shadow: 0 1px 4px rgba(0,0,0,0.05);"><i class="fas fa-arrow-left me-1"></i></a>
            <img src="smarted-letter-only.jpg" alt="SmartED Logo" class=" img-fluid ms-3" style="max-width: 100px;">
        </div>
        <nav class="nav-menu">
            <ul>
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="courses.php" class="nav-link">
                        <i class="fas fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="students.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="exams.php" class="nav-link active">
                        <i class="fas fa-file-alt"></i>
                        <span>Exams</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="approve_students.php" class="nav-link">
                        <i class="fas fa-user-check"></i>
                        <span>Course Enrollments</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="admin-section">
            <div class="admin-avatar">AD</div>
            <div class="admin-details">
                <div class="admin-name"><?php echo htmlspecialchars($admin['admin_id']); ?></div>
                <div class="admin-role">Administrator</div>
            </div>
            <a href="../logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="content-header">
            <div>
                <h1>Exam Students</h1>
                <p class="text-muted"><?php echo htmlspecialchars($exam['exam_name']); ?> - <?php echo htmlspecialchars($exam['course_name']); ?></p>
            </div>
            <div class="date-time">
                <i class="far fa-calendar-alt"></i>
                <?php echo date('F d, Y'); ?>
                <span class="separator">|</span>
                <i class="far fa-clock"></i>
                <span id="live-time"></span>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2>Student Information</h2>
                <a href="exams.php" class="btn-create">
                    <i class="fas fa-arrow-left"></i> Back to Exams
                </a>
            </div>

            <div class="exam-info-card">
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-item">
                            <label>Total Students:</label>
                            <span><?php echo count($students); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item">
                            <label>Exam Taken:</label>
                            <span><?php echo count(array_filter($students, function($s) { return $s['exam_status'] === 'finished'; })); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item">
                            <label>Not Taken:</label>
                            <span><?php echo count(array_filter($students, function($s) { return $s['exam_status'] === 'Not Taken'; })); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item">
                            <label>Reopened:</label>
                            <span><?php echo count(array_filter($students, function($s) { return $s['exam_status'] === 'Reopened'; })); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Exam Status</th>
                            <th>Score</th>
                            <th>Completion Time</th>
                            <th>Reopen Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($student['exam_status']) {
                                        case 'finished':
                                            $status_class = 'success';
                                            break;
                                        case 'Reopened':
                                            $status_class = 'warning';
                                            break;
                                        case 'Not Taken':
                                            $status_class = 'danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $student['exam_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($student['exam_status'] === 'finished' && $student['score'] !== null) {
                                        echo round($student['score'], 1) . '%';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($student['exam_status'] === 'finished' && $student['end_time']) {
                                        echo date('M d, Y H:i', strtotime($student['end_time']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($student['exam_status'] === 'Reopened' && $student['reopen_start_date'] && $student['reopen_end_date']) {
                                        echo date('M d, Y H:i', strtotime($student['reopen_start_date'])) . ' - ' . 
                                             date('M d, Y H:i', strtotime($student['reopen_end_date']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateTime() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            
            // Convert to 12-hour format
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            
            // Add leading zeros
            minutes = minutes < 10 ? '0' + minutes : minutes;
            
            // Update the time display
            document.getElementById('live-time').textContent = hours + ':' + minutes + ' ' + ampm;
        }

        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>

<style>
body {
    background-color: #1e1f25;
    color: #a1a1a1;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    margin: 0;
    padding: 0;
}

.dashboard-layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 250px;
    background-color: rgb(53, 54, 57);
    padding: 20px;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

.brand-section {
    display: flex;
    align-items: center;
    margin-bottom: 40px;
    padding: 0 10px;
}

.logo {
    width: 40px;
    height: 40px;
    background-color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 20px;
    color: #1e1f25;
    margin-right: 12px;
}

.brand-name {
    color: white;
    font-size: 20px;
    margin: 0;
    font-weight: 600;
}

.nav-menu {
    flex: 1;
}

.nav-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin-bottom: 8px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: #a1a1a1;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.nav-link:hover, .nav-link.active {
    background-color: #3498db;
    color: white;
}

.nav-link i {
    margin-right: 12px;
    width: 20px;
    text-align: center;
}

.admin-section {
    display: flex;
    align-items: center;
    padding: 15px;
    background-color: #1e1f25;
    border-radius: 8px;
    margin-top: auto;
}

.admin-avatar {
    width: 40px;
    height: 40px;
    background-color: #3498db;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 12px;
}

.admin-details {
    flex: 1;
}

.admin-name {
    color: white;
    font-size: 14px;
    margin-bottom: 2px;
    font-weight: 500;
}

.admin-role {
    color: #a1a1a1;
    font-size: 12px;
}

.logout-btn {
    color: #ff4757;
    text-decoration: none;
    padding: 8px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.logout-btn:hover {
    background-color: #ff4757;
    color: white;
}

.main-content {
    flex: 1;
    padding: 30px;
    overflow-y: auto;

    max-width: 1500px;
    width: 100%;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.content-header h1 {
    color: white;
    font-size: 24px;
    margin: 0;
}

.content-header p {
    margin: 5px 0 0 0;
    color: #a1a1a1;
}

.date-time {
    color: #a1a1a1;
    font-size: 14px;
}

.date-time i {
    margin-right: 8px;
    color: #3498db;
}

.separator {
    margin: 0 15px;
    color: #373a40;
}

.dashboard-section {
    background-color: rgb(53, 54, 57);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    color: white;
    font-size: 18px;
    margin: 0;
}

.btn-create {
    display: inline-flex;
    align-items: center;
    padding: 8px 15px;
    background-color: #2ecc71;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.btn-create:hover {
    background-color: #27ae60;
    color: white;
}

.btn-create i {
    margin-right: 6px;
}

.exam-info-card {
    background-color: #1e1f25;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.info-item {
    text-align: center;
}

.info-item label {
    display: block;
    color: #a1a1a1;
    font-size: 12px;
    margin-bottom: 5px;
}

.info-item span {
    display: block;
    color: white;
    font-size: 24px;
    font-weight: bold;
}

.table {
    color: #a1a1a1;
    margin: 0;
}

.table thead th {
    background-color: #1e1f25;
    border-bottom: 2px solid #373a40;
    color: #a1a1a1;
    font-weight: 500;
    padding: 12px;
}

.table tbody td {
    padding: 12px;
    border-bottom: 1px solid #373a40;
}

.table tbody tr:hover {
    background-color: #1e1f25;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}

@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }

    .brand-name, .nav-link span, .admin-details {
        display: none;
    }

    .nav-link {
        justify-content: center;
        padding: 15px;
    }

    .nav-link i {
        margin: 0;
    }

    .admin-section {
        padding: 10px;
    }

    .main-content {
        padding: 20px;
    }

    .table-responsive {
        overflow-x: auto;
    }
}
</style> 