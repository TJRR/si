<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 51: componente "Cartoes" da pagina publica do Evento. Cada item
 * aponta para um eixo tematico ja cadastrado (texto completo vem de la,
 * fonte unica) ou carrega titulo e texto proprios.
 */
class EventoSecaoCartoesRepository extends EventoSecaoRepositorioBase
{
    protected function tabela()
    {
        return 'evento_secao_cartoes';
    }

    protected function colunas()
    {
        return ['etiqueta', 'titulo', 'descricao_html', 'colunas', 'efeito_hover', 'efeito_abrir', 'efeito_fechar', 'cor_fundo', 'cor_texto'];
    }

    protected function tabelaItens()
    {
        return 'evento_secao_cartoes_itens';
    }

    protected function colunasItens()
    {
        return ['eixo_tematico_id', 'etiqueta', 'titulo', 'resumo', 'detalhe_html', 'cor', 'cor_fundo'];
    }

    /**
     * Itens ja resolvidos para exibicao: quando o cartao aponta para um eixo
     * tematico, o titulo e o texto completo vem de la (sem copia), e o que
     * foi digitado no cartao continua tendo preferencia quando existir.
     */
    public function listarItensResolvidos($secaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT i.*, e.nome AS eixo_nome, e.descricao AS eixo_descricao
             FROM evento_secao_cartoes_itens i
             LEFT JOIN trabalho_eixos_tematicos e ON e.id = i.eixo_tematico_id
             WHERE i.secao_id = :secao_id
             ORDER BY i.ordem ASC, i.id ASC'
        );
        $stmt->execute(['secao_id' => $secaoId]);

        return array_map(function (array $item) {
            $item['titulo_exibicao'] = trim((string) $item['titulo']) !== ''
                ? $item['titulo']
                : (string) $item['eixo_nome'];
            $item['detalhe_exibicao'] = trim((string) $item['detalhe_html']) !== ''
                ? $item['detalhe_html']
                : ($item['eixo_descricao'] !== null ? '<p>' . htmlspecialchars($item['eixo_descricao'], ENT_QUOTES, 'UTF-8') . '</p>' : '');

            return $item;
        }, $stmt->fetchAll());
    }
}
