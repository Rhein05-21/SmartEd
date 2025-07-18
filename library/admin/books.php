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


// Handle book addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle edit book
    if (isset($_POST['edit_book'])) {
        $book_id = $_POST['book_id'];
        $title = trim($_POST['edit_title']);
        $isbn = trim($_POST['edit_isbn']);
        $isbn = !empty($isbn) ? $isbn : null; // Convert empty string to NULL
        $author_id = $_POST['edit_author_id'];
        $category_id = $_POST['edit_category_id'];

        // Handle cover image upload
        $cover_image = null;
        if (isset($_FILES['edit_cover_image']) && $_FILES['edit_cover_image']['error'] == UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['edit_cover_image']['name'], PATHINFO_EXTENSION);
            $cover_image = uniqid('cover_') . '.' . $ext;
            move_uploaded_file($_FILES['edit_cover_image']['tmp_name'], "uploads/covers/" . $cover_image);
        }

        if (!empty($title)) {
            if ($cover_image) {
                $sql = "UPDATE books SET title = ?, isbn = ?, author_id = ?, category_id = ?, cover_image = ? WHERE id = ?";
                $params = [$title, $isbn, $author_id, $category_id, $cover_image, $book_id];
            } else {
                $sql = "UPDATE books SET title = ?, isbn = ?, author_id = ?, category_id = ? WHERE id = ?";
                $params = [$title, $isbn, $author_id, $category_id, $book_id];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
    } 
    // Handle new book addition
    else if (isset($_POST['title'])) {
        $title = trim($_POST['title']);
        $isbn = trim($_POST['isbn']);
        $isbn = !empty($isbn) ? $isbn : null;
        $author_id = $_POST['author_id'];
        $category_id = $_POST['category_id'];

        // Duplicate book validation
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE title = ? AND author_id = ? AND category_id = ?");
        $stmt->execute([$title, $author_id, $category_id]);
        $book_exists = $stmt->fetchColumn();

        if ($book_exists) {
            $change_password_error = 'A book with the same title, author, and category already exists.';
        } else {
            $cover_image = null;
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $cover_image = uniqid('cover_') . '.' . $ext;
                move_uploaded_file($_FILES['cover_image']['tmp_name'], "uploads/covers/" . $cover_image);
            }
            $stmt = $pdo->prepare("INSERT INTO books (title, isbn, author_id, category_id, cover_image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $isbn, $author_id, $category_id, $cover_image]);
        }
    }
}

// Add this helper function
function sanitize_filename($filename) {
    // Remove any character that isn't A-Z, a-z, 0-9, dot, hyphen or underscore
    return preg_replace("/[^A-Za-z0-9.-_]/", '', $filename);
}

// Handle book deletion
if (isset($_GET['delete'])) {
    // First check if the book is borrowed
    $check_stmt = $pdo->prepare("SELECT available FROM books WHERE id = ?");
    $check_stmt->execute([$_GET['delete']]);
    $book = $check_stmt->fetch();

    if (!$book['available']) {
        $_SESSION['error'] = "Cannot delete this book because it is currently borrowed.";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete associated book requests first
            $delete_requests = $pdo->prepare("DELETE FROM book_requests WHERE book_id = ?");
            $delete_requests->execute([$_GET['delete']]);
            
            // Then delete the book
            $delete_book = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $delete_book->execute([$_GET['delete']]);
            
            // Commit transaction
            $pdo->commit();
            $_SESSION['success'] = "Book deleted successfully.";
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            if ($e->getCode() == 23000 && strpos($e->getMessage(), '1451') !== false) {
                $_SESSION['error'] = "This book has associated records (e.g., notifications, requests) and cannot be deleted. You may archive it instead.";
            } else {
                $_SESSION['error'] = "Error deleting book: " . $e->getMessage();
            }
        }
    }
    header("Location: books.php");
    exit();
}

if (isset($_GET['archived']) && $_GET['archived'] === 'success') {
    $_SESSION['success'] = 'Book archived successfully.';
    header("Location: books.php");
    exit();
}

// Add this right after the opening of the main-content div to display messages

// Fetch all books with author and category names
// After database connection
require_once 'includes/pagination.php';

// Modify books fetch
$pagination = setupPagination($pdo, 'books');
$view = isset($_GET['view']) && $_GET['view'] === 'archived' ? 'archived' : 'active';

$sql = "SELECT * FROM books WHERE archived = 0";

