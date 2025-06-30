<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../shared/admin_login.php');
    exit();
}
$admin_id = $_SESSION['admin_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-200 to-blue-100">
    <div class="bg-white rounded-2xl shadow-lg p-10 w-full max-w-md text-center">
        <h2 class="text-2xl font-bold text-purple-700 mb-2">Welcome to the Library Dashboard, <?php echo htmlspecialchars($admin_id); ?>!</h2>
        <button id="logoutBtn" class="text-sm text-gray-400 hover:text-red-500">Logout</button>
    </div>
    <!-- Modal -->
    <div id="logoutModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50 hidden">
      <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-xs text-center">
        <h3 class="text-lg font-semibold mb-4">Confirm Logout</h3>
        <p class="mb-6">Are you sure you want to log out?</p>
        <div class="flex justify-between gap-4">
          <button id="cancelLogout" class="w-1/2 py-2 rounded bg-gray-200 hover:bg-gray-300">Cancel</button>
          <button id="confirmLogout" class="w-1/2 py-2 rounded bg-red-500 text-white hover:bg-red-600">Logout</button>
        </div>
      </div>
    </div>
    <script>
      const logoutBtn = document.getElementById('logoutBtn');
      const logoutModal = document.getElementById('logoutModal');
      const cancelLogout = document.getElementById('cancelLogout');
      const confirmLogout = document.getElementById('confirmLogout');
      logoutBtn.onclick = () => { logoutModal.classList.remove('hidden'); };
      cancelLogout.onclick = () => { logoutModal.classList.add('hidden'); };
      confirmLogout.onclick = () => { window.location.href = '../shared/logout.php'; };
    </script>
</body>
</html>
