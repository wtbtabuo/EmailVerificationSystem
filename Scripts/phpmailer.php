<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendCustomMail($toEmail, $toName, $subject, $bodyText)
{
    // PHPMailerを使ってメール送信処理を行う
    $mail = new PHPMailer(true);

    try {
        // サーバの設定
        $mail->isSMTP();                                      // SMTPを使用するようにメーラーを設定
        $mail->Host       = 'smtp.gmail.com';                 // GmailのSMTPサーバ
        $mail->SMTPAuth   = true;                             // SMTP認証を有効にする
        $mail->Username   = 'keita753@gmail.com';   // SMTPユーザー名
        $mail->Password   = 'uicfuwwzeexzimzj';               // SMTPパスワード
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // 必要に応じてTLS暗号化を有効にする
        $mail->Port       = 587;                              // 接続先のTCPポート

        // 受信者
        $mail->setFrom('keita753@gmail.com', 'SpaceApp');     // 送信者情報
        $mail->addAddress($toEmail, $toName);                 // 受信者のメールアドレスと名前

        // メールの件名と本文を設定
        $mail->Subject = $subject;                            // 件名
        $mail->isHTML(false);                                 // プレーンテキストメールとして送信
        $mail->Body    = $bodyText;                           // メール本文

        // メールを送信
        $mail->send();
        return true;  // メール送信が成功した場合
    } catch (Exception $e) {
        // 失敗した場合のエラーメッセージ
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
