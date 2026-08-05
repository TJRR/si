<?php

/**
 * Fase 25: liga o modo de manutencao (mesmo mecanismo do botao "Desativar
 * sistema" em Configuracoes) - bloqueia o sistema inteiro, exceto para
 * administradores, e desconecta qualquer outra sessao ja aberta na proxima
 * acao que ela tentar (ver App\Core\Router::despachar()).
 *
 * Pensado pra gatear deploys com seguranca: rodar isso ANTES de subir
 * arquivos/rodar migrations, e database/reativar_sistema.php --confirmar
 * depois de terminar. Funciona por acesso direto ao banco, sem depender de
 * sessao/login nem do proprio Router - serve de kill switch mesmo se algo
 * na camada web quebrar durante a atualizacao.
 *
 * Uso:
 *   php database/desativar_sistema.php                (dry-run - so mostra o status atual)
 *   php database/desativar_sistema.php --confirmar     (aplica de verdade)
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
echo '  - status atual: ' . ((int) $atual['sistema_desativado'] === 1 ? 'DESATIVADO (ja em manutencao)' : 'ativo') . "\n";

if ((int) $atual['sistema_desativado'] === 1) {
    echo "\nO sistema ja esta em modo de manutencao. Nada a fazer.\n";
    exit;
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Ao confirmar, o sistema fica indisponivel para todos os perfis exceto administrador,\n";
    echo "e qualquer sessao ja aberta de outro perfil e' encerrada na proxima acao que tentar.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$configuracoes->desativarSistema();

echo "\nSistema DESATIVADO. Apenas administradores conseguem acessar agora.\n";
echo "Rode 'php database/reativar_sistema.php --confirmar' ao terminar a atualizacao.\n";
