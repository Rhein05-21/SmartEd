<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../shared/admin_login.php");
    exit();
}

require_once '../../shared/db_connect.php';
require_once 'notification_handler.php';

// Change Password Logic
$change_password_message = '';
$change_password_error = '';
$show_change_password_modal = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password_submit'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current_password, $admin['password'])) {
        $change_password_error = 'Current password is incorrect.';
        $show_change_password_modal = true;
    } elseif ($new_password !== $confirm_password) {
        $change_password_error = 'New passwords do not match.';
        $show_change_password_modal = true;
    } elseif (strlen($new_password) < 6) {
        $change_password_error = 'New password must be at least 6 characters long.';
        $show_change_password_modal = true;
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?');
        $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
        $change_password_message = 'Password successfully updated!';
        $show_change_password_modal = true;
    }
}

// Get available books count
$stmt = $pdo->query("SELECT COUNT(*) FROM books WHERE available = 1 AND archived = 0");
$available_books = $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT b.*, a.name as author_name, c.name as category_name 
    FROM books b 
    LEFT JOIN authors a ON b.author_id = a.id 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.archived = 0
    ORDER BY b.title 
    LIMIT 5
");

// Get dashboard statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_books FROM books");
$total_books = $stmt->fetch()['total_books'];

$stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
$total_students = $stmt->fetch()['total_students'];

