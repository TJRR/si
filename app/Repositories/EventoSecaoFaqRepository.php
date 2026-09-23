<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 51: componente "Perguntas frequentes" da pagina publica do Evento.
 * Cadastro proprio por evento, isolado do banco de perguntas do Concurso
 * (que serve o 5o Premio em avaliacao real): so o acordeao e compartilhado.
 */
class EventoSecaoFaqRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_faq';
    }

    protected function colunas()
    {
        return ['etiqueta', 'titulo', 'descricao_html', 'cor_fundo', 'cor_texto'];
    }

    protected function tabelaItens()
    {
        return 'evento_secao_faq_itens';
    }

    protected function colunasItens()
    {
        return ['pergunta', 'resposta_html', 'ativo'];
    }

    public function listarItensAtivos($secaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_secao_faq_itens WHERE secao_id = :secao_id AND ativo = 1 ORDER BY ordem ASC, id ASC');
        $stmt->execute(['secao_id' => $secaoId]);

        return $stmt->fetchAll();
    }
}
