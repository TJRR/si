<?php

/**
 * Fase 25: desliga o modo de manutencao (mesmo mecanismo do botao "Reativar
 * sistema" em Configuracoes) - restabelece o acesso normal para todos os
 * perfis. Ver database/desativar_sistema.php para o contexto completo.
 *
 * Funciona por acesso direto ao banco, sem depender de sessao/login nem do
 * proprio Router - serve de kill switch mesmo se algo travar o administrador
 * fora da interface web durante a atualizacao.
 *
 * Uso:
 *   php database/reativar_sistema.php                (dry-run - so mostra o status atual)
 *   php database/reativar_sistema.php --confirmar     (aplica de verdade)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\ConfiguracaoSistemaRepository;

$confirmar = in_array('--confirmar', $argv, true);

$configuracoes = new ConfiguracaoSistemaRepository();
$atual = $configuracoes->buscar();

if ($atual === false) {
    echo "Erro: nao foi possivel ler configuracoes_sistema (migration 053 aplicada?).\n";
    exit(1);
}

if (!isset($atual['sistema_desativado'])) {
    echo "Erro: coluna sistema_desativado nao existe ainda. Rode 'php database/migrate.php' (migration 100) antes.\n";
    exit(1);
}

echo "Modo de manutencao\n";
echo str_repeat('-', 60) . "\n";
echo '  - status atual: ' . ((int) $atual['sistema_desativado'] === 1 ? 'desativado (em manutencao)' : 'ATIVO (ja funcionando normalmente)') . "\n";

if ((int) $atual['sistema_desativado'] === 0) {
    echo "\nO sistema ja esta ativo. Nada a fazer.\n";
    exit;
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Ao confirmar, o acesso normal volta para todos os perfis imediatamente.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$configuracoes->reativarSistema();

echo "\nSistema REATIVADO. Acesso normal restabelecido para todos os perfis.\n";