// Modify the books query to use simple LIMIT without pagination
$stmt = $pdo->prepare("
    SELECT b.*, a.name as author_name, c.name as category_name 
    FROM books b 
    LEFT JOIN authors a ON b.author_id = a.id 
    LEFT JOIN categories c ON b.category_id = c.id 
    ORDER BY b.title 
    LIMIT 5
");
$stmt->execute();
$books = $stmt->fetchAll();

// Add pagination controls in the books table section
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Library Dashboard</title>
    <link rel="icon" type="image/x-icon" href="smarted-web-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
        }
        .sidebar {
            background-color:rgb(46, 46, 48);
            color: white;
            min-height: 100vh;
            padding: 20px;
            border-right: 1px solid #2d3748;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            transform: translateX(0);
        }
        .sidebar.collapsed {
            transform: translateX(-250px);
        }
        .menu-btn {
            display: none;
            margin-top: 11%;
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            padding: 10px;
            position: absolute;
            color: rgba(82, 80, 78, 0.99);
            top: 10px;
            right: 10px;
            z-index: 1000; /* higher than logo bar */
        }
        @media (max-width: 768px) {
            .menu-btn {
                display: block;
            }
            .sidebar {
                position: fixed;
                z-index: 1040;
                width: 250px;
                height: 100vh;
                left: 0;
                top: 0;
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            margin: 5px 0;
        }
        .sidebar a:hover {
            background-color: #2c3344;
        }
        .sidebar a.active {
            background-color: #2c3344;
            border-left: 3px solid rgba(218, 214, 214, 0.99)   ;
        }
        .main-content {
            padding: 20px;
            color: #ffffff;
        }

        .chat-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background-color: #252d3b;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            z-index: 1000;
            border: 1px solid #2d3748;
        }

        .chat-header {
            padding: 10px;
            background-color: #1c2331;
            color: #ffffff;
            border-radius: 10px 10px 0 0;
            cursor: pointer;
            font-weight: bold;
        }

        .chat-messages {
            height: 300px;
            overflow-y: auto;
            padding: 10px;
            display: none;
        }

        .chat-input-container {
            padding: 10px;
            border-top: 1px solid #2d3748;
            display: none;
        }

        .chat-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #2d3748;
            border-radius: 5px;
            background-color: #1c2331;
            color: #ffffff;
            margin-bottom: 5px;
        }

        .message {
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 5px;
            max-width: 80%;
        }

        .message.sent {
            background-color:rgb(17, 26, 43);
            color: rgba(243, 177, 70, 0.99);
            margin-left: auto;
        }

        .message.received {
            background-color:rgb(17, 26, 43);
            color: #ffffff;
        }

        .message .sender {
            font-size: 0.8em;
            margin-bottom: 2px;
            opacity: 0.8;
        }

        .message .time {
            font-size: 0.7em;
            opacity: 0.6;
            margin-top: 2px;
            text-align: right;
        }
        /* Add these styles in the <style> section */
        .stat-card {
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            color: white;
            transition: transform 0.2s ease;
            cursor: pointer;
            position: relative;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            margin-bottom: 5px;
        }
        .stat-card p {
            margin: 0;
            font-size: 1.1rem;
        }
        .books-card { background-color: #00bcd4; }
        .students-card { background-color: #4CAF50; }
        
        /* Add loading screen styles */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #1a1f2c;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s;
        }
        .loading-screen.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        .logo-bar {
            background: #444 !important;
            min-height: 40px;
            height: 40px;
            width: 100%;
            display: flex;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
        }
        .main-content,
        .container-fluid > .row {
            margin-top: 40px; /* Same as .logo-bar height */
        }
        .btn-light {
            background: #fff !important;
            color: #444 !important;
            border: 1px solid #ccc !important;
            font-weight: bold;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .btn-light:hover {
            background: #e5e5e5 !important;
            color: #222 !important;
        }
        .btn-secondary {
            background: #888 !important;
            color: #fff !important;
            border: none !important;
        }
        .btn-secondary:hover {
            background: #444 !important;
        }
        .stat-card {
            background: #e0e0e0 !important;
            color: #222 !important;
        }
        .books-card {
            background: #d1d5db !important;
        }
        .students-card {
            background: #e5e7eb !important;
        }
        .card {
            background: #fff !important;
            border-radius: 1rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .table th, .table td {
            color: #222 !important;
        }
        
        
    </style>
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
</head>
<body>
    <!-- Logo Bar (Full Width) -->
    <div class="logo-bar d-flex align-items-center" style="background:rgba(66, 65, 65, 0.99); min-height: 55px; height: 40px; ">
    <a href="../../shared/choose_dashboard.php" class="btn btn-light ms-3" style="background: #fff; color: rgba(66, 65, 65, 0.99); border: none; font-weight: bold; box-shadow: 0 1px 4px rgba(0,0,0,0.05);"><i class="fas fa-arrow-left me-1"></i></a>
    <div>
            <img src="smarted-letter-only.jpg" alt="SmartED Logo" class="img-fluid ms-3" style="max-width: 100px;">
        </div>
        <div class="ms-auto me-3">
            <i class="fas fa-cog fs-5 text-light" style="cursor: pointer; " data-bs-toggle="dropdown" aria-expanded="false"></i>
            <ul class="dropdown-menu dropdown-menu-dark">
                <li><h6 class="dropdown-header text-white">Settings</h6></li>
                <li><a class="dropdown-item me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="fas fa-key me-2"></i>Change Password</a></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="handleLogout()"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
    <button class="menu-btn" id="menuBtn">
        <i class="fas fa-bars"></i>
    </button>
    <div class="loading-screen">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-white">Library Dashboard...</p>
    </div>
    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
             
            <div class="col-md-2 sidebar">
                <h4 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['admin_id']); ?></h4>
                <a href="dashboard.php" class="nav-link active"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="categories.php"><i class="fas fa-list me-2"></i> Categories</a>
                <a href="authors.php"><i class="fas fa-users me-2"></i> Authors</a>
                <a href="books.php"><i class="fas fa-book me-2"></i> Books</a>
                <a href="archive_books.php"><i class="fas fa-archive me-2"></i> Archived Books</a>
                <a href="search_student.php"><i class="fas fa-search me-2"></i> Search Student</a>
                <a href="book_requests.php"><i class="fas fa-clock me-2"></i> Book Requests</a>
                <a href="viewfeedbacks.php"><i class="fas fa-comments me-2"></i> View feedbacks</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h2 style="color: #252d3b;">Dashboard</h2>
                    <div>
                        
                    </div>
                </div><br>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="stat-card books-card">
                            <i class="fas fa-book-open position-absolute top-0 end-0 m-3 fa-2x"></i>
                            <h3><?php echo $total_books; ?></h3>
                            <p>Total Books</p>
                            <small class="position-absolute bottom-0 end-0 m-3"><?php echo $available_books; ?> Available</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card students-card" onclick="window.location.href='search_student.php'" style="cursor: pointer;">
                            <i class="fas fa-user-graduate position-absolute top-0 end-0 m-3 fa-2x"></i>
                            <h3><?php echo $total_students; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                </div>

                <!-- Books Table -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>All Books</h5>
                        <button class="btn btn-secondary"  onclick="window.location.href='books.php'">Manage Books</button>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>ISBN</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("
                                    SELECT b.*, a.name as author_name, c.name as category_name 
                                    FROM books b 
                                    LEFT JOIN authors a ON b.author_id = a.id 
                                    LEFT JOIN categories c ON b.category_id = c.id 
                                    WHERE b.archived = 0
                                    LIMIT 5
                                ");
                                while ($row = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['isbn']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['author_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                    echo "<td>";
                                    if ($row['available']) {
                                        echo '<span class="badge bg-success">Available</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Borrowed</span>';
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if ($change_password_message): ?>
                <div class="alert alert-success"><?php echo $change_password_message; ?></div>
            <?php endif; ?>
            <?php if ($change_password_error): ?>
                <div class="alert alert-danger"><?php echo $change_password_error; ?></div>
            <?php endif; ?>
            <form method="POST">
              <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="current_password" name="current_password" required>
                  <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password" tabindex="-1">
                    <i class="fa fa-eye"></i>
                  </button>
                </div>
              </div>
              <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="new_password" name="new_password" required>
                  <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password" tabindex="-1">
                    <i class="fa fa-eye"></i>
                  </button>
                </div>
              </div>
              <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                  <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password" tabindex="-1">
                    <i class="fa fa-eye"></i>
                  </button>
                </div>
              </div>
              <button type="submit" class="btn btn-secondary" name="change_password_submit">Update Password</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading screen script -->
    <script>
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.querySelector('.loading-screen').classList.add('fade-out');
                setTimeout(() => {
                    document.querySelector('.loading-screen').remove();
                }, 100);
            }, 300);
        });

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                // Show loading screen with logout message
                const loadingScreen = document.createElement('div');
                loadingScreen.className = 'loading-screen';
                loadingScreen.innerHTML = `
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-white">Logging out...</p>
                `;
                document.body.appendChild(loadingScreen);
                // Destroy session and redirect to admin_login.php
                setTimeout(() => {
                    window.location.href = '../../shared/logout.php?redirect=admin_login.php';
                }, 800);
            }
        }

        <?php if ($show_change_password_modal): ?>
        window.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            modal.show();
        });
        <?php endif; ?>

        document.querySelectorAll('.toggle-password').forEach(btn => {
          btn.addEventListener('click', function() {
            const input = document.getElementById(this.dataset.target);
            if (input.type === 'password') {
              input.type = 'text';
              this.querySelector('i').classList.remove('fa-eye');
              this.querySelector('i').classList.add('fa-eye-slash');
            } else {
              input.type = 'password';
              this.querySelector('i').classList.remove('fa-eye-slash');
              this.querySelector('i').classList.add('fa-eye');
            }
          });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('menuBtn');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');

            // Hide sidebar by default on small screens
            function handleInitialSidebar() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.add('collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                }
            }
            handleInitialSidebar();

            menuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('collapsed');
            });

            // Hide sidebar if clicking outside on small screens
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && !sidebar.classList.contains('collapsed')) {
                    if (!sidebar.contains(e.target) && e.target !== menuBtn && !menuBtn.contains(e.target)) {
                        sidebar.classList.add('collapsed');
                    }
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                handleInitialSidebar();
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>