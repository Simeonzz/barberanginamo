<?php
// send_gmail_register.php

require_once __DIR__ . '/PHPMailer-PHPMailer-3cd2a2a/src/Exception.php';
require_once __DIR__ . '/PHPMailer-PHPMailer-3cd2a2a/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-PHPMailer-3cd2a2a/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send welcome email after registration
 * @return array [success => bool, message => string]
 */
function sendRegisterEmail($toEmail, $toName){

    $mail = new PHPMailer(true);

    try {

        // SMTP CONFIG
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'barberanginamosalon@gmail.com'; // your gmail
        $mail->Password   = 'gvrdiiorukaz tzkz';       // gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->Timeout = 15;

        // EMAIL CONTENT
        $mail->setFrom('barberanginamosalon@gmail.com', 'Barberang Ina Mo');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Barberang Ina Mo!';
        $mail->Body = "
            <div style='font-family:Poppins,sans-serif'>
                <h2>Hi {$toName} 👋</h2>
                <p>Your account has been successfully created.</p>
                <p>You can now login and book your appointment.</p>
                <br>
                <p>See you soon 💜</p>
            </div>
        ";

        $mail->send();

        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];

    } catch (Exception $e) {

        return [
            'success' => false,
            'message' => $mail->ErrorInfo
        ];
    }
}
?>
