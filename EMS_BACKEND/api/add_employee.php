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

// Validate required fields
if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['department']) || 
    empty($_POST['position']) || empty($_POST['hire_date'])) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Sanitize inputs
$name = sanitize_input($_POST['name']);
$email = sanitize_input($_POST['email']);
$phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : null;
$department = sanitize_input($_POST['department']);
$position = sanitize_input($_POST['position']);
$hire_date = sanitize_input($_POST['hire_date']);
$salary = isset($_POST['salary']) ? sanitize_input($_POST['salary']) : null;
$address = isset($_POST['address']) ? sanitize_input($_POST['address']) : null;

// Handle profile image upload
$profile_image = null;

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . basename($_FILES['profile_image']['name']);
    $uploadFile = $uploadDir . $filename;
    
    // Check if it's an image
    $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($imageFileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed']);
        exit;
    }
    
    // Upload the file
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
        $profile_image = $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert employee record
    $stmt = $pdo->prepare("INSERT INTO employees (name, email, phone, department, position, hire_date, salary, address, profile_image) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $department, $position, $hire_date, $salary, $address, $profile_image]);
    
    $employeeId = $pdo->lastInsertId();
    
    // Log activity
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_name, action, employee_id, employee_name) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user['id'], $user['name'], 'added a new employee', $employeeId, $name]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Employee added successfully', 'employee_id' => $employeeId]);
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>