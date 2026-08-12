<?php

/**
 * Fase 29 (Bug 1): mapeia, em todo o banco, os padroes que explicam
 * integrantes indicados pelo lider que nunca receberam o convite de acesso.
 * So' leitura - nao altera nada. Uso:
 *   php database/investigar_convites_pendentes.php
 *
 * Contexto (achado na investigacao dos casos reais kellyvinente@gmail.com,
 * juliano.amm77@gmail.com, mqtheus2005@gmail.com):
 *
 *   Padrao A - conta "orfa": a pessoa se auto-cadastrou pela tela publica de
 *   Cadastro (cria uma linha em usuarios com senha propria) ANTES ou em vez
 *   de o lider conseguir cadastrar o e-mail dela na equipe. Quando o lider
 *   tenta, ParticipanteController::incluirEmailIntegrante() barra em
 *   silencio (usuarios->buscarPorEmail() encontra a conta auto-cadastrada) -
 *   e o erro nunca aparece na tela (participante/painel.php nao renderiza
 *   flash), entao o lider nem sabe que falhou. Resultado: participantes.email
 *   fica NULL pra sempre e a conta em usuarios fica orfa (sem usuario_participante,
 *   sem perfil).
 *
 *   Padrao B - convite nunca disparado: o e-mail foi cadastrado com sucesso
 *   e o vinculo ja foi homologado, mas ninguem clicou em "Convidar acesso"
 *   na tela de Homologacao (o unico jeito de disparar o convite quando o
 *   e-mail chega DEPOIS da homologacao - AcessoParticipanteService::
 *   liberarAcesso() so roda automatico na hora do homologar()).
 *
 *   Padrao C (informativo, nao e' bug) - e-mail cadastrado mas vinculo ainda
 *   nao homologado: quando homologar, o convite dispara sozinho.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$pdo = Database::conexao();

function linha($ch = '-')
{
    echo str_repeat($ch, 78) . "\n";
}

// ---------------------------------------------------------------------
// Padrao A.1 - contas em "usuarios" orfas (auto-cadastro nunca vinculado
// a nenhum participante e sem nenhum perfil atribuido).
// ---------------------------------------------------------------------
$orfaos = $pdo->query(
    "SELECT u.id, u.nome, u.email, u.status,
            (u.senha_hash IS NOT NULL) AS tem_senha,
            (u.google_id IS NOT NULL) AS tem_google,
            u.criado_em
     FROM usuarios u
     WHERE NOT EXISTS (SELECT 1 FROM usuario_participante up WHERE up.usuario_id = u.id)
       AND NOT EXISTS (SELECT 1 FROM usuario_perfil_concurso upc WHERE upc.usuario_id = u.id)
     ORDER BY u.criado_em ASC"
)->fetchAll();

// ---------------------------------------------------------------------
// Padrao A.2 - participantes sem e-mail cadastrado (candidatos a cruzar
// com a lista acima pelo nome).
// ---------------------------------------------------------------------
$semEmail = $pdo->query(
    "SELECT p.id AS participante_id, p.nome, e.nome_equipe, t.nome AS trilha,
            ep.papel, ep.status_homologacao
     FROM participantes p
     JOIN equipe_participante ep ON ep.participante_id = p.id
     JOIN equipes e ON e.id = ep.equipe_id
     JOIN trilhas t ON t.id = e.trilha_id
     WHERE p.email IS NULL
     ORDER BY e.nome_equipe ASC, ep.papel DESC"
)->fetchAll();

// ---------------------------------------------------------------------
// Padrao B - e-mail cadastrado + vinculo homologado, mas sem nenhum
// usuario vinculado ainda (convite nunca efetivado).
// ---------------------------------------------------------------------
$semConvite = $pdo->query(
    "SELECT p.id AS participante_id, p.nome, p.email, e.nome_equipe, t.nome AS trilha,
            ep.papel, ep.homologado_em
     FROM participantes p
     JOIN equipe_participante ep ON ep.participante_id = p.id
     JOIN equipes e ON e.id = ep.equipe_id
     JOIN trilhas t ON t.id = e.trilha_id
     WHERE p.email IS NOT NULL
       AND ep.status_homologacao = 'homologado'
       AND NOT EXISTS (SELECT 1 FROM usuario_participante up WHERE up.participante_id = p.id)
     ORDER BY ep.homologado_em ASC"
)->fetchAll();

// ---------------------------------------------------------------------
// Padrao C (informativo) - e-mail cadastrado, aguardando homologacao.
// ---------------------------------------------------------------------
$aguardandoHomologacao = $pdo->query(
    "SELECT p.id AS participante_id, p.nome, p.email, e.nome_equipe, t.nome AS trilha, ep.status_homologacao
     FROM participantes p
     JOIN equipe_participante ep ON ep.participante_id = p.id
     JOIN equipes e ON e.id = ep.equipe_id
     JOIN trilhas t ON t.id = e.trilha_id
     WHERE p.email IS NOT NULL
       AND ep.status_homologacao != 'homologado'
       AND NOT EXISTS (SELECT 1 FROM usuario_participante up WHERE up.participante_id = p.id)
     ORDER BY e.nome_equipe ASC"
)->fetchAll();

echo "\n";
linha('=');
echo "PADRAO A.1 - Contas orfas em 'usuarios' (auto-cadastro nunca vinculado)\n";
linha('=');
if (empty($orfaos)) {
    echo "Nenhuma encontrada.\n";
} else {
    foreach ($orfaos as $u) {
        printf(
            "id %-4d | %-30s | %-30s | status=%-9s | senha=%s | google=%s | criado_em=%s\n",
            $u['id'],
            mb_substr($u['nome'], 0, 30),
            mb_substr($u['email'], 0, 30),
            $u['status'],
            $u['tem_senha'] ? 'sim' : 'nao',
            $u['tem_google'] ? 'sim' : 'nao',
            $u['criado_em']
        );
    }
    echo "\nTotal: " . count($orfaos) . "\n";
}

echo "\n";
linha('=');
echo "PADRAO A.2 - Participantes sem e-mail cadastrado\n";
linha('=');
if (empty($semEmail)) {
    echo "Nenhum encontrado.\n";
} else {
    foreach ($semEmail as $p) {
        printf(
            "participante_id %-4d | %-30s | equipe: %-25s | trilha: %-20s | papel=%-10s | homologacao=%s\n",
            $p['participante_id'],
            mb_substr($p['nome'], 0, 30),
            mb_substr($p['nome_equipe'], 0, 25),
            mb_substr($p['trilha'], 0, 20),
            $p['papel'],
            $p['status_homologacao']
        );
    }
    echo "\nTotal: " . count($semEmail) . "\n";
}

// Cruzamento por nome (heuristica - so' um indicio, precisa confirmacao humana
// antes de qualquer vinculo manual).
echo "\n";
linha('-');
echo "Possiveis pares (nome de participante sem e-mail == nome de conta orfa)\n";
linha('-');
$pares = 0;
foreach ($semEmail as $p) {
    foreach ($orfaos as $u) {
        if (mb_strtolower(trim($p['nome'])) === mb_strtolower(trim($u['nome']))) {
            printf(
                "participante_id %d (%s, equipe \"%s\") <-> usuarios.id %d <%s>\n",
                $p['participante_id'],
                $p['nome'],
                $p['nome_equipe'],
                $u['id'],
                $u['email']
            );
            $pares++;
        }
    }
}
if ($pares === 0) {
    echo "Nenhum par encontrado por nome exato (pode haver casos com nome digitado diferente).\n";
}

echo "\n";
linha('=');
echo "PADRAO B - E-mail cadastrado + ja homologado, mas convite nunca efetivado\n";
linha('=');
if (empty($semConvite)) {
    echo "Nenhum encontrado.\n";
} else {
    foreach ($semConvite as $p) {
        printf(
            "participante_id %-4d | %-30s | %-30s | equipe: %-25s | trilha: %-20s | papel=%-10s | homologado_em=%s\n",
            $p['participante_id'],
            mb_substr($p['nome'], 0, 30),
            mb_substr($p['email'], 0, 30),
            mb_substr($p['nome_equipe'], 0, 25),
            mb_substr($p['trilha'], 0, 20),
            $p['papel'],
            $p['homologado_em']
        );
    }
    echo "\nTotal: " . count($semConvite) . "\n";
}

echo "\n";
linha('=');
echo "PADRAO C (informativo) - e-mail cadastrado, aguardando homologacao\n";
linha('=');
if (empty($aguardandoHomologacao)) {
    echo "Nenhum encontrado.\n";
} else {
    foreach ($aguardandoHomologacao as $p) {
        printf(
            "participante_id %-4d | %-30s | %-30s | equipe: %-25s | trilha: %-20s | status=%s\n",
            $p['participante_id'],
            mb_substr($p['nome'], 0, 30),
            mb_substr($p['email'], 0, 30),
            mb_substr($p['nome_equipe'], 0, 25),
            mb_substr($p['trilha'], 0, 20),
            $p['status_homologacao']
        );
    }
    echo "\nTotal: " . count($aguardandoHomologacao) . " (nao e' bug - resolve sozinho quando homologar)\n";
}

echo "\n";
linha('=');
printf(
    "RESUMO: %d contas orfas | %d participantes sem e-mail | %d convites travados (Padrao B) | %d aguardando homologacao (Padrao C)\n",
    count($orfaos),
    count($semEmail),
    count($semConvite),
    count($aguardandoHomologacao)
);
linha('=');
echo "\n";
