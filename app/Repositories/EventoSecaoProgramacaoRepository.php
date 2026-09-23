<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 51: componente "Programacao completa" da pagina publica do Evento,
 * com abas por dia. No modo vinculado, le as Atividades do evento e agrupa
 * por dia e turno sozinho; no modo digitado, usa os itens do componente.
 */
class EventoSecaoProgramacaoRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_programacao';
    }

    protected function colunas()
    {
        return ['etiqueta', 'titulo', 'descricao_html', 'fonte', 'mostrar_local', 'cor_fundo', 'cor_texto'];
    }

    protected function tabelaItens()
    {
        return 'evento_secao_programacao_itens';
    }

    protected function colunasItens()
    {
        return ['atividade_id', 'dia', 'turno', 'horario_texto', 'tipo_texto', 'titulo', 'local', 'descricao'];
    }

    /**
     * Atividades do evento no formato ja usado pela pagina: dia, turno
     * (manha ate 12h, tarde ate 18h, noite depois disso), horario legivel e
     * o tipo cadastrado como etiqueta.
     */
    public function listarAtividadesDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT a.id, a.nome, a.local, a.data_inicio, a.data_fim,
                    t.nome AS tipo_nome, t.cor AS tipo_cor
             FROM evento_atividades a
             LEFT JOIN evento_atividade_tipos t ON t.id = a.tipo_id
             WHERE a.evento_id = :evento_id
             ORDER BY a.data_inicio ASC, a.id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }
}
