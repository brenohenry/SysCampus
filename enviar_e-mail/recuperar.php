<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    //Verifica se o e-mail existe no banco de dados (usando PDO como exemplo)
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        //Gera token aleatório e data de expiração (ex: 1 hora)
        $token = bin2hex(random_bytes(32));
        $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

        //Salva token no banco
        $updateStmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
        $updateStmt->execute([$token, $expiracao, $email]);

        //Configura e enviar e-mail via PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Ou seu provedor SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'syscampus.tcc@gmail.com';
            $mail->Password   = 'sc123tcc'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('syscampus.tcc@gmail.com', 'Nome do Seu Site');
            $mail->addAddress($email);

            $link = "https://SysCampus.com/enviar_e-mail/redefinir.php?token=" . $token;

            $mail->isHTML(true);
            $mail->Subject = 'Recuperacao de Senha';
            $mail->Body    = "Voce solicitou a redefinicao de senha.<br><br>Clique no link para redefinir: <a href='$link'>$link</a><br><br>Este link expira em 1 hora.";

            $mail->send();
            echo "E-mail de recuperação enviado com sucesso!";
        } catch (Exception $e) {
            echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        }
    } else {
        echo "E-mail enviado se cadastrado em nosso sistema."; // Prática de segurança
    }
}
?>