<?php

/**
 * Preenche evento_inscricoes.codigo_credenciamento nas linhas ainda
 * nulas, pelo mesmo gerador de EventoInscricaoRepository::inscrever(), sem
 * reimplementar geracao nem conferencia de unicidade. Nunca preencher por
 * SQL: ver Implantar.md, secao 13.7.
 *
 * Idempotente: so' afeta linhas sem codigo. Reexecutar e' seguro.
 *
 * Uso:
 *   php database/gerar_codigos_credenciamento_pendentes.php            (ensaio)
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
