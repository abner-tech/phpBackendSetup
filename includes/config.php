<?php
    $env_file = parse_ini_file('../\.env');

    $db_user = $env_file["username"];
    $db_pass = $env_file["password"];
    $db_name = $env_file["dbname"];
    $db_host = $env_file["host"];
    $db_port = $env_file["port"];

    $appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $dbconn = pg_connect("host=$db_host port=$db_port user=$db_user password=$db_pass dbname=$db_name");

    $status = pg_connection_status($dbconn);
    $response = [];

    if ($status != PGSQL_CONNECTION_OK) {
        $response['message'] = "connection Failed";
        $response['status'] = "error";
    }
?>