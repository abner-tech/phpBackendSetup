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

    public function InsertImage( $weightID, $locationMoveID, $image_Data) 
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
        (weight_id, location_move_id, image_data )
        VALUES ($1, $2, $3)
        returning id
        ';

        $image_result_id = pg_query_params($this->conn, $query, [
            $weightID,
            $locationMoveID,
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

}

?>