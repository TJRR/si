<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 50: blocos de conteudo proprios de cada Evento, tabela dedicada
 * (evento_blocos_conteudo), isolada de `blocos_conteudo` (Concurso). Sem
 * `chave`/garantirBlocosPadrao() - Evento nao tem blocos padrao fixos tipo
 * "Sobre"/"Premiacao", todo bloco de Evento e livre. A ordem por arrasto e
 * controlada por EventoSecaoOrdemRepository (nao pela coluna `ordem` desta
 * tabela) - o controller admin chama registrarBloco() logo apos criar(),
 * mesmo ponto de chamada usado hoje por BlocoConteudoAdminController com
 * HomeSecaoOrdemRepository.
 */
class EventoBlocoConteudoRepository extends EventoConteudoRepositorioBase
{
    public const IMAGEM_POSICOES = ['esquerda', 'direita'];
    public const CTA_ALINHAMENTOS = ['esquerda', 'centro', 'direita'];

    protected function tabela()
    {
        return 'evento_blocos_conteudo';
    }

    protected function colunas()
    {
        return [
            'etiqueta', 'etiqueta_cor', 'titulo', 'conteudo_html', 'imagem_path', 'imagem_alt', 'imagem_posicao',
            'cor_fundo', 'cor_texto', 'usar_cor_rodape',
            'cta_titulo', 'cta_link', 'cta_cor_fundo', 'cta_cor_texto', 'cta_alinhamento',
            'cta2_titulo', 'cta2_link', 'cta2_cor_fundo', 'cta2_cor_texto',
            'secao_ancora', 'ativo',
        ];
    }
}
