<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../shared/admin_login.php");
    exit();
}
require_once '../../shared/db_connect.php';



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


$stmt = $pdo->query("SELECT * FROM feedbacks ORDER BY created_at DESC");
$feedbacks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Feedbacks</title>
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
        .card {
            border-radius: 15px;
            border: 1px solid #e3e6ed;
            box-shadow: 0 2px 8px rgba(44,62,80,0.04);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #e3e6ed;
        }
        .table {
            background: #fff;
            color: #232b3b;
        }
        .table th {
            color:rgb(41, 41, 41);
            background: #f8f9fa;
            border-bottom: 2px solidrgb(74, 77, 76);
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #f8fafc;
        }
        .table-striped > tbody > tr:nth-of-type(even) {
            background-color: #fff;
        }
        .list-group-item {
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e3e6ed;
        }
        .list-group-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .badge {
            padding: 8px 12px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        .badge.bg-info {
            background-color: #e3f2fd !important;
            color: #0d47a1 !important;
        }
        .text-muted {
            font-size: 0.9rem;
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
        <p class="mt-3 text-white">Loading Feedbacks...</p>
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
                <a href="book_requests.php"><i class="fas fa-clock me-2"></i> Book Requests</a>
                <a href="viewfeedbacks.php" class="nav-link active"><i class="fas fa-comments me-2"></i> View feedbacks</a>
            </div>
            <div class="col-md-10 main-content">
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h2>View Feedbacks</h2>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="mb-0 text-secondary"><i class="fas fa-comments me-2"></i>Student Feedbacks</h5>
                    </div>
                    <div class="card-body">
                        <!-- Tabs for feedback types -->
                        <ul class="nav nav-tabs mb-3" id="feedbackTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="complaint-tab" data-bs-toggle="tab" data-bs-target="#complaint" type="button" role="tab" aria-controls="complaint" aria-selected="true">Complaint</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="problem-tab" data-bs-toggle="tab" data-bs-target="#problem" type="button" role="tab" aria-controls="problem" aria-selected="false">Problem</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="suggestion-tab" data-bs-toggle="tab" data-bs-target="#suggestion" type="button" role="tab" aria-controls="suggestion" aria-selected="false">Suggestion</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="praise-tab" data-bs-toggle="tab" data-bs-target="#praise" type="button" role="tab" aria-controls="praise" aria-selected="false">Praise</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="feedbackTabsContent">
                            <div class="tab-pane fade show active" id="complaint" role="tabpanel" aria-labelledby="complaint-tab">
                                <div class="list-group">
                                    <?php $found = false; foreach ($feedbacks as $fb): if (strtolower($fb['comment_type']) === 'complaint'): $found = true; ?>
                                    <div class="list-group-item mb-3 shadow-sm" style="border-radius: 10px;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-info"><?= htmlspecialchars($fb['comment_type']) ?></span>
                                            <small class="text-muted"><?= date('F j, Y g:i A', strtotime($fb['created_at'])) ?></small>
                                        </div>
                                        <div><strong>Student ID:</strong> <?= htmlspecialchars($fb['student_id']) ?></div>
                                        <div><strong>About:</strong> <?= htmlspecialchars($fb['comment_about']) ?></div>
                                        <div class="mt-2"><strong>Comment:</strong><div class="text-secondary mt-1 ps-2 border-start border-2"><?= nl2br(htmlspecialchars($fb['comment_text'])) ?></div></div>
                                    </div>
                                    <?php endif; endforeach; if (!$found): ?>
                                    <div class="list-group-item text-center text-muted">No complaints found.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="problem" role="tabpanel" aria-labelledby="problem-tab">
                                <div class="list-group">
                                    <?php $found = false; foreach ($feedbacks as $fb): if (strtolower($fb['comment_type']) === 'problem'): $found = true; ?>
                                    <div class="list-group-item mb-3 shadow-sm" style="border-radius: 10px;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-info"><?= htmlspecialchars($fb['comment_type']) ?></span>
                                            <small class="text-muted"><?= date('F j, Y g:i A', strtotime($fb['created_at'])) ?></small>
                                        </div>
                                        <div><strong>Student ID:</strong> <?= htmlspecialchars($fb['student_id']) ?></div>
                                        <div><strong>About:</strong> <?= htmlspecialchars($fb['comment_about']) ?></div>
                                        <div class="mt-2"><strong>Comment:</strong><div class="text-secondary mt-1 ps-2 border-start border-2"><?= nl2br(htmlspecialchars($fb['comment_text'])) ?></div></div>
                                    </div>
                                    <?php endif; endforeach; if (!$found): ?>
                                    <div class="list-group-item text-center text-muted">No problems found.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="suggestion" role="tabpanel" aria-labelledby="suggestion-tab">
                                <div class="list-group">
                                    <?php $found = false; foreach ($feedbacks as $fb): if (strtolower($fb['comment_type']) === 'suggestion'): $found = true; ?>
                                    <div class="list-group-item mb-3 shadow-sm" style="border-radius: 10px;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-info"><?= htmlspecialchars($fb['comment_type']) ?></span>
                                            <small class="text-muted"><?= date('F j, Y g:i A', strtotime($fb['created_at'])) ?></small>
                                        </div>
                                        <div><strong>Student ID:</strong> <?= htmlspecialchars($fb['student_id']) ?></div>
                                        <div><strong>About:</strong> <?= htmlspecialchars($fb['comment_about']) ?></div>
                                        <div class="mt-2"><strong>Comment:</strong><div class="text-secondary mt-1 ps-2 border-start border-2"><?= nl2br(htmlspecialchars($fb['comment_text'])) ?></div></div>
                                    </div>
                                    <?php endif; endforeach; if (!$found): ?>
                                    <div class="list-group-item text-center text-muted">No suggestions found.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="praise" role="tabpanel" aria-labelledby="praise-tab">
                                <div class="list-group">
                                    <?php $found = false; foreach ($feedbacks as $fb): if (strtolower($fb['comment_type']) === 'praise'): $found = true; ?>
                                    <div class="list-group-item mb-3 shadow-sm" style="border-radius: 10px;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-info"><?= htmlspecialchars($fb['comment_type']) ?></span>
                                            <small class="text-muted"><?= date('F j, Y g:i A', strtotime($fb['created_at'])) ?></small>
                                        </div>
                                        <div><strong>Student ID:</strong> <?= htmlspecialchars($fb['student_id']) ?></div>
                                        <div><strong>About:</strong> <?= htmlspecialchars($fb['comment_about']) ?></div>
                                        <div class="mt-2"><strong>Comment:</strong><div class="text-secondary mt-1 ps-2 border-start border-2"><?= nl2br(htmlspecialchars($fb['comment_text'])) ?></div></div>
                                    </div>
                                    <?php endif; endforeach; if (!$found): ?>
                                    <div class="list-group-item text-center text-muted">No praise found.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('menuBtn');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuBtn && sidebar) {
                menuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                });
            }
        });

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
