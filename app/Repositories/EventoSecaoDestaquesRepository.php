<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 51: componente "Destaques" da pagina publica do Evento. No modo
 * vinculado, le as Atividades marcadas com destacar_na_pagina; no modo
 * digitado, mostra os itens cadastrados dentro do proprio componente.
 */
class EventoSecaoDestaquesRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_destaques';
    }

    protected function colunas()
    {
        return ['etiqueta', 'titulo', 'descricao_html', 'fonte', 'colunas', 'cor_fundo', 'cor_texto'];
    }

    protected function tabelaItens()
    {
        return 'evento_secao_destaques_itens';
    }

    protected function colunasItens()
    {
        return ['atividade_id', 'titulo', 'quando_texto', 'local', 'descricao', 'icone'];
    }

    /**
     * Itens digitados, com os dados da atividade vinculada quando houver: o
     * que foi digitado no item vence, para a chamada da pagina poder ser
     * diferente do nome oficial da atividade.
     */
    public function listarItensResolvidos($secaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT i.*, a.nome AS atividade_nome, a.local AS atividade_local,
                    a.data_inicio AS atividade_data_inicio, a.data_fim AS atividade_data_fim,
                    t.nome AS tipo_nome, t.cor AS tipo_cor
             FROM evento_secao_destaques_itens i
             LEFT JOIN evento_atividades a ON a.id = i.atividade_id
             LEFT JOIN evento_atividade_tipos t ON t.id = a.tipo_id
             WHERE i.secao_id = :secao_id
             ORDER BY i.ordem ASC, i.id ASC'
        );
        $stmt->execute(['secao_id' => $secaoId]);

        return $stmt->fetchAll();
    }

    /**
     * Atividades marcadas para destaque naquele evento, usadas quando a
     * secao esta no modo vinculado.
     */
    public function listarAtividadesDestacadas($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT a.*, t.nome AS tipo_nome, t.cor AS tipo_cor
             FROM evento_atividades a
             LEFT JOIN evento_atividade_tipos t ON t.id = a.tipo_id
             WHERE a.evento_id = :evento_id AND a.destacar_na_pagina = 1
             ORDER BY a.data_inicio ASC, a.id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }
}
