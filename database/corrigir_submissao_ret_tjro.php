<?php

/**
 * Fase 21: correcao pontual (nao generica) da submissao da equipe RET-TJRO -
 * ela esta inscrita na trilha "Eixo Solução Tecnológica" (Externa), mas
 * respondeu ao formulario de submissao da Etapa 1 da trilha Interna por
 * engano. importar_submissoes_google_forms.php recusa corretamente essa
 * linha (nao mistura equipe de uma trilha com resposta de outra) - decisao
 * confirmada com o usuario: a equipe nao pode ser penalizada nem reenviar,
 * entao este script usa o conteudo ja respondido (no formulario Interno)
 * para gravar a submissao dela na Etapa 1 da trilha Externa correta.
 *
 * Os formularios das duas trilhas NAO tem o mesmo mapeamento de coluna - a
 * Interna tem 4 campos de resposta (desafio, solucao proposta, potencial de
 * impacto, viabilidade), a Externa tem 5 (as mesmas + "premissas", que a
 * Interna nao pergunta). Por isso o remapeamento abaixo e' por SIGNIFICADO,
 * nao por posicao de coluna - "premissas" fica sempre em branco (a equipe
 * nunca respondeu isso).
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria
 * gravado. Para gravar de verdade:
 *   php corrigir_submissao_ret_tjro.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Texto;
use App\Repositories\CampoDinamicoRepository;
use App\Repositories\DesafioRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;

const NOME_EQUIPE = 'RET-TJRO';
const NOME_TRILHA_EXTERNA = 'Eixo Solução Tecnológica';
const ORDEM_ETAPA_SUBMISSAO = 2;
const CSV_INTERNO = __DIR__ . '/dados_importacao/submissoes_interno.csv';

// Colunas da resposta da equipe no formulario INTERNO (0-indexado) - o
// significado de cada uma, nao a posicao equivalente no formulario Externo.
const COL_NOME_EQUIPE = 2;
const COL_CARIMBO = 0;
const COL_EMAIL_RESPONDENTE = 1;
const COL_DESAFIO = 4;
const COL_SOLUCAO_PROPOSTA = 5;
const COL_POTENCIAL_IMPACTO = 6;
const COL_VIABILIDADE = 7;

function localizarCampoPorPalavraChave(array $campos, array &$usados, $palavraChave)
{
    $alvo = Texto::slugify($palavraChave);

    foreach ($campos as $campo) {
        if (in_array($campo['id'], $usados, true)) {
            continue;
        }

        if (strpos(Texto::slugify($campo['rotulo']), $alvo) !== false) {
            $usados[] = $campo['id'];

            return $campo;
        }
    }

    return null;
}

function resolverDesafioId($textoCsv, array $desafiosDaTrilha)
{
    $alvo = Texto::slugify($textoCsv);

    if ($alvo === '') {
        return null;
    }

    foreach ($desafiosDaTrilha as $desafio) {
        $pergunta = Texto::slugify($desafio['pergunta']);

        if ($pergunta !== '' && (strpos($alvo, $pergunta) !== false || strpos($pergunta, $alvo) !== false)) {
            return (int) $desafio['id'];
        }
    }

    return null;
}

$confirmar = in_array('--confirmar', $argv, true);

if (!file_exists(CSV_INTERNO)) {
    echo "ERRO: arquivo " . CSV_INTERNO . " não encontrado.\n";
    exit(1);
}

$handle = fopen(CSV_INTERNO, 'r');
fgetcsv($handle);
$linhaEncontrada = null;

while (($linha = fgetcsv($handle)) !== false) {
    if (trim($linha[COL_NOME_EQUIPE] ?? '') === NOME_EQUIPE) {
        $linhaEncontrada = $linha;
        break;
    }
}
fclose($handle);

if ($linhaEncontrada === null) {
    echo "ERRO: nenhuma linha para \"" . NOME_EQUIPE . "\" encontrada em " . CSV_INTERNO . "\n";
    exit(1);
}

$equipes = new EquipeRepository();
$equipe = $equipes->buscarPorNome(NOME_EQUIPE);

if ($equipe === null) {
    echo "ERRO: equipe \"" . NOME_EQUIPE . "\" não encontrada em `equipes`.\n";
    exit(1);
}

$trilhas = new TrilhaRepository();
$trilha = $trilhas->buscarPorNome(NOME_TRILHA_EXTERNA);

if ($trilha === null) {
    echo "ERRO: trilha \"" . NOME_TRILHA_EXTERNA . "\" não encontrada.\n";
    exit(1);
}

if ((int) $equipe['trilha_id'] !== (int) $trilha['id']) {
    echo "ERRO: equipe \"" . NOME_EQUIPE . "\" está cadastrada na trilha_id {$equipe['trilha_id']}, não na trilha_id {$trilha['id']} (\"" . NOME_TRILHA_EXTERNA . "\"). Script cancelado — confira antes de prosseguir.\n";
    exit(1);
}

$etapas = new EtapaRepository();
$etapa = $etapas->buscarPorTrilhaEOrdem($trilha['id'], ORDEM_ETAPA_SUBMISSAO);

if ($etapa === null || $etapa['formulario_dinamico_id'] === null) {
    echo "ERRO: etapa de submissão (ordem " . ORDEM_ETAPA_SUBMISSAO . ") da trilha Externa não encontrada ou sem formulário vinculado.\n";
    exit(1);
}

$camposRepo = new CampoDinamicoRepository();
$campos = $camposRepo->listarPorFormulario($etapa['formulario_dinamico_id']);
$desafiosRepo = new DesafioRepository();
$desafiosDaTrilha = $desafiosRepo->listarPorTrilha($trilha['id']);

$fontes = [
    COL_DESAFIO => ['palavra' => 'desafio', 'valor' => trim($linhaEncontrada[COL_DESAFIO] ?? '')],
    COL_SOLUCAO_PROPOSTA => ['palavra' => 'solucao proposta', 'valor' => trim($linhaEncontrada[COL_SOLUCAO_PROPOSTA] ?? '')],
    COL_POTENCIAL_IMPACTO => ['palavra' => 'potencial de impacto', 'valor' => trim($linhaEncontrada[COL_POTENCIAL_IMPACTO] ?? '')],
    COL_VIABILIDADE => ['palavra' => 'viabilidade', 'valor' => trim($linhaEncontrada[COL_VIABILIDADE] ?? '')],
];

$usados = [];
$valoresPorCampo = [];
$desafioEscolhidoId = null;

echo "Equipe: {$equipe['nome_equipe']} (id {$equipe['id']}) - trilha: {$trilha['nome']} (id {$trilha['id']}) - etapa: {$etapa['nome']} (id {$etapa['id']})\n\n";

foreach ($fontes as $indice => $fonte) {
    $campo = localizarCampoPorPalavraChave($campos, $usados, $fonte['palavra']);

    if ($campo === null) {
        echo "AVISO: nenhum campo do formulário Externo bateu com a palavra-chave '{$fonte['palavra']}' - ignorado.\n";
        continue;
    }

    if ($fonte['valor'] === '') {
        echo "Campo \"{$campo['rotulo']}\": (sem resposta na Interna)\n";
        continue;
    }

    if ($campo['tipo'] === 'selecao_tema_desafio') {
        $desafioEscolhidoId = resolverDesafioId($fonte['valor'], $desafiosDaTrilha);
        echo "Campo \"{$campo['rotulo']}\" (Desafio Escolhido): \"{$fonte['valor']}\"\n";
        echo '  -> ' . ($desafioEscolhidoId !== null ? "resolvido para desafio_id {$desafioEscolhidoId}" : 'NÃO RESOLVIDO - ficará em branco') . "\n";

        if ($desafioEscolhidoId !== null) {
            $valoresPorCampo[(string) $campo['id']] = $desafioEscolhidoId;
        }

        continue;
    }

    $valoresPorCampo[(string) $campo['id']] = $fonte['valor'];
    echo "Campo \"{$campo['rotulo']}\": \"" . mb_substr($fonte['valor'], 0, 80) . (mb_strlen($fonte['valor']) > 80 ? '...' : '') . "\"\n";
}

echo "\nCampo \"Premissas\" da Externa: deixado em branco (não perguntado no formulário Interno).\n";

$dadosJson = [
    'campos' => $valoresPorCampo,
    'importado_de' => 'google_forms_trilha_incorreta',
    'carimbo_data_hora' => trim($linhaEncontrada[COL_CARIMBO] ?? ''),
    'email_respondente' => trim($linhaEncontrada[COL_EMAIL_RESPONDENTE] ?? ''),
    'observacao' => 'Submissão originalmente enviada pelo formulário da trilha Interna por engano; equipe está inscrita na trilha Externa. Corrigida manualmente na Fase 21.',
];

$submissoes = new SubmissaoRepository();
$submissaoExistente = $submissoes->buscarPorEquipeEEtapa($equipe['id'], $etapa['id']);

echo "\n" . ($submissaoExistente !== null ? "Já existe submissão (id {$submissaoExistente['id']}) - seria ATUALIZADA." : 'Nenhuma submissão existente - seria CRIADA.') . "\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, rode: php corrigir_submissao_ret_tjro.php --confirmar\n";
    exit(0);
}

if ($submissaoExistente !== null) {
    $submissoes->atualizarDadosJson($submissaoExistente['id'], $dadosJson);
    $submissaoId = $submissaoExistente['id'];
} else {
    $submissaoId = $submissoes->criar($etapa['id'], $etapa['formulario_dinamico_id'], $dadosJson);
    $submissoes->vincularEquipe($submissaoId, $equipe['id']);
}

if ($desafioEscolhidoId !== null) {
    $equipes->definirDesafio($equipe['id'], $desafioEscolhidoId);
}

echo "\n[ok] Submissão #{$submissaoId} gravada para \"" . NOME_EQUIPE . "\" na Etapa 1 da trilha Externa.\n";
