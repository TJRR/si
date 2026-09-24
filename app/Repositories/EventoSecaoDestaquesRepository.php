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
    /**
     * Reabertura da Fase 51: lista fechada de icones dos cartoes de
     * destaque (desenho de traco, 24x24, cor herdada do texto). Chave e' o
     * valor gravado em evento_secao_destaques_itens.icone; para ampliar,
     * basta acrescentar uma entrada aqui, sem mudar banco.
     */
    public const ICONES = [
        'trofeu' => ['rotulo' => 'Troféu', 'svg' => '<path d="M8 21h8M12 17v4M7 4h10l-1 8a4 4 0 0 1-8 0L7 4z"/><path d="M5 4h2v3a3 3 0 0 1-3-3z"/><path d="M19 4h-2v3a3 3 0 0 0 3-3z"/>'],
        'musica' => ['rotulo' => 'Música', 'svg' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>'],
        'raio' => ['rotulo' => 'Raio', 'svg' => '<path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z"/>'],
        'predio' => ['rotulo' => 'Prédio', 'svg' => '<path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>'],
        'microfone' => ['rotulo' => 'Microfone', 'svg' => '<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5M8 22h8"/>'],
        'lampada' => ['rotulo' => 'Lâmpada', 'svg' => '<path d="M9 18h6M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V16h8v-1.3A7 7 0 0 0 12 2z"/>'],
        'calendario' => ['rotulo' => 'Calendário', 'svg' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
        'pessoas' => ['rotulo' => 'Pessoas', 'svg' => '<circle cx="9" cy="7" r="4"/><path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"/><path d="M16 3.1a4 4 0 0 1 0 7.8M22 21v-2a4 4 0 0 0-3-3.9"/>'],
        'livro' => ['rotulo' => 'Livro', 'svg' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>'],
        'computador' => ['rotulo' => 'Computador', 'svg' => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>'],
    ];

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
        return ['atividade_id', 'titulo', 'quando_texto', 'local', 'descricao', 'icone', 'icone_cor'];
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
