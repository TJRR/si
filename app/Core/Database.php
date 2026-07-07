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
        }

        return self::$instancia;
    }
}
