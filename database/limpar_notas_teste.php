<?php

/**
 * Limpa TODAS as notas lancadas, feedbacks de avaliador e resultados
 * publicados do banco - uso pontual, uma vez, ao preparar producao pra
 * comecar a avaliacao "de verdade" depois da fase de testes (Fase 19).
 * Nao apaga inscricoes/submissoes/designacoes de avaliador - so' o que
 * foi produzido durante a avaliacao em si.
 *
 * Tabelas limpas (mesmo raciocinio de database/excluir_usuario.php: as
 * duas primeiras so' existem em funcao de uma nota lancada; as duas
 * ultimas sao resultados PUBLICADOS, calculados a partir das notas -
 * ficariam desatualizados/incoerentes se as notas fossem apagadas e os
 * resultados publicados nao):
 *   notas_lancadas, feedback_submissao, resultados_etapa, resultados_trilha
 *
 * NAO apaga: submissoes (conteudo enviado pelas equipes), equipes,
 * participantes, avaliador_designacoes (quem esta escalado pra avaliar
 * o que - continua valendo, os avaliadores so' vao lancar notas novas).
 *
 * Uso:
 *   php database/limpar_notas_teste.php                (dry-run - so mostra as contagens)
 *   php database/limpar_notas_teste.php --confirmar     (aplica de verdade)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;

$confirmar = in_array('--confirmar', $argv, true);

$pdo = Database::conexao();

// Ordem importa: resultados_trilha/resultados_etapa sao "derivados" das
// notas (publicados manualmente depois de calculados) - limpos primeiro,
// depois o dado bruto (notas_lancadas/feedback_submissao).
$tabelas = ['resultados_trilha', 'resultados_etapa', 'notas_lancadas', 'feedback_submissao'];

function contarLinhas($pdo, $tabela)
{
    return (int) $pdo->query("SELECT COUNT(*) AS total FROM `$tabela`")->fetch()['total'];
}

echo "Limpeza de notas/resultados de teste\n";
echo str_repeat('-', 60) . "\n";

$contagens = [];

foreach ($tabelas as $tabela) {
    $contagens[$tabela] = contarLinhas($pdo, $tabela);
    echo "  - $tabela: {$contagens[$tabela]} linha(s)\n";
}

$total = array_sum($contagens);

if ($total === 0) {
    echo "\nNada pra limpar - todas as tabelas ja estao vazias.\n";
    exit;
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($tabelas as $tabela) {
        $pdo->exec("DELETE FROM `$tabela`");
    }

    Auditoria::registrar('limpar_notas_teste', 'notas_lancadas', null, $contagens, null, 'Limpeza de notas/resultados de teste via CLI (database/limpar_notas_teste.php), antes do inicio da avaliacao real');

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "\nErro ao limpar: " . $e->getMessage() . "\nNada foi alterado (transacao revertida).\n";
    exit(1);
}

echo "\nLimpeza concluida:\n";
foreach ($contagens as $tabela => $qtd) {
    echo "  - $tabela: $qtd linha(s) removida(s)\n";
}
