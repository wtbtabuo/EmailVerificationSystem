<?php
namespace Scripts;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailSender
{
    public static function sendCustomMail($toEmail, $toName, $subject, $bodyText)
    {
        $mail = new PHPMailer(true);

        try {
            // サーバの設定
            $mail->isSMTP();                                      
            $mail->Host       = 'smtp.gmail.com';                 
            $mail->SMTPAuth   = true;                             
            $mail->Username   = 'keita753@gmail.com';   
            $mail->Password   = 'uicfuwwzeexzimzj';               
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   
            $mail->Port       = 587;                              

            // 受信者
            $mail->setFrom('keita753@gmail.com', 'SpaceApp');     
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
