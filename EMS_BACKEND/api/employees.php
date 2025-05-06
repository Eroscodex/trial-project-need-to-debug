<?php
require_once __DIR__ . '/../db.php';

// Validate user token
$user = validateToken();

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if it's a request for a specific employee
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $employee = $stmt->fetch();
        
        if (!$employee) {
            echo json_encode(['success' => false, 'message' => 'Employee not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'employee' => $employee]);
        exit;
    }
    
    // Check if stats are requested
    if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees");
        $totalCount = $stmt->fetch()['count'];
        
        // Get recent hires (last 30 days)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees WHERE hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $recentHires = $stmt->fetch()['count'];
        
        // Get department distribution
        $stmt = $pdo->query("SELECT department, COUNT(*) as count FROM employees GROUP BY department ORDER BY count DESC");
        $departmentCounts = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'totalCount' => $totalCount,
            'recentHires' => $recentHires,
            'departmentCounts' => $departmentCounts
        ]);
        exit;
    }
    
    // Default: list employees with pagination and search
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
    
    // Build the query
    $whereClause = '';
    $params = [];
    
    if ($search) {
        $whereClause = " WHERE name LIKE ? OR email LIKE ? OR department LIKE ? OR position LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as count FROM employees" . $whereClause;
    $stmt = $pdo->prepare($countQuery);
    
    if ($search) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $totalCount = $stmt->fetch()['count'];
    $totalPages = ceil($totalCount / $limit);
    
    // Get employees
    $query = "SELECT * FROM employees" . $whereClause . " ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($query);
    
    if ($search) {
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
    } else {
        $stmt->execute([$limit, $offset]);
    }
    
    $employees = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'totalCount' => $totalCount,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
    exit;
}

// Handle other HTTP methods
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>