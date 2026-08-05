<?php

/**
 * Fase 25 (#1): atribui numero_sigilo_etapa (SubmissaoRepository::
 * garantirNumerosSigilo()) a todas as submissoes de etapas com
 * modo_sigilo = 'cego' que ainda nao tem um numero - substitui a
 * numeracao recalculada a cada requisicao (posicao em listarPorEtapa(),
 * instavel se alguma submissao da etapa fosse removida depois) por um
 * numero persistido, atribuido uma unica vez, embaralhado (nao segue a
 * ordem real de submissao).
 *
 * Pensado pra rodar como parte do deploy da Fase 25, com o sistema em
 * modo de manutencao (database/desativar_sistema.php) - assim o momento
 * exato da atribuicao e' controlado pelo operador, e nao depende de qual
 * avaliador carregar a tela primeiro depois do deploy.
 *
 * Uso:
 *   php database/atribuir_numeros_sigilo_etapa.php                (dry-run)
 *   php database/atribuir_numeros_sigilo_etapa.php --confirmar    (aplica de verdade)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\SubmissaoRepository;

$confirmar = in_array('--confirmar', $argv, true);

$pdo = Database::conexao();

$stmt = $pdo->query(
    "SELECT e.id, e.nome AS etapa_nome, t.nome AS trilha_nome,
            COUNT(s.id) AS total_sem_numero
     FROM etapas e
     INNER JOIN trilhas t ON t.id = e.trilha_id
     INNER JOIN submissoes s ON s.etapa_id = e.id AND s.numero_sigilo_etapa IS NULL
     WHERE e.modo_sigilo = 'cego'
     GROUP BY e.id, e.nome, t.nome
     ORDER BY t.nome ASC, e.nome ASC"
);
$etapas = $stmt->fetchAll();

echo "Atribuicao de numero_sigilo_etapa (sigilo cego)\n";
echo str_repeat('-', 70) . "\n";

if (empty($etapas)) {
    echo "Nenhuma etapa com sigilo cego tem submissao sem numero. Nada a fazer.\n";
    exit;
}

foreach ($etapas as $etapa) {
    echo "  - \"{$etapa['trilha_nome']}\" / \"{$etapa['etapa_nome']}\" (etapa_id {$etapa['id']}): {$etapa['total_sem_numero']} submissao(oes) sem numero\n";
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Os numeros sao atribuidos em ordem embaralhada (nao seguem a ordem de envio).\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$submissoes = new SubmissaoRepository();
$totalAtribuido = [];

try {
    $pdo->beginTransaction();

    foreach ($etapas as $etapa) {
        $qtd = $submissoes->garantirNumerosSigilo((int) $etapa['id']);
        $totalAtribuido[$etapa['etapa_nome']] = $qtd;
    }

    Auditoria::registrar('atribuir_numeros_sigilo', 'submissoes', null, null, $totalAtribuido, 'Backfill de numero_sigilo_etapa via CLI (database/atribuir_numeros_sigilo_etapa.php), Fase 25');

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "\nErro ao atribuir: " . $e->getMessage() . "\nNada foi alterado (transacao revertida).\n";
    exit(1);
}

echo "\nConcluido:\n";
foreach ($totalAtribuido as $etapaNome => $qtd) {
    echo "  - \"{$etapaNome}\": {$qtd} numero(s) atribuido(s)\n";
}
