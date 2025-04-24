<?php

use PgSql\Result;

class Event
{

    //constructor with db connection
    private $conn;
    public function __construct($db)
    {
        $this->conn = $db;

    }

    //add animal to db
    public function addEvent($event_name, $event_descrition)
    {
        try {
            pg_query($this->conn, "BEGIN");

            $event_id = null;

            $query = '
            INSERT INTO event (
                event_name, description
            )
            VALUES ($1, $2)
            RETURNING id
            ';

            $result = pg_query_params(
                $this->conn,
                $query,
                [
                    $event_name,
                    $event_descrition
                ]
            );

            if (!$result) {
                throw new Exception("event insert failed.");
            }

            $event_id = (int) pg_fetch_result($result, 0, 'id');

            pg_query($this->conn, "COMMIT");
            return $event_id;

        } catch (Exception $e) {
            // If any error happens, rollback everything
            pg_query($this->conn, "ROLLBACK");
            return $e->getMessage();
        }
    }

}

?>