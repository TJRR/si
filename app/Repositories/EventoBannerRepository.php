<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 50: faixas (Banner/Hero) proprias de cada Evento, tabela dedicada
 * (evento_banners), isolada de `banners` (Concurso). Mesmos enums de
 * BannerRepository, duplicados aqui de proposito (isolamento total, ver
 * plano da fase - esta classe nunca importa nem altera BannerRepository).
 */
class EventoBannerRepository extends EventoConteudoRepositorioBase
{
    public const CTA_DESTINO_TIPOS = ['link_interno', 'externo', 'ancora', 'arquivo', 'video'];
    public const CTA_POSICOES = [
        'superior_esquerda', 'superior_centro', 'superior_direita',
        'centro_esquerda', 'centro_centro', 'centro_direita',
        'inferior_esquerda', 'inferior_centro', 'inferior_direita',
    ];
    public const CTA_EFEITOS_HOVER = ['nenhum', 'escurecer', 'clarear', 'escala', 'borda', 'iluminar', 'inverter'];
    public const CONTEUDO_ALINHAMENTOS = ['esquerda', 'centro', 'direita'];

    protected function tabela()
    {
        return 'evento_banners';
    }

    protected function colunas()
    {
        return [
            'imagem_desktop_path', 'imagem_mobile_path', 'imagem_alt', 'cor_fundo',
            'conteudo_html', 'conteudo_alinhamento', 'cta_titulo', 'cta_destino_tipo', 'cta_destino_valor',
            'cta_posicao', 'cta_efeito_hover', 'ativo',
        ];
    }
}
