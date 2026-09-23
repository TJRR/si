<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 50: slideshow proprio de cada Evento, tabela dedicada
 * (evento_slides), isolada de `slides` (Concurso). Mesmos enums de
 * SlideRepository, duplicados aqui de proposito (isolamento total, ver
 * plano da fase - esta classe nunca importa nem altera SlideRepository).
 */
class EventoSlideRepository extends EventoConteudoRepositorioBase
{
    public const CTA_TARGETS = ['_self', '_blank'];
    public const CTA_TAMANHOS = ['pequeno', 'medio', 'grande'];
    public const CTA_EFEITOS_HOVER = ['nenhum', 'escurecer', 'clarear', 'escala', 'borda', 'iluminar', 'inverter'];
    public const EFEITOS_TRANSICAO = ['fade', 'slide', 'zoom'];
    public const OVERLAY_EFEITOS = ['nenhum', 'escurecer', 'vinheta', 'pontos', 'linhas', 'halftone', 'trama'];

    protected function tabela()
    {
        return 'evento_slides';
    }

    protected function colunas()
    {
        return [
            'imagem_desktop_path', 'imagem_mobile_path', 'imagem_alt', 'cor_fundo',
            'duracao_ms', 'efeito_transicao', 'overlay_efeito', 'overlay_cor', 'overlay_opacidade',
            'etiqueta_texto', 'etiqueta_cor_fundo', 'etiqueta_cor_texto',
            'titulo_html', 'separador_cor', 'cta_titulo', 'cta_link', 'cta_target',
            'cta_cor_fundo', 'cta_cor_texto', 'cta_tamanho', 'cta_efeito_hover', 'cta_animacao_entrada',
            'cta2_titulo', 'cta2_link', 'cta2_target', 'cta2_cor_fundo', 'cta2_cor_texto',
            'ativo',
        ];
    }
}
