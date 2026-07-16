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
 * Upsert: se a equipe ja tem submissao nesta etapa, os dados sao
 * sobrescritos (nao pulados) - reimportar depois que o participante editou a
 * resposta no Google Forms atualiza a submissao existente em vez de ficar
 * defasado.
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
use App\Repositories\DesafioRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;

// Fase 17 (Bug 1): a etapa de submissao conceitual e' localizada por ordem
// (estavel nas duas trilhas), nao por nome - o nome diverge entre trilhas
// (ex.: "Etapa 1 - Submissao de Ideia" na Interna, "Etapa 1 - Submissao e
// Triagem Conceitual (Baixa Fidelidade)" na Externa).
const ORDEM_ETAPA_SUBMISSAO = 2;

// Colunas fixas, iguais nas duas fontes.
const COL_CARIMBO = 0;
const COL_EMAIL_RESPONDENTE = 1;
const COL_NOME_EQUIPE = 2;

// Fase 17 (Bug 1): a coluna "Tema do Desafio" (radio) do Google Forms nao e'
// mais importada - o sistema so' tem o campo "Desafio Escolhido"
// (selecao_tema_desafio) e infere o Tema automaticamente a partir do Desafio
// resolvido (ver resolverDesafioId()).
$fontes = [
    'Trilha Externa' => [
        'csv' => __DIR__ . '/dados_importacao/submissoes_externo.csv',
        'colunas' => [
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
 * Resolve o texto livre de "Desafio Escolhido" do CSV contra os desafios reais
 * da trilha (texto integral da pergunta, cadastrado via DesafioRepository).
 * Sem correspondencia exata, NAO adivinha - devolve null e quem chamou
 * registra pendencia. Dentro de uma mesma trilha os textos de desafio nao se
 * repetem entre temas (confirmado em DesafiosFase17.md), entao nao e'
 * necessario usar o Tema como desempate.
 */
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

$trilhas = new TrilhaRepository();
$etapas = new EtapaRepository();
$camposRepo = new CampoDinamicoRepository();
$equipes = new EquipeRepository();
$participantes = new ParticipanteRepository();
$submissoes = new SubmissaoRepository();
$desafiosRepo = new DesafioRepository();
$pdo = Database::conexao();

$totais = ['criadas' => 0, 'atualizadas' => 0, 'pendencias' => 0];

foreach ($fontes as $nomeTrilha => $fonte) {
    echo "\n=== {$nomeTrilha} ===\n";

    $trilha = $trilhas->buscarPorNome($nomeTrilha);

    if ($trilha === null) {
        echo "ERRO: trilha '{$nomeTrilha}' nao encontrada.\n";
        continue;
    }

    $etapa = $etapas->buscarPorTrilhaEOrdem($trilha['id'], ORDEM_ETAPA_SUBMISSAO);

    if ($etapa === null) {
        echo "ERRO: nenhuma etapa com ordem=" . ORDEM_ETAPA_SUBMISSAO . " encontrada na trilha '{$nomeTrilha}'.\n";
        continue;
    }

    if ($etapa['formulario_dinamico_id'] === null) {
        echo "ERRO: etapa '{$etapa['nome']}' nao tem formulario vinculado.\n";
        continue;
    }

    $camposDoFormulario = $camposRepo->listarPorFormulario($etapa['formulario_dinamico_id']);
    $desafiosDaTrilha = $desafiosRepo->listarPorTrilha($trilha['id']);

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

        $valoresPorCampo = [];
        $desafioEscolhidoId = null;

        foreach ($camposPorColuna as $indiceColuna => $campo) {
            $valorCsv = lerLinha($linha, $indiceColuna);

            if ($valorCsv === '') {
                continue;
            }

            if ($campo['tipo'] === 'selecao_tema_desafio') {
                $desafioEscolhidoId = resolverDesafioId($valorCsv, $desafiosDaTrilha);

                if ($desafioEscolhidoId === null) {
                    echo "  [PENDENCIA] '{$equipe['nome_equipe']}' (linha {$numeroLinha}): desafio '{$valorCsv}' nao resolvido - campo deixado em branco.\n";
                    $totais['pendencias']++;
                    continue;
                }

                $valoresPorCampo[(string) $campo['id']] = $desafioEscolhidoId;
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

        $acao = $submissaoExistente !== null ? 'atualizada' : 'criada';

        if (!$confirmar) {
            echo "  [seria {$acao}] '{$equipe['nome_equipe']}' (linha {$numeroLinha}), " . count($valoresPorCampo) . " campo(s) preenchido(s).\n";
            $totais[$acao === 'atualizada' ? 'atualizadas' : 'criadas']++;
            continue;
        }

        $pdo->beginTransaction();

        try {
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

            $pdo->commit();
            echo "  [ok] '{$equipe['nome_equipe']}' {$acao} (submissao #{$submissaoId}, linha {$numeroLinha}).\n";
            $totais[$acao === 'atualizada' ? 'atualizadas' : 'criadas']++;
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
echo "Submissoes atualizadas" . (!$confirmar ? ' (simulado)' : '') . ": {$totais['atualizadas']}\n";
echo "Pendencias (equipe ou desafio nao resolvido): {$totais['pendencias']}\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, rode: php importar_submissoes_google_forms.php --confirmar\n";
}
