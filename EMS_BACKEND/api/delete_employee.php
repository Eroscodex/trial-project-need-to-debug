<?php
require_once __DIR__ . '/../db.php';

// Validate user token
$user = validateToken();

// Handle POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate employee ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit;
}

$id = (int) $_POST['id'];

// Check if employee exists
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete employee record
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        // Delete profile image if it exists and is not the default
        if ($employee['profile_image'] && $employee['profile_image'] !== 'default.png' && file_exists('../uploads/' . $employee['profile_image'])) {
            unlink('../uploads/' . $employee['profile_image']);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Log the action
        logActivity($user['id'], 'Deleted employee: ' . $employee['name'] . ' (ID: ' . $id . ')');
        
        echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
    } else {
        // Rollback on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to delete employee']);
    }
} catch (PDOException $e) {
    // Rollback on exception
    $pdo->rollBack();
    
    // Log error
    error_log('Database error: ' . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>