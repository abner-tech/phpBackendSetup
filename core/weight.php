<?php

use PgSql\Result;

class Weight
{

    //constructor with db connection
    private $conn;
    public function __construct($db)
    {
        $this->conn = $db;

    }

    //gettting weight history for animal using animal ID from database
    public function getWeightHistoryByAnimalID($animal_id)
    {
        // $query = 'SELECT id, farm_name FROM location';
        // pg_prepare($this->conn, "get_locations", query: $query);
        // $stmt = pg_execute($this->conn, "get_locations", params: []);
        // return $stmt;
    }


    public function createWeight($animal_id, $weight, $memo, $userID)
    {
        $query = '
            INSERT INTO weight_log
            (animal_id, weight, memo, added_by_id)
            VALUES ($1, $2, $3, $4)
            RETURNING id';
        $result = pg_query_params(connection: $this->conn, query: $query, params: [$animal_id, $weight, $memo, $userID]);

        if ($result) {
            $id = (int) pg_fetch_result(result: $result, row: 0, field: 'id');
            return $id;
        } else {
            return false;
        }
    }

    public function InsertAnimalLocation($animal_id, $new_farm_id, $old_location_move_id, $added_by_id)
    {
        $query = '
        INSERT INTO location_move
        (animal_id, new_farm_id, old_location_move_id, added_by_id)
        VALUES ( $1, $2, $3, $4)
        RETURNING id
        ';

        $location_result_id = pg_query_params($this->conn, $query, [
            $animal_id,
            $new_farm_id,
            $old_location_move_id,
            $added_by_id
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