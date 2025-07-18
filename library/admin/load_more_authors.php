<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    exit('Unauthorized');
}

require_once 'config/db_connect.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 4;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT * FROM authors ORDER BY name LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$authors = $stmt->fetchAll();

foreach ($authors as $author): ?>
    <tr class="lazy-load-item" data-loaded="false">
        <td><?php echo htmlspecialchars($author['name']); ?></td>
        <td>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $author['id']; ?>">
                Edit
            </button>
            <a href="?delete=<?php echo $author['id']; ?>" 
               class="btn btn-danger btn-sm"
               onclick="return confirm('Are you sure?')">
                Delete
            </a>
        </td>
    </tr>
<?php endforeach; ?>