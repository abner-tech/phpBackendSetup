<?php

use PgSql\Result;

class Image
{

    //constructor with db connection
    private $conn;
    public function __construct($db)
    {
        $this->conn = $db;

    }

    //gettting user from database
    public function getImages( $dbTable, $id)
    {
    }

    public function InsertImage( $weightID, $locationMoveID, $animal_id , $image_Data) 
    {
        //validation of image is done before insert
        if (
            !is_string($image_Data) ||
            !preg_match('/^data:image\/(png|jpeg|gif);base64,/', $image_Data)
        ) {
            return 'invalid image data format';
        }


        //decode base64 image string
        $base64_str = preg_replace('#^data:image/\w+;base64,#i', '', $image_Data);
        $decoded_image_Data = base64_decode($base64_str);
        if ($decoded_image_Data === false) {
            return 'failed to decode base64 image data';
        }

        $query = ' 
        INSERT INTO image 
        (weight_id, location_move_id, animal_id, image_data )
        VALUES ($1, $2, $3, $4)
        returning id
        ';

        $image_result_id = pg_query_params($this->conn, $query, [
            $weightID,
            $locationMoveID,
            $animal_id,
            $image_Data,
        ]);

        //check if record inserted successfully
        if ($image_result_id) {
            $image_result_id = (int) pg_fetch_result(result: $image_result_id, row: 0, field: 'id');
            return $image_result_id;
        } else {
            return 'error inserting animal image';
        }

    }

    public function getImagesByAnimalID($animalID) {
        $query = '
            SELECT encode (i.image_data, \'escape\') AS image_data, l.id AS move_ID, w.id AS weight_ID, a.id AS animal_id
            FROM image AS i
            LEFT JOIN location_move AS l ON i.location_move_id = l.id AND l.animal_id = $1
            LEFT JOIN weight_log AS w ON i.weight_id = w.id AND w.animal_id = $1
			LEFT JOIN animal AS a ON i.animal_id = a.id
            WHERE image_data IS NOT NULL 
			AND (
				w.animal_id = $1 OR
				l.animal_id = $1 OR
				i.animal_id = $1 
			)
            ORDER BY i.id DESC
        ';

        $result = pg_query_params($this->conn, $query, [$animalID] );
        return $result;
    }

}

?>