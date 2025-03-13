<?php

use PgSql\Result;

require_once '../middleware/auth/token.php';

class Location
{

    //constructor with db connection
    private $conn;
    public function __construct($db)
    {
        $this->conn = $db;

    }

    //gettting user from database
    public function getLocations(): bool|Result
    {
        $query = 'SELECT id, farm_name FROM location';
        pg_prepare($this->conn, "get_locations", query: $query);
        $stmt = pg_execute($this->conn, "get_locations", params: []);
        return $stmt;
    }


    public function createLocation($farm_name, $location): bool|string|null
    {
        $query = '
            INSERT INTO location
            (farm_name, location)
            VALUES ($1, $2)
            RETURNING id';
        $result = pg_query_params(connection: $this->conn, query: $query, params: [$farm_name, $location]);

        if ($result) {
            return pg_fetch_result(result: $result, row: 0, field: 'id');
        } else {
            return false;
        }
    }

}

?>