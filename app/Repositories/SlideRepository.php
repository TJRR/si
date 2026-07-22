<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.2 Slideshow) - slides do slideshow principal da home publica.
 * Fase 19 (#84 v2): deixou de ser escopado por concurso - e' configuracao
 * do site, nao da edicao. Enums fechados (cta_target/cta_tamanho/
 * cta_efeito_hover) sao expostos como constantes publicas para o Controller
 * validar entrada e para a view montar os <select>, evitando duplicar a
 * lista em dois lugares.
 */
class SlideRepository
{
    public const CTA_TARGETS = ['_self', '_blank'];
    public const CTA_TAMANHOS = ['pequeno', 'medio', 'grande'];
    public const CTA_EFEITOS_HOVER = ['nenhum', 'escurecer', 'clarear', 'escala', 'borda', 'iluminar', 'inverter'];
    public const EFEITOS_TRANSICAO = ['fade', 'slide', 'zoom'];
    public const OVERLAY_EFEITOS = ['nenhum', 'escurecer', 'vinheta', 'pontos', 'linhas', 'halftone', 'trama'];

    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM slides ORDER BY ordem ASC, id ASC')->fetchAll();
    }

    public function listarAtivos()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM slides WHERE ativo = 1 ORDER BY ordem ASC, id ASC')->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM slides WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $slide = $stmt->fetch();

        return $slide !== false ? $slide : null;
    }

    public function criar(array $dados)
    {
        $pdo = Database::conexao();
        $proximaOrdem = (int) $pdo->query('SELECT COALESCE(MAX(ordem), -1) + 1 FROM slides')->fetchColumn();

        $campos = $dados + ['ordem' => $proximaOrdem];

        $stmt = $pdo->prepare(
            'INSERT INTO slides (
                imagem_desktop_path, imagem_mobile_path, imagem_alt, cor_fundo,
                duracao_ms, efeito_transicao, overlay_efeito, overlay_cor, overlay_opacidade, titulo_html,
                separador_cor, cta_titulo, cta_link, cta_target, cta_cor_fundo, cta_cor_texto,
                cta_tamanho, cta_efeito_hover, cta_animacao_entrada, ordem, ativo
            ) VALUES (
                :imagem_desktop_path, :imagem_mobile_path, :imagem_alt, :cor_fundo,
                :duracao_ms, :efeito_transicao, :overlay_efeito, :overlay_cor, :overlay_opacidade, :titulo_html,
                :separador_cor, :cta_titulo, :cta_link, :cta_target, :cta_cor_fundo, :cta_cor_texto,
                :cta_tamanho, :cta_efeito_hover, :cta_animacao_entrada, :ordem, :ativo
            )'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'slides', $id, null, $campos);

        return $id;
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();

        $stmt = $pdo->prepare(
            'UPDATE slides SET
                imagem_desktop_path = :imagem_desktop_path,
                imagem_mobile_path = :imagem_mobile_path,
                imagem_alt = :imagem_alt,
                cor_fundo = :cor_fundo,
                duracao_ms = :duracao_ms,
                efeito_transicao = :efeito_transicao,
                overlay_efeito = :overlay_efeito,
                overlay_cor = :overlay_cor,
                overlay_opacidade = :overlay_opacidade,
                titulo_html = :titulo_html,
                separador_cor = :separador_cor,
                cta_titulo = :cta_titulo,
                cta_link = :cta_link,
                cta_target = :cta_target,
                cta_cor_fundo = :cta_cor_fundo,
                cta_cor_texto = :cta_cor_texto,
                cta_tamanho = :cta_tamanho,
                cta_efeito_hover = :cta_efeito_hover,
                cta_animacao_entrada = :cta_animacao_entrada,
                ativo = :ativo
             WHERE id = :id'
        );
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar', 'slides', $id, $antes, $dados);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM slides WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'slides', $id, $antes, null);
    }

    /**
     * Reordenacao em lote (arrastar-e-soltar/botoes cima-baixo) - recebe os
     * ids na nova ordem visual e grava o indice de cada um como 'ordem'.
     */
    public function reordenar(array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE slides SET ordem = :ordem WHERE id = :id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'slides', null, null, ['ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
