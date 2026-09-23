<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 51: componente "Cronograma" da pagina publica do Evento (linha do
 * tempo de marcos, ex.: o cronograma de submissao do edital).
 */
class EventoSecaoCronogramaRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_cronograma';
    }

    protected function colunas()
    {
        return ['etiqueta', 'titulo', 'descricao_html', 'cor_fundo', 'cor_texto'];
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
