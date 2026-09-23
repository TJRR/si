<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 51: componente "Local e acesso" da pagina publica do Evento.
 * Componente sem lista: nao tem tabela de itens, so a instancia.
 */
class EventoSecaoLocalRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_local';
    }

    protected function colunas()
    {
        return [
            'etiqueta', 'titulo', 'endereco', 'descricao_html',
            'mapa_embed_url', 'mapa_link', 'imagem_path', 'imagem_alt',
            'cor_fundo', 'cor_texto',
        ];
    }
}
