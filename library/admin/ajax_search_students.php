<?php
session_start();

// Set JSON response headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    require_once '../../shared/db_connect.php';
    require_once 'includes/pagination.php';

    // Get search term from AJAX request
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $conditions = "WHERE firstname LIKE :search OR lastname LIKE :search OR email LIKE :search OR student_id LIKE :search";
    $pagination = setupPagination($pdo, 'students', $conditions);
    
    $stmt = $pdo->prepare("
        SELECT * FROM students 
        $conditions
        ORDER BY firstname, lastname 
        LIMIT :limit OFFSET :offset
    ");
    $search_param = "%$search%";
    $stmt->bindValue(':search', $search_param);
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
} else {
    $pagination = setupPagination($pdo, 'students');
    $stmt = $pdo->prepare("
        SELECT * FROM students 
        ORDER BY firstname, lastname 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
}

if (!$stmt->execute()) {
        throw new PDOException('Failed to execute query');
    }
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students) && !empty($search)) {
        echo json_encode([
            'students' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 0
            ],
            'message' => 'No students found matching your search criteria.'
        ]);
        exit();
    }

    // Format dates and sanitize output
    foreach ($students as &$student) {
        $student['birthdate'] = date('Y-m-d', strtotime($student['birthdate']));
        $student['created_at'] = date('Y-m-d', strtotime($student['created_at']));
        $student = array_map('htmlspecialchars', $student);
    }

    // Prepare response data
    $response = [
        'students' => $students,
        'pagination' => [
            'current_page' => $pagination['page'],
            'total_pages' => $pagination['total_pages']
        ]
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while fetching students. Please try again.']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['error' => 'An unexpected error occurred. Please try again.']);
}