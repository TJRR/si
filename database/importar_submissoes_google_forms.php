<?php

/**
 * Importa as respostas da Etapa 1 (submissao conceitual), coletadas por um
 * Google Forms externo em cada trilha enquanto a plataforma nao estava
 * pronta, para dentro de submissoes.dados_json - vinculando cada resposta a
 * uma equipe ja homologada (o Cadastro de Equipe ja rodou dentro do sistema,
 * este script NUNCA cria equipe nova).
 *
 * Os campos de destino (id, rotulo, tipo) sao lidos em tempo de execucao do
 * formulario_dinamico vinculado a etapa - o id de cada campo muda entre
 * ambientes (dev/prod), entao nunca e hardcodado aqui. O casamento
 * coluna-do-csv -> campo-do-formulario e feito por uma palavra-chave no
 * rotulo (ver $fontes[...]['colunas']), ajustavel se os rotulos reais divergirem.
 *
 * Idempotente: se a equipe ja tem submissao nesta etapa, a linha e pulada
 * (rodar o script mais de uma vez nao duplica).
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito,
 * sem gravar nada. Para gravar de verdade:
 *   php importar_submissoes_google_forms.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Texto;
use App\Repositories\CampoDinamicoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TemaDesafioRepository;
use App\Repositories\TrilhaRepository;

// Nome real da etapa de submissao conceitual, cadastrado hoje igual nas duas
// trilhas. ATENCAO: o usuario relatou que os nomes de etapa no admin estao
// incorretos e serao corrigidos para bater com o edital - conferir/ajustar
// esta constante contra a tela Admin > Trilha > Etapas antes de rodar.
const NOME_ETAPA_SUBMISSAO = 'Submissao de Ideia';

// Colunas fixas, iguais nas duas fontes.
const COL_CARIMBO = 0;
const COL_EMAIL_RESPONDENTE = 1;
const COL_NOME_EQUIPE = 2;

// Ordem importa: cada palavra-chave e' procurada, em sequencia, entre os
// campos do formulario ainda nao usados - resolve a ambiguidade de
// "Tema do Desafio" tambem conter a palavra "desafio" (ver
// localizarCampoPorPalavraChave()).
$fontes = [
    'Trilha Externa' => [
        'csv' => __DIR__ . '/dados_importacao/submissoes_externo.csv',
        'colunas' => [
            3 => 'tema',
            4 => 'desafio',
            5 => 'solucao proposta',
            6 => 'premissas',
            7 => 'potencial de impacto',
            8 => 'viabilidade',
        ],
    ],
    'Trilha Interna' => [
        'csv' => __DIR__ . '/dados_importacao/submissoes_interno.csv',
        'colunas' => [
            3 => 'tema',
            4 => 'desafio',
            5 => 'solucao proposta',
            6 => 'potencial de impacto',
            7 => 'viabilidade',
        ],
    ],
];

function lerLinha($linha, $indice)
{
    return isset($linha[$indice]) ? trim($linha[$indice]) : '';
}

/**
 * Acha, entre os campos ainda nao usados, o primeiro cujo rotulo (sem
 * acento/caixa) contem a palavra-chave. Marca o campo como usado em $usados
 * para nao ser reaproveitado por uma palavra-chave seguinte.
 */
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

/**
 * Resolve o texto de "Tema do Desafio" do CSV contra os temas_desafios reais
 * da trilha (nome cadastrado, ignorando o sufixo "(Tema N)"). Sem
 * correspondencia exata, NAO adivinha - devolve null e quem chamou registra
 * pendencia.
 */
function resolverTemaDesafioId($textoCsv, array $temasDaTrilha)
{
    $alvo = Texto::slugify(preg_replace('/\(tema\s*\d+\)/i', '', $textoCsv));

    if ($alvo === '') {
        return null;
    }

    foreach ($temasDaTrilha as $tema) {
        $nomeTema = Texto::slugify(preg_replace('/\(tema\s*\d+\)/i', '', $tema['nome']));

        if ($nomeTema !== '' && (strpos($alvo, $nomeTema) !== false || strpos($nomeTema, $alvo) !== false)) {
            return (int) $tema['id'];
        }
    }

    return null;
}

$confirmar = in_array('--confirmar', $argv, true);

$trilhas = new TrilhaRepository();
$etapas = new EtapaRepository();
$camposRepo = new CampoDinamicoRepository();
$equipes = new EquipeRepository();
$participantes = new ParticipanteRepository();
$submissoes = new SubmissaoRepository();
$temasRepo = new TemaDesafioRepository();
$pdo = Database::conexao();

$totais = ['criadas' => 0, 'puladas' => 0, 'pendencias' => 0];

