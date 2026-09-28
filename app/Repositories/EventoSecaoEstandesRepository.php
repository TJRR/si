<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 54: componente "Estandes" da pagina publica do Evento. Componente
 * sem lista propria: os itens sao os estandes ativos do evento
 * (EstandeRepository::listarAtivosPublico()), como Destaques e Programacao
 * fazem com as Atividades.
 */
class EventoSecaoEstandesRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_estandes';
    }

    protected function colunas()
    {
        return ['etiqueta', 'titulo', 'descricao_html', 'cor_fundo', 'cor_texto'];
    }
}
