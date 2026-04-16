<?php
require_once('src/PHPMailer.php');
require_once('src/SMTP.php');
require_once('src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!empty($_POST["nome"]) && !empty($_POST["email"]) && !empty($_POST["telefone"]) && isset($_POST["mensagem"])) {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $telefone = $_POST["telefone"];
    $mensagem = $_POST["mensagem"];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('E-mail inválido.'); window.history.back();</script>";
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'medsyncc@gmail.com';
        $mail->Password = 'rcdu jzij lotr akgk'; // Use variável de ambiente em produção!
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('medsyncc@gmail.com', 'Formulário do Site');
        $mail->addReplyTo($email, $nome);
        $mail->addAddress('medsyncc@gmail.com', 'Recebimento de Mensagens');

        $mail->isHTML(true);
        $mail->Subject = 'Nova mensagem de contato';
        $mail->Body    = "
            <strong>Nome:</strong> $nome<br>
            <strong>Email:</strong> $email<br>
            <strong>Telefone:</strong> $telefone<br>
            <strong>Mensagem:</strong><br>$mensagem
        ";
        $mail->AltBody = "Nome: $nome\nEmail: $email\nTelefone: $telefone\n\nMensagem:\n$mensagem";

        if ($mail->send()) {
            echo "<script>alert('Email enviado com sucesso!'); window.history.back();</script>";
        } else {
            echo "<script>alert('Falha no envio do email.'); window.history.back();</script>";
        }

    } catch (Exception $e) {
        echo "<script>alert('Erro ao enviar mensagem: {$mail->ErrorInfo}'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Todos os campos obrigatórios devem ser preenchidos.'); window.history.back();</script>";
}
