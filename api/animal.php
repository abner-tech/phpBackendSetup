<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the User class
$animal = new animal(db: $dbconn);

// Handle GET request to retrieve all or one animal
if ($_SERVER['REQUEST_METHOD'] === 'GET') {


    if (isset($_GET['search_id'])) { //fwetching for a list to select
        $search = $_GET['search_id'];
        $sex = $_GET['sex'];
        $result = $animal->search($search, $sex);

        if ($result == false) {
            http_response_code(404);
            echo json_encode(value: ['message' => 'requested resource not found']);
            exit;
        }

        $animal_data = pg_fetch_all($result);
        http_response_code(200);
        echo json_encode($animal_data);

    } else if (isset($_GET['animal_id'])) { //single animal fetched
        $animal_id = (int) $_GET['animal_id'] ? $_GET['animal_id'] : 0;
        $result = $animal->getAnimalByID($animal_id);

        if (!$result) {
            http_response_code(404);
            echo json_encode(value: ['message' => 'requested resource not found']);
            exit;
        }

        $animal_data = pg_fetch_assoc($result);
        http_response_code(200);
        echo json_encode($animal_data);


    } else {
        //all animals fetched
        $sortField = isset($_GET['sortedField']) ? $_GET['sortedField'] : '';
        $search = isset($_GET['filteredTerm']) ? $_GET['filteredTerm'] : '';
        $ORDER_BY = isset($_GET['order']) ? $_GET['order'] : '';

        //sanitize data
        $sanitizeClass = new Sanitize();
        $sortField = $sanitizeClass->sanitizeStringOrNull($sortField);
        $search = $sanitizeClass->sanitizeStringOrNull($search);
        $ORDER_BY = $sanitizeClass->sanitizeStringOrNull($ORDER_BY);

        // Declare a variable to hold the result
        $get_animal_query = $animal->getanimals($sortField, $search, $ORDER_BY);

        if ($get_animal_query) {
            // Fetch data: All animals => `pg_fetch_all()`
            $animal_data = pg_fetch_all(result: $get_animal_query);

            if ($animal_data) {
                http_response_code(response_code: 200);
                echo json_encode(value: ['animals' => $animal_data]);
                exit;
            } else {
                http_response_code(response_code: 404);
                echo json_encode(value: ['message' => 'requested resource not found']);
                exit;
            }
        } else {
            http_response_code(response_code: 500);
            echo json_encode(value: ['message' => 'Server encountered an error and could not complete your request']);
        }

    }
    exit;
}

// Handle POST request to add a new animal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

    //verifying required fields are given before db insert
    if (!empty($data['added_by_id']) && !empty($data['color_id']) && !empty($data['location'])) {

        //prepare/ sanitize info to insert data

        $sanitizeClass = new Sanitize();
        $blpa_number = $sanitizeClass->sanitizeIntegerOrNull($data['blpa_number']);
        $color_id = $sanitizeClass->sanitizeIntegerOrNull($data['color_id']);
        $sire_id = $sanitizeClass->sanitizeIntegerOrNull($data['sire_id']);
        $dam_id = $sanitizeClass->sanitizeIntegerOrNull($data['dam_id']);
        $dob = $sanitizeClass->sanitizeDateOrNull($data['dob']);
        $gender = $sanitizeClass->sanitizeStringOrNull($data['gender']);
        $added_by_id = $sanitizeClass->sanitizeIntegerOrNull($data['added_by_id']);

        $location = $sanitizeClass->sanitizeStringOrNull($data['location']);
        $weight = $sanitizeClass->sanitizeIntegerOrNull($data['weight']);
        // $weight_memo = $sanitizeClass->sanitizeStringOrNull($data['weight_memo']);
        // $weight_memo = $sanitizeClass->sanitizeStringOrNull($data['weight_memo']);

        $result = $animal->addAnimal(
            $blpa_number,
            $color_id,
            $sire_id,
            $dam_id,
            $dob,
            $gender,
            $added_by_id,
            $data['image'], //image is sanitized in image class
            $location,
            $weight
        );
        if (is_string($result)) {
            http_response_code(response_code: 201);
            echo json_encode(value: ['message' => 'successfully added animal with id: ' . $result]);
        } else {
            http_response_code(response_code: 500);
            echo json_encode([
                'status_message' => 'Server encountered an error and could not process your request',
                "error" => $result['error']
            ]);
        }
    } else {
        http_response_code(response_code: 400);
        echo json_encode(value: ['error' => 'Invalid input']);
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