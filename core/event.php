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

    public function getEvents() {
        $query = '
            SELECt 
                id, 
                event_name,
                description,
                created_timestamp
            FROM event
            WHERE visible = true
        ';

        $stmt = pg_query($this->conn, $query);
        return $stmt;
    }

    public function getEventByID($event_id) {
        $query = '
            SELECt 
                id, 
                event_name,
                description,
                created_timestamp
            FROM event
            WHERE id = $1
        ';

        $stmt = pg_query_params($this->conn, $query, [$event_id]);
        return $stmt;
    }

    public function getEventLogs() {
        $query = '
            SELECt 
                id, event_id, animal_id, status, memo, weight_id,
                visible, event_date, created_timestamp 
            FROM event_log
            WHERE visible = true
        ';

        $stmt = pg_query($this->conn, $query);
        return $stmt;
    }

    public function addEventLog($event_id, $animal_id, $status, $memo, $weight_id) {
        try {
            pg_query($this->conn, "BEGIN");

            $event_log_id = null;

            $query = '
            INSERT INTO event_log (
                event_id, animal_id, status, memo, weight_id
            )
            VALUES ($1, $2, $3, $4, $5)
            RETURNING id
            ';

            $result = pg_query_params(
                $this->conn,
                $query,
                [
                    $event_id,
                    $animal_id,
                    $status,
                    $memo,
                    $weight_id
                ]
            );

            if (!$result) {
                throw new Exception("event log insert failed.");
            }

            $event_log_id = (int) pg_fetch_result($result, 0, 'id');

            pg_query($this->conn, "COMMIT");
            return $event_log_id;

        } catch (Exception $e) {
            // If any error happens, rollback everything
            pg_query($this->conn, "ROLLBACK");
            return $e->getMessage();
        }
    }
    public function getEventLogByID($event_log_id) {
        $query = '
            SELECt 
                id, event_id, animal_id, status, memo, weight_id,
                visible, event_date, created_timestamp 
            FROM event_log
            WHERE id = $1
        ';

        $stmt = pg_query_params($this->conn, $query, [$event_log_id]);
        return $stmt;
    }

    public function updateEvent($event_id, $event_name, $event_description) {
        try {
            pg_query($this->conn, "BEGIN");

            $query = '
                UPDATE event
                SET event_name = $1, description = $2
                WHERE id = $3
                RETURNING id
            ';

            $result = pg_query_params(
                $this->conn,
                $query,
                [
                    $event_name,
                    $event_description,
                    $event_id
                ]
            );

            if (!$result) {
                throw new Exception("event update failed.");
            }

            pg_query($this->conn, "COMMIT");
            return true;

        } catch (Exception $e) {
            // If any error happens, rollback everything
            pg_query($this->conn, "ROLLBACK");
            return false;
        }
    }
}

?>