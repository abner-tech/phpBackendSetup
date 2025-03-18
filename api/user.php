<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the User class
$user = new User(db: $dbconn);

// Handle GET request to retrieve users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Parse the URL to check if a user ID is provided
    $request_uri = explode(separator: '/', string: trim(string: $_SERVER['REQUEST_URI'], characters: '/'));
    $endpoint_index = array_search(needle: 'user.php', haystack: $request_uri);
    $user_id = isset($request_uri[$endpoint_index + 1]) ? (int) $request_uri[$endpoint_index + 1] : null;

    // Declare a variable to hold the result
    $get_user_query = $user_id ? $user->getUserByID(userID: $user_id) : $user->getUser();

    if ($get_user_query) {
        // Fetch data: Single user => `pg_fetch_assoc()`, All users => `pg_fetch_all()`
        $user_data = $user_id ? pg_fetch_assoc(result: $get_user_query) : pg_fetch_all(result: $get_user_query);

        if ($user_data) {
            http_response_code(response_code: 200);
            echo json_encode(value: ['data' => $user_data]);
        } else {
            http_response_code(response_code: 404);
            echo json_encode(value: ['message' => 'requested resource not found']);
        }
    } else {
        http_response_code(response_code: 500);
        echo json_encode(value: ['message' => 'Server encountered an error and could not complete your request']);
    }
    exit;
}

// Handle POST request to add a new user or login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data)) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid JSON input']);
        exit;
    }

    // Handle register of user
    if (!empty($data['firstname']) && !empty($data['lastname']) && !empty($data['email']) && !empty($data['password'])) {

        // Sanitize input
        $firstname = htmlspecialchars(trim($data['firstname']));
        $lastname = htmlspecialchars(trim($data['lastname']));
        $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($data['password']); // Password should be hashed, not just trimmed

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid email format']);
            exit;
        }

        // Validate other fields (add more validation as needed)
        if (empty($firstname) || empty($lastname) || empty($password)) {
            http_response_code(400);
            echo json_encode(['message' => 'Fields cannot be empty after sanitization']);
            exit;
        }

        // Create user
        $result = $user->createUser($firstname, $lastname, $email, $password);

        if ($result) {
            http_response_code(201);
            echo json_encode(['message' => 'User created successfully', 'user_ID' => $result]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Server encountered an error and could not complete your request']);
        }
    } else if (empty($data['firstname']) && empty($data['lastname']) && !empty($data['email']) && !empty($data['password'])) { // Corrected array access

        // Handle user login
        $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($data['password']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid email format']);
            exit;
        }

        $result = $user->userLogin($email, $password);

        if (!$result) {
            http_response_code(404);
            echo json_encode(['message' => 'The requested resource could not be found']);
            exit;
        }

        echo json_encode(['data' => $result]);

    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid input']);
    }
    exit;
}

// Handle preflight (CORS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(response_code: 200);
    exit;
}

// If method is not handled
http_response_code(response_code: 405);
echo json_encode(value: ['message' => 'Method Not Allowed']);
?>
