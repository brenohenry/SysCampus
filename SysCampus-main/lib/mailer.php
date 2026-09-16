<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__.'/../vendor/autoload.php';
$mailConfig=require __DIR__.'/../mail.config.php';

function send_password_reset_email(string $to,string $name,string $resetUrl,string $expiresAt):void {
    global $mailConfig;
    $mail=new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host=$mailConfig['smtp.gmail.com'];
    $mail->SMTPAuth=true;
    $mail->Username=$mailConfig['syscampus.tcc@gmail.com'];
    $mail->Password=$mailConfig['sc123tcc'];
    $mail->SMTPSecure=$mailConfig['tls'];
    $mail->Port=$mailConfig['587'];
    $mail->CharSet='UTF-8';

    $mail->setFrom($mailConfig['from_email'],$mailConfig['from_name']);
    $mail->addAddress($to,$name);
    $mail->isHTML(true);
    $mail->Subject='SysCampus — Redefinição de senha';
    $safeName=htmlspecialchars($name,ENT_QUOTES,'UTF-8');
    $safeUrl=htmlspecialchars($resetUrl,ENT_QUOTES,'UTF-8');
    $expires=(new DateTimeImmutable($expiresAt))->format('d/m/Y H:i');
    $mail->Body=<<<HTML
<div style="font-family:Arial,sans-serif;line-height:1.6;color:#1f2937">
  <h2>Redefinição de senha — SysCampus</h2>
  <p>Olá, {$safeName}.</p>
  <p>Recebemos uma solicitação para redefinir a senha da sua conta no SysCampus.</p>
  <p><a href="{$safeUrl}" style="display:inline-block;padding:12px 18px;background:#1f5fae;color:#fff;text-decoration:none;border-radius:6px">Redefinir minha senha</a></p>
  <p>O link é válido até <strong>{$expires}</strong>.</p>
  <p>Se você não solicitou essa alteração, ignore esta mensagem.</p>
  <p>SysCampus — Lumen Veritas</p>
</div>
HTML;
    $mail->AltBody="Olá, {$name}. Acesse {$resetUrl} para redefinir sua senha. O link é válido até {$expires}.";
    $mail->send();
}
