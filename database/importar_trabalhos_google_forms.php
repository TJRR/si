<?php

/**
 * Fase 51: traz para o sistema os trabalhos recebidos pelo formulario
 * externo usado enquanto o modulo de Trabalhos ainda nao estava em
 * producao (canal alternativo previsto no item 5.2 do edital n. 10/2026).
 *
 * Uso:
 *   php database/importar_trabalhos_google_forms.php \
 *       --evento=1 --planilha=/caminho/respostas.csv --arquivos=/caminho/anexos
 *
 * A planilha pode ser o CSV ou o XLSX exportado do Google, tanto faz.
 *
 * Opcoes:
 *   --mapa=/caminho/mapa.json    mapeamento campo -> rotulo exato da coluna.
 *                                Sem ele, o script sugere um mapeamento a
 *                                partir do cabecalho e NAO grava nada.
 *   --declaracao=/caminho.html   texto da declaracao aceita no formulario
 *                                externo (registrado como aceite de cada
 *                                trabalho). Sem ele, usa o texto padrao
 *                                abaixo, transcrito do formulario.
 *   --responsavel=<id>           usuario que consta como autor da campanha
 *                                de convites (padrao: o Administrador de
 *                                menor id).
 *   --confirmar                  grava de verdade (sem isto e' simulacao).
 *   --enfileirar-convites        alem de gravar, coloca os convites na fila
 *                                de envio do evento (10 por execucao do
 *                                agendador). NUNCA usar na maquina de
 *                                simulacao: ela roda com dados reais de
 *                                producao e avisaria pessoas de verdade.
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que faria.
 * Rodar de novo depois de confirmar e' seguro: cada resposta ja trazida
 * aparece como "ja importado", sem gravar nem convidar de novo.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\TrabalhoImportacaoService;

const DECLARACAO_PADRAO = '<p>Declaro que li e aceito integralmente as normas do edital, que todos os coautores conhecem e concordam com o conteúdo do trabalho, que a versão para avaliação não contém identificação de autoria e que autorizo, sem ônus, a publicação e a divulgação do trabalho pelo TJRR, com o devido crédito aos autores.</p>';

$confirmar = in_array('--confirmar', $argv, true);
$enfileirarConvites = in_array('--enfileirar-convites', $argv, true);
$eventoId = null;
$planilha = null;
$pastaArquivos = null;
$caminhoMapa = null;
$caminhoDeclaracao = null;
$responsavelId = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--evento=') === 0) {
        $eventoId = (int) substr($arg, strlen('--evento='));
    } elseif (strpos($arg, '--planilha=') === 0) {
        $planilha = substr($arg, strlen('--planilha='));
    } elseif (strpos($arg, '--arquivos=') === 0) {
        $pastaArquivos = substr($arg, strlen('--arquivos='));
    } elseif (strpos($arg, '--mapa=') === 0) {
        $caminhoMapa = substr($arg, strlen('--mapa='));
    } elseif (strpos($arg, '--declaracao=') === 0) {
        $caminhoDeclaracao = substr($arg, strlen('--declaracao='));
    } elseif (strpos($arg, '--responsavel=') === 0) {
        $responsavelId = (int) substr($arg, strlen('--responsavel='));
    }
}

if ($eventoId === null || $planilha === null || $pastaArquivos === null) {
    echo "Uso: php database/importar_trabalhos_google_forms.php --evento=1 --planilha=respostas.csv --arquivos=/pasta/anexos [--mapa=mapa.json] [--confirmar] [--enfileirar-convites]\n";
    exit(1);
}

if (!is_file($planilha)) {
    echo "Planilha nao encontrada: {$planilha}\n";
    exit(1);
}

if (!is_dir($pastaArquivos)) {
    echo "Pasta de anexos nao encontrada: {$pastaArquivos}\n";
    exit(1);
}

if ($enfileirarConvites && !$confirmar) {
    echo "--enfileirar-convites so funciona junto com --confirmar.\n";
    exit(1);
}

/**
 * Planilha exportada do Google, em CSV ou XLSX. O XLSX e' lido direto do
 * proprio arquivo (e' um zip com XML dentro), sem biblioteca nova: ZipArchive
 * e SimpleXML ja estao no ambiente. Isso evita o erro mais provavel no dia da
 * atualizacao, que e' exportar num formato e o script so aceitar o outro.
 */
function lerPlanilha($caminho)
{
    return strtolower(pathinfo($caminho, PATHINFO_EXTENSION)) === 'xlsx'
        ? lerXlsx($caminho)
        : lerCsv($caminho);
}

