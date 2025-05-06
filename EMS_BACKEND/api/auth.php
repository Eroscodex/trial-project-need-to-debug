<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection settings (in case you want to connect to a real database later)
$host = 'localhost';
$dbname = 'employee_management';
$username = 'root';
$password = '';

// Hardcoded admin credentials (for this example)
$validUsername = 'admin';
$validPassword = 'admin123';

// Get the raw POST data
$data = json_decode(file_get_contents("php://input"), true);

// Check if username and password are provided
if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required'
    ]);
    exit;
}

$inputUsername = $data['username'];
$inputPassword = $data['password'];

// Validate credentials
if ($inputUsername === $validUsername && $inputPassword === $validPassword) {
    // Create a simulated token (in a real app, use JWT or similar)
    $token = base64_encode(json_encode([
        'username' => $validUsername,
        'exp' => time() + (24 * 60 * 60) // Token expires in 24 hours
    ]));
    
    // Simulated user data
    $user = [
        'id' => 1,
        'username' => $validUsername,
        'name' => 'Administrator',
        'email' => 'admin@example.com',
        'role' => 'admin'
    ];
    
    // Successful login response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user
    ]);
} else {
    // Failed login response
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password'
    ]);
}
?>