<?php

/**
 * Fase 17 (Bug 9): migra uma equipe de uma trilha para outra - caso pontual
 * deste concurso (comissao decidiu realocar equipes entre Interna/Externa),
 * sem virar funcionalidade de interface (comprometeria a genericidade do
 * motor configuravel do sistema).
 *
 * O desafio_id da equipe e' sempre zerado na migracao: o Desafio escolhido
 * pertence ao Tema/trilha antigos (Bug 2 escopa desafios por trilha) e nao
 * existe na trilha nova - a equipe precisa escolher de novo na trilha nova
 * (ou o Admin cadastra manualmente depois, se a submissao ja foi feita).
 *
 * Identificacao da equipe: por id (--id=123) ou por nome (--nome="Equipe X"),
 * igual aos outros scripts do projeto.
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito,
 * sem gravar nada. Para gravar de verdade:
 *   php migrar_equipe_trilha.php --id=123 --trilha-destino="Trilha Externa" --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\EquipeRepository;
use App\Repositories\TrilhaRepository;

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
$id = lerArgumento($argv, 'id');
$nome = lerArgumento($argv, 'nome');
$trilhaDestinoNome = lerArgumento($argv, 'trilha-destino');

if ($trilhaDestinoNome === null || ($id === null && $nome === null)) {
    echo "Uso: php migrar_equipe_trilha.php (--id=123 | --nome=\"Nome da Equipe\") --trilha-destino=\"Trilha Externa\" [--confirmar]\n";
    exit(1);
}

$equipes = new EquipeRepository();
$trilhas = new TrilhaRepository();

$trilhaDestino = $trilhas->buscarPorNome($trilhaDestinoNome);

if ($trilhaDestino === null) {
    echo "ERRO: trilha destino '{$trilhaDestinoNome}' nao encontrada.\n";
    exit(1);
}

$equipe = $id !== null ? $equipes->buscarPorId((int) $id) : $equipes->buscarPorNome($nome);

if ($equipe === null) {
    echo "ERRO: equipe nao encontrada (id='{$id}', nome='{$nome}').\n";
    exit(1);
}

if ((int) $equipe['trilha_id'] === (int) $trilhaDestino['id']) {
    echo "AVISO: a equipe '{$equipe['nome_equipe']}' já está na trilha '{$trilhaDestinoNome}'. Nada a fazer.\n";
    exit(0);
}

$trilhaOrigem = $trilhas->buscarPorId($equipe['trilha_id']);

echo "Equipe: '{$equipe['nome_equipe']}' (id {$equipe['id']})\n";
echo "Trilha atual: '{$trilhaOrigem['nome']}'\n";
echo "Trilha destino: '{$trilhaDestino['nome']}'\n";
echo "Desafio atual (desafio_id): " . ($equipe['desafio_id'] !== null ? $equipe['desafio_id'] : 'nenhum') . " -> sera zerado\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, rode com --confirmar\n";
    exit(0);
}

$equipes->migrarParaTrilha($equipe['id'], $trilhaDestino['id']);

echo "\n[ok] equipe '{$equipe['nome_equipe']}' migrada para '{$trilhaDestino['nome']}'.\n";
