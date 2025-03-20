<?php

use PgSql\Result;

require_once '../middleware/auth/token.php';

class animal
{

    //constructor with db connection
    private $conn;
    public function __construct($db)
    {
        $this->conn = $db;

    }

    //gettting Animals from database
    public function getanimals(): bool|Result
    {

        //query to fetch the animal with the latest location of the animal
        $query = '
        SELECT
            a.id, a.blpa_number, c.color, a.sire_id, a.dam_id,
            a.dob, a.gender, a.added_by_id, a.created_timestamp AS created_date,
    		a.updated_timestamp AS updated_date, encode (i.image_data, \'escape\') AS image , l.farm_name AS location
        FROM animal AS a
        INNER JOIN color AS c ON a.color_id = c.id
        INNER JOIN (
	        SELECT
	            animal_id, MAX(created_at) AS latest_move
	        FROM location_move
            GROUP BY animal_id
        ) AS latest_lm ON a.id = latest_lm.animal_id
        INNER JOIN location_move 
            AS lm 
            ON a.id = lm.animal_id
		    AND lm.created_at = latest_lm.latest_move
        INNER JOIN location AS l ON lm.new_farm_id = l.id
        LEFT JOIN image AS i ON i.location_move_id = lm.id
        WHERE a.visible = true
        LIMIT 200
        ';
        pg_prepare($this->conn, "get_animals", query: $query);
        $stmt = pg_execute($this->conn, "get_animals", params: []);
        return $stmt;
    }


    //not fully implemented
    public function getAnimalByID($animalID): bool|Result
    {
        $query = '
        SELECT 
            a.id, a.blpa_number, a.color_id, a.sire_id, a.dam_id,
            a.dob, a.gender, a.added_by_id, a.created_timestamp,
            lm.new_farm_id AS location_id,
            encode(i.image_data, \'escape\') AS image_data
        FROM animal AS a
        INNER JOIN (
            SELECT 
                animal_id,
                MAX(created_at) AS latest_move
            FROM location_move
            GROUP BY animal_id
        ) AS latest_lm ON a.id = latest_lm.animal_id
        INNER JOIN location_move AS lm 
            ON latest_lm.latest_move = lm.created_at 
            AND a.id = lm.animal_id
        LEFT JOIN LATERAL (
            SELECT i.image_data
            FROM image AS i
            WHERE i.location_move_id = lm.id
            ORDER BY i.created_timestamp DESC
            LIMIT 1
        ) AS i ON TRUE
        WHERE a.id = $1;

        ';
        $stmt = pg_query_params($this->conn, $query, [$animalID]);
        return $stmt;

    }

    //sanitation of input values
    private function sanitizeIntegerOrNull($value)
    {
        $sanitized = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        return ($sanitized === false || $sanitized === null) ? null : $sanitized;
    }

    // Function to sanitize a string and return null if empty
    private function sanitizeStringOrNull($value)
    {
        $sanitized = filter_var($value, FILTER_SANITIZE_STRING);
        return empty($sanitized) ? null : $sanitized;
    }

    // Function to sanitize a date and return null if invalid
    private function sanitizeDateOrNull($date)
    {
        if (empty($date)) {
            return null;
        }
        $formattedDate = DateTime::createFromFormat('Y-m-d', $date);
        return ($formattedDate && $formattedDate->format('Y-m-d') === $date) ? $formattedDate->format('Y-m-d') : null;
    }

    public function addAnimal($blpa_num, $color_id, $sire_id, $dam_id, $dob, $gender, $added_by_id, $image_Data, $location_id)
    {
        $blpa_num = $this->sanitizeIntegerOrNull($blpa_num);
        $color_id = $this->sanitizeIntegerOrNull($color_id);
        $sire_id = $this->sanitizeIntegerOrNull($sire_id);
        $dam_id = $this->sanitizeIntegerOrNull($dam_id);
        $dob = $this->sanitizeDateOrNull($dob);
        $gender = $this->sanitizeStringOrNull($gender);
        $added_by_id = $this->sanitizeIntegerOrNull($added_by_id);
        $location_id = $this->sanitizeIntegerOrNull($location_id);

        //insert animal info first
        $query = '
            INSERT INTO animal
            (blpa_number, 
            color_id, 
            sire_id, 
            dam_id, 
            dob, 
            gender,
            added_by_id, 
            visible, 
            created_timestamp, 
            updated_timestamp)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), NOW()
            )
            RETURNING id';
        $animal_result_id = pg_query_params(connection: $this->conn, query: $query, params: [
            $blpa_num,
            $color_id,
            $sire_id,
            $dam_id,
            $dob,
            $gender,
            $added_by_id,
            true
        ]);

        $returnArray = [];

        //check if record inserted successfully
        if ($animal_result_id) {
            $animal_result_id = (int) pg_fetch_result(result: $animal_result_id, row: 0, field: 'id');
            $returnArray['animal_id'] = $animal_result_id;
        } else {
            $returnArray['error'] = $animal_result_id;
            return $returnArray;
        }

        //insert the first location of the animal and get id
        $locationClass = new Location($this->conn);
        $location_result_id = $locationClass->InsertAnimalLocation($animal_result_id, $location_id, null, $added_by_id);

        if (!is_int($location_result_id) && is_string($location_result_id)) {
            $returnArray['error'] = $location_result_id;
            return $returnArray;
        }

        $returnArray['location_id'] = $location_result_id;

        //then validate and insert the image of the animal if given
        if ($image_Data !== '' && $image_Data !== null) {
            $imageClass = new Image($this->conn);
            $image_result_id = $imageClass->InsertImage(null, $location_result_id, $image_Data);

            if (is_string($image_result_id)) {
                $returnArray['error'] = $image_result_id;
            } elseif (is_int($image_result_id)) {
                $returnArray['image_id'] = $image_result_id;
            }
        }

        return $returnArray;
    }

}

?>