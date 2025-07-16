<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../../shared/db_connect.php';

if (isset($_GET['id'])) {
    $book_id = $_GET['id'];
    $student_id = $_SESSION['student_id'];
    $return_date = isset($_GET['return_date']) ? $_GET['return_date'] : null;

    // Get the internal id from students table first
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        $_SESSION['error'] = "Student not found.";
        header('Location: browse_books.php');
        exit();
    }

    // Check if book is available and not already requested by this student
    $stmt = $pdo->prepare("
        SELECT b.available, 
               (SELECT COUNT(*) FROM book_requests 
                WHERE book_id = ? AND student_id = ? 
                AND status IN ('pending', 'approved')) as has_active_request
        FROM books b 
        WHERE b.id = ?
    ");
    $stmt->execute([$book_id, $student['id'], $book_id]);
    $result = $stmt->fetch();

    if ($result['available'] && !$result['has_active_request']) {
        // Insert book request as pending
        $stmt = $pdo->prepare("INSERT INTO book_requests (student_id, book_id, status, return_date) VALUES (?, ?, 'pending', ?)");
        $stmt->execute([$student['id'], $book_id, $return_date]);
        
        $_SESSION['success'] = "Book request submitted successfully!";
    } else {
        $_SESSION['error'] = "Book is not available or you already have an active request for this book.";
    }
}

header('Location: browse_books.php');
exit();
?>