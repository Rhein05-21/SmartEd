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

    $stmt = $pdo->prepare('SELECT password FROM admin WHERE id = ?');
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
        $stmt = $pdo->prepare('UPDATE admin SET password = ? WHERE id = ?');
        $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
        $change_password_message = 'Password successfully updated!';
        $show_change_password_modal = true;
    }
}

// Handle request status updates
// After database connection
if (isset($_GET['approve'])) {
    $request_id = $_GET['approve'];
    
    // Get request details for notification
    $stmt = $pdo->prepare("
        SELECT br.*, b.title as book_title, s.student_id as student_number
        FROM book_requests br
        JOIN books b ON br.book_id = b.id
        JOIN students s ON br.student_id = s.id
        WHERE br.id = ?
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    if ($request) {
        // Use the return_date already set by the student
        $return_date = $request['return_date'];
        
        // Update request status and approval date only (do not overwrite return_date)
        $stmt = $pdo->prepare("
            UPDATE book_requests 
            SET status = 'approved',
                approve_date = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$request_id]);
        
        // Now set the book as borrowed
        $stmt = $pdo->prepare("UPDATE books SET available = 0 WHERE id = ?");
        $stmt->execute([$request['book_id']]);
        
        // Create notification for student with the correct return date
        $message = "Your request for the book '{$request['book_title']}' has been approved. Please return by " . date('M d, Y', strtotime($return_date));
        createNotification($request['student_number'], $request['book_id'], $message, $return_date);
    }
    
    header('Location: book_requests.php');
    exit();
}

