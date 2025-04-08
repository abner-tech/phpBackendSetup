<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the needed classes
$sanitizeClass = new Sanitize();
$imageClass = new Image($dbconn);
$weightClass = new Weight($dbconn);

// Handle GET request to retrieve animal_weight
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    //animal history requested
    if (isset($_GET['animal_id']) && isset($_GET['history'])) {
        $animal_id = $sanitizeClass->sanitizeIntegerOrNull($_GET['animal_id']);
        $history = $sanitizeClass->sanitizeStringOrNull($_GET['history']);

        if ($animal_id > 0 && $history === "true") {
            $result = $weightClass->getWeightHistoryByAnimalID($animal_id);
            $weight_data = pg_fetch_all($result);
            if ($weight_data) {
                http_response_code(response_code: 200);
                echo json_encode(["data" => $weight_data]);
            } else {
                http_response_code(response_code: 404);
            }
        }
    }
    // else {
    //just locations requested


    // Declare a variable to hold the result
    // $get_location_query = $location->getLocations();

    // if ($get_location_query) {
    //     // Fetch data: All locations => `pg_fetch_all()`
    //     $location_data = pg_fetch_all(result: $get_location_query);

    //     if ($location_data) {
    //         http_response_code(response_code: 200);
    //         echo json_encode(value: ['locations' => $location_data]);
    //     } else {
    //         http_response_code(response_code: 404);
    //         echo json_encode(value: ['message' => 'requested resource not found']);
    //     }
    // } else {
    //     http_response_code(response_code: 500);
    //     echo json_encode(value: ['message' => 'Server encountered an error and could not complete your request']);
    // }
    // }
    exit;
}

// Handle POST request to add a new location
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['new_animal_history']) && $_GET['new_animal_history'] === 'true') {
        $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

        //data validation and sanitiatin
        $animal_id = isset($data['animal_id']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['animal_id']) : null;
        $new_weight = isset($data['weight']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['weight']) : null;
        $old_weight = isset($data['old_weight']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['old_weight']) : null;
        $recorded_by_id = isset($data['recorded_by_id']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['recorded_by_id']) : null;
        $memo = isset($data['memo']) ?
            $sanitizeClass->sanitizeStringOrNull($data['memo']) : null;
        $image_data = isset($data['image_data']) ?
            $data['image_data'] : null;


        $result;
        $image_result;

        //enter animal record
        if ($animal_id && $new_weight && $recorded_by_id) {
            $result = $weightClass->createWeight(
                $animal_id,
                $new_weight,
                $memo,
                $added_by_id
            );
        }

        if ($result && $image_data !== null && is_int($result)) {
            $image_result = $imageClass->InsertImage(
                $result,
                null,
                $animal_id,
                $image_data
            );
        }

        if (is_int($result) && $image_data !== null && is_int($image_result)) {
            http_response_code(201);
            echo json_encode([
                'message' => 'New animal weight saved with image.',
                'info' => [
                    'weight_id' => $result,
                    'image_id' => $image_result
                ]
            ]);
        } else if (is_int($result) && $image_data === null) {
            http_response_code(201);
            echo json_encode([
                'message' => 'New animal weight saved without image.',
                'info' => [
                    'weight_id' => $result,
                ]
            ]);
        } else if (is_int($result) && $image_data && is_string($image_result)) {
            http_response_code(207); // Multi-Status
            echo json_encode([
                'message' => 'New animal weight saved, but image upload failed.',
                'errors' => [
                    'image' => $image_result
                ],
                'info' => [
                    'weight_id' => $result,
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'message' => 'Server encountered an error and could not process your request.'
            ]);
            // Log the error for debugging
            error_log("Server error: weight save failed. result: " . print_r($result, true) . ", image_result: " . print_r($image_result, true));
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