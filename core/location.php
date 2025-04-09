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

    public function InsertAnimalLocation_NO_transactional_sql(
        $animal_id,
        $new_location_name,
        $old_location_move_name,
        $added_by_id,
        $weight_id
    ) {
        $query = '
        INSERT INTO location_move
        (animal_id, new_location_name, old_location_name, added_by_id, weight)
        VALUES ( $1, $2, $3, $4, $5)
        RETURNING id;
        ';

        $location_result_id = pg_query_params($this->conn, $query, [
            $animal_id,
            $new_location_name,
            $old_location_move_name,
            $added_by_id,
            $weight_id
        ]);

        if ($location_result_id) {
            $id = (int) pg_fetch_result(result: $location_result_id, row: 0, field: 'id');
            return $id;
        } else {
            return 'failure to save animal location';
        }
    }

    public function getAnimalMovementHistryByID_NO_transactinal_sql($animal_id)
    {
        $query = '
        SELECT 
			lm.id, lm.new_location_name,lm.old_location_name, l.location_address AS to_address,
			 encode (i.image_data, \'escape\') AS image_data, lm.created_timestamp, CONCAT (u.firstname, \' \', u.lastname) AS recorded_by,
			wl.weight
		FROM location_move AS lm
		LEFT JOIN location AS l ON l.location_name = lm.new_location_name
		INNER JOIN users AS u ON u.id = lm.added_by_id
		LEFT JOIN weight_log AS wl ON wl.id = lm.weight_id
		LEFT JOIN image AS i ON lm.image_id = i.id
		WHERE lm.animal_id = $1
		ORDER BY id DESC
        ';

        return (pg_query_params(connection: $this->conn, query: $query, params: [$animal_id]));

    }

    public function addWeightId_NO_transactional_sql($movement_id, $weigh_id, $image_id)
    {
        $query = '
        UPDATE location_move
        SET weight_id = $1, image_id = $3
        WHERE id = $2
        RETURNING id
        ';

        $location_result_id = pg_query_params($this->conn, $query, [
            $weigh_id,
            $movement_id,
            $image_id
        ]);

        if ($location_result_id) {
            $id = (int) pg_fetch_result(result: $location_result_id, row: 0, field: 'id');
            return $id;
        } else {
            return 'failure to update animal location';
        }
    }

    public function InsertAnimalLocation_transactional_sql(
        $animal_id,
        $new_location_name,
        $old_location_move_name,
        $added_by_id,
        $weight,
        $image
    ) {
        try {
            pg_query($this->conn, "BEGIN");

            // 1. Insert image if provided
            $image_id = null;
            if (!empty($image_Data)) {
                $imageClass = new Image($this->conn);
                $image_result_id = $imageClass->InsertImage_No_transactional_sql(null, $image_Data);
                if (is_string($image_result_id)) {
                    throw new Exception("Image insert error: " . $image_result_id);
                }

                $image_id = $image_result_id;
            }

            $query = '
                INSERT INTO location_move (
                    animal_id, new_location_name, old_location_name, added_by_id, weight_id
                ) VALUES ($1, $2, $3, $4, $5)
                RETURNING id;
            ';

            $location_result = pg_query_params($this->conn, $query, [
                $animal_id,
                $new_location_name,
                $old_location_move_name,
                $added_by_id,
                $weight
            ]);

            if (!$location_result) {
                throw new Exception("Failed to insert location move.");
            }

            $id = (int) pg_fetch_result($location_result, 0, 'id');

            // Commit the transaction
            pg_query($this->conn, "COMMIT");

            return $id;

        } catch (Exception $e) {
            // Rollback the transaction on error
            pg_query($this->conn, "ROLLBACK");
            return 'Error inserting location: ' . $e->getMessage();
        }
    }


}

?>