if (isset($_GET['decline'])) {
    $request_id = $_GET['decline'];
    
    // Get request details for notification
    $stmt = $pdo->prepare("
        SELECT br.*, b.title as book_title, s.student_id as student_number
        FROM book_requests br
        JOIN books b ON br.book_id = b.id
        JOIN students s ON br.student_id = s.id
        WHERE br.id = ?
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    if ($request) {
        $pdo->beginTransaction();
        try {
            // Update request status to declined
            $stmt = $pdo->prepare("
                UPDATE book_requests 
                SET status = 'declined'
                WHERE id = ?
            ");
            $stmt->execute([$request_id]);
            
            // Make book available again
            $stmt = $pdo->prepare("UPDATE books SET available = 1 WHERE id = ?");
            $stmt->execute([$request['book_id']]);
            
            // Create notification for student
            $message = "Your request for the book '{$request['book_title']}' has been declined.";
            createNotification($request['student_number'], $request['book_id'], $message);
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error declining request: " . $e->getMessage();
        }
    }
    
    header('Location: book_requests.php');
    exit();
}

if (isset($_GET['return'])) {
    $request_id = $_GET['return'];
    
    // Update request status and book availability
    $pdo->beginTransaction();
    try {
        // Mark request as returned
        $stmt = $pdo->prepare("
            UPDATE book_requests 
            SET status = 'returned', 
                return_date = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$request_id]);
        
        // Make book available again
        $stmt = $pdo->prepare("
            UPDATE books b 
            JOIN book_requests br ON b.id = br.book_id 
            SET b.available = 1 
            WHERE br.id = ?
        ");
        $stmt->execute([$request_id]);
        
        $pdo->commit();
        header('Location: book_requests.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error processing return: " . $e->getMessage();
    }
}

// Modify the query to show more details
$stmt = $pdo->prepare("
    SELECT br.*, 
           CONCAT(s.firstname, ' ', s.lastname) as student_name,
           s.email as student_email,
           b.title as book_title,
           b.isbn,
           br.borrow_date,
           br.approve_date,
           br.return_date
    FROM book_requests br
    JOIN students s ON br.student_id = s.student_id
    JOIN books b ON br.book_id = b.id
    ORDER BY 
        CASE br.status 
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'returned' THEN 3
            WHEN 'declined' THEN 4
        END,
        br.borrow_date DESC
");

// Also update the pagination query
$stmt = $pdo->prepare("
    SELECT br.*, 
           CONCAT(s.firstname, ' ', s.lastname) as student_name, 
           s.email as student_email,
           b.title as book_title,
           b.isbn
    FROM book_requests br
    JOIN students s ON br.student_id = s.id
    JOIN books b ON br.book_id = b.id
    ORDER BY br.borrow_date DESC
    LIMIT :limit OFFSET :offset
");
require_once 'includes/pagination.php';

// Modify requests fetch
$pagination = setupPagination($pdo, 'book_requests');
$stmt = $pdo->prepare("
    SELECT br.*, 
           CONCAT(s.firstname, ' ', s.lastname) as student_name, 
           s.email as student_email,
           b.title as book_title,
           b.isbn
    FROM book_requests br
    JOIN students s ON br.student_id = s.id
    JOIN books b ON br.book_id = b.id
    ORDER BY br.borrow_date DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Requests</title>
    <link rel="icon" type="image/x-icon" href="smarted-web-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
        }
        .sidebar {
            background-color: rgb(46, 46, 48);
            color: white;
            min-height: 100vh;
            padding: 20px;
            border-right: 1px solid #2d3748;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }
        .menu-btn {
            background: none;
            display: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            margin-left: auto;
            border-radius: 6px;
            padding: 6px 12px;
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
            border-left: 3px solid rgba(218, 214, 214, 0.99);
        }
        .main-content {
            padding: 20px;
        }
        .top-bar {
            background-color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
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
            transition: opacity 1.5s;
        }
        .loading-screen.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        .logo-bar {
            background: rgba(66, 65, 65, 0.99) !important;
            min-height: 55px;
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
            margin-top: 40px;
        }
        .sidebar-footer {
            margin-top: auto;
            color: #bbb;
            font-size: 0.95em;
            padding-bottom: 10px;
        }
        .btn-light {
            background: #fff !important;
            color: rgba(66, 65, 65, 0.99) !important;
            border: none !important;
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
        .card {
            background: #fff !important;
            border-radius: 1rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .table th, .table td {
            color: #222 !important;
        }
        .dropdown-menu-dark {
            background-color: #444 !important;
        }
        .dropdown-header.text-warning, .dropdown-header.text-white {
            color: #fff !important;
        }
        .btn-danger {
            background: #bbb !important;
            color: #fff !important;
            border: none !important;
        }
        .btn-danger:hover {
            background: #888 !important;
        }
    </style>
</head>
<body>
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
        <p class="mt-3 text-white">Book Requests...</p>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <h4 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['admin_id']); ?></h4>
                <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="categories.php"><i class="fas fa-list me-2"></i> Categories</a>
                <a href="authors.php"><i class="fas fa-users me-2"></i> Authors</a>
                <a href="books.php"><i class="fas fa-book me-2"></i> Books</a>
                <a href="archive_books.php"><i class="fas fa-archive me-2"></i> Archived Books</a>
                <a href="search_student.php"><i class="fas fa-search me-2"></i> Search Student</a>
                <a href="book_requests.php" class="nav-link active"><i class="fas fa-clock me-2"></i> Book Requests</a>
                <a href="viewfeedbacks.php"><i class="fas fa-comments me-2"></i> View feedbacks</a>
            </div>
            <div class="col-md-10 main-content">
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h2>Book Requests</h2>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th >Book Title</th>
                                    <th>Requested By</th>
                                    <th>Status</th>
                                    <th>Request Date</th>
                                    <th>Return Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['book_title']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($request['student_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($request['student_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            $status_class = match($request['status']) {
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'returned' => 'info',
                                                'declined' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            echo date('Y-m-d H:i', strtotime($request['borrow_date']));
                                            if ($request['approve_date']) {
                                                echo '<br><small class="text-success">Approved: ' . date('Y-m-d H:i', strtotime($request['approve_date'])) . '</small>';
                                            }
                                            if ($request['return_date'] && $request['status'] === 'returned') {
                                                echo '<br><small class="text-info">Returned: ' . date('Y-m-d H:i', strtotime($request['return_date'])) . '</small>';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $request['return_date'] ? date('Y-m-d', strtotime($request['return_date'])) : '<span class="text-muted">N/A</span>'; ?>
                                    </td>
                                    <td>
                                        <?php if ($request['status'] == 'pending'): ?>
                                            <a href="?approve=<?php echo $request['id']; ?>" 
                                               class="btn btn-success btn-sm me-1 equal-width-btn"
                                               onclick="return confirm('Approve this request?')">
                                                Approve
                                            </a>
                                            <a href="?decline=<?php echo $request['id']; ?>" 
                                               class="btn btn-danger btn-sm equal-width-btn"
                                               onclick="return confirm('Decline this request?')">
                                                Decline
                                            </a>
                                        <?php elseif ($request['status'] == 'approved'): ?>
                                            <a href="?return=<?php echo $request['id']; ?>" 
                                               class="btn btn-primary btn-sm"
                                               onclick="return confirm('Mark this book as returned?')">
                                                Return
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination Controls -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php if ($pagination['page'] > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $pagination['page'] - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <li class="page-item <?php echo $i === $pagination['page'] ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $pagination['page'] + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
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
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add JavaScript for menu toggle
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.querySelector('.loading-screen').classList.add('fade-out');
                setTimeout(() => {
                    document.querySelector('.loading-screen').remove();
                }, 100);
            }, 300);
        
            // Add menu toggle functionality
            const menuBtn = document.getElementById('menuBtn');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuBtn && sidebar) {
                menuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                });
        
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', (e) => {
                    if (window.innerWidth <= 768 && 
                        !sidebar.contains(e.target) && 
                        !menuBtn.contains(e.target) && 
                        !sidebar.classList.contains('collapsed')) {
                        sidebar.classList.add('collapsed');
                    }
                });
            }
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
                
                // Redirect after a short delay to show the animation
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
    </script>

</body>
</html>