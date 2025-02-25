<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once "../packages/PHPMailer-master/src/Exception.php";
require_once "../packages/PHPMailer-master/src/SMTP.php";
require_once "../packages/PHPMailer-master/src/PHPMailer.php";

class EmailService
{
    private $mail;

    public function __construct()
    {
        // Initialize PHPMailer
        $this->mail = new PHPMailer(true);

        // Set basic email parameters
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com'; // SMTP server
        $this->mail->SMTPAuth = true;
        //the 2 parameters below, need to be saved in .env and create a git ignore in a few minutes
        $this->mail->Username = ''; //email credentials
        $this->mail->Password = ''; //email password credential
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587; // SMTP Port
    }

    // Function to send password reset email
    public function sendPasswordResetEmail($toEmail, $token)
    {
        try {
            $this->mail->setFrom('no-reply@example.com', 'Brite Solutions');
            $this->mail->addAddress($toEmail);
            $this->mail->Subject = 'Password Reset Request';
            $this->mail->isHTML(true);

            // Load the external HTML template
            $htmlTemplate = file_get_contents(__DIR__ . '/htmlMail/passwordReset.html');

            // Replace the placeholder with the reset link
            $htmlContent = str_replace('{{token}}', $token, $htmlTemplate);

            // Set the email body
            $this->mail->Body = $htmlContent;

            // Send the email
            $this->mail->send();
            echo 'Password reset email has been sent.';
        } catch (Exception $e) {
            echo 'Error sending password reset email: ' . $this->mail->ErrorInfo;
        }
    }

    // Function to send account activation email
    public function sendActivationEmail($toEmail, $activationLink)
    {
        try {
            $this->mail->setFrom('no-reply@example.com', 'Your App Name');
            $this->mail->addAddress($toEmail);
            $this->mail->Subject = 'Account Activation';
            $this->mail->isHTML(true);

            // Load the external HTML template
            $htmlTemplate = file_get_contents(__DIR__ . '/htmlMail/account_activation.html');

            // Replace the placeholder with the activation link
            $htmlContent = str_replace('{{activationLink}}', $activationLink, $htmlTemplate);

            // Set the email body
            $this->mail->Body = $htmlContent;

            // Send the email
            $this->mail->send();
            echo 'Activation email has been sent.';
        } catch (Exception $e) {
            echo 'Error sending activation email: ' . $this->mail->ErrorInfo;
        }
    }
}

