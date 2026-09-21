<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Fase 42: unico ponto do projeto que usa bacon/bacon-qr-code - primeira
 * dependencia de terceiros nova desde a Fase 1 (Implantar.md 13.1/13.2).
 * Escolhida em vez de endroid/qr-code (citada no prompt da fase) por ser a
 * biblioteca de baixo nivel que o proprio endroid embrulha: so' exige PHP
 * ^7.1 + ext-iconv (padrao) + 1 dependencia transitiva (dasprid/enum), sem
 * puxar Symfony inteiro nem exigir GD/Imagick - renderiza SVG puro, que
 * embute direto no HTML como string, sem gerar arquivo em disco.
 */
class QrCodeService
{
    public static function renderizarSvg($conteudo, $tamanhoPx = 240)
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle($tamanhoPx),
            new SvgImageBackEnd()
        ));

        return $writer->writeString((string) $conteudo);
    }
}
