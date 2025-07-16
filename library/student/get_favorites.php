<?php
session_start();
require_once '../../shared/db_connect.php';

$student_id = $_SESSION['student_id'];
$stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

$stmt_favorites = $pdo->prepare("
    SELECT b.*, a.name as author_name, c.name as category_name
    FROM favorites f
    JOIN books b ON f.book_id = b.id
    LEFT JOIN authors a ON b.author_id = a.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE f.student_id = ?
    ORDER BY f.created_at DESC
");
$stmt_favorites->execute([$student['id']]);
$favorite_books = $stmt_favorites->fetchAll();

foreach ($favorite_books as $book) {
    echo '<div class="col">
        <div class="card h-100 bg-secondary text-white">
            <div class="card-body">
                <h5 class="card-title text-info">'.htmlspecialchars($book['title']).'</h5>
                <p class="card-text">Author: '.htmlspecialchars($book['author_name']).'</p>
                <p class="card-text">Category: '.htmlspecialchars($book['category_name']).'</p>';
    if ($book['isbn']) {
        echo '<p class="card-text">ISBN: '.htmlspecialchars($book['isbn']).'</p>';
    }
    echo    '</div>
        </div>
    </div>';
}
if (empty($favorite_books)) {
    echo '<div class="col-12 text-center"><p class="text-muted">No favorite books yet.</p></div>';
}
?>