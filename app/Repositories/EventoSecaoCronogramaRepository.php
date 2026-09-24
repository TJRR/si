<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 51: componente "Cronograma" da pagina publica do Evento (linha do
 * tempo de marcos, ex.: o cronograma de submissao do edital). Desde a
 * reabertura da fase, a secao tem duas colunas: texto com tres botoes e
 * contato a esquerda, quadro com a linha do tempo a direita.
 */
class EventoSecaoCronogramaRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_cronograma';
    }

    protected function colunas()
    {
        return [
            'etiqueta', 'titulo', 'descricao_html', 'titulo_quadro',
            'botao1_titulo', 'botao1_documento_id', 'botao1_link', 'botao2_titulo', 'botao2_link',
            'botao3_titulo', 'botao3_documento_id', 'botao3_link',
            'mostrar_contato', 'cor_fundo', 'cor_texto',
        ];
    }

    protected function tabelaItens()
    {
        return 'evento_secao_cronograma_itens';
    }

    protected function colunasItens()
    {
        return ['periodo_texto', 'descricao', 'data_referencia', 'cor'];
    }
}