$stmt = $pdo->prepare("
    SELECT b.*, a.name as author_name, c.name as category_name 
    FROM books b 
    LEFT JOIN authors a ON b.author_id = a.id 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.archived = 0
    ORDER BY b.title
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll();

// Fetch authors and categories for dropdowns
$authors = $pdo->query("SELECT * FROM authors ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books</title>
    <link rel="icon" type="image/x-icon" href="smarted-web-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Same styles as previous pages -->
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
        <p class="mt-3 text-white">Manage Books...</p>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <h4 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['admin_id']); ?></h4>
                <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="categories.php"><i class="fas fa-list me-2"></i> Categories</a>
                <a href="authors.php"><i class="fas fa-users me-2"></i> Authors</a>
                <a href="books.php" class="nav-link active"><i class="fas fa-book me-2"></i> Books</a>
                <a href="archive_books.php"><i class="fas fa-archive me-2"></i> Archived Books</a>
                <a href="search_student.php"><i class="fas fa-search me-2"></i> Search Student</a>
                <a href="book_requests.php"><i class="fas fa-clock me-2"></i> Book Requests</a>
                <a href="viewfeedbacks.php"><i class="fas fa-comments me-2"></i> View feedbacks</a>
            </div>
            <div class="col-md-10 main-content">
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h2>Manage Books</h2>
                    <!--<div>
                        <button class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                        <a href="#" onclick="handleLogout()" class="btn btn-danger">Logout</a>
                    </div>-->
                </div>
                <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <?php if ($change_password_error): ?>
                            <div class="alert alert-danger">
                                <?php echo $change_password_error; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" class="row g-3" enctype="multipart/form-data">
                            <div class="col-md-6">
                                <input type="text" name="title" class="form-control" placeholder="Book Title" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="isbn" class="form-control" placeholder="ISBN (optional)">
                            </div>
                            <div class="col-md-6">
                                <select name="author_id" class="form-control" required>
                                    <option value="">Select Author</option>
                                    <?php foreach ($authors as $author): ?>
                                        <option value="<?php echo $author['id']; ?>">
                                            <?php echo htmlspecialchars($author['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="file" name="cover_image" class="form-control" accept="image/*">
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-secondary">Add Book</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($books as $book): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <?php if ($book['cover_image']): ?>
                                            <img src="../../library/admin/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top book-cover-img" alt="Book Cover">
                                        <?php else: ?>
                                            <img src="default_cover.jpg" class="card-img-top book-cover-img" alt="No Cover">
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h5 class="card-title"><strong>Book Title:</strong><?php echo htmlspecialchars($book['title']); ?></h5>
                                            <p class="card-text"><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                                            <p class="card-text"><strong>Author:</strong> <?php echo htmlspecialchars($book['author_name']); ?></p>
                                            <p class="card-text"><strong>Category:</strong> <?php echo htmlspecialchars($book['category_name']); ?></p>
                                            <p class="card-text">
                                                <span class="badge <?php echo $book['available'] ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $book['available'] ? 'Available' : 'Borrowed'; ?>
                                                </span>
                                            </p>
                                            <div>
                                                <!-- Actions: Edit/Delete buttons here -->
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-secondary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $book['id']; ?>">
                                                        Edit
                                                    </button>
                                                    <a href="?delete=<?php echo $book['id']; ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Are you sure?')">
                                                        Delete
                                                    </a>
                                                    <button class="btn btn-warning btn-sm archive-btn" data-book-id="<?php echo $book['id']; ?>">
                                                        Archive
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php if ($pagination['page'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($pagination['page'] - 1); ?>">&laquo; Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <li class="page-item <?php echo ($i == $pagination['page']) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($pagination['page'] + 1); ?>">Next &raquo;</a>
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

    <!-- Edit Modals -->
    <?php foreach ($books as $book): ?>
    <div class="modal fade" id="editModal<?php echo $book['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $book['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?php echo $book['id']; ?>">Edit Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <input type="hidden" name="edit_book" value="1">
                        
                        <div class="mb-3">
                            <label for="edit_title<?php echo $book['id']; ?>" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title<?php echo $book['id']; ?>" 
                                   name="edit_title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_isbn<?php echo $book['id']; ?>" class="form-label">ISBN (Optional)</label>
                            <input type="text" class="form-control" id="edit_isbn<?php echo $book['id']; ?>" 
                                   name="edit_isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_author_id<?php echo $book['id']; ?>" class="form-label">Author</label>
                            <select class="form-control" id="edit_author_id<?php echo $book['id']; ?>" name="edit_author_id" required>
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?php echo $author['id']; ?>" 
                                            <?php echo ($author['id'] == $book['author_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($author['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_category_id<?php echo $book['id']; ?>" class="form-label">Category</label>
                            <select class="form-control" id="edit_category_id<?php echo $book['id']; ?>" name="edit_category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($category['id'] == $book['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_cover_image<?php echo $book['id']; ?>" class="form-label">Cover Image (Optional)</label>
                            <input type="file" class="form-control" id="edit_cover_image<?php echo $book['id']; ?>" name="edit_cover_image" accept="image/*">
                            <?php if ($book['cover_image']): ?>
                                <p class="mt-2">Current Cover: <img src="uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Current Cover" style="max-height: 100px;">
                                <a href="?delete_cover=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger mt-1" onclick="return confirm('Are you sure you want to delete the cover image?')">Delete Cover</a></p>
                            <?php endif; ?>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-secondary">Save Changes</button>
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

        // Archive button AJAX
        document.querySelectorAll('.archive-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var bookId = this.getAttribute('data-book-id');
                if (confirm('Are you sure you want to archive this book?')) {
                    fetch('archive_book_action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=archive&book_id=' + encodeURIComponent(bookId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Hide the book card
                            this.closest('.col-md-4').remove();
                            // Optionally, show a success message
                            window.location.href = 'books.php?archived=success';
                        } else {
                            alert('Failed to archive book: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(() => alert('AJAX error. Please try again.'));
                }
            });
        });
    </script>

</body>
</html>