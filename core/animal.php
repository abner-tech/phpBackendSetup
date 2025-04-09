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
 	        a.id, a.blpa_number, c.color, a.sire_id, a.dam_id, a.dob, a.gender, a.added_by_id,
 	        a.created_timestamp AS created_date, a.updated_timestamp AS updated_date, 
	        encode (i.image_data, \'escape\') AS image, lm.new_location_name AS location, wl.weight
        FROM animal AS a
        INNER JOIN color AS c ON a.color_id = c.id
        INNER JOIN (
	        SELECT animal_id, MAX(created_timestamp) AS latest_move
	        FROM location_move
	        GROUP BY animal_id
        ) AS latest_lm ON a.id = latest_lm.animal_id
        LEFT JOIN location_move AS lm ON a.id = lm.animal_id AND lm.created_timestamp = latest_lm.latest_move
        INNER JOIN location AS l ON lm.new_location_name = l.location_name
        LEFT JOIN image AS i ON i.id = a.image_id
        INNER JOIN (
	        SELECT animal_id, MAX(created_timestamp) as latest_weight
	        FROM weight_log
	        GROUP BY animal_id
        ) AS latest_wl ON a.id = latest_wl.animal_id
        LEFT JOIN weight_log AS wl ON a.id = wl.animal_id AND wl.created_timestamp = latest_wl.latest_weight
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
            lm.new_location_name AS location, encode(i.image_data, \'escape\') AS image_data, wl.weight
        FROM animal AS a
        INNER JOIN (
            SELECT animal_id, MAX(created_timestamp) AS latest_move
            FROM location_move
            GROUP BY animal_id
        ) AS latest_lm ON a.id = latest_lm.animal_id
        INNER JOIN location_move AS lm 
            ON latest_lm.latest_move = lm.created_timestamp 
            AND a.id = lm.animal_id
        LEFT JOIN LATERAL (
            SELECT i.image_data
            FROM image AS i
            WHERE i.id = a.image_id
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
        $location,
        $weight,

    ) {

        try {
            // start transaction for animal insert
            pg_query($this->conn, "BEGIN ");

            // Start the transaction
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

            // 2. Insert animal
            $animal_insert_query = '
                INSERT INTO animal (
                    blpa_number, color_id, sire_id, dam_id,
                    dob, gender, image_id, added_by_id, visible,
                    created_timestamp, updated_timestamp
                )
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8, TRUE, NOW(), NOW())
                RETURNING id
            ';

            $animal_result = pg_query_params(
                $this->conn,
                $animal_insert_query,
                [
                    $blpa_num,
                    $color_id,
                    $sire_id,
                    $dam_id,
                    $dob,
                    $gender,
                    $image_id,
                    $added_by_id
                ]
            );

            if (!$animal_result) {
                throw new Exception("Animal insert failed.");
            }

            $animal_id = (int) pg_fetch_result($animal_result, 0, 'id');

            // 3. Insert initial location
            $locationClass = new Location($this->conn);
            $location_result_id = $locationClass->InsertAnimalLocation_NO_transactional_sql(
                $animal_id,
                $location,
                null,
                $added_by_id, null
            );

            if (!is_int($location_result_id)) {
                throw new Exception("Location insert error: $location_result_id");
            }

            // 4. Insert weight if provided
            $weight_result_id = null;
            if (!empty($weight)) {
                $weightClass = new Weight($this->conn);
                $weight_result_id = $weightClass->createWeight(
                    $animal_id,
                    $weight,
                    null,
                    $added_by_id
                );

                if (!is_int($weight_result_id) && !empty($weight_result_id)) {
                    throw new Exception("Weight insert error: $weight_result_id");
                }

            }

            // 5 add animal id to the image
            if (!empty($image_Data)) {
                $imageClass = new Image($this->conn);
                $image_result_id = $imageClass->UpdateAnimalIdToImage_NO_transactional_sql($animal_id, $image_id);
                if (is_string($image_result_id)) {
                    throw new Exception("Image insert error: " . $image_result_id);
                }
                $image_id = $image_result_id;
            }

            //6. add the weight id to the movement record
            if ($weight_result_id && $location_result_id) {
                $LocationClass = new Location($this->conn);
                $WeightUpdateResult = $LocationClass->addWeightId_NO_transactional_sql(
                    $location_result_id,
                    $weight_result_id,
                    $image_id
                );
                if (is_string($WeightUpdateResult)) {
                    throw new Exception("Location update error: " . $WeightUpdateResult);
                }
            }

            // 7. All went well: commit
            pg_query($this->conn, "COMMIT");
            return 'successfully added animal with id: ' . $animal_id;

        } catch (Exception $e) {
            // If any error happens, rollback everything
            pg_query($this->conn, "ROLLBACK");
            $returnArray['error'] = $e->getMessage();
            return $returnArray;
        }
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