foreach ($fontes as $nomeTrilha => $fonte) {
    echo "\n=== {$nomeTrilha} ===\n";

    $trilha = $trilhas->buscarPorNome($nomeTrilha);

    if ($trilha === null) {
        echo "ERRO: trilha '{$nomeTrilha}' nao encontrada.\n";
        continue;
    }

    $etapa = $etapas->buscarPorTrilhaENome($trilha['id'], NOME_ETAPA_SUBMISSAO);

    if ($etapa === null) {
        echo "ERRO: etapa '" . NOME_ETAPA_SUBMISSAO . "' nao encontrada na trilha '{$nomeTrilha}'.\n";
        echo "       Confira o nome real em Admin > Trilha > Etapas e ajuste a constante NOME_ETAPA_SUBMISSAO.\n";
        continue;
    }

    if ($etapa['formulario_dinamico_id'] === null) {
        echo "ERRO: etapa '" . NOME_ETAPA_SUBMISSAO . "' nao tem formulario vinculado.\n";
        continue;
    }

    $camposDoFormulario = $camposRepo->listarPorFormulario($etapa['formulario_dinamico_id']);
    $temasDaTrilha = $temasRepo->listarPorTrilha($trilha['id']);

    $usados = [];
    $camposPorColuna = [];

    foreach ($fonte['colunas'] as $indiceColuna => $palavraChave) {
        $campo = localizarCampoPorPalavraChave($camposDoFormulario, $usados, $palavraChave);

        if ($campo === null) {
            echo "AVISO: nenhum campo do formulario bateu com a palavra-chave '{$palavraChave}' (coluna {$indiceColuna}) - essa coluna sera ignorada.\n";
            continue;
        }

        $camposPorColuna[$indiceColuna] = $campo;
    }

    if (!file_exists($fonte['csv'])) {
        echo "ERRO: arquivo {$fonte['csv']} nao encontrado.\n";
        continue;
    }

    $handle = fopen($fonte['csv'], 'r');
    fgetcsv($handle); // descarta cabecalho
    $numeroLinha = 1;

    while (($linha = fgetcsv($handle)) !== false) {
        $numeroLinha++;

        $nomeEquipe = lerLinha($linha, COL_NOME_EQUIPE);

        if ($nomeEquipe === '') {
            continue; // linha em branco no fim do arquivo
        }

        $emailRespondente = strtolower(lerLinha($linha, COL_EMAIL_RESPONDENTE));

        $equipe = $equipes->buscarPorTrilhaENome($trilha['id'], $nomeEquipe);

        if ($equipe === null && $emailRespondente !== '') {
            $participante = $participantes->buscarPorEmail($emailRespondente);

            if ($participante !== null) {
                $candidata = $equipes->buscarPorParticipante($participante['id']);

                if ($candidata !== null && (int) $candidata['trilha_id'] === (int) $trilha['id']) {
                    $equipe = $candidata;
                    echo "  [info] '{$nomeEquipe}' (linha {$numeroLinha}) casada por e-mail do respondente, nao pelo nome (equipe real: '{$equipe['nome_equipe']}').\n";
                }
            }
        }

        if ($equipe === null) {
            echo "  [PENDENCIA] equipe '{$nomeEquipe}' (linha {$numeroLinha}) nao encontrada nem por nome nem por e-mail ({$emailRespondente}) - pulada.\n";
            $totais['pendencias']++;
            continue;
        }

        $submissaoExistente = $submissoes->buscarPorEquipeEEtapa($equipe['id'], $etapa['id']);

        if ($submissaoExistente !== null) {
            echo "  [pulada] '{$equipe['nome_equipe']}' ja tem submissao nesta etapa (linha {$numeroLinha}).\n";
            $totais['puladas']++;
            continue;
        }

        $valoresPorCampo = [];

        foreach ($camposPorColuna as $indiceColuna => $campo) {
            $valorCsv = lerLinha($linha, $indiceColuna);

            if ($valorCsv === '') {
                continue;
            }

            if ($campo['tipo'] === 'selecao_tema_desafio') {
                $temaId = resolverTemaDesafioId($valorCsv, $temasDaTrilha);

                if ($temaId === null) {
                    echo "  [PENDENCIA] '{$equipe['nome_equipe']}' (linha {$numeroLinha}): tema/desafio '{$valorCsv}' nao resolvido - campo deixado em branco.\n";
                    $totais['pendencias']++;
                    continue;
                }

                $valoresPorCampo[(string) $campo['id']] = $temaId;
                continue;
            }

            $valoresPorCampo[(string) $campo['id']] = $valorCsv;
        }

        $dadosJson = [
            'campos' => $valoresPorCampo,
            'importado_de' => 'google_forms',
            'carimbo_data_hora' => lerLinha($linha, COL_CARIMBO),
            'email_respondente' => lerLinha($linha, COL_EMAIL_RESPONDENTE),
            'linha_planilha' => $numeroLinha,
        ];

        if (!$confirmar) {
            echo "  [seria criada] '{$equipe['nome_equipe']}' (linha {$numeroLinha}), " . count($valoresPorCampo) . " campo(s) preenchido(s).\n";
            $totais['criadas']++;
            continue;
        }

        $pdo->beginTransaction();

        try {
            $submissaoId = $submissoes->criar($etapa['id'], $etapa['formulario_dinamico_id'], $dadosJson);
            $submissoes->vincularEquipe($submissaoId, $equipe['id']);

            $pdo->commit();
            echo "  [ok] '{$equipe['nome_equipe']}' importada (submissao #{$submissaoId}, linha {$numeroLinha}).\n";
            $totais['criadas']++;
        } catch (\Exception $e) {
            $pdo->rollBack();
            echo "  [ERRO] linha {$numeroLinha} ('{$nomeEquipe}'): " . $e->getMessage() . "\n";
            $totais['pendencias']++;
        }
    }

    fclose($handle);
}

echo "\n=== Resumo ===\n";
echo "Submissoes criadas" . (!$confirmar ? ' (simulado)' : '') . ": {$totais['criadas']}\n";
echo "Puladas (ja existia submissao): {$totais['puladas']}\n";
echo "Pendencias (equipe ou tema/desafio nao resolvido): {$totais['pendencias']}\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, rode: php importar_submissoes_google_forms.php --confirmar\n";
}
