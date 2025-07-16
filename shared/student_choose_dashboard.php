<?php
session_start();
require_once '../shared/db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_number'], $_POST['password'])) {
    $student_number = trim($_POST['student_number']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND verified = 1");
    $stmt->execute([$student_number]);
    $student = $stmt->fetch();

    if ($student && password_verify($password, $student['password'])) {
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['student_email'] = $student['email'];
        header("Location: student_choose_dashboard.php");
        exit();
    } else {
        $login_error = "Invalid student number, password, or account not verified.";
    }
}

// Fetch current student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $birthdate = DateTime::createFromFormat('m-d-Y', $_POST['birthdate']);
    if ($birthdate) {
        $birthdate = $birthdate->format('Y-m-d');
    } else {
        $login_error = "Invalid birthdate format. Please use MM-DD-YYYY.";
    }
    $age = intval($_POST['age']);
    $phone = trim($_POST['phone']);
    $school = trim($_POST['school']);
    $address = trim($_POST['address']);

    // Validate phone
    if (!preg_match('/^\\d{11}$/', $phone)) {
        $login_error = "Phone number must be exactly 11 digits.";
    } else {
        $stmt = $pdo->prepare("UPDATE students SET firstname=?, lastname=?, birthdate=?, age=?, phone=?, school=?, address=? WHERE student_id=?");
        $stmt->execute([$firstname, $lastname, $birthdate, $age, $phone, $school, $address, $_SESSION['student_id']]);
        // Refresh student info
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$_SESSION['student_id']]);
        $student = $stmt->fetch();
        $login_error = "Profile updated successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Choose Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-green-100">
    <div class="bg-white rounded-lg shadow-lg p-8 flex flex-col items-center">
        <h2 class="text-2xl font-bold mb-6">Welcome, <?php echo htmlspecialchars($student['firstname'] ?? ''); ?>!</h2>
        <p class="mb-6">Please choose your dashboard:</p>
        <div class="flex space-x-8">
            <a href="../library/student/dashboard.php" class="px-6 py-4 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition">Go to Library</a>
            <a href="../exam/student/student_dashboard.php" class="px-6 py-4 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition">Take Course/Exam</a>
        </div>

        <!-- Update Profile Button -->
        <button onclick="document.getElementById('profileModal').classList.remove('hidden')" class="mt-8 px-6 py-2 bg-yellow-500 text-white rounded-lg font-semibold hover:bg-yellow-600 transition">
            Update Profile
        </button>

        <!-- Profile Update Modal -->
        <div id="profileModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50 hidden">
            <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md relative">
                <button onclick="document.getElementById('profileModal').classList.add('hidden')" class="absolute top-2 right-2 text-gray-500 hover:text-red-500 text-2xl">&times;</button>
                <h3 class="text-xl font-bold mb-4">Update Profile</h3>
                <form method="POST" action="">
                    <div class="flex flex-wrap -mx-2">
                        <div class="w-full md:w-1/2 px-2 mb-3">
                            <label class="block mb-1">First Name</label>
                            <input type="text" name="firstname" value="<?php echo htmlspecialchars($student['firstname'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div class="w-full md:w-1/2 px-2 mb-3">
                            <label class="block mb-1">Last Name</label>
                            <input type="text" name="lastname" value="<?php echo htmlspecialchars($student['lastname'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div class="w-full md:w-1/2 px-2 mb-3">
                            <label class="block mb-1">Birthdate</label>
                            <input
                                type="text"
                                name="birthdate"
                                id="birthdateInput"
                                placeholder="MM-DD-YYYY"
                                pattern="^(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])-[0-9]{4}$"
                                value="<?php echo htmlspecialchars($student['birthdate'] ? date('m-d-Y', strtotime($student['birthdate'])) : ''); ?>"
                                class="w-full border rounded px-3 py-2"
                                required
                            >
                            <small class="text-gray-500">Format: MM-DD-YYYY</small>
                        </div>
                        <div class="w-full md:w-1/2 px-2 mb-3">
                            <label class="block mb-1">Age</label>
                            <input type="number" name="age" id="ageInput" value="<?php echo htmlspecialchars($student['age'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required readonly>
                        </div>
                        <div class="w-full md:w-1/2 px-2 mb-3">
                            <label class="block mb-1">Phone (11 digits)</label>
                            <input type="text" name="phone" pattern="[0-9]{11}" maxlength="11" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div class="w-full md:w-1/2 px-2 mb-3">
                            <label class="block mb-1">School/University</label>
                            <input type="text" name="school" value="<?php echo htmlspecialchars($student['school'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div class="w-full md:w-1/2 px-2 mb-3">
                            <label class="block mb-1">Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="w-full bg-green-500 text-white py-2 rounded font-semibold hover:bg-green-600 transition">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    <script>
// Auto-calculate age from birthdate
function calculateAge(birthdate) {
    if (!birthdate) return '';
    const today = new Date();
    const birth = new Date(birthdate);
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
}
document.addEventListener('DOMContentLoaded', function() {
    const birthdateInput = document.getElementById('birthdateInput');
    const ageInput = document.getElementById('ageInput');
    if (birthdateInput && ageInput) {
        birthdateInput.addEventListener('change', function() {
            ageInput.value = calculateAge(this.value);
        });
        // Set age on modal open if birthdate is already filled
        if (birthdateInput.value) {
            ageInput.value = calculateAge(birthdateInput.value);
        }
    }
    flatpickr("#birthdateInput", {
      dateFormat: "m-d-Y",
      maxDate: "today",
      defaultDate: "<?php echo $student['birthdate'] ? date('m-d-Y', strtotime($student['birthdate'])) : ''; ?>"
    });
});
</script>
</body>
</html>
