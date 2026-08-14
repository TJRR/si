<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\View;

/**
 * Fase 30: extrai o padrao Dompdf ja usado (identico) em
 * ResultadoAdminController::relatorioPdf()/relatorioNotasPdf() (Fases 23/26)
 * - essas duas telas continuam como estao, so' o codigo novo desta fase
 * passa a chamar este servico em vez de duplicar o bloco pela 3a vez.
 */
class PdfService
{
    public static function streamar($view, array $dados, $nomeArquivo, $orientacao = 'portrait', $tamanhoPapel = 'A4', $forcarDownload = false)
    {
        $html = View::renderizarString($view, $dados);

        ini_set('memory_limit', '256M');

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($tamanhoPapel, $orientacao);
        $dompdf->render();
        $dompdf->stream($nomeArquivo, ['Attachment' => $forcarDownload]);
        exit;
    }
}
