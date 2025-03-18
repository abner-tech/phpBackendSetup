<?php

class AuthToken
{

    private string $secretKey;
    private string $serverName;
    private DateTimeImmutable $issueAt;

    public function __construct($db)
    {
        date_default_timezone_set("America/Belize");
        $this->conn = $db;

        $env_file = parse_ini_file(__DIR__ . '/\.env');

        $this->secretKey = $env_file["secretKey"];
        $this->serverName = $env_file["serverName"] ?? 'localhost';
        $this->issueAt = new DateTimeImmutable("now", new DateTimeZone("America/Belize"));
    }

    private function base64url_encode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    private function generate_jwt($headers, $payload, $secret = 'secret')
    {
        $headers_encoded = $this->base64url_encode($headers);
        $payload_encoded = $this->base64url_encode($payload);
        $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
        $signature_encoded = $this->base64url_encode($signature);
        $jwt = "$headers_encoded.$payload_encoded.$signature_encoded";
        return $jwt;
    }

    public function createBearerToken(string $user_ID, string $token_Type)
    {
        $expire = $this->issueAt->modify('+60 minutes');

        $header = json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
            'iat' => $this->issueAt->format('Y-m-d H:i:s'), // Issued at (formatted)
            'iss' => $this->serverName,                      // Issuer
            'nbf' => $this->issueAt->format('Y-m-d H:i:s'), // Not before
        ]);

        $payload = json_encode([
            'expiry' => $expire->format('Y-m-d H:i:s'),  // Expiry in normal time format
            'userID' => $user_ID,
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
    private function insertTokenToDB(string $token, string $scope)
    {

    }

    //not in use given that same its in the same scenario insertTokenToDB
    public function authenticateToken(string $jwt)
    {
        // Split the token
        $tokenSlice = explode('.', $jwt);
        if (count($tokenSlice) !== 3) {
            return false; // Invalid token format
        }

        //not being used
        // $header = $this->base64UrlDecode($tokenSlice[0]);
        $payload = $this->base64UrlDecode($tokenSlice[1]);
        $signatureProvided = $tokenSlice[2];

        // Decode payload JSON
        $payloadObj = json_decode($payload);
        if (!isset($payloadObj->expiry)) {
            return false; // Expiry field missing
        }

        // Check expiry time
        $expiry = strtotime($payloadObj->expiry);
        $currentTime = Time();
        $isTokenExpired = ($expiry < $currentTime);

        // Build signature
        $secretKey = $this->secretKey;
        $signature = hash_hmac('SHA256', $tokenSlice[0] . "." . $tokenSlice[1], $secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        // Compare signatures
        $isTokenValid = ($base64UrlSignature === $signatureProvided);

        if(!$isTokenExpired && $isTokenValid) {
            return  (int) $payloadObj->userID;
        } else {
            return false;
        }

    }


    // Base64 URL decode function
    function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

}
?>