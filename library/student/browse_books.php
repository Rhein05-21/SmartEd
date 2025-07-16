<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../../shared/db_connect.php';

$student_id = $_SESSION['student_id'];
$name = $_SESSION['name'];

// Handle search and filtering
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Get all categories for the dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Build the query for books
$query = "
    SELECT b.*, a.name as author_name, c.name as category_name 
    FROM books b 
    LEFT JOIN authors a ON b.author_id = a.id 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.available = 1 AND b.archived = 0
";

$params = [];

if ($search) {
    $query .= " AND (b.title LIKE ? OR a.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $query .= " AND c.id = ?";
    $params[] = $category;
}

$query .= " ORDER BY b.title";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books - SmartEd</title>
    <link rel="icon" type="image/x-icon" href="version2.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #ffffff;
            --secondary-bg: #b3b3b3;
            --accent-color: #888888;
            --text-color: #333333;
            --muted-text: #666666;
            --border-color: #cccccc;
        }

        body {
            background-color: #f4f4f4;
            color: #222;
            min-height: 100vh;
        }

        .sidebar {
            background-color: rgb(46, 46, 48);
            color: #fff;
            width: 250px;
            padding: 20px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid #2d3748;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .welcome-card {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }

        .nav-link {
            color: #fff;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            background-color: #2c3344;
            color: #fff;
        }

        .logo {
            color: #fff;
            font-size: 24px;
            margin-bottom: 30px;
            display: block;
            text-decoration: none;
        }

        .logo:hover {
            color: var(--text-color);
        }

        .search-container {
            background-color: #e0e0e0;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid #cccccc;
        }

        .form-control, .form-select {
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 12px;
            border-radius: 8px;
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--primary-bg);
            border-color: var(--accent-color);
            color: var(--text-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 216, 199, 0.25);
        }

        .form-select option {
            background-color: var(--primary-bg);
            color: var(--text-color);
        }

        .btn-info, .btn.btn-info {
            background: #fff !important;
            color: #444 !important;
            border: 1px solid #ccc !important;
            font-weight: bold;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }

        .btn-info:hover, .btn.btn-info:hover {
            background: #e5e5e5 !important;
            color: #222 !important;
        }

        .book-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px 0;
            align-items: stretch;
        }

        .book-card {
            background-color: #fff;
            border-radius: 15px;
            padding: 20px;
            border: 1px solid #cccccc;
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            color: #222;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            max-width: 320px;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .book-card h4 {
            color: #888;
            margin-bottom: 15px;
            font-size: 1.25rem;
            line-height: 1.4;
            min-height: 2.8em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-card p {
            color: var(--muted-text);
            margin-bottom: 10px;
        }

        .book-card-footer {
            margin-top: auto;
        }

        .text-muted {
            color: var(--muted-text) !important;
        }

        .text-danger {
            color: #ff4444 !important;
        }

        .text-danger:hover {
            color: #ff6666 !important;
        }
        .loading-screen {
            background: #f4f4f4;
            color: #222;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 1.5s;
        }
        .spinner-border.text-primary {
            color: #888 !important;
        }
        
.modal-dialog {
    margin: 5vh auto !important;
    max-height: 90vh;
    overflow-y: auto;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 9999;
}

.modal-content {
    max-height: 80vh;
    overflow-y: auto;
}
.logo-bar {
            margin-left: 5%;
            min-height: 40px;
            height: 40px;
            width: 10%;
            display: flex;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
        }
    </style>
</head>
<body>
    <div class="loading-screen">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3" style="color:#222;">Browse Books...</p>
    </div>
    <div class="sidebar">
    <a href="dashboard.php" class="logo">
            <div class="logo-bar"><img src="smarted-letter-only.jpg" alt="SmartEd Logo" class="img-fluid" style="max-width: 90px;"></div>
        </a>
        <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <a class="nav-link active" href="browse_books.php">
                <i class="fas fa-book me-2"></i> Browse Books
            </a>
            <a class="nav-link" href="my_books.php">
                <i class="fas fa-bookmark me-2"></i> My Books
            </a>
            <a class="nav-link" href="update_profile.php">
                <i class="fas fa-user-edit me-2"></i> Update Profile
            </a>
            <a class="nav-link" href="../../shared/student_choose_dashboard.php">
                <i class="fas fa-th-large me-2"></i> Switch Dashboard
            </a>
        </nav>
    </div>

    

    <div class="main-content">
    <div class="d-flex justify-content-end align-items-center mb-2" style="min-height: 20px;">
        <!--<div class="position-relative me-3">
            <i class="fas fa-cog fs-5 text-info" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
            <ul class="dropdown-menu dropdown-menu-dark">
                <li><h6 class="dropdown-header">Settings</h6></li>
                <li><a class="dropdown-item" href="update_profile.php"><i class="fas fa-user-edit me-2"></i>Update Profile</a></li>
                <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#feedbackModal" href="#"><i class="fas fa-comment me-2"></i>Feedback</a></li>
                <li><a class="dropdown-item" href="#" onclick="handleLogout()"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>-->
    </div>
    <div class="welcome-card">
        <h2>Browse Books</h2>
        <p>Search and request books from our collection</p>
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

    <div class="search-container">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search by title or author..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-info w-100 search-btn">
                    <i class="fas fa-search me-2"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <?php if (count($books) > 0): ?>
        <div class="book-grid">
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <?php if (!empty($book['cover_image'])): ?>
                        <img src="../admin/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover" style="width: 100%; max-height: 180px; object-fit: contain; border-radius: 0.5rem; margin-bottom: 10px; background: #f8f8f8; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                    <?php else: ?>
                        <img src="../admin/default_cover.jpg" alt="No Cover" style="width: 100%; max-height: 180px; object-fit: contain; border-radius: 0.5rem; margin-bottom: 10px; background: #f8f8f8; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                    <?php endif; ?>
                    <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                    <p>Author: <?php echo htmlspecialchars($book['author_name']); ?></p>
                    <p>Category: <?php echo htmlspecialchars($book['category_name']); ?></p>
                    <?php if ($book['isbn']): ?>
                        <p>ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                    <?php endif; ?>
                    <div class="book-card-footer mt-auto">
                        <button type="button" class="btn btn-info w-100 request-btn" data-book-id="<?php echo $book['id']; ?>">
                            Request book
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted">No books found matching your criteria.</p>
    <?php endif; ?>

    <!-- Modal -->
    <div class="modal fade" id="returnDateModal" tabindex="-1" role="dialog" aria-labelledby="returnDateModalLabel" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="background-color: var(--secondary-bg); color: var(--text-color);">
                <div class="modal-header border-bottom border-secondary">
                    <h2 class="modal-title h5" id="returnDateModalLabel">Book Return Date</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
                        <span class="visually-hidden">Close modal</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Please select when you will return this book. The return date must be between after tomorrow and 30 days from now.</p>
                    <form id="requestBookForm" method="GET" action="request_book.php">
                        <input type="hidden" name="id" id="bookId">
                        <div class="mb-3">
                            <label for="returnDate" class="form-label">Select Return Date</label>
                            <input type="date" class="form-control" id="returnDate" name="return_date" required aria-describedby="returnDateHelp">
                            <div id="returnDateHelp" class="form-text text-muted">Please select a return date between after tomorrow and 30 days from now.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info" onclick="submitRequest()">Confirm Request</button>
                </div>
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
                }, 400);
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
                    <p class="mt-3" style="color:#222;">Logging out...</p>
                `;
                document.body.appendChild(loadingScreen);
                // Redirect after a short delay to show the animation
                setTimeout(() => {
                    window.location.href = 'logout.php';
                }, 800);
            }
        }

        // Initialize modal when document is ready
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOMContentLoaded event fired');
            const modalElement = document.getElementById('returnDateModal');
            console.log('Modal element:', modalElement);
            
            if (modalElement) {
                try {
                    window.requestModal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: true
                    });
                    console.log('Modal initialized successfully');

                    // Add click event listeners to all request buttons
                    document.querySelectorAll('.request-btn').forEach(button => {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            const bookId = this.getAttribute('data-book-id');
                            openRequestModal(bookId);
                        });
                    });
                } catch (error) {
                    console.error('Error initializing modal:', error);
                }
            } else {
                console.error('Modal element not found in DOM');
            }
        });

        function openRequestModal(bookId) {
            console.log('openRequestModal called with bookId:', bookId);
            
            // Set the book ID in the hidden input
            const bookIdInput = document.getElementById('bookId');
            if (!bookIdInput) {
                console.error('bookId input element not found');
                return;
            }
            bookIdInput.value = bookId;
            
            // Set min date to today
            const today = new Date();

            // Set max date to 30 days from now
            const maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + 30);
            
            // Format dates for the date input
            const todayFormatted = today.toISOString().split('T')[0];
            const maxDateFormatted = maxDate.toISOString().split('T')[0];
            
            // Set min and max attributes for the date input
            const returnDateInput = document.getElementById('returnDate');
            if (!returnDateInput) {
                console.error('returnDate input element not found');
                return;
            }
            returnDateInput.min = todayFormatted;
            returnDateInput.max = maxDateFormatted;
            returnDateInput.value = todayFormatted; // Set default value to today
            
            // Show the modal
            if (window.requestModal) {
                console.log('Attempting to show modal');
                try {
                    window.requestModal.show();
                    console.log('Modal show method called');
                } catch (error) {
                    console.error('Error showing modal:', error);
                }
            } else {
                console.error('Modal not initialized');
            }
        }

        function submitRequest() {
            const returnDate = document.getElementById('returnDate').value;
            const selectedDate = new Date(returnDate);
            // Set min date to today
            const today = new Date();

            // Set max date to 30 days from now
            const maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + 30);
            
            // Format dates for the date input
            const todayFormatted = today.toISOString().split('T')[0];
            const maxDateFormatted = maxDate.toISOString().split('T')[0];
            
            // Set min and max attributes for the date input
            const returnDateInput = document.getElementById('returnDate');
            if (!returnDateInput) {
                console.error('returnDate input element not found');
                return;
            }
            returnDateInput.min = todayFormatted;
            returnDateInput.max = maxDateFormatted;
            returnDateInput.value = todayFormatted; // Set default value to today
            
            if (selectedDate < today || selectedDate > maxDate) {
                alert('Please select a return date between today and 30 days from now.');
                return;
            }
            
            // Hide the modal first
            if (window.requestModal) {
                window.requestModal.hide();
            }
            
            // Submit the form
            document.getElementById('requestBookForm').submit();
        }
    </script>
</body>
</html>



