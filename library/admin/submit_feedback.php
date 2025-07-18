<?php
require_once '../../shared/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$commentType = $input['commentType'] ?? '';
$commentAbout = $input['commentAbout'] ?? '';
$commentText = $input['commentText'] ?? '';
$studentId = $input['studentId'] ?? '';

if (empty($commentType) || empty($commentAbout) || empty($commentText) || empty($studentId)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    // Get the internal student ID
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    $internal_student_id = $student['id'];
    
    // Insert feedback into database
    $stmt = $pdo->prepare("
        INSERT INTO feedback (student_id, comment_type, comment_about, comment_text, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([$internal_student_id, $commentType, $commentAbout, $commentText]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
    }
    
} catch (PDOException $e) {
    error_log("Error submitting feedback: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 