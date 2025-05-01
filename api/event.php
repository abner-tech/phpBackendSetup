<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the User class
$sanitizeClass = new Sanitize();
$eventClass = new Event($dbconn);

// Handle GET request to retrieve users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['event_id'])) { //single event request
        $event_id = $sanitizeClass->sanitizeIntegerOrNull($_GET['event_id']);
        $result = $eventClass->getEventByID($event_id);
        if ($result) {
            $result_data = pg_fetch_assoc($result);
            http_response_code(response_code: 200);
            echo json_encode($result_data);
        } else {
            http_response_code(response_code: 404);
            echo json_encode(value: [
                'status_message' => 'server encountered an error and could not process your request',
                "error" => $result
            ]);
        }
    } else if (isset($_GET['event_logs'])) { //single event request

        $result = $eventClass->getEventLogs();
        if ($result) {
            $result_data = pg_fetch_all($result);
            http_response_code(response_code: 200);
            echo json_encode($result_data);
        } else {
            http_response_code(response_code: 404);
            echo json_encode(value: [
                'status_message' => 'server encountered an error and could not process your request',
                "error" => $result
            ]);
        }

    } else { //all event  request

        $result = $eventClass->getEvents();
        if ($result && is_array(pg_fetch_all($result))) {
            $result_data = pg_fetch_all($result);
            http_response_code(response_code: 200);
            echo json_encode($result_data);
        } else {
            http_response_code(response_code: 404);
            echo json_encode(value: [
                'status_message' => 'server encountered an error and could not process your request',
                "error" => $result
            ]);
        }
    }


    exit;
}

// Handle POST request to add a new color
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

    if (isset($_Get['event_log'])) { //adding and event log for
        $event_id = $sanitizeClass->sanitizeIntegerOrNull($data['event_id']);
        $animal_id = $sanitizeClass->sanitizeIntegerOrNull($data['animal_id']);
        $status = $sanitizeClass->sanitizeStringOrNull($data['status']);
        $memo = $sanitizeClass->sanitizeStringOrNull($data['memo']);
        $weight_id = $sanitizeClass->sanitizeIntegerOrNull($data['weight_id']);

        //handle register of user
        if ($event_id && $animal_id && $status) {
            $result = $eventClass->addEventLog($event_id, $animal_id, $status, $memo, $weight_id);
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
    } else { // just adding an event
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
    }
    exit;
}

if($_SERVER["REQUEST_METHOD"] === "PUT") {
    // Get the JSON input
    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

    if (isset($_GET['event_id'])) { //adding and event log for
        $event_id = $sanitizeClass->sanitizeIntegerOrNull($data['id']);
        $event_name = $sanitizeClass->sanitizeStringOrNull($data['event_name']);
        $description = $sanitizeClass->sanitizeStringOrNull($data['description']);

        //handle register of user
        if ($event_id && $event_name && $description) {
            $result = $eventClass->updateEvent($event_id, $event_name, $description);
            if ($result) {
                http_response_code(200);
                echo json_encode([
                    'message' => "event with ID $event_id updated successfully"
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
    } else { // just adding an event
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