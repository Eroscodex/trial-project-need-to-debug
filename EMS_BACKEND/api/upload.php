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

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error = isset($_FILES['file']) ? upload_error_message($_FILES['file']['error']) : 'No file uploaded';
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

$uploadDir = '../uploads/';

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$filename = uniqid() . '_' . basename($_FILES['file']['name']);
$uploadFile = $uploadDir . $filename;

// Check if it's an allowed file type
$fileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
$allowedTypes = isset($_POST['allowedTypes']) ? explode(',', $_POST['allowedTypes']) : ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed']);
    exit;
}

// Check file size (max 10MB by default)
$maxSize = isset($_POST['maxSize']) ? (int)$_POST['maxSize'] : 10000000; // 10MB default
if ($_FILES['file']['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is ' . ($maxSize / 1000000) . 'MB']);
    exit;
}

// Attempt to upload the file
if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
    // Log the action
    logActivity($user['id'], 'Uploaded file: ' . $filename);
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'File uploaded successfully',
        'file' => [
            'name' => $filename,
            'original_name' => $_FILES['file']['name'],
            'path' => $uploadFile,
            'url' => '/uploads/' . $filename,
            'type' => $fileType,
            'size' => $_FILES['file']['size']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}

/**
 * Get error message for upload error code
 */
function upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
}
?>