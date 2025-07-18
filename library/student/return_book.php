<?php
session_start();
header('Content-Type: application/json');
require_once '../../shared/db_connect.php';

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_GET['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'No request ID']);
    exit;
}

$request_id = intval($_GET['request_id']);

// Get internal student id
$stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Get the book_id for this request
$stmt = $pdo->prepare("SELECT book_id FROM book_requests WHERE id = ? AND student_id = ?");
$stmt->execute([$request_id, $student['id']]);
$book_id = $stmt->fetchColumn();

if (!$book_id) {
    echo json_encode(['success' => false, 'message' => 'Book request not found']);
    exit;
}

$pdo->beginTransaction();
try {
// Update the book request status
$stmt = $pdo->prepare("UPDATE book_requests SET status = 'returned', return_date = NOW() WHERE id = ? AND student_id = ?");
$stmt->execute([$request_id, $student['id']]);

    // Set the book as available
    $stmt = $pdo->prepare("UPDATE books SET available = 1 WHERE id = ?");
    $stmt->execute([$book_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to return book: ' . $e->getMessage()]);
}
exit; 