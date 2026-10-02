<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\ContatoConcursoRepository;
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

            $mail->setFrom($config['from_email'], self::nomeRemetente($config));
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

    /**
     * Nome do remetente: o "Nome do organizador para assinatura dos e-mails"
     * de Configuracoes, Contato, o mesmo da assinatura. Em branco, vale o da
     * configuracao de envio. Lido a cada envio, para a troca na tela valer
     * no envio seguinte.
     */
    private static function nomeRemetente(array $config)
    {
        $contato = (new ContatoConcursoRepository())->buscar();

        if ($contato !== null && isset($contato['nome_organizador_assinatura']) && trim($contato['nome_organizador_assinatura']) !== '') {
            return trim($contato['nome_organizador_assinatura']);
        }

        return isset($config['from_name']) ? $config['from_name'] : '';
    }

    private static function config()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/smtp.php';
        }

        return self::$config;
    }
}
