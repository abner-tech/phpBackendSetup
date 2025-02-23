<?php
// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Initialize API
include_once '../core/initialize.php';

// Instantiate the User class
$user = new User($dbconn);

// Handle GET request to retrieve users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $get_user_query = $user->getUser();
    if ($get_user_query) {
        $user_data = pg_fetch_all($get_user_query);
        echo json_encode(array('data' => $user_data));
    } else {
        echo json_encode(array('message' => 'No users found'));
    }
    exit;
}

// Handle POST request to add a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!empty($data['firstname']) && !empty($data['lastname']) && !empty($data['email']) && !empty($data['password'])) {
        $result = $user->createUser($data['firstname'], $data['lastname'], $data['email'], $data['password']);
        if ($result) {
            echo json_encode(array('message' => $result));
        } else {
            echo json_encode(array('message' => 'Failed to create user'));
        }
    } else {
        echo json_encode(array('message' => 'Invalid input. All fields are required'));
    }
    exit;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// If method is not handled
http_response_code(405);
echo json_encode(array('message' => 'Method Not Allowed'));
?>
