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
        $query = 'SELECT id, location_name AS farm_name FROM location';
        pg_prepare($this->conn, "get_locations", query: $query);
        $stmt = pg_execute($this->conn, "get_locations", params: []);
        return $stmt;
    }


    public function createLocation($farm_name, $location): bool|string|null
    {
        $query = '
            INSERT INTO location
            (location_name, location_address)
            VALUES ($1, $2)
            RETURNING id';
        $result = pg_query_params(connection: $this->conn, query: $query, params: [$farm_name, $location]);

        if ($result) {
            return (int) pg_fetch_result(result: $result, row: 0, field: 'id');
        } else {
            return false;
        }
    }

    public function InsertAnimalLocation_NO_transactional_sql($animal_id, $new_location_name, $old_location_move_name, $added_by_id) {
        $query = '
        INSERT INTO location_move
        (animal_id, new_location_name, old_location_name, added_by_id)
        VALUES ( $1, $2, $3, $4)
        RETURNING id;
        ';

        $location_result_id = pg_query_params($this->conn, $query, [
            $animal_id, $new_location_name, $old_location_move_name, $added_by_id
        ]);

        if ($location_result_id) {
            $id = (int) pg_fetch_result(result: $location_result_id, row: 0, field: 'id');
            return $id;
        } else {
           return 'failure to save animal location';
        }
    }
}

?>