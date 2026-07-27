<?php

/**
 * Fase 21: libera acesso retroativamente aos integrantes de equipe cujo
 * vinculo (equipe_participante) ja esta com status_homologacao='homologado'
 * mas nunca passou pela tela de homologacao - caso das ~380 linhas
 * importadas direto no banco antes da abertura da Fase 21, que por isso
 * nunca dispararam AcessoParticipanteService::liberarAcesso() (so'
 * HomologacaoController::homologarUmVinculo() chama esse metodo, e essas
 * linhas ja nao aparecem mais como pendentes).
 *
 * A condicao "up.id IS NULL" na consulta abaixo torna o script idempotente:
 * quem ja foi liberado (tem linha em usuario_participante) nunca aparece de
 * novo, mesmo rodando o script varias vezes.
 *
 * Participantes sem e-mail cadastrado (alguns inscritos nao informaram
 * e-mail) sao listados a parte no relatorio - liberarAcesso() nao cria conta
 * sem e-mail, entao essas pessoas precisam ser tratadas manualmente depois.
 *
 * ATENCAO (mesmo problema ja resolvido em liberar_acesso_auditoria.php):
 * rodando via CLI nao ha $_SERVER['HTTP_HOST'], entao urlAbsoluta() (chamada
 * dentro de liberarAcesso()) cairia em "localhost" no link do e-mail. Por
 * isso --dominio e' obrigatorio junto com --confirmar - o script popula
 * $_SERVER['HTTPS']/$_SERVER['HTTP_HOST'] a partir dele antes de liberar.
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o relatorio, sem
 * gravar nada nem enviar e-mail nenhum. Para gravar de verdade:
 *   php liberar_acesso_retroativo.php --confirmar --dominio=https://npi.tjrr.jus.br
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\ParticipanteRepository;
use App\Services\AcessoParticipanteService;

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
$dominio = lerArgumento($argv, 'dominio');

if ($confirmar && $dominio === null) {
    echo "Uso: php liberar_acesso_retroativo.php --confirmar --dominio=https://npi.tjrr.jus.br\n";
    echo "  (--dominio e' obrigatorio junto com --confirmar, para montar o link do e-mail corretamente)\n";
    exit(1);
}

$pdo = Database::conexao();
$stmt = $pdo->query(
    "SELECT ep.id AS vinculo_id, ep.participante_id, e.trilha_id, e.nome_equipe
     FROM equipe_participante ep
     INNER JOIN equipes e ON e.id = ep.equipe_id
     LEFT JOIN usuario_participante up ON up.participante_id = ep.participante_id
     WHERE ep.status_homologacao = 'homologado' AND up.id IS NULL
     ORDER BY e.nome_equipe ASC"
);
$pendentes = $stmt->fetchAll();

if (empty($pendentes)) {
    echo "Nenhum vinculo homologado pendente de liberacao de acesso encontrado.\n";
    exit(0);
}

$participantes = new ParticipanteRepository();

$comEmail = [];
$semEmail = [];

foreach ($pendentes as $pendente) {
    $participante = $participantes->buscarPorId($pendente['participante_id']);

    if ($participante === null) {
        continue;
    }

    $item = [
        'participante' => $participante,
        'trilha_id' => (int) $pendente['trilha_id'],
        'nome_equipe' => $pendente['nome_equipe'],
    ];

    if (!empty($participante['email'])) {
        $comEmail[] = $item;
    } else {
        $semEmail[] = $item;
    }
}

echo "Vinculos homologados sem acesso liberado: " . count($pendentes) . "\n";
echo "  Com e-mail cadastrado (serao liberados): " . count($comEmail) . "\n";
echo "  Sem e-mail cadastrado (nao podem ser liberados automaticamente): " . count($semEmail) . "\n";

if (!empty($semEmail)) {
    echo "\nParticipantes SEM e-mail (tratar manualmente depois):\n";
    foreach ($semEmail as $item) {
        echo "  - {$item['participante']['nome']} (id {$item['participante']['id']}) - equipe: {$item['nome_equipe']}\n";
    }
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado nem enviado.\n";
    echo "Para liberar de verdade, repita o comando com --confirmar --dominio=https://npi.tjrr.jus.br\n";
    exit(0);
}

$partesDominio = parse_url($dominio);

if ($partesDominio === false || empty($partesDominio['host'])) {
    echo "\nERRO: --dominio invalido: {$dominio}\n";
    exit(1);
}

$_SERVER['HTTPS'] = (isset($partesDominio['scheme']) && $partesDominio['scheme'] === 'https') ? 'on' : 'off';
$_SERVER['HTTP_HOST'] = $partesDominio['host'] . (isset($partesDominio['port']) ? ':' . $partesDominio['port'] : '');

$servico = new AcessoParticipanteService();
$sucesso = 0;
$erros = [];

foreach ($comEmail as $item) {
    try {
        $servico->liberarAcesso($item['participante'], $item['trilha_id'], $item['nome_equipe']);
        $sucesso++;
    } catch (\Exception $e) {
        $erros[] = $item['participante']['nome'] . ' (id ' . $item['participante']['id'] . '): ' . $e->getMessage();
    }
}

echo "\nAcesso liberado com sucesso para {$sucesso} participante(s).\n";

if (!empty($erros)) {
    echo count($erros) . " falha(s) - conferir manualmente:\n";
    foreach ($erros as $erro) {
        echo "  - {$erro}\n";
    }
}
