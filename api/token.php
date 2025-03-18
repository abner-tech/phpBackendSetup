<?php

// Initialize API
include_once '../core/initialize.php';

// Instantiate the User class
$Token = new AuthToken(db: $dbconn);
$UserClass = new User(db: $dbconn);


// Handle POST request validate token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the JSON input
    $data = json_decode(json: file_get_contents(filename: "php://input"), associative: true);

    //handle register of user
    if (!empty($data['token'])) {
        //prepare to ivalidate

        //sanitize input
        $providedToken = htmlspecialchars(trim($data['token']));

        $isValid = $Token->authenticateToken($providedToken);

        if ($isValid !== false && $isValid !== null) {
            http_response_code(response_code: 200);
            //fetch user info and new token
            $user_ID = $isValid;

            $userInfo = $UserClass->getUserByID($user_ID);
            if ($userInfo) {
                $userInfo = pg_fetch_assoc($userInfo);
                echo json_encode(['data' => $userInfo]);
            } else {
                http_response_code(404);
                echo json_encode(array('error'=> 'the server could not find the requested resource'));
            }
        }
    } else {
        http_response_code(response_code: 401);
        echo json_encode(value: ['message' => 'unauthorized']);
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