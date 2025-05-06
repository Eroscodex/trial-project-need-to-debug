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
$profile_image = $employee['profile_image']; // Keep existing image by default
 
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
    
    // Check file size (max 5MB)
    if ($_FILES['profile_image']['size'] > 5000000) {
        echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB']);
        exit;
    }
    
    // Attempt to upload the file
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
        // Delete old image if it exists and is not the default image
        if ($employee['profile_image'] && $employee['profile_image'] !== 'default.png' && file_exists($uploadDir . $employee['profile_image'])) {
            unlink($uploadDir . $employee['profile_image']);
        }
        
        $profile_image = $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Check if the email already exists for another employee
$stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
$stmt->execute([$email, $id]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already in use by another employee']);
    exit;
}

// Validate date format (YYYY-MM-DD)
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $hire_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Update employee record
    $sql = "UPDATE employees SET 
            name = ?, 
            email = ?, 
            phone = ?, 
            department = ?, 
            position = ?, 
            hire_date = ?, 
            salary = ?, 
            address = ?, 
            profile_image = ?,
            updated_at = NOW()
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $name, 
        $email, 
        $phone, 
        $department, 
        $position, 
        $hire_date, 
        $salary, 
        $address, 
        $profile_image, 
        $id
    ]);
    
    if ($result) {
        // Commit transaction
        $pdo->commit();
        
        // Log the action
        logActivity($user['id'], 'Updated employee record: ' . $name . ' (ID: ' . $id . ')');
        
        // Return success response with updated employee data
        echo json_encode([
            'success' => true, 
            'message' => 'Employee updated successfully',
            'employee' => [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'department' => $department,
                'position' => $position,
                'hire_date' => $hire_date,
                'salary' => $salary,
                'address' => $address,
                'profile_image' => $profile_image
            ]
        ]);
    } else {
        // Rollback on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to update employee']);
    }
} catch (PDOException $e) {
    // Rollback on exception
    $pdo->rollBack();
    
    // Log error
    error_log('Database error: ' . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>