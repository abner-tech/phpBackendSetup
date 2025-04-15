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


    public function createLocation(
        $farm_name,
        $city,
        $district,
        $street_address,
        $notes
    ): bool|string|null {
        $query = '
            INSERT INTO location
            (farm_name, street_address, city, district, notes)
            VALUES ($1, $2, $3, $4, $5)
            RETURNING id';
        $result = pg_query_params(connection: $this->conn, query: $query, params: [
            $farm_name,
            $street_address,
            $city,
            $district,
            $notes
        ]);

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
        (animal_id, new_location_name, old_location_name, added_by_id, weight_id)
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
			lm.id, lm.new_location_name,lm.old_location_name, CONCAT (l.street_address, \' \', l.city , \' \', l.district) AS to_address,
			 encode (i.image_data, \'escape\') AS image_data, lm.created_timestamp, CONCAT (u.firstname, \' \', u.lastname) AS recorded_by,
			wl.weight
		FROM location_move AS lm
		LEFT JOIN location AS l ON l.farm_name = lm.new_location_name
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

    public function insertAnimalLocation_transactional_sql(
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
            if (!empty($image)) {
                $imageClass = new Image($this->conn);
                $image_result_id = $imageClass->InsertImage_No_transactional_sql($animal_id, $image);

                if (is_string($image_result_id)) {
                    throw new Exception("Image insert error: " . $image_result_id);
                }

                $image_id = $image_result_id;
            }

            // 2. Insert weight if provided
            $weight_id = null;
            if (!empty($weight)) {
                $weightClass = new Weight($this->conn);
                $weight_result_id = $weightClass->createWeight(
                    $animal_id,
                    $weight,
                    null,
                    $added_by_id,
                    $image_id
                );

                if (!is_int($weight_result_id)) {
                    throw new Exception("Weight insert error: " . $weight_result_id);
                }

                $weight_id = $weight_result_id;
            }

            // 3. Insert location move
            $query = '
                INSERT INTO location_move (
                    animal_id, new_location_name, old_location_name, added_by_id, weight_id, image_id
                ) VALUES ($1, $2, $3, $4, $5, $6)
                RETURNING id;
            ';

            $location_result = pg_query_params($this->conn, $query, [
                $animal_id,
                $new_location_name,
                $old_location_move_name,
                $added_by_id,
                $weight_id,
                $image_id
            ]);

            if (!$location_result) {
                throw new Exception("Failed to insert location move");
            }

            $id = (int) pg_fetch_result($location_result, 0, 'id');

            pg_query($this->conn, "COMMIT");

            return $id;

        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            return 'Error inserting location: ' . $e->getMessage();
        }
    }


    public function locationsSummary()
    {
        $query = '
		SELECT
    		l.id,
    		l.farm_name,
    		l.street_address, l.city, l.district,
    		COUNT(DISTINCT lm.animal_id) AS animal_count,
    		l.created_timestamp
		FROM location AS l
		LEFT JOIN (
 		    SELECT lm.*
		    FROM location_move lm
		    INNER JOIN (
 		        SELECT
            		animal_id,
            		MAX(created_timestamp) AS latest_move
        		FROM location_move
        		GROUP BY animal_id
    		) AS latest
    		ON lm.animal_id = latest.animal_id AND lm.created_timestamp = latest.latest_move
		) AS lm ON lm.new_location_name = l.farm_name
		GROUP BY l.id, l.farm_name, l.created_timestamp
		ORDER BY animal_count DESC;

        ';

        $stmt = pg_query($this->conn, $query);
        return $stmt;
    }

    public function location_record($farm_id) {
        $query = '
        SELECT 
            id, farm_name, street_address, city, district, notes, created_timestamp
        FROM location
        WHERE id = $1
        ';

        $stmt = pg_query_params( $this->conn, $query, [$farm_id]);
        $result = pg_fetch_assoc($stmt, 0);
        return $result;
    }


}

?>