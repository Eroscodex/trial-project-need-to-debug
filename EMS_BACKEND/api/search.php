<?php
require_once __DIR__ . '/../db.php';

// Validate user token
$user = validateToken();

// Handle GET requests only
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check for search query
if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode(['success' => false, 'message' => 'Search query is required']);
    exit;
}

$query = sanitize_input($_GET['q']);

// Get page and limit from query parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

// Additional filters
$filters = [];
$params = [];

// Base search parameters
$params[] = '%' . $query . '%';
$params[] = '%' . $query . '%';
$params[] = '%' . $query . '%';
$params[] = '%' . $query . '%';

// Filter by department
if (isset($_GET['department']) && !empty($_GET['department'])) {
    $filters[] = "department = ?";
    $params[] = $_GET['department'];
}

// Filter by position
if (isset($_GET['position']) && !empty($_GET['position'])) {
    $filters[] = "position = ?";
    $params[] = $_GET['position'];
}

// Filter by hire date range
if (isset($_GET['hire_from']) && !empty($_GET['hire_from'])) {
    $filters[] = "hire_date >= ?";
    $params[] = $_GET['hire_from'];
}

if (isset($_GET['hire_to']) && !empty($_GET['hire_to'])) {
    $filters[] = "hire_date <= ?";
    $params[] = $_GET['hire_to'];
}

// Combine filters
$whereFilters = '';
if (!empty($filters)) {
    $whereFilters = "AND " . implode(" AND ", $filters);
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM employees 
             WHERE (name LIKE ? OR email LIKE ? OR department LIKE ? OR position LIKE ?) 
             $whereFilters";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();

// Search employees
$sql = "SELECT id, name, email, phone, department, position, hire_date, 
        salary, address, profile_image, created_at, updated_at
        FROM employees
        WHERE (name LIKE ? OR email LIKE ? OR department LIKE ? OR position LIKE ?) 
        $whereFilters
        ORDER BY name ASC
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pagination info
$total_pages = ceil($total / $limit);

// Process results
foreach ($employees as &$employee) {
    // Add profile image URL
    if ($employee['profile_image']) {
        $employee['profile_image_url'] = '/backend/uploads/' . $employee['profile_image'];
    } else {
        $employee['profile_image_url'] = '/backend/uploads/default.png';
    }
    
    // Format dates
    $employee['hire_date_formatted'] = date('F j, Y', strtotime($employee['hire_date']));
    $employee['created_at_formatted'] = date('F j, Y', strtotime($employee['created_at']));
    $employee['updated_at_formatted'] = $employee['updated_at'] ? date('F j, Y', strtotime($employee['updated_at'])) : null;
}

// Log the search activity
logActivity($user['id'], 'Searched for employees: ' . $query);

echo json_encode([
    'success' => true,
    'employees' => $employees,
    'pagination' => [
        'total' => (int)$total,
        'per_page' => $limit,
        'current_page' => $page,
        'total_pages' => $total_pages,
        'has_more' => $page < $total_pages
    ],
    'query' => $query
]);
?>