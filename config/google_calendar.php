<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Credencial do grupo, lida do banco (cifrada), com reserva em
 * config/local.php. So' o grupo completo vem do banco; incompleto, vale a
 * reserva inteira. Ver Implantar.md, secao 13.6.
 */

$local = require __DIR__ . '/local.php';
$reserva = $local['google_service_account'];

try {
    $doBanco = (new \App\Repositories\CredencialSistemaRepository())->obterGrupo('google_service_account');
} catch (\Throwable $e) {
    // Falha de leitura do banco cai na reserva, sem derrubar a aplicacao.
    $doBanco = [];
}

$obrigatorios = ['client_email', 'private_key'];

foreach ($obrigatorios as $campo) {
    if (empty($doBanco[$campo])) {
        return $reserva;
    }
}

return array_merge($reserva, $doBanco);
