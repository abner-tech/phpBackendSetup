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
        $query = '
            WITH RECURSIVE recursive_weight AS (
                SELECT 
                wl.id, 
                wl.animal_id,
				wl.weight,
                wl.memo, 
                CONCAT( u.firstname, \' \', u.lastname ) AS recorded_by, 
                wl.created_timestamp
            FROM weight_log AS wl
            INNER JOIN users AS u ON u.id = wl.added_by_id
            WHERE wl.animal_id = $1

            UNION ALL

            -- Recursive case: Get previous movements
            SELECT 
                pwl.id, 
                pwl.animal_id,
				pwl.weight,
                pwl.memo,
                CONCAT(u.firstname, \' \', u.lastname) AS recorded_by, 
                pwl.created_timestamp
            FROM weight_log AS pwl
            INNER JOIN users AS u ON u.id = pwl.added_by_id
            )
        SELECT 
            DISTINCT rwl.id, 
            rwl.animal_id,
			rwl.weight,
            rwl.memo, 
            rwl.recorded_by, 
            rwl.created_timestamp, 
            encode (i.image_data, \'escape\') AS image_data 
            -- i.id AS image_id 
        FROM (SELECT * FROM recursive_weight) AS rwl
        LEFT JOIN image AS i ON rwl.id = i.weight_id
		ORDER BY created_timestamp DESC;
        ';

        return( pg_query_params(connection: $this->conn, query: $query, params: [$animal_id]));
    }


    //add new weight log for an animal
    public function createWeight($animal_id, $weight, $memo, $userID): bool|string|null
    {
        $query = '
            INSERT INTO weight_log
            (animal_id, weight, memo, added_by_id)
            VALUES ($1, $2, $3, $4)
            RETURNING id';
        $result = pg_query_params(connection: $this->conn, query: $query, params: [$animal_id, $weight, $memo, $userID]);

        if ($result) {
            return pg_fetch_result(result: $result, row: 0, field: 'id');
        } else {
            return false;
        }
    }


}

?>