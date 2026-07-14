<?php

/**
 * Define/troca o e-mail de um participante ja cadastrado - uso exclusivo de
 * setup de ambiente de teste (ex.: dar um e-mail de teste a um participante
 * importado sem e-mail, ou trocar pra bater com um usuario de teste ja
 * cadastrado na tela Usuarios, e assim poder logar como ele).
 *
 * Nao existe tela nenhuma (admin, lider ou autoedicao) que altere o e-mail
 * de um participante, de proposito - so' este script, fora da aplicacao.
 * Depois de rodar, use database/liberar_acesso_teste.php pra vincular esse
 * e-mail a um usuario de login.
 *
 * Uso (identifique o participante por id OU por nome exato - se o nome
 * bater com mais de um participante, o script lista os ids e para):
 *   php database/definir_email_teste.php --participante-id=42 --email=fulano@teste.com
 *   php database/definir_email_teste.php --nome="Fulano da Silva" --email=fulano@teste.com
 *   ... --confirmar (em qualquer uma das duas formas acima, pra aplicar de verdade)
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\ParticipanteRepository;
use App\Repositories\EquipeRepository;

$confirmar = in_array('--confirmar', $argv, true);
$participanteId = null;
$nome = null;
$email = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--participante-id=') === 0) {
        $participanteId = (int) substr($arg, strlen('--participante-id='));
    } elseif (strpos($arg, '--nome=') === 0) {
        $nome = substr($arg, strlen('--nome='));
    } elseif (strpos($arg, '--email=') === 0) {
        $email = substr($arg, strlen('--email='));
    }
}

if ($email === null || $email === '' || ($participanteId === null && ($nome === null || $nome === ''))) {
    echo "Uso: php database/definir_email_teste.php --participante-id=42 --email=fulano@teste.com [--confirmar]\n";
    echo "  ou: php database/definir_email_teste.php --nome=\"Fulano da Silva\" --email=fulano@teste.com [--confirmar]\n";
    exit(1);
}

$participantes = new ParticipanteRepository();
$equipes = new EquipeRepository();

if ($participanteId !== null) {
    $participante = $participantes->buscarPorId($participanteId);

    if ($participante === null) {
        echo "Nenhum participante encontrado com id $participanteId.\n";
        exit(1);
    }
} else {
    $encontrados = $participantes->buscarTodosPorNome($nome);

    if (empty($encontrados)) {
        echo "Nenhum participante encontrado com o nome \"$nome\".\n";
        exit(1);
    }

    if (count($encontrados) > 1) {
        echo "Mais de um participante com o nome \"$nome\" - rode de novo com --participante-id= usando um destes ids:\n";
        foreach ($encontrados as $item) {
            $equipeItem = $equipes->buscarPorParticipante($item['id']);
            $nomeEquipe = $equipeItem !== null ? $equipeItem['nome_equipe'] : 'sem equipe';
            echo "  id {$item['id']} - e-mail atual: " . ($item['email'] !== null ? $item['email'] : '(nenhum)') . " - equipe: $nomeEquipe\n";
        }
        exit(1);
    }

    $participante = $encontrados[0];
}

$equipe = $equipes->buscarPorParticipante($participante['id']);

echo "Participante: {$participante['nome']} (id {$participante['id']})\n";
echo "E-mail atual: " . ($participante['email'] !== null ? $participante['email'] : '(nenhum)') . "\n";
echo "Novo e-mail: $email\n";
echo $equipe !== null ? "Equipe: {$equipe['nome_equipe']}\n" : "Aviso: este participante ainda nao tem equipe vinculada.\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$participantes->atualizarEmail($participante['id'], $email);

echo "\nE-mail atualizado. Se ainda nao existir um usuario de login com este e-mail, cadastre um na tela Usuarios\n";
echo "(perfil \"participante\", senha definida manualmente) e depois rode:\n";
echo "  php database/liberar_acesso_teste.php --email=$email --confirmar\n";
