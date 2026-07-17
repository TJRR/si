<?php

/**
 * Fase 18: script generico para renomear uma equipe (o admin tambem pode
 * fazer isso pela tela normal de edicao de equipe, quando existir - este
 * script serve para correcoes em lote/via terminal, no mesmo espirito dos
 * outros scripts de database/). So' altera nome_equipe; vinculo_institucional
 * e observacoes sao preservados exatamente como estao hoje.
 *
 * Identificacao da equipe: --equipe-id=123 OU --equipe-nome="Nome Atual"
 * (mesmo padrao dos demais scripts deste diretorio).
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito,
 * sem gravar nada. Para gravar de verdade, repita o comando com --confirmar.
 *
 * Exemplos:
 *   php database/renomear_equipe.php --equipe-id=129 --novo-nome="Nexo Documental 2.0" --confirmar
 *   php database/renomear_equipe.php --equipe-nome="Justiça em Movimento" --novo-nome="Justiça em Movimento Digital" --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\EquipeRepository;

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
$equipeId = lerArgumento($argv, 'equipe-id');
$equipeNome = lerArgumento($argv, 'equipe-nome');
$novoNome = lerArgumento($argv, 'novo-nome');

if (($equipeId === null && $equipeNome === null) || empty($novoNome)) {
    echo "Uso: php database/renomear_equipe.php (--equipe-id=123 | --equipe-nome=\"Nome Atual\") --novo-nome=\"Nome Novo\" [--confirmar]\n";
    exit(1);
}

$equipes = new EquipeRepository();

$equipe = $equipeId !== null ? $equipes->buscarPorId((int) $equipeId) : $equipes->buscarPorNome($equipeNome);

if ($equipe === null) {
    echo "ERRO: equipe não encontrada (id='{$equipeId}', nome='{$equipeNome}').\n";

    if ($equipeNome !== null) {
        $semelhantes = $equipes->listarSemelhantesPorNome($equipeNome);

        if (!empty($semelhantes)) {
            echo "Equipes com nome parecido:\n";
            foreach ($semelhantes as $candidata) {
                echo "  id {$candidata['id']} - '{$candidata['nome_equipe']}'\n";
            }
        }
    }

    exit(1);
}

if (trim($novoNome) === '') {
    echo "ERRO: o novo nome não pode ser vazio.\n";
    exit(1);
}

if (strcasecmp($equipe['nome_equipe'], $novoNome) === 0) {
    echo "AVISO: a equipe já se chama '{$equipe['nome_equipe']}'. Nada a fazer.\n";
    exit(0);
}

$conflito = $equipes->buscarPorTrilhaENome($equipe['trilha_id'], $novoNome);

if ($conflito !== null) {
    echo "AVISO: já existe outra equipe chamada '{$novoNome}' nesta mesma trilha (id {$conflito['id']}) - o sistema não impede nomes duplicados, mas confirme se é intencional antes de prosseguir.\n";
}

echo "Equipe (id {$equipe['id']}): '{$equipe['nome_equipe']}' -> '{$novoNome}'\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, repita o comando com --confirmar.\n";
    exit(0);
}

$equipes->atualizar($equipe['id'], $novoNome, (string) $equipe['vinculo_institucional'], (string) $equipe['observacoes']);

echo "\n[ok] equipe renomeada para '{$novoNome}'.\n";