function lerXlsx($caminho)
{
    $zip = new ZipArchive();

    if ($zip->open($caminho) !== true) {
        throw new \RuntimeException('Nao foi possivel abrir a planilha XLSX.');
    }

    $compartilhadas = [];
    $conteudoCompartilhado = $zip->getFromName('xl/sharedStrings.xml');

    if ($conteudoCompartilhado !== false) {
        $xmlCompartilhado = simplexml_load_string($conteudoCompartilhado);

        foreach ($xmlCompartilhado->si as $item) {
            $texto = '';

            foreach ($item->xpath('.//*[local-name()="t"]') as $pedaco) {
                $texto .= (string) $pedaco;
            }

            $compartilhadas[] = $texto;
        }
    }

    $conteudoPlanilha = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($conteudoPlanilha === false) {
        throw new \RuntimeException('Planilha XLSX sem a primeira aba esperada.');
    }

    $xml = simplexml_load_string($conteudoPlanilha);
    $matriz = [];

    foreach ($xml->sheetData->row as $linhaXml) {
        $linha = [];

        foreach ($linhaXml->c as $celula) {
            $referencia = (string) $celula['r'];
            $coluna = preg_replace('/[0-9]/', '', $referencia);
            $tipo = (string) $celula['t'];
            $valor = '';

            if ($tipo === 's') {
                $indice = (int) $celula->v;
                $valor = isset($compartilhadas[$indice]) ? $compartilhadas[$indice] : '';
            } elseif ($tipo === 'inlineStr') {
                foreach ($celula->xpath('.//*[local-name()="t"]') as $pedaco) {
                    $valor .= (string) $pedaco;
                }
            } elseif (isset($celula->v)) {
                $valor = (string) $celula->v;
            }

            $linha[$coluna] = $valor;
        }

        $matriz[] = $linha;
    }

    if (empty($matriz)) {
        throw new \RuntimeException('Planilha vazia.');
    }

    $cabecalho = array_map('trim', array_values($matriz[0]));
    $colunasCabecalho = array_keys($matriz[0]);
    $linhas = [];

    foreach (array_slice($matriz, 1) as $linhaBruta) {
        $linha = [];

        foreach ($colunasCabecalho as $posicao => $coluna) {
            $linha[$cabecalho[$posicao]] = isset($linhaBruta[$coluna]) ? $linhaBruta[$coluna] : '';
        }

        if (count(array_filter($linha, function ($valor) {
            return trim((string) $valor) !== '';
        })) === 0) {
            continue;
        }

        $linhas[] = $linha;
    }

    return ['cabecalho' => $cabecalho, 'linhas' => $linhas];
}

/**
 * Leitura do CSV exportado pelo Google: separador virgula, primeira linha e'
 * o cabecalho, e o arquivo costuma vir com marca de ordem de bytes.
 */
function lerCsv($caminho)
{
    $manipulador = fopen($caminho, 'r');

    if ($manipulador === false) {
        throw new \RuntimeException('Nao foi possivel abrir a planilha.');
    }

    $cabecalho = fgetcsv($manipulador, 0, ',');

    if ($cabecalho === false) {
        throw new \RuntimeException('Planilha vazia.');
    }

    $cabecalho[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cabecalho[0]);
    $cabecalho = array_map('trim', $cabecalho);
    $linhas = [];

    while (($colunas = fgetcsv($manipulador, 0, ',')) !== false) {
        if (count(array_filter($colunas, function ($valor) {
            return trim((string) $valor) !== '';
        })) === 0) {
            continue;
        }

        $linha = [];

        foreach ($cabecalho as $indice => $nomeColuna) {
            $linha[$nomeColuna] = isset($colunas[$indice]) ? $colunas[$indice] : '';
        }

        $linhas[] = $linha;
    }

    fclose($manipulador);

    return ['cabecalho' => $cabecalho, 'linhas' => $linhas];
}

$planilhaLida = lerPlanilha($planilha);
$cabecalho = $planilhaLida['cabecalho'];
$linhas = $planilhaLida['linhas'];

echo "Planilha: " . count($linhas) . " resposta(s), " . count($cabecalho) . " coluna(s).\n\n";

