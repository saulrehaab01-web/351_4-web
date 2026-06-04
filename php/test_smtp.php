<?php
require_once __DIR__ . '/vendor/autoload.php'; // ← Composer handles everything

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->SMTPDebug  = 2;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jutanghwa09@gmail.com';
    $mail->Password   = 'xivn unyp geds hhdn';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->Timeout    = 10;

    $mail->setFrom('jutanghwa09@gmail.com', 'Test');
    $mail->addAddress('jutanghwa09@gmail.com');
    $mail->Subject = 'SMTP Test';
    $mail->Body    = 'If you see this, SMTP works!';

    $mail->send();
    echo '<p style="color:green">✅ Email sent successfully!</p>';
} catch (Exception $e) {
    echo '<p style="color:red">❌ Error: ' . $mail->ErrorInfo . '</p>';
}