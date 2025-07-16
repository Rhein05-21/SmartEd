<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'config/db_connect.php';

// Get all students basic information
$stmt = $pdo->prepare("SELECT id, student_id, firstname, lastname FROM students ORDER BY firstname, lastname");
$stmt->execute();
$students = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($students);