if ($caminhoMapa === null) {
    $sugestao = TrabalhoImportacaoService::sugerirMapa($cabecalho);

    echo "Nenhum mapeamento informado (--mapa=). Sugestao a partir do cabecalho real:\n\n";
    echo json_encode($sugestao, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    echo "Colunas da planilha ainda sem campo correspondente:\n";

    foreach (TrabalhoImportacaoService::colunasNaoMapeadas($cabecalho, $sugestao) as $coluna) {
        echo "  - {$coluna}\n";
    }

    echo "\nConfira/corrija essa sugestao, salve num arquivo .json e rode de novo com --mapa=.\n";
    exit(0);
}

if (!is_file($caminhoMapa)) {
    echo "Arquivo de mapeamento nao encontrado: {$caminhoMapa}\n";
    exit(1);
}

$mapa = json_decode(file_get_contents($caminhoMapa), true);

if (!is_array($mapa)) {
    echo "Arquivo de mapeamento invalido (esperado um objeto JSON campo -> coluna).\n";
    exit(1);
}

$faltando = TrabalhoImportacaoService::obrigatoriosFaltando($mapa);

if (!empty($faltando)) {
    echo "Campos obrigatorios sem coluna no mapeamento: " . implode(', ', $faltando) . "\n";
    exit(1);
}

$naoMapeadas = TrabalhoImportacaoService::colunasNaoMapeadas($cabecalho, $mapa);

if (!empty($naoMapeadas)) {
    echo "Aviso: colunas da planilha sem campo correspondente (serao ignoradas):\n";

    foreach ($naoMapeadas as $coluna) {
        echo "  - {$coluna}\n";
    }

    echo "\n";
}

// O rotulo da coluna da declaracao no formulario externo e' o proprio texto
// que a pessoa aceitou - quando ele esta mapeado, e' ele que fica registrado
// como aceite, palavra por palavra, em vez do texto de reserva deste script.
$textoDeclaracao = DECLARACAO_PADRAO;

if (!empty($mapa['declaracao']) && mb_strlen($mapa['declaracao']) > 80) {
    $textoDeclaracao = '<p>' . htmlspecialchars(trim($mapa['declaracao']), ENT_QUOTES, 'UTF-8') . '</p>';
}

if ($caminhoDeclaracao !== null) {
    if (!is_file($caminhoDeclaracao)) {
        echo "Arquivo da declaracao nao encontrado: {$caminhoDeclaracao}\n";
        exit(1);
    }

    $textoDeclaracao = file_get_contents($caminhoDeclaracao);
}

if ($responsavelId === null) {
    $pdo = Database::conexao();
    $stmt = $pdo->query(
        "SELECT u.id FROM usuarios u
         JOIN usuario_perfil_concurso upc ON upc.usuario_id = u.id
         JOIN perfis p ON p.id = upc.perfil_id
         WHERE p.chave = 'administrador'
         ORDER BY u.id ASC LIMIT 1"
    );
    $responsavelId = (int) $stmt->fetchColumn();
}

if ($responsavelId <= 0) {
    echo "Nao foi possivel identificar o usuario responsavel pela importacao (--responsavel=<id>).\n";
    exit(1);
}

$servico = new TrabalhoImportacaoService();
$itens = $servico->analisar($eventoId, $linhas, $mapa, $pastaArquivos);

$contagem = [];

foreach ($itens as $item) {
    $contagem[$item['situacao']] = (isset($contagem[$item['situacao']]) ? $contagem[$item['situacao']] : 0) + 1;
}

$rotulos = [
    'aceito' => 'aceitos',
    'ja_importado' => 'ja importados (nada a fazer)',
    'descartado' => 'descartados pelo item 5.9 (envio mais recente do mesmo autor)',
    'recusado' => 'recusados',
    'sem_email' => 'sem e-mail na resposta',
    'fora_prazo' => 'fora do prazo',
];

echo "Resumo da analise:\n";

foreach ($rotulos as $chave => $rotulo) {
    echo '  ' . str_pad($rotulo, 60, '.') . ' ' . (isset($contagem[$chave]) ? $contagem[$chave] : 0) . "\n";
}

echo "\nDetalhe por resposta:\n";

foreach ($itens as $item) {
    $titulo = $item['dados']['titulo'] !== '' ? $item['dados']['titulo'] : '(sem titulo)';
    echo '  linha ' . $item['linha'] . ' [' . $item['situacao'] . '] ' . mb_substr($titulo, 0, 60) . ' <' . $item['dados']['autor_email'] . ">\n";

    foreach ($item['motivos'] as $motivo) {
        echo '      - ' . $motivo . "\n";
    }
}

if (!$confirmar) {
    echo "\nModo simulacao: nada foi gravado. Repita com --confirmar para aplicar.\n";
    exit(0);
}

$relatorio = $servico->importar($eventoId, $itens, $enfileirarConvites, $responsavelId, $textoDeclaracao);

echo "\nGravados: " . $relatorio['gravados'] . " trabalho(s).\n";
echo 'Contas criadas agora: ' . count($relatorio['convites_novos']) . ".\n";
echo 'Autores que ja tinham conta: ' . count($relatorio['convites_existentes']) . ".\n";

if (!empty($relatorio['falhas'])) {
    echo "\nFalhas (nenhum dado pela metade, cada trabalho grava em transacao propria):\n";

    foreach ($relatorio['falhas'] as $falha) {
        echo '  linha ' . $falha['linha'] . ': ' . $falha['erro'] . "\n";
    }
}

if ($enfileirarConvites) {
    echo "\nConvites colocados na fila de envio do evento. O agendador envia 10 por execucao;\n";
    echo "acompanhe em Eventos > Comunicacao ou rode manualmente:\n";
    echo "  php database/processar_comunicacao_evento.php\n";
} else {
    echo "\nNenhum convite foi enfileirado (use --enfileirar-convites quando quiser avisar os autores).\n";
}
