<?php

/**
 * Fase 21: remove submissoes duplicadas por id explicito - uso pontual para
 * corrigir equipe que enviou submissao mais de uma vez na mesma etapa (ver
 * database/diagnosticar_submissoes_equipe.php para levantar os ids antes de
 * rodar este script). Nunca escolhe sozinho qual manter/remover - o operador
 * decide os ids a partir do relatorio do diagnostico.
 *
 * PROTECAO (mesmo espirito de excluir_usuario.php): recusa remover qualquer
 * submissao que ja tenha linha em notas_lancadas, avaliador_designacoes,
 * resultados_etapa ou feedback_submissao - ja faz parte do processo de
 * avaliacao, remover poderia deixar designacao/nota/resultado orfao.
 * submissao_cpfs NAO bloqueia (e' so' o registro de CPF pra checagem de
 * duplicidade entre equipes) e e' removida junto.
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria
 * removido. Para remover de verdade:
 *   php remover_submissoes.php --ids=12,13 --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;

function lerArgumento($argv, $nome)
{
    foreach ($argv as $arg) {
        if (strpos($arg, "--{$nome}=") === 0) {
            return substr($arg, strlen("--{$nome}="));
        }
    }

    return null;
}

$confirmar = in_array('--confirmar', $argv, true);
$idsArgumento = lerArgumento($argv, 'ids');

if ($idsArgumento === null || $idsArgumento === '') {
    echo "Uso: php remover_submissoes.php --ids=12,13 [--confirmar]\n";
    exit(1);
}

$ids = array_filter(array_map('intval', explode(',', $idsArgumento)));

if (empty($ids)) {
    echo "Nenhum id valido em --ids.\n";
    exit(1);
}

$tabelasBloqueiam = ['notas_lancadas', 'avaliador_designacoes', 'resultados_etapa', 'feedback_submissao'];

$pdo = Database::conexao();
$podeRemover = [];
$bloqueadas = [];

foreach ($ids as $id) {
    $stmt = $pdo->prepare(
        'SELECT s.*, e.nome AS etapa_nome, eq.nome_equipe
         FROM submissoes s
         INNER JOIN etapas e ON e.id = s.etapa_id
         LEFT JOIN equipes eq ON eq.id = s.equipe_id
         WHERE s.id = :id'
    );
    $stmt->execute(['id' => $id]);
    $submissao = $stmt->fetch();

    if ($submissao === false) {
        echo "[ERRO] submissao id {$id} nao encontrada - pulando.\n";
        continue;
    }

    $motivoBloqueio = null;

    foreach ($tabelasBloqueiam as $tabela) {
        $stmtDep = $pdo->prepare("SELECT COUNT(*) FROM `{$tabela}` WHERE submissao_id = :id");
        $stmtDep->execute(['id' => $id]);

        if ((int) $stmtDep->fetchColumn() > 0) {
            $motivoBloqueio = $tabela;
            break;
        }
    }

    if ($motivoBloqueio !== null) {
        $bloqueadas[] = $submissao;
        echo "[BLOQUEADA] id {$id} - equipe \"{$submissao['nome_equipe']}\" - ja tem linha em {$motivoBloqueio}, ja faz parte do processo de avaliacao. NAO sera removida.\n";
        continue;
    }

    $podeRemover[] = $submissao;
    echo "[ok pra remover] id {$id} - equipe \"{$submissao['nome_equipe']}\" - etapa \"{$submissao['etapa_nome']}\" - criado_em {$submissao['criado_em']}\n";
}

if (empty($podeRemover)) {
    echo "\nNenhuma submissao elegivel para remocao.\n";
    exit(0);
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi removido.\n";
    echo "Para remover de verdade, repita o comando com --confirmar\n";
    exit(0);
}

foreach ($podeRemover as $submissao) {
    $id = (int) $submissao['id'];

    $pdo->beginTransaction();

    try {
        $pdo->prepare('DELETE FROM submissao_cpfs WHERE submissao_id = :id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM submissoes WHERE id = :id')->execute(['id' => $id]);

        Auditoria::registrar('remover', 'submissoes', $id, $submissao, null);

        $pdo->commit();
        echo "[removido] id {$id} - equipe \"{$submissao['nome_equipe']}\"\n";
    } catch (\Exception $e) {
        $pdo->rollBack();
        echo "[ERRO] falha ao remover id {$id}: " . $e->getMessage() . "\n";
    }
}
