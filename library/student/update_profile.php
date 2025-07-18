<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../../shared/db_connect.php';

// Get current student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

// Get student_id from session
$student_id = $_SESSION['student_id'];

// Handle form submission
// Add password handling to the PHP section at the top
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get profile update fields if they exist
    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $age = isset($_POST['age']) ? trim($_POST['age']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $school = isset($_POST['school']) ? trim($_POST['school']) : '';
    
    // Get password fields if they exist
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Handle password update if provided
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM students WHERE student_id = ?");
        $stmt->execute([$_SESSION['student_id']]);
        $stored_password = $stmt->fetchColumn();

        if (password_verify($current_password, $stored_password)) {
            if ($new_password === $confirm_password) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                $stmt->execute([$hashed_password, $_SESSION['student_id']]);
                $_SESSION['success'] = "Profile and password updated successfully!";
            } else {
                $_SESSION['error'] = "New passwords do not match.";
            }
        } else {
            $_SESSION['error'] = "Current password is incorrect.";
        }
    }

    // Update profile information
    if (!empty($firstname) && !empty($lastname) && !empty($email)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE students 
                SET firstname = ?, lastname = ?, email = ?, age = ?, address = ?, phone = ?, school = ?
                WHERE student_id = ?
            ");
            $stmt->execute([$firstname, $lastname, $email, $age, $address, $phone, $school, $_SESSION['student_id']]);
            
            $_SESSION['name'] = $firstname . ' ' . $lastname;
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: update_profile.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating profile. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - SmartEd</title>
    <link rel="icon" type="image/x-icon" href="version2.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Same styles as other pages -->
    <style>
        /* Copy the same root and style definitions from other pages */
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
            background-color: var(--secondary-bg);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
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

        .btn-info, .btn.btn-info, .btn-primary {
            background: #fff !important;
            color: #444 !important;
            border: 1px solid #ccc !important;
            font-weight: bold;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .btn-info:hover, .btn.btn-info:hover, .btn-primary:hover {
            background: #e5e5e5 !important;
            color: #222 !important;
        }

        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .book-card {
            background-color: var(--secondary-bg);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .book-card h4 {
            color: var(--accent-color);
            margin-bottom: 15px;
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
        .loading-screen.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        .logo-bar {
            margin-left: 5%;
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
        <p class="mt-3" style="color:#222;">Update Profile...</p>
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
            <a class="nav-link" href="my_books.php">
                <i class="fas fa-bookmark me-2"></i> My Books
            </a>
            <a class="nav-link active" href="update_profile.php">
                <i class="fas fa-user-edit me-2"></i> Update Profile
            </a>
            <a class="nav-link" href="../../shared/student_choose_dashboard.php">
                <i class="fas fa-th-large me-2"></i> Switch Dashboard
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="welcome-card">
            <h2>Update Profile</h2>
            <p>Update your personal information</p>
            <p class="mb-0">Student ID: <?php echo htmlspecialchars($student_id); ?></p>
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

        <div class="card">
            
            <div class="card-body" style="background-color: var(--secondary-bg); border-radius: 15px;">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="firstname" class="form-label text-white">First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" 
                               value="<?php echo htmlspecialchars($student['firstname']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="lastname" class="form-label text-white">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" 
                               value="<?php echo htmlspecialchars($student['lastname']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label text-white">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="age" class="form-label text-white">Age</label>
                        <input type="number" class="form-control" id="age" name="age" 
                               value="<?php echo htmlspecialchars($student['age']); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label text-white">Address</label>
                        <input type="text" class="form-control" id="address" name="address" 
                               value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label text-white">Phone Number(11-digits)</label>
                        <input type="text" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="school" class="form-label text-white">School / University (Optional)</label>
                        <input type="text" class="form-control" id="school" name="school" 
                               value="<?php echo htmlspecialchars($student['school'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn" style="background-color: var(--accent-color); color: var(--primary-bg);">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add these additional styles in the <style> section -->
        
    </div>

    <div class="main-content">

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

        <div class="card mt-4">
            <div class="card-body" style="background-color: var(--secondary-bg); border-radius: 15px;">
                <h4 class="text-white mb-4">Change Password</h4>
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label for="current_password" class="form-label text-white">Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="new_password" class="form-label text-white">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="confirm_password" class="form-label text-white">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn" style="background-color: var(--accent-color); color: var(--primary-bg);">
                            <i class="fas fa-key me-2"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add these additional styles in the <style> section -->
        <style>
            .card {
                background-color: transparent;
                border: none;
            }
            
            .form-control {
                background-color: var(--primary-bg);
                border: 1px solid var(--border-color);
                color: var(--text-color);
                padding: 10px 15px;
                border-radius: 8px;
            }
        
            .form-control:focus {
                background-color: var(--primary-bg);
                border-color: var(--accent-color);
                color: var(--text-color);
                box-shadow: 0 0 0 0.25rem rgba(0, 216, 199, 0.25);
            }

            .input-group .btn {
                background-color: var(--primary-bg);
                border-color: var(--border-color);
                color: var(--text-color);
            }

            .input-group .btn:hover {
                background-color: var(--accent-color);
                border-color: var(--accent-color);
                color: var(--primary-bg);
            }
        
            .welcome-card {
                background: linear-gradient(45deg, #cccccc, #b3b3b3);
                padding: 30px;
                border-radius: 15px;
                margin-bottom: 30px;
                position: relative;
                overflow: hidden;
            }
        
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                transition: all 0.3s ease;
            }
            .logo-bar {
            margin-left: 5%;
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
        </style>
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

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
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
</body>
</html>