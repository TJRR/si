<?php

/**
 * Envia o e-mail de "acesso liberado" (AcessoParticipanteService::liberarAcesso)
 * para os vinculos equipe_participante que ja estao 'homologado' mas nunca
 * passaram pelo fluxo normal de homologacao (HomologacaoController), que e
 * quem dispara esse e-mail automaticamente. E o caso dos vinculos marcados
 * homologado por homologar_dados_historicos.php (importacao da edicao 2026,
 * cujo processo de inscricao ja tinha rodado fora do sistema).
 *
 * Script de execucao unica: futuras homologacoes normais ja disparam o
 * e-mail sozinhas, nao precisam passar por aqui.
 *
 * Por padrao roda em modo consulta (dry-run): so lista quem receberia
 * convite, sem enviar nada. Para enviar de verdade:
 *   php enviar_convites_homologados_historicos.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\AcessoParticipanteService;

$confirmar = in_array('--confirmar', $argv, true);

$pdo = Database::conexao();
$stmt = $pdo->prepare(
    "SELECT p.id, p.nome, p.email, e.nome_equipe, e.trilha_id
     FROM equipe_participante ep
     INNER JOIN participantes p ON p.id = ep.participante_id
     INNER JOIN equipes e ON e.id = ep.equipe_id
     LEFT JOIN usuario_participante up ON up.participante_id = p.id
     WHERE ep.status_homologacao = 'homologado' AND up.participante_id IS NULL
     ORDER BY e.nome_equipe ASC, p.nome ASC"
);
$stmt->execute();
$candidatos = $stmt->fetchAll();

if (empty($candidatos)) {
    echo "Nenhum vinculo homologado sem acesso encontrado.\n";
    exit;
}

echo count($candidatos) . " vinculo(s) homologado(s) sem acesso encontrado(s):\n";
foreach ($candidatos as $candidato) {
    $email = $candidato['email'] !== null && $candidato['email'] !== '' ? $candidato['email'] : '(sem e-mail cadastrado)';
    echo "- {$candidato['nome']} <{$email}> — equipe \"{$candidato['nome_equipe']}\"\n";
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nenhum e-mail foi enviado.\n";
    echo "Para enviar de verdade, rode: php enviar_convites_homologados_historicos.php --confirmar\n";
    exit;
}

echo "\nEnviando convites...\n";

$servico = new AcessoParticipanteService();
$enviados = 0;

foreach ($candidatos as $candidato) {
    if ($candidato['email'] === null || $candidato['email'] === '') {
        echo "- PULADO (sem e-mail cadastrado): {$candidato['nome']}\n";
        continue;
    }

    $servico->liberarAcesso($candidato, $candidato['trilha_id'], $candidato['nome_equipe']);
    $enviados++;
    echo "- Convite enviado: {$candidato['nome']} <{$candidato['email']}>\n";
}

echo "\n{$enviados} convite(s) enviado(s).\n";
