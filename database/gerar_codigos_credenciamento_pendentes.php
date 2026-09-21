<?php

/**
 * Preenche evento_inscricoes.codigo_credenciamento (Fase 42) em toda linha
 * que ainda esta' com o valor NULL - caso das inscricoes gravadas ANTES
 * desta fase existir (migration 129 so' adiciona a coluna, sem backfill em
 * SQL de proposito: RAND()/UUID() do MySQL nao sao CSPRNG, e a mesma coluna
 * vai servir de credencial de credenciamento presencial na Fase 43 - toda
 * geracao precisa vir da mesma fonte de entropia).
 *
 * Chama EventoInscricaoRepository::gerarCodigoCredenciamentoUnico() - o
 * MESMO metodo usado por inscrever() para inscricao nova - sem reimplementar
 * geracao nem checagem de unicidade aqui.
 *
 * Idempotente: so' afeta linhas com codigo_credenciamento IS NULL, nunca
 * sobrescreve um codigo ja gerado. Reexecutar e' seguro.
 *
 * Uso:
 *   php database/gerar_codigos_credenciamento_pendentes.php            (dry-run)
 *   php database/gerar_codigos_credenciamento_pendentes.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\EventoInscricaoRepository;

$confirmar = in_array('--confirmar', $argv, true);

$pdo = Database::conexao();
$repositorio = new EventoInscricaoRepository();

$pendentes = $pdo->query(
    'SELECT id, evento_id, usuario_id FROM evento_inscricoes WHERE codigo_credenciamento IS NULL ORDER BY id ASC'
)->fetchAll();

if (empty($pendentes)) {
    echo "Nenhuma inscricao pendente - todas ja tem codigo_credenciamento.\n";
    exit;
}

echo "Inscricoes pendentes de codigo_credenciamento: " . count($pendentes) . "\n";
foreach ($pendentes as $inscricao) {
    echo "  id {$inscricao['id']} (evento {$inscricao['evento_id']}, usuario {$inscricao['usuario_id']})\n";
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$stmt = $pdo->prepare('UPDATE evento_inscricoes SET codigo_credenciamento = :codigo WHERE id = :id');

foreach ($pendentes as $inscricao) {
    $codigo = $repositorio->gerarCodigoCredenciamentoUnico();

    $stmt->execute(['codigo' => $codigo, 'id' => $inscricao['id']]);

    Auditoria::registrar(
        'gerar_codigo_credenciamento',
        'evento_inscricoes',
        $inscricao['id'],
        ['codigo_credenciamento' => null],
        ['codigo_credenciamento' => $codigo],
        'Codigo de credenciamento gerado retroativamente via CLI (database/gerar_codigos_credenciamento_pendentes.php)'
    );
}

echo "\n" . count($pendentes) . " inscricao(oes) atualizada(s) com codigo_credenciamento.\n";
