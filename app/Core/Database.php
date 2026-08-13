<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class Database
{
    private static $instancia;

    public static function conexao()
    {
        if (self::$instancia === null) {
            $config = require __DIR__ . '/../../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['name']
            );

            self::$instancia = new \PDO($dsn, $config['user'], $config['pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            // Fase 29 (Tira-Duvidas): sem isso, CURRENT_TIMESTAMP/NOW() do
            // MySQL usa o fuso do proprio servidor de banco (normalmente
            // UTC), enquanto o PHP calcula "agora" em America/Boa_Vista
            // (UTC-4) - qualquer conta de "tempo decorrido desde X" (SLA de
            // duvidas, por exemplo) saia com 4h de erro sistematico. Usa a
            // funcao global config('timezone') (config/config.php, NAO o
            // $config local acima, que e' so' credencial de banco) direto
            // em vez de date_default_timezone_get(), pra funcionar igual em
            // request web e em script CLI que nao passe por
            // config/bootstrap.php.
            $fuso = new \DateTimeZone(config('timezone'));
            $offsetSegundos = $fuso->getOffset(new \DateTime('now', $fuso));
            $offsetFormatado = sprintf(
                '%+03d:%02d',
                intdiv($offsetSegundos, 3600),
                abs($offsetSegundos % 3600) / 60
            );
            self::$instancia->exec("SET time_zone = '" . $offsetFormatado . "'");
        }

        return self::$instancia;
    }
}
