<?php
session_start();
// If already logged in, redirect to choose_dashboard.php if accessing admin_login.php
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

// Fetch current profile data
require_once 'db_connect.php';
try {
    $stmt = $pdo->prepare("SELECT firstname, middlename, lastname FROM admins WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $profile = ['firstname' => '', 'middlename' => '', 'lastname' => ''];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    
    if (!empty($firstname) && !empty($lastname)) {
        require_once 'db_connect.php';
        try {
            $sql = "UPDATE admins SET firstname = ?, middlename = ?, lastname = ? WHERE admin_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$firstname, $middlename, $lastname, $admin_id]);
            $_SESSION['profile_update_success'] = true;
        } catch (PDOException $e) {
            $_SESSION['profile_update_error'] = true;
        }
        
        // Redirect to refresh the page
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['dashboard_choice'])) {
        if ($_POST['dashboard_choice'] === 'exam') {
            header('Location: ../exam/admin/admin_dashboard.php');
            exit();
        } elseif ($_POST['dashboard_choice'] === 'library') {
            header('Location: ../library/admin/dashboard.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartEd</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        .modal.show {
            display: flex;
        }
    </style>
    <script>
        function openModal() {
            document.getElementById('profileModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('profileModal').classList.remove('show');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('profileModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</head>
<body class="min-h-screen flex items-center justify-center" style="background-image: url('newbg.jpg'); background-size: cover; background-repeat: no-repeat; background-position: center;">
    <div class="bg-white rounded-2xl shadow-lg p-10 w-full max-w-md text-center">
        <?php if (isset($_SESSION['profile_update_success'])): ?>
            <div class="bg-gray-50 border border-gray-200 text-gray-800 px-4 py-3 rounded-lg relative mb-4 flex items-center" role="alert">
                <svg class="w-5 h-5 text-gray-800 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="block sm:inline">Profile updated successfully!</span>
            </div>
            <?php unset($_SESSION['profile_update_success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['profile_update_error'])): ?>
            <div class="bg-gray-50 border border-gray-200 text-gray-800 px-4 py-3 rounded-lg relative mb-4 flex items-center" role="alert">
                <svg class="w-5 h-5 text-gray-800 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span class="block sm:inline">Failed to update profile. Please try again.</span>
            </div>
            <?php unset($_SESSION['profile_update_error']); ?>
        <?php endif; ?>

        <h2 class="text-2xl font-bold text-gray-800 mb-2">Hello, <?php echo htmlspecialchars($admin_id); ?>!</h2>
        <button onclick="openModal()" class="mb-6 text-gray-700 hover:text-gray-500 inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="ml-2 text-gray-700 hover:text-gray-500">Update Profile</span>
        </button>

        <!-- Modal -->
        <div id="profileModal" class="modal">
            <div class="bg-white rounded-2xl shadow-lg p-10 w-full max-w-md m-auto">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h3 class="text-xl font-bold text-gray-800">Update Profile</h3>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>
                <form method="POST" class="text-left">
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="firstname">First Name *</label>
                        <input type="text" name="firstname" id="firstname" required class="appearance-none bg-gray-50 border border-gray-200 rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:border-gray-500 focus:bg-white transition-colors" value="<?php echo htmlspecialchars($profile['firstname'] ?? ''); ?>">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="middlename">Middle Name (Optional)</label>
                        <input type="text" name="middlename" id="middlename" class="appearance-none bg-gray-50 border border-gray-200 rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:border-gray-500 focus:bg-white transition-colors" value="<?php echo htmlspecialchars($profile['middlename'] ?? ''); ?>">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="lastname">Last Name *</label>
                        <input type="text" name="lastname" id="lastname" required class="appearance-none bg-gray-50 border border-gray-200 rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:border-gray-500 focus:bg-white transition-colors" value="<?php echo htmlspecialchars($profile['lastname'] ?? ''); ?>">
                    </div>
                    <div class="flex justify-end gap-4 mt-8 pt-4 border-t">
                        <button type="button" onclick="closeModal()" class="bg-gray-100 text-gray-700 py-2 px-6 rounded-full font-semibold hover:bg-gray-200 transition">Cancel</button>
                        <button type="submit" name="update_profile" class="bg-gray-800 text-white py-2 px-6 rounded-full font-semibold hover:bg-gray-700 transition">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <p class="mb-6 text-gray-600">Choose which dashboard you want to access:</p>
        <form method="POST">
            <button name="dashboard_choice" value="exam" class="w-full mb-4 bg-gray-800 text-white py-3 rounded-full font-semibold text-lg hover:bg-gray-600 transition">Exam Dashboard</button>
            <button name="dashboard_choice" value="library" class="w-full bg-gray-600 text-white py-3 rounded-full font-semibold text-lg hover:bg-gray-800 transition">Library Dashboard</button>
        </form>
        <form method="POST" action="logout.php" class="mt-6">
            <button type="submit" class="text-lg text-black-400 hover:text-red-500">Logout</button>
        </form>
    </div>
</body>
</html>