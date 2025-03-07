<?php

use PgSql\Result;

require_once '../middleware/auth/token.php';

class Color
{

    //constructor with db connection
    private $conn;
    public function __construct($db)
    {
        $this->conn = $db;

    }

    //gettting user from database
    public function getColors(): bool|Result
    {
        $query = 'SELECT id, color, description FROM color';
        pg_prepare($this->conn, "get_colors", query: $query);
        $stmt = pg_execute($this->conn, "get_colors", params: []);
        return $stmt;
    }

    // public function getUserByID($userID): bool|Result
    // {
    //     $query = 'SELECT
    //         id,
    //         created_at,
    //         firstname,
    //         lastname,
    //         email
    //         FROM users
    //         WHERE id = $1';
    //     $stmt = pg_query_params($this->conn, $query, [$userID]);
    //     return $stmt;

    // }

    public function createColor($color, $description): bool|string|null
    {
        $query = '
            INSERT INTO color
            (color, description)
            VALUES ($1, $2)
            RETURNING id';
        $result = pg_query_params(connection: $this->conn, query: $query, params: [$color, $description]);

        if ($result) {
            return pg_fetch_result(result: $result, row: 0, field: 'id');
        } else {
            return false;
        }
    }

}

?>