<?php

use PgSql\Result;

class animal
{

    //constructor with db connection
    private $conn;
    public function __construct($db)
    {
        $this->conn = $db;

    }

    //gettting Animals from database
    public function getanimals($sortField, $search, $order_by): bool|Result
    {
        //query to fetch the animal with the latest location of the animal
        $query = '
        SELECT
            a.id, a.blpa_number, c.color as color, a.sire_id, a.dam_id,
            a.dob, a.gender, a.added_by_id, a.created_timestamp AS created_date,
    		a.updated_timestamp AS updated_date, encode (i.image_data, \'escape\') AS image , 
            l.farm_name AS location, wl.weight AS weight
        FROM animal AS a
        INNER JOIN color AS c ON a.color_id = c.id
        INNER JOIN (
	        SELECT
	            animal_id, MAX(created_at) AS latest_move
	        FROM location_move
            GROUP BY animal_id
        ) AS latest_lm ON a.id = latest_lm.animal_id
        LEFT JOIN location_move 
            AS lm 
            ON a.id = lm.animal_id
		    AND lm.created_at = latest_lm.latest_move
        INNER JOIN location AS l ON lm.new_farm_id = l.id
        LEFT JOIN image AS i ON i.location_move_id = lm.id
        INNER JOIN (
            SELECT animal_id, MAX(created_timestamp) AS latest_weight
            FROM weight_log
            GROUP BY animal_id
        ) AS latest_wl ON a.id = latest_wl.animal_id
         LEFT JOIN weight_log
            AS wl
            ON a.id = wl.animal_id
            AND wl.created_timestamp = latest_wl.latest_weight
        WHERE 
            a.visible = true AND
            (
                (a.id::TEXT LIKE $1 OR $1 = \'\') OR
                (a.blpa_number::TEXT LIKE $1 OR $1 = \'\')
                OR $1 = \'\'
            )
        ORDER BY ' . pg_escape_string($sortField) . ' ' . pg_escape_string($order_by) . '
        LIMIT 200
        ';
        $stmt = pg_query_params($this->conn, $query, params: ['%' . $search . '%']);
        return $stmt;
    }

    //not fully implemented
    public function getAnimalByID($animalID): bool|Result
    {
        $query = '
        SELECT 
            a.id, a.blpa_number, a.color_id, a.sire_id, a.dam_id, a.dob, a.gender, a.added_by_id, a.created_timestamp,
            lm.new_farm_id AS location_id, encode(i.image_data, \'escape\') AS image_data, wl.weight
        FROM animal AS a
        INNER JOIN (
            SELECT animal_id, MAX(created_at) AS latest_move
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
        INNER JOIN (
            SELECT wl.animal_id, MAX(created_timestamp) AS latest_weight
            FROM weight_log AS wl
            GROUP BY wl.animal_id
        ) AS latest_wl ON a.id = latest_wl.animal_id
         LEFT JOIN weight_log
         AS wl ON a.id = wl.animal_id AND wl.created_timestamp = latest_wl.latest_weight
        WHERE a.id = $1;

        ';
        $stmt = pg_query_params($this->conn, $query, [$animalID]);
        return $stmt;

    }


    //add animal to db
    public function addAnimal(
        $blpa_num,
        $color_id,
        $sire_id,
        $dam_id,
        $dob,
        $gender,
        $added_by_id,
        $image_Data,
        $location_id,
        $weight,
        $weight_memo
    ) {

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

        //insert the location of the animal if given
        if ($weight && $weight !== null) {
            $weightClass = new Weight($this->conn);
            $weight_result_id = $weightClass->createWeight(
                $animal_result_id,
                $weight,
                $weight_memo
            );

            if( !is_int($weight_result_id) && is_string($weight_result_id)) {
                $returnArray['error'] = $weight_result_id;
                return $returnArray;
            }

            $returnArray['weight_log_id'] = $weight_result_id;
        }

        return $returnArray;
    }


    //function to fetch based on the search form the db for selecteion
    public function search($search, $sex)
    {
        $query = '
            SELECT id, blpa_number
            FROM ANIMAL
            WHERE (id::TEXT LIKE $1 OR blpa_number::TEXT LIKE $1) 
                AND visible = true AND LOWER(gender) = LOWER($2)
            LIMIT 5
        ';
        $stmt = pg_query_params($this->conn, $query, ['%' . $search . '%', $sex]);
        return $stmt;
    }

}

?>