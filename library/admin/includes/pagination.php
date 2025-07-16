<?php
function setupPagination($pdo, $table, $conditions = '') {  
    $limit = 5; // Default limit per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page);
    
    // Get total records with conditions if provided
    $query = "SELECT COUNT(*) FROM $table ";
    if (!empty($conditions)) {
        $query .= $conditions;
    }
    
    $stmt = $pdo->prepare($query);
    if (!empty($conditions) && strpos($conditions, ':search') !== false) {
        $search_param = "%{$_GET['search']}%";
        $stmt->bindValue(':search', $search_param);
    }
    $stmt->execute();
    $total_records = (int)$stmt->fetchColumn();
    
    // Calculate total pages and ensure integer division
    $total_pages = (int)ceil($total_records / $limit);
    $page = min($page, max(1, $total_pages));
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset,
        'total_pages' => $total_pages
    ];
}