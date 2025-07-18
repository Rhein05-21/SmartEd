<?php
require_once '../../shared/db_connect.php';

// Handle POST requests for marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'mark_as_read' && isset($input['notification_id'])) {
        $success = markNotificationAsRead($input['notification_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }
}

function createNotification($student_id, $book_id, $message, $return_date = null) {
    global $pdo;
    
    try {
        // Get the internal student ID
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return false;
        }
        
        $internal_student_id = $student['id'];
        
        // Insert notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (student_id, book_id, message, return_date) 
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([$internal_student_id, $book_id, $message, $return_date]);
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

function checkOverdueBooks() {
    global $pdo;
    
    try {
        // Get all approved book requests that are overdue
        $stmt = $pdo->prepare("
            SELECT br.id, br.book_id, br.student_id, br.return_date, b.title, s.student_id as student_number
            FROM book_requests br
            JOIN books b ON br.book_id = b.id
            JOIN students s ON br.student_id = s.id
            WHERE br.status = 'approved'
            AND br.return_date < CURRENT_DATE
            AND NOT EXISTS (
                SELECT 1 FROM notifications n 
                WHERE n.student_id = br.student_id 
                AND n.book_id = br.book_id 
                AND n.message LIKE 'WARNING: The book%is overdue%'
            )
        ");
        $stmt->execute();
        $overdueBooks = $stmt->fetchAll();
        
        foreach ($overdueBooks as $book) {
            $daysOverdue = floor((strtotime('now') - strtotime($book['return_date'])) / (60 * 60 * 24));
            $message = "WARNING: The book '{$book['title']}' is overdue by {$daysOverdue} days. Please return immediately to avoid penalties.";
            createNotification($book['student_number'], $book['book_id'], $message, $book['return_date']);
        }
        
        return count($overdueBooks);
    } catch (PDOException $e) {
        error_log("Error checking overdue books: " . $e->getMessage());
        return false;
    }
}

function checkUpcomingDueDates() {
    global $pdo;
    
    try {
        // Get all approved book requests with return dates within the next 3 days
        $stmt = $pdo->prepare("
            SELECT br.id, br.book_id, br.student_id, br.return_date, b.title, s.student_id as student_number
            FROM book_requests br
            JOIN books b ON br.book_id = b.id
            JOIN students s ON br.student_id = s.id
            WHERE br.status = 'approved'
            AND br.return_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)
            AND NOT EXISTS (
                SELECT 1 FROM notifications n 
                WHERE n.student_id = br.student_id 
                AND n.book_id = br.book_id 
                AND n.message LIKE 'Reminder: You need to return%'
            )
        ");
        $stmt->execute();
        $upcomingReturns = $stmt->fetchAll();
        
        foreach ($upcomingReturns as $return) {
            $daysLeft = floor((strtotime($return['return_date']) - strtotime('now')) / (60 * 60 * 24));
            $message = "Reminder: You need to return '{$return['title']}' in {$daysLeft} days (due on " . date('M d, Y', strtotime($return['return_date'])) . ")";
            createNotification($return['student_number'], $return['book_id'], $message, $return['return_date']);
        }
        
        return count($upcomingReturns);
    } catch (PDOException $e) {
        error_log("Error checking upcoming due dates: " . $e->getMessage());
        return false;
    }
}

function getStudentNotifications($student_id) {
    global $pdo;
    
    try {
        // Get the internal student ID
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return [];
        }
        
        $internal_student_id = $student['id'];
        
        // Get all unread notifications for the student
        $stmt = $pdo->prepare("
            SELECT n.*, b.title as book_title
            FROM notifications n
            JOIN books b ON n.book_id = b.id
            WHERE n.student_id = ? AND n.is_read = FALSE
            ORDER BY 
                CASE 
                    WHEN n.message LIKE 'WARNING%' THEN 1
                    WHEN n.message LIKE 'Reminder%' THEN 2
                    ELSE 3
                END,
                n.created_at DESC
        ");
        $stmt->execute([$internal_student_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting student notifications: " . $e->getMessage());
        return [];
    }
}

function markNotificationAsRead($notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
        return $stmt->execute([$notification_id]);
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

// Auto-check for overdue books and upcoming returns
checkOverdueBooks();
checkUpcomingDueDates();
?> 