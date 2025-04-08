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

    public function InsertImage( $animal_id, $image_Data) 
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
            INSERT INTO image (animal_id, image_data)
            VALUES ($1, $2)
            RETURNING id;
        ';

        pg_query($this->conn, 'BEGIN;');

        $image_result_id = pg_query_params($this->conn, $query, [
            $animal_id,
            $image_Data,
        ]);

        //check if record inserted successfully
        if ($image_result_id) {
            $image_result_id = (int) pg_fetch_result(result: $image_result_id, row: 0, field: 'id');

            if(is_int($image_result_id)) {
                pg_query($this->conn,'COMMIT;');
                pg_query($this->conn, 'END;');
            } else {
                pg_query($this->conn,'ROLLBACK;');
                pg_query($this->conn, 'END;');
            }
            return $image_result_id;
        } else {
            pg_query($this->conn,'ROLLBACK;');
            pg_query($this->conn, 'END;');
            return 'error inserting animal image';
        }

    }

    public function InsertImage_NO_transactional_sql($animal_id, $image_Data) 
{
    // Validate base64 format
    if (
        !is_string($image_Data) ||
        !preg_match('/^data:image\/(png|jpeg|gif);base64,/', $image_Data)
    ) {
        return 'invalid image data format';
    }

    // Decode base64
    $base64_str = preg_replace('#^data:image/\w+;base64,#i', '', $image_Data);
    $decoded_image_Data = base64_decode($base64_str);

    if ($decoded_image_Data === false) {
        return 'failed to decode base64 image data';
    }

    // Insert image (no BEGIN/COMMIT here!)
    $query = ' 
        INSERT INTO image (animal_id, image_data)
        VALUES ($1, $2)
        RETURNING id;
    ';

    $result = pg_query_params($this->conn, $query, [
        $animal_id,
        $image_Data,
    ]);

    if (!$result) {
        return 'error inserting animal image';
    }

    $image_id = pg_fetch_result($result, 0, 'id');

    return is_numeric($image_id) ? (int)$image_id : 'error retrieving inserted image id';
}


}

?>