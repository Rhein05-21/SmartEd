<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../shared/admin_login.php");
    exit();
}
require_once '../../shared/db_connect.php';

$stmt = $pdo->prepare("
    SELECT b.*, a.name as author_name, c.name as category_name 
    FROM books b 
    LEFT JOIN authors a ON b.author_id = a.id 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.archived = 1
    ORDER BY b.title
");
$stmt->execute();
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Books</title>
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
            z-index: 1000;
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
            color: #222;
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
            margin-top: 40px;
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
        .card {
            background: #fff !important;
            border-radius: 1rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .table th, .table td {
            color: #222 !important;
        }
        .book-cover-img {
            width: 90%;
            max-height: 220px;
            object-fit: contain;
            display: block;
            margin-left: auto;
            margin-right: auto;
            margin-top: 10px;
            margin-bottom: 10px;
            border-radius: 0.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            background: #f8f8f8;
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
        <p class="mt-3 text-white">Archived Books...</p>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <h4 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['admin_id']); ?></h4>
                <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="categories.php"><i class="fas fa-list me-2"></i> Categories</a>
                <a href="authors.php"><i class="fas fa-users me-2"></i> Authors</a>
                <a href="books.php"><i class="fas fa-book me-2"></i> Books</a>
                <a href="archive_books.php" class="nav-link active"><i class="fas fa-archive me-2"></i> Archived Books</a>
                <a href="search_student.php"><i class="fas fa-search me-2"></i> Search Student</a>
                <a href="book_requests.php"><i class="fas fa-clock me-2"></i> Book Requests</a>
                <a href="viewfeedbacks.php"><i class="fas fa-comments me-2"></i> View feedbacks</a>
            </div>
            <div class="col-md-10 main-content">
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h2 style="color: #252d3b;">Archived Books</h2>
                    
                </div>
                <div class="row mt-4">
                    <?php foreach ($books as $book): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <?php if ($book['cover_image']): ?>
                                    <img src="uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top book-cover-img" alt="Book Cover">
                                <?php else: ?>
                                    <img src="default_cover.jpg" class="card-img-top book-cover-img" alt="No Cover">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><strong>Book Title:</strong> <?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="card-text"><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                                    <p class="card-text"><strong>Author:</strong> <?php echo htmlspecialchars($book['author_name']); ?></p>
                                    <p class="card-text"><strong>Category:</strong> <?php echo htmlspecialchars($book['category_name']); ?></p>
                                    <div>
                                        <button class="btn btn-success btn-sm unarchive-btn" data-book-id="<?php echo $book['id']; ?>">Unarchive</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                const loadingScreen = document.createElement('div');
                loadingScreen.className = 'loading-screen';
                loadingScreen.innerHTML = `
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-white">Logging out...</p>
                `;
                document.body.appendChild(loadingScreen);
                setTimeout(() => {
                    window.location.href = '../../shared/logout.php?redirect=admin_login.php';
                }, 800);
            }
        }
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
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && !sidebar.classList.contains('collapsed')) {
                    if (!sidebar.contains(e.target) && e.target !== menuBtn && !menuBtn.contains(e.target)) {
                        sidebar.classList.add('collapsed');
                    }
                }
            });
            window.addEventListener('resize', function() {
                handleInitialSidebar();
            });
        });
        document.querySelectorAll('.unarchive-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var bookId = this.getAttribute('data-book-id');
                if (confirm('Unarchive this book?')) {
                    fetch('archive_book_action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=unarchive&book_id=' + encodeURIComponent(bookId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.col-md-4').remove();
                        } else {
                            alert('Failed to unarchive book: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(() => alert('AJAX error. Please try again.'));
                }
            });
        });
    </script>
</body>
</html>
