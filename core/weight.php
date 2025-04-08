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

    //gettting user from database
    public function getWeight()
    {
        // $query = 'SELECT id, farm_name FROM location';
        // pg_prepare($this->conn, "get_locations", query: $query);
        // $stmt = pg_execute($this->conn, "get_locations", params: []);
        // return $stmt;
    }


    public function createWeight($animal_id, $weight, $memo)
    {
        $query = '
            INSERT INTO weight_log
            (animal_id, weight, memo)
            VALUES ($1, $2, $3)
            RETURNING id';
        $result = pg_query_params(connection: $this->conn, query: $query, params: [$animal_id, $weight, $memo]);

        if ($result) {
            $id = (int) pg_fetch_result(result: $result, row: 0, field: 'id');
            return $id;
        } else {
            return false;
        }
    }


    

}

?>