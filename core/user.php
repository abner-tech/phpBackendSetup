<?php
    class User{
        
        //constructor with db connection
        private $conn;
        public function __construct($db) {
            $this->conn = $db;

        }
        // //user properties
        // public $user_id;
        // public $created_at;
        // public $user_name;
        // public $user_email;
        // public $password_hash;
        // public $actived;

        //gettting user from database
        public function getUser(){
            $query = 'SELECT id, created_at, firstname, lastname, email FROM users';
            $result = pg_prepare($this->conn, "get_users", $query);
            $stmt = pg_execute($this->conn, "get_users", []);
            return $stmt;
        }

        public function getUserByID($userID) {
            $query = 'SELECT 
            id,
            created_at,
            firstname,
            lastname,
            email
            FROM users
            WHERE id == $1';
            $result = pg_query_params($this->conn, $query, array($userID));


            if($result) {
                $user = pg_fetch_assoc($result);
                pg_free_result($result);
                return $user;
            } else {
                return ["message" => "user Not Found"];
            }
        }

        public function createUser( $firstname, $lastname, $email, $password ) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            return $hashed_password;
        }

    }

?>