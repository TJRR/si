<?php

/**
 * Expurgo automatico das imagens de comprovacao da Divulgacao, por prazo
 * em dias contado do fim do evento. Uso:
 *   php database/expurgar_imagens_divulgacao.php            (ensaio, so' lista)
 *   php database/expurgar_imagens_divulgacao.php --confirmar
 *
 * So' alcanca eventos com o prazo preenchido em Divulgacao, Configuracoes.
 * Apaga pelas mesmas chamadas do botao "Apagar imagens" da tela Comprovacoes:
 * a comprovacao e os pontos continuam registrados, so' o arquivo deixa de
 * existir. Seguro rodar quantas vezes quiser: so' toca em imagem que ainda
 * existe.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Repositories\DivulgacaoComprovacaoRepository;
use App\Repositories\DivulgacaoConfigRepository;
use App\Services\ArquivoPrivadoService;

date_default_timezone_set(config('timezone'));

$confirmar = in_array('--confirmar', $argv, true);
$comprovacoes = new DivulgacaoComprovacaoRepository();
$hoje = date('Y-m-d');
$totalApagadas = 0;
$totalPendentes = 0;

foreach ((new DivulgacaoConfigRepository())->listarComRetencao() as $evento) {
    if (empty($evento['data_fim'])) {
        continue;
    }

    $vencimento = date('Y-m-d', strtotime(substr((string) $evento['data_fim'], 0, 10) . ' +' . (int) $evento['dias_retencao_imagens'] . ' days'));

    if ($vencimento >= $hoje) {
        continue;
    }

    $linhas = $comprovacoes->listarComArquivoDoEvento((int) $evento['evento_id']);

    if ($linhas === []) {
        continue;
    }

    $totalPendentes += count($linhas);
    echo 'Evento #' . (int) $evento['evento_id'] . ' (' . $evento['nome'] . '): ' . count($linhas) . ' imagem(ns), prazo vencido em ' . $vencimento . "\n";

    if (!$confirmar) {
        continue;
    }

    $apagadas = 0;

    foreach ($linhas as $linha) {
        ArquivoPrivadoService::remover($linha['arquivo_path']);
        $comprovacoes->marcarArquivoRemovido($linha['id']);
        $apagadas++;
    }

    $totalApagadas += $apagadas;
    Auditoria::registrar('expurgar_imagens_automatico', 'evento_divulgacao_comprovacoes', null, null, [
        'evento_id' => (int) $evento['evento_id'],
        'imagens' => $apagadas,
        'dias_retencao_imagens' => (int) $evento['dias_retencao_imagens'],
    ], 'Expurgo automatico por prazo (database/expurgar_imagens_divulgacao.php)');
}

if ($totalPendentes === 0) {
    echo "Nada a expurgar.\n";
    exit(0);
}

if (!$confirmar) {
    echo "\nEnsaio: nada foi apagado. Rode de novo com --confirmar para apagar.\n";
    exit(0);
}

echo $totalApagadas . " imagem(ns) apagada(s).\n";
