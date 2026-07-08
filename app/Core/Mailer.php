<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    private static $config;

    public static function enviar($destinatarioEmail, $assunto, $corpoHtml)
    {
        $config = self::config();

        if ($config['user'] === '' || $config['pass'] === '') {
            return ['sucesso' => false, 'erro' => 'SMTP nao configurado neste ambiente.'];
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->Port = $config['port'];
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Username = $config['user'];
            $mail->Password = $config['pass'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($destinatarioEmail);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $corpoHtml;

            $mail->send();

            return ['sucesso' => true, 'erro' => null];
        } catch (PHPMailerException $e) {
            return ['sucesso' => false, 'erro' => $mail->ErrorInfo];
        }
    }

    private static function config()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/smtp.php';
        }

        return self::$config;
    }
}
