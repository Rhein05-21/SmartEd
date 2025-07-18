<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: ../../shared/student_login.php');
    exit();
}

require_once '../../shared/db_connect.php';
require_once '../admin/notification_handler.php';

$student_id = $_SESSION['student_id'];

// Get student name from database if not in session
if (!isset($_SESSION['name'])) {
    $stmt = $pdo->prepare("SELECT firstname, lastname FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    if ($student) {
        $name = $student['firstname'] . ' ' . $student['lastname'];
        $_SESSION['name'] = $name; // Store in session for future use
    } else {
        $name = 'Student'; // Fallback name
    }
} else {
$name = $_SESSION['name'];
}

// Get notifications for the student
$notifications = getStudentNotifications($student_id);
$unread_count = count($notifications);

// Get available books count
$stmt = $pdo->query("SELECT COUNT(*) FROM books WHERE available = 1 AND archived = 0");
$available_books = $stmt->fetchColumn();

// Get pending requests count for current student
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM book_requests br
    JOIN students s ON br.student_id = s.id
    WHERE s.student_id = ? AND br.status = 'pending'
");
$stmt->execute([$student_id]);
$pending_requests = $stmt->fetchColumn();

// Get list of available books for display
$stmt = $pdo->query("
    SELECT b.*, a.name as author_name, c.name as category_name 
    FROM books b 
    LEFT JOIN authors a ON b.author_id = a.id 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.available = 1 AND b.archived = 0
    ORDER BY b.title 
    LIMIT 4
");
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SmartEd</title>
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

        .sidebar .nav-link {
            color: #fff;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #2c3344;
            color: #fff;
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

        .stats-card {
            background-color: #e0e0e0;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 1px solid #cccccc;
            cursor: pointer;
            transition: transform 0.3s;
            color: #222;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .nav-link {
            color: var(--text-color);
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background-color: var(--accent-color);
            color: var(--primary-bg);
        }

        .nav-link.active {
            background-color: var(--accent-color);
            color: var(--primary-bg);
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

        .stats-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
            color: #444;
        }

        .text-info {
            color: #888 !important;
        }

        .text-info:hover {
            color: #444 !important;
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

        .text-danger {
            color: #ff4444 !important;
        }

        .text-danger:hover {
            color: #ff6666 !important;
        }

        .text-muted {
            color: var(--muted-text) !important;
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
            min-height: 250px;
            color: #222;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            max-width: 320px;
        }

        .book-card h4 {
            font-size: 1.25rem;
            line-height: 1.4;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 2.8em;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #888;
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
        .loading-screen.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        .rating-stars {
            color: #ffd700;
            cursor: pointer;
        }
        .rating-stars i {
            margin-right: 5px;
            opacity: 0.3;
            transition: all 0.2s;
        }
        .rating-stars i.active {
            opacity: 1;
        }
        .rating-stars i:hover {
            transform: scale(1.2);
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
        <p class="mt-3" style="color:#222;">SmartEd Dashboard...</p>
    </div>
    <div class="sidebar">
        <a href="dashboard.php" class="logo">
            <div class="logo-bar"><img src="smarted-letter-only.jpg" alt="e-Shelf Logo" class="img-fluid" style="max-width: 90px;"></div>
        </a>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="browse_books.php">
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
        <div class="d-flex justify-content-end mb-3">
            
            <div class="position-relative me-3">
                <i class="fas fa-bell fs-5 text-info" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
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
                    <li><a class="dropdown-item text-info" href="viewnotification.php">View all notifications</a></li>
                </ul>
            </div>
            <div class="position-relative me-3">
                <i class="fas fa-cog fs-5 text-info" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><h6 class="dropdown-header">Settings</h6></li>
                    <li><a class="dropdown-item" href="update_profile.php"><i class="fas fa-user-edit me-2"></i>Update Profile</a></li>
                    <li><a class="dropdown-item" href="../../shared/student_choose_dashboard.php"><i class="fas fa-th-large me-2"></i>Switch Dashboard</a></li>
                    <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#feedbackModal" href="#"><i class="fas fa-comment me-2"></i>Feedback</a></li>
                    <li><a class="dropdown-item" href="#" onclick="handleLogout()"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
        <div class="welcome-card">
            <h2>Welcome, <?php echo htmlspecialchars($name); ?>!</h2>
            <p class="mb-0">Student ID: <?php echo htmlspecialchars($student_id); ?></p>
            <p>Browse and request books from our collection</p>
            
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="stats-card">
                    <i class="fas fa-book stats-icon"></i>
                    <h3>Available Books</h3>
                    <div class="stats-number"><?php echo $available_books; ?></div>
                    <a href="browse_books.php" class="text-info text-decoration-none">Click to view all books</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <i class="fas fa-clock stats-icon"></i>
                    <h3>Pending Requests</h3>
                    <div class="stats-number"><?php echo $pending_requests; ?></div>
                    <a href="my_books.php" class="text-info text-decoration-none">Click to view your requests</a>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Available Books</h3>
                <button class="btn btn-info" onclick="window.location.href='browse_books.php'">
                    View All Books
                </button>
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
                            <div class="book-card-footer">
                                <a href="browse_books.php" class="btn btn-info">
                                    Go to browse books
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">No books available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>



    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="feedbackModalLabel">Write Your Feedback</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="feedbackForm">
                            <div class="mb-3">
                                <label class="form-label">What kind of comment would you like to send?</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="commentType" id="complaint" value="Complaint" required>
                                        <label class="form-check-label" for="complaint">Complaint</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="commentType" id="problem" value="Problem">
                                        <label class="form-check-label" for="problem">Problem</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="commentType" id="suggestion" value="Suggestion">
                                        <label class="form-check-label" for="suggestion">Suggestion</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="commentType" id="praise" value="Praise">
                                        <label class="form-check-label" for="praise">Praise</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="commentAbout" class="form-label">What about the library do you want to comment on?</label>
                                <input type="text" class="form-control bg-dark text-white" id="commentAbout" name="commentAbout" placeholder="What about the library do you want to comment on?" required>
                            </div>
                            <div class="mb-3">
                                <label for="commentText" class="form-label">Enter your comments in the space provided below</label>
                                <textarea class="form-control bg-dark text-white" id="commentText" name="commentText" rows="4" required placeholder="Enter your comments in the space provided below"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-info" id="submitFeedback">Submit Feedback</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-info rounded position-fixed" 
            style="bottom: 25px; left: 30px; z-index: 1050;" 
            data-bs-toggle="modal" data-bs-target="#feedbackModal">
      <i class="fas fa-comment"> Feedback</i>
    </button>

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

        // Star rating functionality
        const ratingStars = document.querySelectorAll('.rating-stars i');
        const selectedRating = document.getElementById('selectedRating');

        ratingStars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = parseInt(star.getAttribute('data-rating'));
                selectedRating.value = rating;
                
                ratingStars.forEach(s => {
                    s.classList.remove('active');
                    if (parseInt(s.getAttribute('data-rating')) <= rating) {
                        s.classList.add('active');
                    }
                });
            });

            star.addEventListener('mouseover', () => {
                const rating = parseInt(star.getAttribute('data-rating'));
                ratingStars.forEach(s => {
                    if (parseInt(s.getAttribute('data-rating')) <= rating) {
                        s.style.opacity = '1';
                    }
                });
            });

            star.addEventListener('mouseout', () => {
                ratingStars.forEach(s => {
                    if (!s.classList.contains('active')) {
                        s.style.opacity = '0.3';
                    }
                });
            });
        });

        // Feedback submission
        document.getElementById('submitFeedback').addEventListener('click', () => {
            const commentType = document.querySelector('input[name="commentType"]:checked');
            const commentAbout = document.getElementById('commentAbout').value;
            const commentText = document.getElementById('commentText').value;

            if (!commentType) {
                alert('Please select a comment type');
                return;
            }
            if (!commentAbout.trim()) {
                alert('Please specify what about the library you want to comment on');
                return;
            }
            if (!commentText.trim()) {
                alert('Please write your feedback');
                return;
            }

            fetch('../admin/submit_feedback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    commentType: commentType.value,
                    commentAbout: commentAbout,
                    commentText: commentText,
                    studentId: '<?php echo htmlspecialchars($student_id); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for your feedback!');
                    document.getElementById('feedbackForm').reset();
                    $('#feedbackModal').modal('hide');
                } else {
                    alert('Error submitting feedback. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting feedback. Please try again.');
            });
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