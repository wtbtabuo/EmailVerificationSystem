<?php
namespace Scripts;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Helpers\Settings;

class MailSender
{
    public static function sendCustomMail($toEmail, $toName, $subject, $bodyText)
    {
        $mail = new PHPMailer(true);
        $mailAddress = Settings::env('FROM_EMAIL');
        $password = Settings::env('EMAIL_PASSWORD');
        try {
            // サーバの設定
            $mail->isSMTP();                                      
            $mail->Host       = 'smtp.gmail.com';                 
            $mail->SMTPAuth   = true;                             
            $mail->Username   = $mailAddress;   
            $mail->Password   = $password;               
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   
            $mail->Port       = 587;                              

            // 受信者
            $mail->setFrom($mailAddress, '');     
            $mail->addAddress($toEmail, $toName);                 

            // メールの件名と本文を設定
            $mail->Subject = $subject;                            
            $mail->isHTML(false);                                 
            $mail->Body    = $bodyText;                           

            // メールを送信
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
