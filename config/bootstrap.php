<?php

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', $config['env'] === 'local' ? '1' : '0');

date_default_timezone_set($config['timezone']);

session_save_path(__DIR__ . '/../storage/sessions');
session_start();

set_exception_handler(function (\Throwable $e) use ($config) {
    http_response_code(500);
    \App\Core\Auditoria::registrar(
        'erro_sistema',
        'sistema',
        null,
        null,
        null,
        $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine()
    );

    if ($config['env'] === 'local') {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        require __DIR__ . '/../app/Views/erro/500.php';
    }
});
