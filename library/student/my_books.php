<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../../shared/db_connect.php';
require_once '../admin/notification_handler.php';

$student_id = $_SESSION['student_id'];

// Get notifications for the student
$notifications = getStudentNotifications($student_id);
$unread_count = count($notifications);

// Get the internal id from students table first
$stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header('Location: login.php');
    exit();
}

// Fetch student's book requests using internal student ID
$stmt = $pdo->prepare("
    SELECT br.*, b.title as book_title, b.isbn, b.pdf_path, b.cover_image,
           a.name as author_name, c.name as category_name,
           (SELECT COUNT(*) FROM favorites f WHERE f.student_id = ? AND f.book_id = b.id) as is_favorite
    FROM book_requests br
    JOIN books b ON br.book_id = b.id
    LEFT JOIN authors a ON b.author_id = a.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE br.student_id = ? AND br.status IN ('pending', 'approved', 'returned', 'declined')
    ORDER BY 
        CASE br.status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'returned' THEN 3
            WHEN 'declined' THEN 4
        END,
        br.borrow_date DESC
");
$stmt->execute([$student['id'], $student['id']]);

// Fetch favorite books
$stmt_favorites = $pdo->prepare("
    SELECT b.*, a.name as author_name, c.name as category_name
    FROM favorites f
    JOIN books b ON f.book_id = b.id
    LEFT JOIN authors a ON b.author_id = a.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE f.student_id = ?
    ORDER BY f.created_at DESC
");
$stmt_favorites->execute([$student['id']]);
$favorite_books = $stmt_favorites->fetchAll();
$requests = $stmt->fetchAll();

$active_requests = [];
$history_requests = [];
foreach ($requests as $req) {
    if (in_array($req['status'], ['pending', 'approved'])) {
        $active_requests[] = $req;
    } else {
        $history_requests[] = $req;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books - SmartEd</title>
    <link rel="icon" type="image/x-icon" href="version2.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
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

        .nav-link:hover {
            background-color: #2c3344;
            color: #fff;
        }

        .nav-link.active {
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
            color: #fff;
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
            font-size: 1.2rem;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            min-height: 2.9rem;
        }

        .book-card p {
            margin-bottom: 10px;
            color: var(--muted-text);
        }

        .badge {
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .alert {
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 20px;
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
        .loading-screen.fade-out {
            opacity: 0;
            pointer-events: none;
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

        /* Custom style for active tab */
        .nav-tabs .nav-link.active {
            background-color:rgb(119, 119, 119) !important;
            color:rgb(255, 255, 255) !important; /* Optional: accent color for text */
            border-color: #252d3b #252d3b #1c2331 #252d3b !important; /* match your theme */
            margin-bottom: 10px;
        }
        .nav-tabs .nav-link {
            color: #cbd5e0 !important; /* muted text for inactive tabs */
        }
        .btn-primary, .btn-info, .btn.btn-info {
            background: #fff !important;
            color: #444 !important;
            border: 1px solid #ccc !important;
            font-weight: bold;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .btn-primary:hover, .btn-info:hover, .btn.btn-info:hover {
            background: #e5e5e5 !important;
            color: #222 !important;
        }
        .text-info {
            color: var(--accent-color) !important;
        }
        .text-info:hover {
            color: #aaaaaa !important;
        }
        .spinner-border.text-primary {
            color: #888 !important;
        }
    </style>
</head>
<body>
    <div class="loading-screen">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3" style="color:#222;">My Books...</p>
    </div>
    <div class="sidebar">
    <a href="dashboard.php" class="logo">
            <div class="logo-bar"><img src="smarted-letter-only.jpg" alt="SmartEd Logo" class="img-fluid" style="max-width: 90px;"></div>
        </a>
        <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="browse_books.php">
                <i class="fas fa-book me-2"></i> Browse Books
            </a>
            <a class="nav-link active" href="my_books.php">
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
    </div>
        <div class="d-flex justify-content-end mb-3">
            <div class="position-relative me-3">
                
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <li>
                                <a class="dropdown-item" href="#" onclick="markNotificationAsRead(<?php echo $notification['id']; ?>)">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                    <small class="text-muted d-block"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="#">No new notifications</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-info" href="my_books.php">View all notifications</a></li>
                </ul>
            </div>
        </div>
        <div class="welcome-card">
            <h2>My Book Requests</h2>
            <p>Track your book requests and their status</p>
            <button class="btn btn-primary position-absolute top-0 end-0 m-3" onclick="showFavorites()">
                <i class="fas fa-heart me-2"></i> My Favorites
            </button>
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

        <ul class="nav nav-tabs mb-3" id="requestTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
              Active Requests
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">
              History
            </button>
          </li>
        </ul>
        <div class="tab-content" id="requestTabsContent">
          <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
            <div class="book-grid">
              <?php if (count($active_requests) > 0): ?>
                <?php foreach ($active_requests as $request): ?>
                  <div class="book-card">
                    <?php if (!empty($request['cover_image'])): ?>
                        <img src="../admin/uploads/covers/<?php echo htmlspecialchars($request['cover_image']); ?>" alt="Book Cover" style="width: 100%; max-height: 180px; object-fit: contain; border-radius: 0.5rem; margin-bottom: 10px; background: #f8f8f8; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                    <?php else: ?>
                        <img src="../admin/default_cover.jpg" alt="No Cover" style="width: 100%; max-height: 180px; object-fit: contain; border-radius: 0.5rem; margin-bottom: 10px; background: #f8f8f8; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h4 class="mb-0"><?php echo htmlspecialchars($request['book_title']); ?></h4>
                        <button class="btn btn-link p-0 favorite-btn" onclick="toggleFavorite(<?php echo $request['book_id']; ?>, this)" data-favorite="<?php echo $request['is_favorite'] ? 'true' : 'false'; ?>">
                            <i class="fas fa-heart <?php echo $request['is_favorite'] ? 'text-danger' : 'text-muted'; ?>"></i>
                        </button>
                    </div>
                    <p>Author: <?php echo htmlspecialchars($request['author_name']); ?></p>
                    <p>Category: <?php echo htmlspecialchars($request['category_name']); ?></p>
                    <?php if ($request['isbn']): ?>
                        <p>ISBN: <?php echo htmlspecialchars($request['isbn']); ?></p>
                    <?php endif; ?>
                    <p>Status: 
                      <span class="badge <?php 
                        echo match($request['status']) {
                          'pending' => 'bg-warning',
                          'approved' => 'bg-success',
                          'returned' => 'bg-info',
                          'declined' => 'bg-danger',
                          default => 'bg-secondary'
                        };
                      ?>">
                        <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                      </span>
                    </p>
                    <p>Requested: <?php echo date('M d, Y', strtotime($request['borrow_date'])); ?></p>
                    <?php if ($request['return_date']): ?>
                      <p>Returned: <?php echo date('M d, Y', strtotime($request['return_date'])); ?></p>
                    <?php endif; ?>
                    <?php if ($request['status'] == 'approved'): ?>
                      <button class="btn btn-primary btn-sm mt-2" onclick="returnBook(<?php echo $request['id']; ?>)">
                        <i class="fas fa-undo me-1"></i> Return Book
                      </button>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-muted">No active book requests found.</p>
              <?php endif; ?>
            </div>
          </div>
          <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
            <div class="book-grid">
              <?php if (count($history_requests) > 0): ?>
                <?php foreach ($history_requests as $request): ?>
                  <div class="book-card">
                    <?php if (!empty($request['cover_image'])): ?>
                        <img src="../admin/uploads/covers/<?php echo htmlspecialchars($request['cover_image']); ?>" alt="Book Cover" style="width: 100%; max-height: 180px; object-fit: contain; border-radius: 0.5rem; margin-bottom: 10px; background: #f8f8f8; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                    <?php else: ?>
                        <img src="../admin/default_cover.jpg" alt="No Cover" style="width: 100%; max-height: 180px; object-fit: contain; border-radius: 0.5rem; margin-bottom: 10px; background: #f8f8f8; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h4 class="mb-0"><?php echo htmlspecialchars($request['book_title']); ?></h4>
                        <button class="btn btn-link p-0 favorite-btn" onclick="toggleFavorite(<?php echo $request['book_id']; ?>, this)" data-favorite="<?php echo $request['is_favorite'] ? 'true' : 'false'; ?>">
                            <i class="fas fa-heart <?php echo $request['is_favorite'] ? 'text-danger' : 'text-muted'; ?>"></i>
                        </button>
                    </div>
                    <p>Author: <?php echo htmlspecialchars($request['author_name']); ?></p>
                    <p>Category: <?php echo htmlspecialchars($request['category_name']); ?></p>
                    <?php if ($request['isbn']): ?>
                        <p>ISBN: <?php echo htmlspecialchars($request['isbn']); ?></p>
                    <?php endif; ?>
                    <p>Status: 
                      <span class="badge <?php 
                        echo match($request['status']) {
                          'pending' => 'bg-warning',
                          'approved' => 'bg-success',
                          'returned' => 'bg-info',
                          'declined' => 'bg-danger',
                          default => 'bg-secondary'
                        };
                      ?>">
                        <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                      </span>
                    </p>
                    <p>Requested: <?php echo date('M d, Y', strtotime($request['borrow_date'])); ?></p>
                    <?php if ($request['return_date']): ?>
                      <p>Returned: <?php echo date('M d, Y', strtotime($request['return_date'])); ?></p>
                    <?php endif; ?>
                    <?php if ($request['status'] == 'approved'): ?>
                      <button class="btn btn-primary btn-sm mt-2" onclick="returnBook(<?php echo $request['id']; ?>)">
                        <i class="fas fa-undo me-1"></i> Return Book
                      </button>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-muted">No request history found.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
    </div>
    <script>
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.querySelector('.loading-screen').classList.add('fade-out');
                setTimeout(() => {
                    document.querySelector('.loading-screen').remove();
                }, 400);
            }, 300);
        });

        function returnBook(requestId) {
            if (confirm('Are you sure you want to return this book?')) {
                // Show loading screen
                const loadingScreen = document.createElement('div');
                loadingScreen.className = 'loading-screen';
                loadingScreen.innerHTML = `
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-white">Processing return...</p>
                `;
                document.body.appendChild(loadingScreen);

                // Send return request
                fetch(`return_book.php?request_id=${requestId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        const successAlert = document.createElement('div');
                        successAlert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                        successAlert.style.zIndex = '9999';
                        successAlert.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <span>Book returned successfully!</span>
                            </div>
                        `;
                        document.body.appendChild(successAlert);

                        // Remove loading screen and redirect after delay
                        setTimeout(() => {
                            loadingScreen.remove();
                            successAlert.remove();
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Show error message
                        const errorAlert = document.createElement('div');
                        errorAlert.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3';
                        errorAlert.style.zIndex = '9999';
                        errorAlert.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <span>${data.message || 'Error returning book'}</span>
                            </div>
                        `;
                        document.body.appendChild(errorAlert);

                        // Remove loading screen and error message after delay
                        setTimeout(() => {
                            loadingScreen.remove();
                            errorAlert.remove();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingScreen.remove();
                    alert('An error occurred while processing your request');
                });
            }
        }

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
    </script>
<!-- Favorites Modal -->
    <div class="modal fade" id="favoritesModal" tabindex="-1" aria-labelledby="favoritesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="favoritesModalLabel">My Favorite Books</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($favorite_books as $book): ?>
                            <div class="col">
                                <div class="card h-100 bg-secondary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title text-info"><?php echo htmlspecialchars($book['title']); ?></h5>
                                        <p class="card-text">Author: <?php echo htmlspecialchars($book['author_name']); ?></p>
                                        <p class="card-text">Category: <?php echo htmlspecialchars($book['category_name']); ?></p>
                                        <?php if ($book['isbn']): ?>
                                            <p class="card-text">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($favorite_books)): ?>
                            <div class="col-12 text-center">
                                <p class="text-muted">No favorite books yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showFavorites() {
            // Fetch latest favorites via AJAX
            fetch('get_favorites.php')
                .then(response => response.text())
                .then(html => {
                    document.querySelector('#favoritesModal .modal-body .row').innerHTML = html;
                    const favoritesModal = new bootstrap.Modal(document.getElementById('favoritesModal'));
                    favoritesModal.show();
                });
        }

        function toggleFavorite(bookId, button) {
            const formData = new FormData();
            formData.append('book_id', bookId);

            fetch('toggle_favorite.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const isFavorite = data.action === 'added';
                    const icon = button.querySelector('i');
                    button.dataset.favorite = isFavorite.toString();
                    icon.className = `fas fa-heart ${isFavorite ? 'text-danger' : 'text-muted'}`;

                    Swal.fire({
                        icon: 'success',
                        title: isFavorite ? 'Added to favorites!' : 'Removed from favorites',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    throw new Error(data.message || 'Error updating favorite status');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: error.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            });
        }

        function markNotificationAsRead(notificationId) {
            fetch('../admin/notification_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_as_read',
                    notification_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to update notifications
                    window.location.reload();
                }
            });
        }
    </script>
</body>
</html>