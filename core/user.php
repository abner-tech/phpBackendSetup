<?php
    class User{
        private $conn;
        private $user_table = 'user';

        //user properties
        public $user_id;
        public $created_at;
        public $user_name;
        public $user_email;
        public $password_hash;
        public $actived;

        //constructor with db connection

        public function __construct($db) {
            $this->conn = $db;

        }

        //gettting user from database
        public function readUser(){
            $query = 'SELECT * FROM users';
            $stmt = $this->conn->prepare($query);
            $stmt->excecute();
            echo json_decode($stmt);
        }
    }

?>