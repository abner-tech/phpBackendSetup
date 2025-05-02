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
    public function addEvent($event_name, $event_descrition, $added_by_id)
    {
        try {
            pg_query($this->conn, "BEGIN");

            $event_id = null;

            $query = '
            INSERT INTO event (
                event_name, description, added_by_id
            )
            VALUES ($1, $2, $3)
            RETURNING id
            ';

            $result = pg_query_params(
                $this->conn,
                $query,
                [
                    $event_name,
                    $event_descrition,
                    $added_by_id
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

    public function getEvents()
    {
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

    public function getEventByNameSearch($Event_search)
    {
        $query = '
            SELECT 
                id, 
                event_name,
                description,
                created_timestamp
            FROM event
            WHERE visible = true
                AND (event_name LIKE $1)
        ';

        $stmt = pg_query_params($this->conn, $query, ["%$Event_search%"]);
        return $stmt;
    }

    public function getEventByID($event_id)
    {
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

    public function getEventLogs()
    {
        $query = '
            SELECT 
                el.id, el.event_id, el.animal_id, el.status, el.memo, el.weight_id,
                el.visible, el.event_date, el.created_timestamp , wl.weight, e.event_name, a.blpa_number,
				encode (i.image_data, \'escape\') AS image
            FROM event_log AS el
			LEFT JOIN weight_log AS wl ON wl.id = el.weight_id
			INNER JOIN event AS e ON e.id = el.event_id
			INNER JOIN animal AS a ON el.animal_id = a.id
			LEFT JOIN image AS i ON el.image_id = i.id
            WHERE el.visible = true
        ';

        $stmt = pg_query($this->conn, $query);
        return $stmt;
    }

    public function addEventLog($event_id, $animal_id, $status, $memo, $weight, $added_by_id, $image_Data)
    {
        try {
            pg_query($this->conn, "BEGIN");

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
            // 2. Insert weight if provided
            $weight_result_id = null;
            if (!empty($weight)) {
                $weightClass = new Weight($this->conn);
                $weight_result_id = $weightClass->createWeight(
                    $animal_id,
                    $weight,
                    null,
                    $added_by_id,
                    $image_id
                );

                if (!is_int($weight_result_id) && !empty($weight_result_id)) {
                    throw new Exception("Weight insert error: $weight_result_id");
                }
            }

            $event_log_id = null;

            $query = '
            INSERT INTO event_log (
                event_id, animal_id, status, memo, weight_id, event_date, image_id, added_by_id
            )
            VALUES ($1, $2, $3, $4, $5, NOW(), $6, $7)
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
                    $weight_result_id, 
                    $image_id,
                    $added_by_id
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
    public function getEventLogByID($event_log_id)
    {
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

    public function updateEvent($event_id, $event_name, $event_description)
    {
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