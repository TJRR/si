<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 51: componente "Contagem regressiva" da pagina publica do Evento.
 * Os itens sao as datas-chave listadas ao lado do relogio.
 */
class EventoSecaoContagemRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_contagem';
    }

    protected function colunas()
    {
        return ['etiqueta', 'titulo', 'descricao_html', 'data_alvo', 'cor_fundo', 'cor_texto', 'cor_circulo'];
    }

    protected function tabelaItens()
    {
        return 'evento_secao_contagem_itens';
    }

    protected function colunasItens()
    {
        return ['texto', 'data_referencia', 'cor_marcador'];
    }
}
