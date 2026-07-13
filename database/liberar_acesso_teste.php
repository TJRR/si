<?php

/**
 * Libera acesso de teste para UM participante especifico, pelo e-mail, SEM
 * enviar nenhum e-mail (nem tentativa) - diferente de
 * enviar_convites_homologados_historicos.php, que processa todos os vinculos
 * homologados sem acesso E dispara e-mail de verdade para cada um.
 *
 * Uso (so um --email por execucao):
 *   php database/liberar_acesso_teste.php --email=fulano@exemplo.com
 *   php database/liberar_acesso_teste.php --email=fulano@exemplo.com --confirmar
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito.
 * Precisa que o vinculo equipe_participante deste participante ja esteja
 * 'homologado' (senao nao faz sentido liberar acesso).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\EquipeRepository;
use App\Repositories\ParticipanteRepository;
use App\Services\AcessoParticipanteService;

$confirmar = in_array('--confirmar', $argv, true);
$email = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--email=') === 0) {
        $email = substr($arg, strlen('--email='));
    }
}

if ($email === null || $email === '') {
    echo "Uso: php database/liberar_acesso_teste.php --email=fulano@exemplo.com [--confirmar]\n";
    exit(1);
}

$participantes = new ParticipanteRepository();
$equipes = new EquipeRepository();

$participante = $participantes->buscarPorEmail($email);

if ($participante === null) {
    echo "Nenhum participante encontrado com o e-mail \"$email\".\n";
    exit(1);
}

$equipe = $equipes->buscarPorParticipante($participante['id']);

if ($equipe === null) {
    echo "Participante encontrado ({$participante['nome']}), mas sem equipe vinculada.\n";
    exit(1);
}

$vinculo = $equipes->buscarVinculo($equipe['id'], $participante['id']);

if ($vinculo === null || $vinculo['status_homologacao'] !== 'homologado') {
    echo "O vinculo deste participante com a equipe \"{$equipe['nome_equipe']}\" nao esta homologado "
        . "(status atual: " . ($vinculo['status_homologacao'] ?? 'sem vinculo') . "). Nada foi feito.\n";
    exit(1);
}

echo "Participante: {$participante['nome']} <{$email}>\n";
echo "Equipe: {$equipe['nome_equipe']}\n";
echo "Isso vai criar/aprovar a conta de usuario, atribuir o perfil 'participante' e vincular ao participante -- SEM enviar nenhum e-mail.\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, rode: php database/liberar_acesso_teste.php --email=$email --confirmar\n";
    exit;
}

(new AcessoParticipanteService())->liberarAcesso($participante, $equipe['trilha_id'], $equipe['nome_equipe'], false);

echo "\nAcesso liberado (sem e-mail enviado). O usuario ja pode entrar com Google usando este e-mail, ou você pode gerar uma senha para ele diretamente no banco se precisar de login por senha.\n";
