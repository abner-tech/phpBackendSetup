<?php
    $env_file_path = realpath(__DIR__."/.env");

    $db_user = "testingdb";
    $db_pass = "testingdb";
    $db_name = "testingdb";
    $db_host = "localhost";
    $db_port = 32768;

    $appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $dbconn = pg_connect("host=".$db_host." port=".$db_port." user=".$db_user." password=".$db_pass." dbname=".$db_name);

    $status = pg_connection_status($dbconn);
    if ($status == PGSQL_CONNECTION_OK) {
        echo"Connected successfully";
    } else {
        echo "connectin failed";
    }

    
?>