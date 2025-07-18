<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}
require_once '../../shared/db_connect.php';
$action = $_POST['action'] ?? '';
$book_id = $_POST['book_id'] ?? '';
if (!is_numeric($book_id)) {
    echo json_encode(['success' => false, 'error' => 'Invalid book ID']);
    exit();
}
try {
    if ($action === 'archive') {
        $stmt = $pdo->prepare('UPDATE books SET archived = 1 WHERE id = ?');
        $stmt->execute([$book_id]);
        echo json_encode(['success' => true]);
        exit();
    } elseif ($action === 'unarchive') {
        $stmt = $pdo->prepare('UPDATE books SET archived = 0 WHERE id = ?');
        $stmt->execute([$book_id]);
        echo json_encode(['success' => true]);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit();
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
} 