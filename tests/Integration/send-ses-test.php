<?php

// 1) Ative todos os erros na tela
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 2) Mensagem de início
//echo "[1] Script iniciado\n";

// 3) Carregue o autoloader
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload)) {
    die("[!] Não achei o autoload em $autoload\n");
}
require $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1) Substitua pelas suas credenciais SMTP da AWS
$smtpHost = 'email-smtp.us-east-1.amazonaws.com';
$smtpPort = 587;                // ou 465 se usar ssl
$smtpUser = 'AKIAVAVZ53YBCDCS4VVV';
$smtpPass = 'BCNsddU3IeXeOiXGcUk3ie0d7YYabzi9+gzgJ2Vdix6T';

// 2) Destino e remetente de teste
$fromEmail = 'ti@superestagios.com.br';
$fromName = 'Super Estágios';
$toEmail = 'ti.superest@gmail.com';

//echo "[3] Configurando PHPMailer\n";
$mail = new PHPMailer(true);

try {
    // Configurações SMTP
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS na porta 587
    $mail->Port = $smtpPort;
    $mail->SMTPDebug = 2; // debug no console

    // Remetente e Destinatário
//    echo "[4] Definindo remetente e destinatário\n";
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail);

    // Conteúdo
//    echo "[5] Definindo conteúdo\n";
    $mail->Subject = 'Testando envio de email via AWS SES';
    $mail->Body = 'Olá! Este é um teste de envio via AWS SES.';
    $mail->isHTML(false);

//    echo "[6] Enviando...\n";
    $mail->send();

    echo "✔ E-mail enviado com sucesso via AWS SES SMTP\n";
} catch (Exception $e) {
    echo "✖ Erro ao enviar: {$mail->ErrorInfo}\n";
}
