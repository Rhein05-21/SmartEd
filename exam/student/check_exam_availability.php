<?php
session_start();
require_once '../../shared/db_connect.php';
require_once '../config/timezone.php';

if (!isset($_SESSION['student_id']) || !isset($_POST['exam_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$student_id = $_SESSION['student_id'];
$exam_id = $_POST['exam_id'];

// Get exam details
$stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    echo json_encode(['status' => 'error', 'message' => 'Exam not found']);
    exit;
}

$now = new DateTime();
$start_date = new DateTime($exam['start_date']);
$end_date = new DateTime($exam['end_date']);

error_log("NOW: " . $now->format('Y-m-d H:i:s'));
error_log("START: " . $start_date->format('Y-m-d H:i:s'));
error_log("END: " . $end_date->format('Y-m-d H:i:s'));

if ($now < $start_date) {
    echo json_encode(['status' => 'error', 'message' => 'Exam has not started yet']);
    exit;
}
if ($now > $end_date) {
    echo json_encode(['status' => 'error', 'message' => 'Exam has ended']);
    exit;
}

// Check if already finished
$stmt = $pdo->prepare("SELECT status FROM exam_attempts WHERE exam_id = ? AND student_id = ? ORDER BY attempt_id DESC LIMIT 1");
$stmt->execute([$exam_id, $student_id]);
$attempt = $stmt->fetch();

if ($attempt && $attempt['status'] === 'finished') {
    echo json_encode(['status' => 'error', 'message' => 'You have already completed this exam']);
    exit;
}

echo json_encode(['status' => 'ok']);
