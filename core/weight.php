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
        SELECT 
	        wl.animal_id, 
	        wl.created_timestamp, 
	        wl.weight, CONCAT (u.firstname, \' \', u.lastname) AS recorded_by, 
	        encode (i.image_data, \'escape\') AS image_data, wl.memo 
        FROM weight_log AS wl
        LEFT JOIN image as i ON i.id = wl.image_id
        INNER JOIN users AS u ON u.id = wl.added_by
        WHERE wl.animal_id = $1
        ORDER BY wl.id DESC
        ';

        $stmt = pg_query_params($this->conn, $query, [$animal_id]);
        return $stmt;
    }


    // no transactional sql
    public function createWeight($animal_id, $weight, $memo, $userID, $image_id)
    {
        $query = '
            INSERT INTO weight_log
            (animal_id, weight, memo, added_by, image_id)
            VALUES ($1, $2, $3, $4, $5)
            RETURNING id';
        $result = pg_query_params(
            $this->conn,
            $query,
            [
                $animal_id,
                $weight,
                $memo,
                $userID,
                $image_id
            ]
        );

        if ($result) {
            $id = (int) pg_fetch_result(result: $result, row: 0, field: 'id');
            return $id;
        } else {
            return false;
        }
    }


    //transactional sql

    public function createWeight_Transactional_sql($animal_id, $weight, $memo, $userID, $image_data)
    {
        try {
            // Begin transaction
            pg_query($this->conn, "BEGIN");
    
            // 1. Optional: Validate required fields (basic sanity check)
            if (empty($animal_id) || empty($weight) || empty($userID)) {
                throw new Exception("Missing required fields for weight log.");
            }
    
            // 2. Insert image if provided
            $image_id = null;
            if (!empty($image_data)) {
                $imageClass = new Image($this->conn);
                $image_result_id = $imageClass->InsertImage($animal_id, $image_data);
    
                if (is_string($image_result_id)) {
                    throw new Exception("Image insert error: " . $image_result_id);
                }
    
                $image_id = $image_result_id;
            }
    
            // 3. Insert weight log
            $query = '
                INSERT INTO weight_log (
                    animal_id, weight, memo, image_id, added_by
                ) VALUES ($1, $2, $3, $4, $5)
                RETURNING id
            ';
            $result = pg_query_params($this->conn, $query, [
                $animal_id,
                $weight,
                $memo,
                $image_id,
                $userID
            ]);
    
            if (!$result) {
                throw new Exception("Failed to insert weight log.");
            }
    
            $weight_log_id = (int) pg_fetch_result($result, 0, 'id');
    
            // 4. Commit the transaction
            pg_query($this->conn, "COMMIT");
    
            // 5. Return the new weight log ID
            return $weight_log_id;
    
        } catch (Exception $e) {
            // Rollback on any error
            pg_query($this->conn, "ROLLBACK");
            return "Error creating weight log: " . $e->getMessage();
        }
    }
    


}

?>