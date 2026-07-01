<?php

use App\Services\MailService;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    echo 'Composer autoload fehlt. Bitte composer install ausführen.';
    exit;
}
require $autoload;

spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = __DIR__ . '/' . lcfirst(str_replace('\\', '/', $class)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

header('Content-Type: text/plain; charset=UTF-8');

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 0;
    MailService::applySmtpTransport($mail);

    $from = (string) (getenv('MAIL_FROM_ADDRESS') ?: 'easytime@easydrivers.at');
    $mail->setFrom($from);
    $mail->addReplyTo($from);

    $toParam = trim((string) ($_GET['to'] ?? ''));
    if ($toParam !== '') {
        foreach (array_map('trim', explode(',', $toParam)) as $addr) {
            if ($addr !== '') {
                $mail->addAddress($addr);
            }
        }
    } else {
        $mail->addAddress('andreas.brachinger@sz-ybbs.ac.at');
        $mail->addAddress('bernhard.proksch@sz-ybbs.ac.at');
    }

    $mail->isHTML(true);
    $mail->Subject = 'Testmail über PHPMailer';
    $mail->Body = '<b>Hallo!</b><br>Das ist eine Testnachricht von EasyTime über PHPMailer';
    $mail->AltBody = 'Hallo! Das ist eine Testnachricht von EasyTime über PHPMailer mit SMTP Auth.';

    $mail->send();
    echo "Nachricht wurde erfolgreich gesendet!\n";
} catch (Exception $e) {
    http_response_code(500);
    echo 'Nachricht konnte nicht gesendet werden. Fehler: ' . $mail->ErrorInfo;
}
