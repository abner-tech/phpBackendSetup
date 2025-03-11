<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the Location class
$location = new Location(db: $dbconn);

// Handle GET request to retrieve Locations
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Declare a variable to hold the result
    $get_location_query =$location->getLocations();

    if ($get_location_query) {
        // Fetch data: All locations => `pg_fetch_all()`
        $location_data =pg_fetch_all(result: $get_location_query);

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
    exit;
}

// Handle POST request to add a new location
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true); 

    //handle register of location
    if (!empty($data['farm_name'])) {
      //prepare to insert data
      $loc = '';
        if(!empty($data['location'])) {
            $loc = $data['location'];
        }

        $result = $location->createLocation($data['farm_name'],$loc);
        if($result) {
            http_response_code(response_code: 201);
            echo json_encode(value: ['message'=> 'location registered successfully', 'location_id'=> $result]);
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
