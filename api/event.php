<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the User class
$sanitizeClass = new Sanitize();
$eventClass = new Event($dbconn);

// Handle GET request to retrieve users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // // Declare a variable to hold the result
    // $get_color_query =$color->getColors();

    // if ($get_color_query) {
    //     // Fetch data: All colors => `pg_fetch_all()`
    //     $color_data =pg_fetch_all(result: $get_color_query);

    //     if ($color_data) {
    //         http_response_code(response_code: 200);
    //         echo json_encode(value: ['colors' => $color_data]);
    //     } else {
    //         http_response_code(response_code: 404);
    //         echo json_encode(value: ['message' => 'requested resource not found']);
    //     }
    // } else {
    //     http_response_code(response_code: 500);
    //     echo json_encode(value: ['message' => 'Server encountered an error and could not complete your request']);
    // }
    // exit;
}

// Handle POST request to add a new color
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

    $description = $data['description'] ?
        $sanitizeClass->sanitizeStringOrNull($data['description']) : null;
    $event_name = $data['event_name'] ?
        $sanitizeClass->sanitizeStringOrNull($data['event_name']) : null;
    //handle register of user
    if ($event_name && $description) {


        $result = $eventClass->addEvent($event_name, $description);
        if (is_int($result)) {
            http_response_code(201);
            echo json_encode([
                'message' => 'New event movement saved with ID ' . $result
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'status_message' => 'server encountered an error and could not process your request',
                'error' => $result,
            ]);
            error_log("Server error: movement save failed. result: " . print_r($result, true));
        }
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