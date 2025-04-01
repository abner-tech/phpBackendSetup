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

    public function getAnimalMovementHistryByID($animal_id)
    {
        $query = '
        WITH RECURSIVE location_history AS (
                -- Base case: Get the latest movement for the given animal
                SELECT 
                lm.id, 
                lm.animal_id, 
                lm.old_location_move_id, 
                l.farm_name, 
                l.location AS address, 
                CONCAT(u.firstname, \' \', u.lastname) AS recorded_by, 
                lm.created_at AS recorded_date
            FROM location_move AS lm
            INNER JOIN location AS l ON l.id = lm.new_farm_id
            INNER JOIN users AS u ON u.id = lm.added_by_id
            WHERE lm.animal_id = $1

            UNION ALL

            -- Recursive case: Get previous movements
            SELECT 
                prev_lm.id, 
                prev_lm.animal_id, 
                prev_lm.old_location_move_id, 
                l.farm_name, 
                l.location AS address, 
                CONCAT(u.firstname, \' \', u.lastname) AS recorded_by, 
                prev_lm.created_at AS recorded_date
            FROM location_move AS prev_lm
            INNER JOIN location_history AS lh ON prev_lm.id = lh.old_location_move_id
            INNER JOIN location AS l ON l.id = prev_lm.new_farm_id
            INNER JOIN users AS u ON u.id = prev_lm.added_by_id
            )
        SELECT 
            DISTINCT sq.id, 
            sq.animal_id, 
            sq.old_location_move_id, 
            sq.farm_name, sq.address, 
            sq.recorded_by, 
            sq.recorded_date , 
            encode (i.image_data, \'escape\') AS image_data 
            -- i.id AS image_id --tesing to use already sent image commnet: tried 1: failed
        FROM (SELECT * FROM location_history) AS sq
        LEFT JOIN image AS i ON sq.id = i.location_move_id
        ORDER BY recorded_date DESC
        ';

        return( pg_query_params(connection: $this->conn, query: $query, params: [$animal_id]));

    }

}

?>