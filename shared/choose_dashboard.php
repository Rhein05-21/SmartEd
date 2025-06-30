<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['dashboard_choice'])) {
        if ($_POST['dashboard_choice'] === 'exam') {
            header('Location: ../exam/exam_dashboard.php');
            exit();
        } elseif ($_POST['dashboard_choice'] === 'library') {
            header('Location: ../library/library_dashboard.php');
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-200 to-blue-100">
    <div class="bg-white rounded-2xl shadow-lg p-10 w-full max-w-md text-center">
        <h2 class="text-2xl font-bold text-purple-700 mb-2">Hello, <?php echo htmlspecialchars($admin_id); ?>!</h2>
        <p class="mb-6 text-gray-600">Choose which dashboard you want to access:</p>
        <form method="POST">
            <button name="dashboard_choice" value="exam" class="w-full mb-4 bg-gradient-to-r from-purple-500 to-blue-500 text-white py-3 rounded-full font-semibold text-lg hover:from-purple-600 hover:to-blue-600 transition">Exam Dashboard</button>
            <button name="dashboard_choice" value="library" class="w-full bg-gradient-to-r from-blue-500 to-purple-500 text-white py-3 rounded-full font-semibold text-lg hover:from-blue-600 hover:to-purple-600 transition">Library Dashboard</button>
        </form>
        <form method="POST" action="logout.php" class="mt-6">
            <button type="submit" class="text-sm text-gray-400 hover:text-red-500">Logout</button>
        </form>
    </div>
</body>
</html> 