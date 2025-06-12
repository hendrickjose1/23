<?php
// config/mailer.php
require_once __DIR__ . '/../vendor/autoload.php';

function configurarMailer() {
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.morismetal.cl'; // Tu servidor SMTP
    $mail->SMTPAuth = true;
    $mail->Username = 'notificaciones@morismetal.cl'; // Tu correo
    $mail->Password = 'tu_contraseña_segura'; // Tu contraseña
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->setFrom('notificaciones@morismetal.cl', 'Morismetal');
    $mail->isHTML(true);

    return $mail;
}

function enviarCorreo($destinatario, $asunto, $cuerpo, $adjuntos = []) {
    $mail = configurarMailer();
    $mail->addAddress($destinatario);
    $mail->Subject = $asunto;
    $mail->Body = $cuerpo;

    foreach ($adjuntos as $adjunto) {
        $mail->addAttachment($adjunto['ruta'], $adjunto['nombre']);
    }

    if (!$mail->send()) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
    return true;
}