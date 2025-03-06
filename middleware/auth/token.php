<?php


class AuthToken {

    private string $secretKey;
    private string $serverName;
    private DateTimeImmutable $issueAt;

    public function __construct($db) {
        $this->conn = $db;

        $env_file = parse_ini_file(__DIR__.'/\.env');

        $this->secretKey = $env_file["secretKey"];
        $this->serverName = $env_file["serverName"] ?? 'localhost';
        $this->issueAt = new DateTimeImmutable("now", new DateTimeZone("America/Belize"));
    }

    private function base64url_encode($str) {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    private function generate_jwt($headers, $payload, $secret = 'secret') {
        $headers_encoded = $this->base64url_encode($headers);
        $payload_encoded = $this->base64url_encode($payload);
        $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
        $signature_encoded = $this->base64url_encode($signature);
        $jwt = "$headers_encoded.$payload_encoded.$signature_encoded";
        return $jwt;
    }

    public function createBearerToken(string $user_name, string $token_Type) {
        $expire = $this->issueAt->modify('+60 minutes');
    
        $header = json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
            'iat'  => $this->issueAt->format('Y-m-d H:i:s'), // Issued at (formatted)
            'iss'  => $this->serverName,                      // Issuer
            'nbf'  => $this->issueAt->format('Y-m-d H:i:s'), // Not before
        ]);
    
        $payload = json_encode([
            'expiry' => $expire->format('Y-m-d H:i:s'),  // Expiry in normal time format
            'userName' => $user_name,
            'tokenType' => $token_Type
        ]);
    
        $jwt = $this->generate_jwt($header, $payload, $this->secretKey);



        return [
            'token' => $jwt,
            'expiry' => $expire,
        ];
    }

    //not yet implemented, will decide validation method later
    //to know if token needs to be inserted to DB
    private function insertTokenToDB(string $token, string $scope) {
        
    }

    //noy in use given that same its in the same scenario insertTokenToDB
    private function authenticateToken(string $jwt, $secretKey) {
        //split the token
        $tokenSlice = explode('.', $jwt);
        $header = base64_decode($tokenSlice[0]);
        $payload = base64_decode($tokenSlice[1]);
        $signiture_provided = $tokenSlice[2];

        //check expiry time
        $expiry = json_decode($payload)->expiry;
        $is_token_expired = ($expiry - $this->issueAt->format('Y-m-d H:i:s')) < 0;

        //build signiture
        $base64Header = base64_encode($header);
        $base64Payload = base64_encode($payload);
        $signiture = hash_hmac('SHA256', $base64Header.".".$base64Payload, $secretKey, true);
        $base64UrlSigniture = base64_encode($signiture);

        //compare
        $is_token_valid = ($base64UrlSigniture === $signiture_provided);

        if ($is_token_expired || !$is_token_valid) {
            return false;
        } else {
            return true;
        }
    }
}
?>
