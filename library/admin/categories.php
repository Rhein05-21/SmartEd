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

// Handle category addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['category_name'])) {
        $category_name = trim($_POST['category_name']);
        if (!empty($category_name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$category_name]);
        }
    }
    // Add this new edit handler
    else if (isset($_POST['edit_category_name'])) {
        $category_id = $_POST['category_id'];
        $category_name = trim($_POST['edit_category_name']);
        if (!empty($category_name)) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$category_name, $category_id]);
            header("Location: categories.php");
            exit();
        }
    }
}

// Handle category deletion
if (isset($_GET['delete'])) {
    // First check if there are any books associated with this category
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
    $check_stmt->execute([$_GET['delete']]);
    $book_count = $check_stmt->fetchColumn();

    if ($book_count > 0) {
        // If there are books, show an error message
        $_SESSION['error'] = "Cannot delete category. There are books associated with this category. Please remove or reassign the books first.";
        header("Location: categories.php");
        exit();
    } else {
        // If no books, proceed with deletion
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header("Location: categories.php");
        exit();
    }
}

// Fetch all categories
// After the session and db connection code
// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Get total number of categories
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_pages = ceil($total_categories / $items_per_page);

// Using prepared statement with integer binding
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
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
            transform: translateX(0);
        }
        .sidebar.collapsed {
            transform: translateX(-250px);
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
        <p class="mt-3 text-white">Manage Categories...</p>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h4 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['admin_id']); ?></h4>
                <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="categories.php" class="nav-link active"><i class="fas fa-list me-2"></i> Categories</a>
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
                    <h2>Manage Categories</h2>
                    <!--<div>
                        <!--<button class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                        <a href="#" onclick="handleLogout()" class="btn btn-danger">Logout</a>
                    </div>-->
                </div>

                <!-- Add Category Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" class="d-flex gap-2">
                            <input type="text" name="category_name" class="form-control" placeholder="Enter category name" required>
                            <button type="submit" class="btn btn-secondary">Add Category</button>
                        </form>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover">
                                <thead style="position: sticky; top: 0; background: white; z-index: 1;">
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="lazy-load-container">
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center">No categories found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $category): ?>
                                        <tr class="lazy-load-item" style="opacity: 0; transition: opacity 0.3s;">
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td>
                                                <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $category['id']; ?>">
                                                    Edit
                                                </button>
                                                <a href="?delete=<?php echo $category['id']; ?>&page=<?php echo $page; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Are you sure?')">
                                                    Delete
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>">&laquo; Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>">Next &raquo;</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add this script before the closing body tag -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    entry.target.style.opacity = '1';
                                }
                            });
                        }, {
                            root: document.querySelector('.table-responsive'),
                            threshold: 0.1
                        });

                        document.querySelectorAll('.lazy-load-item').forEach(item => {
                            observer.observe(item);
                        });
                    });
                    
                </script>
            </div>
        </div>
    </div>

    <!-- Edit Modals -->
    <?php foreach ($categories as $category): ?>
    <div class="modal fade" id="editModal<?php echo $category['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                        <input type="text" name="edit_category_name" class="form-control" 
                               value="<?php echo htmlspecialchars($category['name']); ?>" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-secondary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

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
</body>
</html>