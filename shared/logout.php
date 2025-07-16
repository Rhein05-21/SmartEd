<?php
session_start();
session_unset();
session_destroy();

if (isset($_GET['type']) && $_GET['type'] === 'student') {
    header('Location: ../shared/student_login.php');
} else {
    header('Location: ../shared/admin_login.php');
}
exit();
?> 