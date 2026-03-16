
<?php
// send_otp.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer-PHPMailer-3cd2a2a/src/Exception.php';
require_once __DIR__ . '/PHPMailer-PHPMailer-3cd2a2a/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-PHPMailer-3cd2a2a/src/SMTP.php';

function sendOTPEmail($toEmail, $toName, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings - MORE RELIABLE CONFIGURATION
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Set to DEBUG_SERVER for troubleshooting
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'simeonkulalo32@gmail.com'; // CHANGE THIS
        $mail->Password   = 'ijnk hxhv pzxl coqw'; // CHANGE THIS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 30;
        
        // CRITICAL: Disable SSL verification for local development
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Alternative SSL context
        $mail->Context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        // Recipients
        $mail->setFrom('YOUR_EMAIL@gmail.com', 'Barberang Ina Mo');
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo('YOUR_EMAIL@gmail.com', 'Barberang Ina Mo');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification OTP - Barberang Ina Mo';
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .header h1 { color: #FFD700; margin: 0; font-size: 24px; }
                .content { background: #f5f5f5; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-code { background: #1a1a1a; color: #FFD700; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; border-radius: 10px; letter-spacing: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BARBERANG INA MO</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$toName}!</h2>
                    <p>Your OTP for email verification is:</p>
                    <div class='otp-code'>{$otp}</div>
                    <p>This code expires in 10 minutes.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Barberang Ina Mo</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Hello {$toName},\n\nYour OTP is: {$otp}\n\nThis code expires in 10 minutes.\n\nThank you,\nBarberang Ina Mo";

        $mail->send();
        return ['success' => true, 'message' => 'OTP sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}

function generateOTP($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= mt_rand(0, 9);
    }
    return $otp;
}
?>