<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the classes used
$location = new Location(db: $dbconn);
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
    } else if (isset($_GET['summary'])) {  //farms summary requested
        $summary = $sanitizeClass->sanitizeStringOrNull($_GET['summary']);
        if ($summary) {
            $result = $location->locationsSummary();
            if ($result && is_array(pg_fetch_all($result))) {
                $movement_data = pg_fetch_all($result);
                http_response_code(response_code: 200);
                echo json_encode($movement_data);
            } else {
                http_response_code(response_code: 404);
                echo json_encode(value: [
                    'status_message' => 'server encountered an error and could not process your request',
                    "error" => $summary
                ]);
            }
        }
    } else if (isset($_GET['farm_id'])) { //single farm info requested
        $farm_id = $_GET['farm_id'] ?
            $sanitizeClass->sanitizeIntegerOrNull($_GET['farm_id']) : null;

        $result = $location->location_record($farm_id);

        if ($result) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(response_code: 400);
            echo json_encode(value: ['message' => 'Invalid input']);
        }

    } else { //just locations requested


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

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    $data = json_decode(file_get_contents("php://input"), true);

    //sanitize and validate input
    $farm_id = $data['id'] ?
        $sanitizeClass->sanitizeIntegerOrNull($data['id']) : null;
    $city = $data['city'] ?
        $sanitizeClass->sanitizeStringOrNull($data['city']) : null;
    $district = $data['district'] ?
        $sanitizeClass->sanitizeStringOrNull($data['district']) : null;
    // $farm_name = $data['farm_name'] ?
    //     $sanitizeClass->sanitizeStringOrNull($data['farm_name']) : null;
    $notes = $data['notes'] ?
        $sanitizeClass->sanitizeStringOrNull($data['notes']) : null;
    $street_address = $data['street_address'] ?
        $sanitizeClass->sanitizeStringOrNull($data['street_address']) : null;
    //handle register of location
    if ($city && $district && $farm_id && $city) {
        //prepare to insert data
        $result = $location->updateLocation(
            $farm_id,
            $city,
            $district,
            $street_address,
            $notes
        );
        if ($result) {
            http_response_code(response_code: 201);
            echo json_encode(value: ['message' => 'location registered successfully', 'location_id' => $result]);
        }
    } else {
        http_response_code(response_code: 400);
        echo json_encode(value: ['message' => 'Invalid input']);
    }



    exit;
}

// Handle POST request to add a new location
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_GET['new_animal_history']) && $_GET['new_animal_history'] === 'true') { // animal location update or insert
        $data = json_decode(file_get_contents("php://input"), true);

        // Sanitize and validate input
        $animal_id = isset($data['animal_id']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['animal_id']) : null;
        $new_location_name = isset($data['new_location_name']) ?
            $sanitizeClass->sanitizeStringOrNull($data['new_location_name']) : null;
        $old_location_name = isset($data['old_location_name']) ?
            $sanitizeClass->sanitizeStringOrNull($data['old_location_name']) : null;
        $added_by_id = isset($data['added_by_id']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['added_by_id']) : null;
        $image = isset($data['image_data']) ?
            $data['image_data'] : null;
        $weight = isset($data['weight']) ?
            $sanitizeClass->sanitizeIntegerOrNull($data['weight']) : null;

        $result = null;

        if ($animal_id && $new_location_name && $old_location_name && $added_by_id) {
            $result = $location->insertAnimalLocation_transactional_sql(
                $animal_id,
                $new_location_name,
                $old_location_name,
                $added_by_id,
                $weight,
                $image
            );
        }

        if (is_int($result)) {
            http_response_code(201);
            echo json_encode([
                'message' => 'New animal movement saved with ID ' . $result
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'status_message' => 'server encountered an error and could not process your request',
                'error' => $result,
            ]);
            error_log("Server error: movement save failed. result: " . print_r($result, true));
        }
        // just a new farm locatio input
    } else { //farm location registration
        // Get the JSON input
        $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

        $city = $data['city'] ?
            $sanitizeClass->sanitizeStringOrNull($data['city']) : null;
        $district = $data['district'] ?
            $sanitizeClass->sanitizeStringOrNull($data['district']) : null;
        $farm_name = $data['farm_name'] ?
            $sanitizeClass->sanitizeStringOrNull($data['farm_name']) : null;
        $notes = $data['notes'] ?
            $sanitizeClass->sanitizeStringOrNull($data['notes']) : null;
        $street_address = $data['street_address'] ?
            $sanitizeClass->sanitizeStringOrNull($data['street_address']) : null;

        //handle register of location
        if ($city && $district && $farm_name && $city) {
            //prepare to insert data
            $result = $location->createLocation(
                $farm_name,
                $city,
                $district,
                $street_address,
                $notes
            );
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