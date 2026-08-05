<?php

/**
 * Fase 25 (#2/#3): limpa notas lancadas (e feedback de submissao associado)
 * de um avaliador especifico - mesmo raciocinio de
 * database/limpar_notas_teste.php (nota sem feedback ou feedback sem nota
 * orfao nao faz sentido ficar pra tras), mas aqui escopado a UM avaliador,
 * com filtro opcional por etapa e/ou por equipe.
 *
 * Escopo determinado pelos argumentos:
 *   --email so'                        -> todas as notas do avaliador, em TODAS as etapas
 *   --email + --etapa_id                -> so' as notas do avaliador nessa etapa
 *   --email + --etapa_id + --equipe     -> so' as notas do avaliador para a submissao dessa equipe nessa etapa
 *
 * Nao mexe em resultados_etapa/resultados_trilha - se alguma etapa
 * atingida ja tiver resultado publicado, o dry-run avisa que sera'
 * necessario "Reabrir etapa" manualmente depois (tela Resultados), se o
 * ranking precisar refletir a mudanca.
 *
 * Uso:
 *   php database/limpar_notas_avaliador.php --email="avaliador@exemplo.com"
 *   php database/limpar_notas_avaliador.php --email="avaliador@exemplo.com" --etapa_id=5
 *   php database/limpar_notas_avaliador.php --email="avaliador@exemplo.com" --etapa_id=5 --equipe="KIP Tecnologia"
 *   (adicione --confirmar em qualquer uma das formas acima para aplicar de verdade)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\EquipeRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\UsuarioRepository;

function lerArgumento($argv, $nome)
{
    foreach ($argv as $arg) {
        if (strpos($arg, "--{$nome}=") === 0) {
            return substr($arg, strlen("--{$nome}="));
        }
    }

    return null;
}

$email = lerArgumento($argv, 'email');
$etapaId = lerArgumento($argv, 'etapa_id');
$nomeEquipe = lerArgumento($argv, 'equipe');
$confirmar = in_array('--confirmar', $argv, true);

if ($email === null || $email === '') {
    echo "Uso: php database/limpar_notas_avaliador.php --email=\"avaliador@exemplo.com\" [--etapa_id=N] [--equipe=\"Nome\"]\n";
    exit(1);
}

if ($nomeEquipe !== null && $etapaId === null) {
    echo "Informe --etapa_id junto com --equipe (precisa saber em qual etapa esta' a submissao da equipe).\n";
    exit(1);
}

$etapaId = $etapaId !== null ? (int) $etapaId : null;

$usuarios = new UsuarioRepository();
$usuario = $usuarios->buscarPorEmail($email);

if ($usuario === null) {
    echo "Nenhum usuário encontrado com o e-mail \"{$email}\".\n";
    exit(1);
}

$submissaoId = null;

if ($nomeEquipe !== null) {
    $equipes = new EquipeRepository();
    $equipe = $equipes->buscarPorNome($nomeEquipe);

    if ($equipe === null) {
        $semelhantes = $equipes->listarSemelhantesPorNome($nomeEquipe);

        if (empty($semelhantes)) {
            echo "Nenhuma equipe encontrada com o nome \"{$nomeEquipe}\".\n";
            exit(1);
        }

        echo "Nenhuma equipe com nome EXATO \"{$nomeEquipe}\" - equipes parecidas encontradas:\n";
        foreach ($semelhantes as $item) {
            echo "  - \"{$item['nome_equipe']}\" (id {$item['id']})\n";
        }
        echo "\nRode de novo com o nome exato de uma das opções acima.\n";
        exit(1);
    }

    $submissao = (new SubmissaoRepository())->buscarPorEquipeEEtapa($equipe['id'], $etapaId);

    if ($submissao === null) {
        echo "A equipe \"{$equipe['nome_equipe']}\" não tem submissão na etapa_id {$etapaId}.\n";
        exit(1);
    }

    $submissaoId = (int) $submissao['id'];
}

echo "Avaliador: {$usuario['nome']} <{$usuario['email']}> (usuario_id {$usuario['id']})\n";
if ($submissaoId !== null) {
    echo "Escopo: submissão da equipe \"{$nomeEquipe}\" na etapa_id {$etapaId} (submissao_id {$submissaoId})\n";
} elseif ($etapaId !== null) {
    echo "Escopo: todas as notas do avaliador na etapa_id {$etapaId}\n";
} else {
    echo "Escopo: TODAS as notas do avaliador, em todas as etapas\n";
}
echo str_repeat('-', 70) . "\n";

$pdo = Database::conexao();

$sql = "SELECT s.etapa_id, e.nome AS etapa_nome, t.nome AS trilha_nome,
               COUNT(DISTINCT n.id) AS total_notas, COUNT(DISTINCT f.id) AS total_feedbacks
        FROM submissoes s
        INNER JOIN etapas e ON e.id = s.etapa_id
        INNER JOIN trilhas t ON t.id = e.trilha_id
        LEFT JOIN notas_lancadas n ON n.submissao_id = s.id AND n.usuario_id = :usuario_id
        LEFT JOIN feedback_submissao f ON f.submissao_id = s.id AND f.usuario_id = :usuario_id
        WHERE (n.id IS NOT NULL OR f.id IS NOT NULL)";
$params = ['usuario_id' => $usuario['id']];

if ($submissaoId !== null) {
    $sql .= ' AND s.id = :submissao_id';
    $params['submissao_id'] = $submissaoId;
} elseif ($etapaId !== null) {
    $sql .= ' AND s.etapa_id = :etapa_id';
    $params['etapa_id'] = $etapaId;
}

$sql .= ' GROUP BY s.etapa_id, e.nome, t.nome ORDER BY t.nome ASC, e.nome ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$porEtapa = $stmt->fetchAll();

if (empty($porEtapa)) {
    echo "Nenhuma nota/feedback deste avaliador encontrado neste escopo. Nada a fazer.\n";
    exit;
}

$resultadosEtapa = new ResultadoEtapaRepository();
$etapaIdsAfetadas = [];

foreach ($porEtapa as $linha) {
    echo "  - \"{$linha['trilha_nome']}\" / \"{$linha['etapa_nome']}\" (etapa_id {$linha['etapa_id']}): {$linha['total_notas']} nota(s), {$linha['total_feedbacks']} feedback(s)\n";

    if ($resultadosEtapa->jaPublicado((int) $linha['etapa_id'])) {
        echo "    [ATENÇÃO] esta etapa já tem resultado PUBLICADO - o ranking não vai refletir esta mudança até você usar \"Reabrir etapa\" manualmente.\n";
    }

    $etapaIdsAfetadas[] = (int) $linha['etapa_id'];
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$sqlDeleteNotas = 'DELETE FROM notas_lancadas WHERE usuario_id = :usuario_id';
$sqlDeleteFeedback = 'DELETE FROM feedback_submissao WHERE usuario_id = :usuario_id';
$paramsDelete = ['usuario_id' => $usuario['id']];

if ($submissaoId !== null) {
    $sqlDeleteNotas .= ' AND submissao_id = :submissao_id';
    $sqlDeleteFeedback .= ' AND submissao_id = :submissao_id';
    $paramsDelete['submissao_id'] = $submissaoId;
} elseif ($etapaId !== null) {
    $sqlDeleteNotas .= ' AND submissao_id IN (SELECT id FROM submissoes WHERE etapa_id = :etapa_id)';
    $sqlDeleteFeedback .= ' AND submissao_id IN (SELECT id FROM submissoes WHERE etapa_id = :etapa_id)';
    $paramsDelete['etapa_id'] = $etapaId;
}

try {
    $pdo->beginTransaction();

    $stmtNotas = $pdo->prepare($sqlDeleteNotas);
    $stmtNotas->execute($paramsDelete);
    $notasApagadas = $stmtNotas->rowCount();

    $stmtFeedback = $pdo->prepare($sqlDeleteFeedback);
    $stmtFeedback->execute($paramsDelete);
    $feedbacksApagados = $stmtFeedback->rowCount();

    Auditoria::registrar('limpar_notas_avaliador', 'notas_lancadas', $usuario['id'], null, [
        'usuario_id' => $usuario['id'],
        'email' => $usuario['email'],
        'etapa_id' => $etapaId,
        'submissao_id' => $submissaoId,
        'etapas_afetadas' => $etapaIdsAfetadas,
        'notas_apagadas' => $notasApagadas,
        'feedbacks_apagados' => $feedbacksApagados,
    ], 'Limpeza de notas de um avaliador via CLI (database/limpar_notas_avaliador.php)');

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "\nErro ao limpar: " . $e->getMessage() . "\nNada foi alterado (transação revertida).\n";
    exit(1);
}

echo "\nLimpeza concluída: {$notasApagadas} nota(s) e {$feedbacksApagados} feedback(s) apagados.\n";
