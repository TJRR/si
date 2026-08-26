<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 35 (Parte C): a credencial passa a vir de credenciais_sistema
 * (cifrada, migration 115). O bloco de config/local.php continua aqui como
 * RESERVA, e nao por descuido:
 *
 * - na hora da atualizacao, producao continua funcionando pelo arquivo ate'
 *   as credenciais serem transferidas pelo migrar_credenciais.php;
 * - se a chave-mestra sumir ou o banco estiver fora do ar, a integracao
 *   volta a funcionar pelo arquivo em vez de simplesmente morrer;
 * - a reversao para a fase anterior funciona sozinha.
 *
 * Esta reserva sai numa fase futura, depois de confirmado em producao -
 * nunca na mesma fase que a introduz.
 *
 * O grupo so' e' aceito do banco quando esta COMPLETO ($obrigatorios): um
 * grupo pela metade (ex.: client_email gravado e private_key ilegivel
 * porque a chave-mestra mudou) e' pior que nenhum, porque tentaria
 * autenticar com credencial quebrada em vez de cair na reserva.
 */

$local = require __DIR__ . '/local.php';
$reserva = $local['google'];

try {
    $doBanco = (new \App\Repositories\CredencialSistemaRepository())->obterGrupo('google_oauth');
} catch (\Throwable $e) {
    // Banco fora do ar ou tabela ainda nao criada (migration 115 nao
    // aplicada): nunca derrubar a aplicacao por causa disso.
    $doBanco = [];
}

$obrigatorios = ['client_id', 'client_secret'];

foreach ($obrigatorios as $campo) {
    if (empty($doBanco[$campo])) {
        return $reserva;
    }
}

return array_merge($reserva, $doBanco);
