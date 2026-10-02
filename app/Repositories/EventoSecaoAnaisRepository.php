<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Componente "Anais" da pagina publica do Evento (migration 206). Sem lista
 * propria: mostra o volume publicado (EventoAnaisRepository) e, com
 * mostrar_selecionados ligado, a relacao publica dos trabalhos selecionados.
 */
class EventoSecaoAnaisRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_anais';
    }

    protected function colunas()
    {
        return ['etiqueta', 'titulo', 'descricao_html', 'mostrar_selecionados', 'cor_fundo', 'cor_texto'];
    }
}
