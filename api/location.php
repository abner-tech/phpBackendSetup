<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the Location class
$location = new Location(db: $dbconn);
$sanitizeClass = new Sanitize();
$imageClass = new Image($dbconn);
$sanitizeClass = new Sanitize();
$imageClass = new Image($dbconn);

// Handle GET request to retrieve Locations
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    //animal history requested
    if (isset($_GET['animal_id']) && isset($_GET['history'])) {
        $animal_id = $sanitizeClass->sanitizeIntegerOrNull($_GET['animal_id']);
        $history = $sanitizeClass->sanitizeStringOrNull($_GET['history']);

        if ($animal_id > 0 && $history === "true") {
            $result = $location->getAnimalMovementHistryByID_NO_transactinal_sql($animal_id);
            $movement_data = pg_fetch_all($result);
            if ($movement_data) {
                http_response_code(response_code: 200);
                echo json_encode(["movements" => $movement_data]);
            } else {
                http_response_code(response_code: 404);
            }
        }
    } else {
        //just locations requested


        // Declare a variable to hold the result
        $get_location_query = $location->getLocations();

        if ($get_location_query) {
            // Fetch data: All locations => `pg_fetch_all()`
            $location_data = pg_fetch_all(result: $get_location_query);

            if ($location_data) {
                http_response_code(response_code: 200);
                echo json_encode(value: ['locations' => $location_data]);
            } else {
                http_response_code(response_code: 404);
                echo json_encode(value: ['message' => 'requested resource not found']);
            }
        } else {
            http_response_code(response_code: 500);
            echo json_encode(value: ['message' => 'Server encountered an error and could not complete your request']);
        }
    }
    exit;
}

// Handle POST request to add a new location
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_GET['new_animal_history']) && $_GET['new_animal_history'] === 'true') {
        $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

        //data validation

        $animal_id = isset($data['animal_id']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['animal_id']) : null;
        $new_location_name = isset($data['new_location_name']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['location_id']) : null;
        $old_movement_name = isset($data['old_location_name']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['old_movement_id']) : null;
        $recorded_by_id = isset($data['added_by_id']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['added_by_id']) : null;
        $image_data = isset($data['image_data']) ?
            $data['image_data'] : null;
        $weight = isset($data['weight']) ?
        $sanitizeClass->sanitizeIntegerOrNull($data['weight']) : null;


        $result;
        $image_result;

        if ($animal_id && $new_location_name && $old_movement_name && $recorded_by_id) {
            $result = $location->InsertAnimalLocation_transactional_sql(
                $animal_id,
                $new_location_name,
                $location_name,
                $recorded_by_id, $weight, $image
            );

        }

        if ($result && $image_data !== null) {
            $image_result = $imageClass->InsertImage_NO_transactional_sql(
                $animal_id,
                $image_data
            );
        }

        if (is_int($result) && $image_data !== null && is_int($result)) {
            http_response_code(201);
            echo json_encode([
                'message' => 'new animal movement saved',
                'info' => [
                    'movement_id' => $result,
                    'image_id' => $image_result
                ]
            ]);
        } else if (is_int($result) && $image_data === null) {
            http_response_code(201);
            echo json_encode([
                'message' => 'new animal movement saved',
                'info' => [
                    'movement_id' => $result,
                ]
            ]);
        } else if (is_int($result) && $image_data && is_string($image_result)) {
            http_response_code(207); // Multi-Status
            echo json_encode([
                'message' => 'New animal movement saved, but image upload failed.',
                'errors' => [
                    'image' => $image_result
                ],
                'info' => [
                    'movement_id' => $result,
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'message' => 'server encountered an error and could not process your request'
            ]);
            // Log the error for debugging
            error_log("Server error: movement save failed. result: " . print_r($result, true) . ", image_result: " . print_r($image_result, true));
        }
    } else {
        // Get the JSON input
        $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

        //handle register of location
        if (!empty($data['farm_name'])) {
            //prepare to insert data
            $loc = '';
            if (!empty($data['location'])) {
                $loc = $data['location'];
            }

            $result = $location->createLocation($data['farm_name'], $loc);
            if ($result) {
                http_response_code(response_code: 201);
                echo json_encode(value: ['message' => 'location registered successfully', 'location_id' => $result]);
            }
        } else {
            http_response_code(response_code: 400);
            echo json_encode(value: ['message' => 'Invalid input']);
        }
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