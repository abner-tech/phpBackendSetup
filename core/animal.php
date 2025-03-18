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
        $query = 'SELECT
        id, blpa_number, color_id, sire_id, dam_id,
        dob, gender, added_by_id, created_timestamp, updated_timestamp
        FROM animal
        WHERE visible = true
        LIMIT 200';
        pg_prepare($this->conn, "get_animals", query: $query);
        $stmt = pg_execute($this->conn, "get_animals", params: []);
        return $stmt;
    }


    //not fully implemented
    public function getAnimalByID($animalID): bool|Result
    {
        $query = 'SELECT
            id,
            created_at,
            firstname,
            lastname,
            email
            FROM Animals
            WHERE id = $1';
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
        if ($image_Data !== '' || $image_Data !== null) {
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