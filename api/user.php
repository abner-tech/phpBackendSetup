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
    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

    //handle register of user
    if (!empty($data['firstname']) && !empty($data['lastname']) && !empty($data['email']) && !empty($data['password'])) {
        $result = $user->createUser(firstname: $data['firstname'], lastname: $data['lastname'], email: $data['email'], password: $data['password']);
        if ($result) {
            http_response_code(response_code: 201);
            echo json_encode(value: ['message' => 'User created successfully', 'user_ID' => $result]);
        } else {
            http_response_code(response_code: 500);
            echo json_encode(value: ['message' => 'server encountered an error and could not complete your request']);
        }
    } else if( empty($data['firstname']) && empty($data['lastname']) && !empty(['email']) &&!empty(['password'])) {
        //handle user login
        $result = $user->userLogin(email: $data['email'], password: $data['password']);
        if (!$result) {
            http_response_code(404);
            echo json_encode(value: ['message' => 'the requested resoure could not be found']);
            exit;
        }
        //$user = pg_fetch_assoc($result);
        echo json_encode(value: ['data'=> $result]);

    } else {
        http_response_code(response_code: 400);
        echo json_encode(value: ['message' => 'Invalid input']);
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
