<?php

/**
 * Forca o CPF de um participante de volta para um valor invalido - uso
 * exclusivo de setup de ambiente de teste (ex.: reabrir o alerta "CPF
 * invalido" pra testar o sino de notificacoes de novo, depois de ja ter
 * corrigido o CPF pela tela).
 *
 * Uso (identifique o participante por id OU por nome exato - se o nome
 * bater com mais de um participante, o script lista os ids e para):
 *   php database/definir_cpf_teste.php --participante-id=42 --cpf=00000000000
 *   php database/definir_cpf_teste.php --nome="Leandro Torres" --cpf=00000000000
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
$cpf = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--participante-id=') === 0) {
        $participanteId = (int) substr($arg, strlen('--participante-id='));
    } elseif (strpos($arg, '--nome=') === 0) {
        $nome = substr($arg, strlen('--nome='));
    } elseif (strpos($arg, '--cpf=') === 0) {
        $cpf = substr($arg, strlen('--cpf='));
    }
}

if ($cpf === null || $cpf === '' || ($participanteId === null && ($nome === null || $nome === ''))) {
    echo "Uso: php database/definir_cpf_teste.php --participante-id=42 --cpf=00000000000 [--confirmar]\n";
    echo "  ou: php database/definir_cpf_teste.php --nome=\"Fulano da Silva\" --cpf=00000000000 [--confirmar]\n";
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
            echo "  id {$item['id']} - CPF atual: " . ($item['cpf'] !== null ? $item['cpf'] : '(nenhum)') . " - equipe: $nomeEquipe\n";
        }
        exit(1);
    }

    $participante = $encontrados[0];
}

echo "Participante: {$participante['nome']} (id {$participante['id']})\n";
echo "CPF atual: " . ($participante['cpf'] !== null ? $participante['cpf'] : '(nenhum)') . "\n";
echo "Novo CPF: $cpf\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$participantes->atualizarCpf($participante['id'], $cpf);

echo "\nCPF atualizado. O alerta \"CPF invalido\" (sino de notificacoes) e' recriado no proximo carregamento de\n";
echo "pagina desse participante (participante/index, meusDados etc.), via ParticipanteController::sincronizarAlertaCpf().\n";
