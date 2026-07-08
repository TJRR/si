<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Services\AuthService;

if ($argc < 5) {
    echo "Uso: php database/testar_login_google.php <google_id> <email> <nome> <email_verified 0|1>\n";
    exit(1);
}

$dadosGoogle = [
    'google_id' => $argv[1],
    'email' => $argv[2],
    'nome' => $argv[3],
    'email_verified' => $argv[4] === '1',
];

$resultado = (new AuthService())->resolverUsuarioGoogle($dadosGoogle);

echo "Entrada: " . var_export($dadosGoogle, true) . "\n";
echo "Resultado: " . var_export($resultado, true) . "\n";
