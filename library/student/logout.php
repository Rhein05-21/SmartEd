<?php
session_start();

// Destroy the session
session_destroy();

// Redirect to shared login page
header('Location: ../../shared/student_login.php');
exit();