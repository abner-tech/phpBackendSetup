<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the User class
$image = new image(db: $dbconn);

// Handle GET request to retrieve all images
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['animal_id'])) {

   
    exit;
}

// Handle GET request to retrieve all images for a specific animal
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['animal_id'])) {
    $animal_id = (int) $_GET['animal_id'] ? $_GET['animal_id'] : 0;
    $result = $image->getImagesByAnimalID($animal_id);

    if (!$result) {
        http_response_code(response_code: 404);
        echo json_encode(value: ['message' => 'requested resource not found']);
        exit;
    }

    $image_data = pg_fetch_all(result: $result);
    http_response_code(response_code: 200);
    echo json_encode(value: $image_data);

    exit;
}

// Handle POST request to add a new image
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true); 

    //handle insert of new image
    
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
