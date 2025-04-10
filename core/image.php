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
    public function getImages($dbTable, $id)
    {
    }

    // transactional sql
    public function InsertImage($animal_id, $image_Data)
    {
        try {
            // 1. Validate image format
            if (
                !is_string($image_Data) ||
                !preg_match('/^data:image\/(png|jpeg|gif);base64,/', $image_Data)
            ) {
                throw new Exception('Invalid image data format');
            }
    
            // 2. Decode base64 string
            $base64_str = preg_replace('#^data:image/\w+;base64,#i', '', $image_Data);
            $decoded_image_Data = base64_decode($base64_str);
            if ($decoded_image_Data === false) {
                throw new Exception('Failed to decode base64 image data');
            }
    
            // 3. Start transaction
            pg_query($this->conn, 'BEGIN;');
    
            // 4. Insert into DB
            $query = '
                INSERT INTO image (animal_id, image_data)
                VALUES ($1, $2)
                RETURNING id;
            ';
    
            $image_result = pg_query_params($this->conn, $query, [
                $animal_id,
                $image_Data
            ]);
    
            if (!$image_result) {
                throw new Exception('Failed to insert image into the database');
            }
    
            $image_id = (int) pg_fetch_result($image_result, 0, 'id');
    
            if (!is_int($image_id)) {
                throw new Exception('Invalid image ID returned from database');
            }
    
            // 5. Commit and return ID
            pg_query($this->conn, 'COMMIT;');
            return $image_id;
    
        } catch (Exception $e) {
            // Rollback on failure
            pg_query($this->conn, 'ROLLBACK;');
            return 'Image insert error: ' . $e->getMessage();
        }
    }
    

    // no transactional sql
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

        return is_numeric($image_id) ? (int) $image_id : 'error retrieving inserted image id';
    }

    public function UpdateAnimalIdToImage_NO_transactional_sql($animal_id, $image_id)
    {
        $query = '
        UPDATE image
        SET animal_id = $1, updated_timestamp = NOW()
        WHERE id = $2
        RETURNING id
        ';

        $result = pg_query_params($this->conn, $query, [
            $animal_id,
            $image_id,
        ]);

        if (!$result) {
            return 'error updating image record';
        }

        $result_id = pg_fetch_result($result, 0, 'id');
        return is_numeric($result_id) ? (int) $result_id : 'error retrieving inserted image id';

    }

    public function getImagesByAnimalID($animalID) {
        $query = '
            SELECT encode (i.image_data, \'escape\') AS image_data
            FROM image AS i
            WHERE i.animal_id = $1
            ORDER BY i.id DESC
        ';

        $result = pg_query_params($this->conn, $query, [$animalID] );
        return $result;
    }

}

?>