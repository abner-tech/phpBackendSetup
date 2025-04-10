<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the User class
$image = new image(db: $dbconn);
$sanitizeClass = new Sanitize();

// Handle GET request to retrieve all images
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['animal_id'])) {


    exit;
}

// Handle GET request to retrieve all images for a specific animal
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['animal_id'])) {
    $animal_id = (int) $_GET['animal_id'] ? $_GET['animal_id'] : 0;
    $animal_id = $sanitizeClass->sanitizeIntegerOrNull($animal_id);
    $result = $image->getImagesByAnimalID($animal_id);

    if (!$result) {
        http_response_code(response_code: 404);
        echo json_encode(value: ['message' => 'requested resource not found']);
        exit;
    }

    $image_data = pg_fetch_all(result: $result);
    http_response_code(response_code: 200);
    echo json_encode(["data" => $image_data]);

    exit;
}

// Handle POST request to add a new image
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //fetch JSON info
    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);
    if (
        isset($data['image_data']) &&
        (isset($data['animal_id']))
    ) {
        // Get the JSON input values
        $animal_id = (int) isset($data['animal_id']) ?
            $sanitizeClass->sanitizeIntegerOrNull((int) $data['animal_id']) : null;
        $weight_id = (int) isset($data['weight_id']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['weight_id']) : null;
        $location_move_id = (int) isset($data['location_move_id']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['move_id']) : null;

        $image_data = $sanitizeClass->sanitizeStringOrNull($data['image_data']);

        // handle insert of new image
        $result = $image->InsertImage($animal_id, $image_data);
        if (is_int($result)) {
            http_response_code(201);
            echo (json_encode(["message" => "successfully added image with id " . $result]));
        } else {
            http_response_code(500);
            echo (json_encode([
                "status_message" => "server encountered an error and could not complete the request",
                "error" => $result
            ]));
        }
    } else {
        http_response_code(500);
        echo (json_encode(["error" => "invalid request"]));
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