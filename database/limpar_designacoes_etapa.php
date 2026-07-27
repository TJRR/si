<?php

/**
 * Fase 21: zera as designacoes de avaliador (avaliador_designacoes) de uma
 * etapa inteira - uso excepcional para desfazer um sorteio de teste/rascunho
 * rodado em producao antes das submissoes reais existirem (designou
 * avaliadores, alguns ja sem perfil valido, a submissoes que na epoca ainda
 * nao tinham conteudo real).
 *
 * DesignacaoAdminController::remover() (a tela normal) recusa remover
 * designacao de origem 'sorteio' de proposito (Fase 17, Bug 3 - preserva a
 * lisura do sorteio aceito). Essa trava continua correta para o uso do
 * dia a dia; este script e' a excecao deliberada, fora da tela, para um
 * reset completo antes do sorteio de verdade.
 *
 * PROTECAO: se o avaliador de uma designacao ja lancou nota para a
 * submissao correspondente, essa designacao especifica NAO e' removida a
 * menos que --forcar-com-notas seja passado (mesmo padrao de
 * excluir_usuario.php).
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria
 * removido. Para remover de verdade:
 *   php limpar_designacoes_etapa.php --etapa-id=5 --confirmar
 *   php limpar_designacoes_etapa.php --etapa-id=5 --confirmar --forcar-com-notas
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\NotaLancadaRepository;

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
$forcarComNotas = in_array('--forcar-com-notas', $argv, true);
$etapaId = lerArgumento($argv, 'etapa-id');

if ($etapaId === null || $etapaId === '') {
    echo "Uso: php limpar_designacoes_etapa.php --etapa-id=5 [--confirmar] [--forcar-com-notas]\n";
    exit(1);
}

$etapaId = (int) $etapaId;

$pdo = Database::conexao();
$stmt = $pdo->prepare(
    'SELECT ad.id, ad.submissao_id, ad.usuario_id, ad.origem, u.nome AS usuario_nome,
            e.nome_equipe
     FROM avaliador_designacoes ad
     INNER JOIN submissoes s ON s.id = ad.submissao_id
     LEFT JOIN equipes e ON e.id = s.equipe_id
     INNER JOIN usuarios u ON u.id = ad.usuario_id
     WHERE s.etapa_id = :etapa_id
     ORDER BY e.nome_equipe ASC, u.nome ASC'
);
$stmt->execute(['etapa_id' => $etapaId]);
$designacoes = $stmt->fetchAll();

if (empty($designacoes)) {
    echo "Nenhuma designação encontrada para a etapa {$etapaId}.\n";
    exit(0);
}

$notas = new NotaLancadaRepository();
$podeRemover = [];
$bloqueadas = [];

foreach ($designacoes as $designacao) {
    $temNota = $notas->contarNotasPorUsuario($designacao['submissao_id'], $designacao['usuario_id']) > 0;

    if ($temNota && !$forcarComNotas) {
        $bloqueadas[] = $designacao;
    } else {
        $podeRemover[] = $designacao;
    }
}

echo count($designacoes) . " designação(ões) encontrada(s) na etapa {$etapaId}:\n";
foreach ($designacoes as $designacao) {
    $marca = in_array($designacao, $bloqueadas, true) ? ' [BLOQUEADA - ja tem nota lancada]' : '';
    echo "  - equipe \"{$designacao['nome_equipe']}\" - avaliador \"{$designacao['usuario_nome']}\" - origem: {$designacao['origem']}{$marca}\n";
}

echo "\nSerão removidas: " . count($podeRemover) . "\n";
echo "Bloqueadas (já têm nota lançada): " . count($bloqueadas) . "\n";

if (!empty($bloqueadas) && !$forcarComNotas) {
    echo "Para remover mesmo as bloqueadas, rode de novo com --forcar-com-notas (além de --confirmar).\n";
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi removido.\n";
    echo "Para remover de verdade, repita o comando com --confirmar\n";
    exit(0);
}

if (empty($podeRemover)) {
    echo "\nNada a remover.\n";
    exit(0);
}

$ids = array_column($podeRemover, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmtRemover = $pdo->prepare("DELETE FROM avaliador_designacoes WHERE id IN ({$placeholders})");
$stmtRemover->execute($ids);

Auditoria::registrar('limpar_designacoes_etapa', 'avaliador_designacoes', $etapaId, $podeRemover, null);

echo "\n" . count($ids) . " designação(ões) removida(s) da etapa {$etapaId}.\n";
