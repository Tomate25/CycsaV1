<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables (from .env.local if exists, else .env)
$envPath = __DIR__ . '/../.env';
if (file_exists(__DIR__ . '/../.env.local')) {
    $envPath = __DIR__ . '/../.env.local';
}

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1], " \"'");
        }
    }
}

$mail = new PHPMailer(true);

try {
    // Enable SMTP Debugging
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->CharSet = 'UTF-8';
    
    $remitenteCorreo = $_ENV['MAIL_FROM'] ?? 'noreply@cycsa.com';
    $remitenteNombre = $_ENV['APP_NAME'] ?? 'CYCSA';
    $mail->setFrom($remitenteCorreo, $remitenteNombre);
    $mail->addAddress('destinatario@prueba.com');
    
    $mail->isHTML(true);
    $mail->Subject = 'Prueba de Diagnostico SMTP - CYCSA';
    $mail->Body    = '<h1>Prueba</h1><p>Esta es una prueba de envio de correo.</p>';

    $mailHost = $_ENV['MAIL_HOST'] ?? '';
    echo "MAIL_HOST: '$mailHost'\n";
    echo "MAIL_PORT: '" . ($_ENV['MAIL_PORT'] ?? '') . "'\n";
    echo "MAIL_USER: '" . ($_ENV['MAIL_USER'] ?? '') . "'\n";
    echo "MAIL_SECURE: '" . ($_ENV['MAIL_SECURE'] ?? '') . "'\n";

    if (!empty($mailHost)) {
        $mail->isSMTP();
        $mail->Host       = $mailHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USER'] ?? '';
        $mail->Password   = $_ENV['MAIL_PASS'] ?? '';
        
        $seguridad = strtolower($_ENV['MAIL_SECURE'] ?? '');
        if ($seguridad === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $_ENV['MAIL_PORT'] ?? 465;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
        }
    } else {
        echo "Usando isMail()\n";
        $mail->isMail();
    }

    echo "Intentando enviar correo...\n";
    if ($mail->send()) {
        echo "¡Correo enviado con exito!\n";
    } else {
        echo "Fallo al enviar correo.\n";
    }
} catch (\Throwable $e) {
    echo "Excepcion atrapada: " . $e->getMessage() . "\n";
    echo "Info de error PHPMailer: " . $mail->ErrorInfo . "\n";
}
