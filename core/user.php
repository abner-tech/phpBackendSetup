<?php

use PgSql\Result;

require_once '../middleware/auth/token_type.php';
require_once '../middleware/auth/token.php';
require_once '../mailer/mailer.php';

use Middleware\Auth\TokenTypes;

class User
{

    //constructor with db connection
    private $conn;
    public function __construct($db)
    {
        $this->conn = $db;

    }

    //gettting user from database
    public function getUser(): bool|Result
    {
        $query = 'SELECT id, created_at, firstname, lastname, email FROM users';
        pg_prepare($this->conn, "get_users", query: $query);
        $stmt = pg_execute($this->conn, "get_users", params: []);
        return $stmt;
    }

    public function getUserByID($userID): bool|Result
    {
        $query = 'SELECT
            id,
            created_at,
            firstname,
            lastname,
            email, 
            admin
            FROM users
            WHERE id = $1';
        $stmt = pg_query_params($this->conn, $query, [$userID]);
        return $stmt;

    }

    public function createUser($firstname, $lastname, $email, $password): bool|string|null
    {
        $hashed_password = password_hash(password: $password, algo: PASSWORD_BCRYPT);
        $query = '
            INSERT INTO users
            (firstname, lastname, email, password_hash)
            VALUES ($1, $2, $3, $4)
            RETURNING id';
        $result = pg_query_params(connection: $this->conn, query: $query, params: [$firstname, $lastname, $email, $hashed_password]);

        if ($result) {
            return pg_fetch_result(result: $result, row: 0, field: 'id');
        } else {
            return false;
        }
    }

    public function userLogin(string $email, string $password)
    {
        $query = '
            SELECT
            id,
            encode (password_hash, \'escape\') AS password_hash
            FROM users 
            WHERE email = $1
            LIMIT 1';
        $stmt = pg_query_params($this->conn, $query, [$email]);

        if (!$stmt) {
            return false;
        }

        $user = pg_fetch_assoc($stmt);

        if (!$user) {
            //echo 'one';
            return false; // User not found
        }
        $stored_password = $user['password_hash'];
        $userID = $user['id'];

        if (!password_verify(password: $password, hash: $stored_password)) {
            //echo 'two';
            return false; // Password incorrect
        }

        //create new bearer token
        $bearer_token = new AuthToken($this->conn); //initiate the class with the functions
        $tokenTypes = new TokenTypes; //tje types of tokens defined
        $jwt = $bearer_token->createBearerToken($userID, $tokenTypes->auth_token);

        $userInfo = $this->getUserByID($userID);
        if ($userInfo) {
            $userInfo = pg_fetch_assoc($userInfo);
        }

        return [
            'userInfo' => $userInfo,
            'tokenInfo' => $jwt,
        ];
    }

    //functino to send a welcome email notofication, implamented later
    public function sendRegisteredEmail(){
       
    }

}

?>