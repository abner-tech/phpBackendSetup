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

    public function addAnimal($blpa_num, $color_id, $sire_id, $dam_id, $dob, $gender, $added_by_id): bool|string|null
    {
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
        $result = pg_query_params(connection: $this->conn, query: $query, params: [
            $blpa_num,
            $color_id,
            $sire_id,
            $dam_id,
            $dob,
            $gender,
            $added_by_id,
            true
        ]);

        //then insert the image of the animal


        //then insert the location of the animal

        if ($result) {
            return pg_fetch_result(result: $result, row: 0, field: 'id');
        } else {
            return false;
        }
    }

}

?>