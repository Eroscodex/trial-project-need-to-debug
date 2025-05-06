<?php
require_once __DIR__ . '/../db.php';

// Validate user token
$user = validateToken();

// Check user permissions (admin only for viewing logs)
if (!isset($user['role']) || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

// Handle GET request to fetch activity logs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get page and limit from query parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;
    
    // Filter options
    $filters = [];
    $params = [];
    
    // Filter by user_id
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $filters[] = "user_id = ?";
        $params[] = (int)$_GET['user_id'];
    }
    
    // Filter by date range
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $filters[] = "created_at >= ?";
        $params[] = $_GET['start_date'] . ' 00:00:00';
    }
    
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $filters[] = "created_at <= ?";
        $params[] = $_GET['end_date'] . ' 23:59:59';
    }
    
    // Filter by action (search in action)
    if (isset($_GET['action']) && !empty($_GET['action'])) {
        $filters[] = "action LIKE ?";
        $params[] = '%' . $_GET['action'] . '%';
    }
    
    // Combine filters
    $where = '';
    if (!empty($filters)) {
        $where = "WHERE " . implode(" AND ", $filters);
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM activity_log $where";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // Get activity logs with pagination
    $sql = "SELECT al.*, u.username as username
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.id
            $where
            ORDER BY al.created_at DESC
            LIMIT $limit OFFSET $offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $total_pages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'pagination' => [
            'total' => (int)$total,
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'has_more' => $page < $total_pages
        ]
    ]);
    exit;
}

// Function to log activity (used by other scripts)
function logActivity($user_id, $action) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, ip_address, created_at) VALUES (?, ?, ?, NOW())");
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
    return $stmt->execute([$user_id, $action, $ip]);
}

// If not a GET request, return method not allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
?>