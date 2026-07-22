<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.3 Banner/Hero) - banners empilhaveis abaixo do slideshow.
 * Fase 19 (#84 v2): deixou de ser escopado por concurso - e' configuracao
 * do site. Mesmo padrao de SlideRepository (enums expostos como
 * constantes, reordenar() em lote).
 */
class BannerRepository
{
    public const CTA_DESTINO_TIPOS = ['link_interno', 'externo', 'ancora', 'arquivo', 'video'];
    public const CTA_POSICOES = [
        'superior_esquerda', 'superior_centro', 'superior_direita',
        'centro_esquerda', 'centro_centro', 'centro_direita',
        'inferior_esquerda', 'inferior_centro', 'inferior_direita',
    ];
    public const CTA_EFEITOS_HOVER = ['nenhum', 'escurecer', 'clarear', 'escala', 'borda', 'iluminar', 'inverter'];
    public const CONTEUDO_ALINHAMENTOS = ['esquerda', 'centro', 'direita'];

    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM banners ORDER BY ordem ASC, id ASC')->fetchAll();
    }

    public function listarAtivos()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM banners WHERE ativo = 1 ORDER BY ordem ASC, id ASC')->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM banners WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $banner = $stmt->fetch();

        return $banner !== false ? $banner : null;
    }

    public function criar(array $dados)
    {
        $pdo = Database::conexao();
        $proximaOrdem = (int) $pdo->query('SELECT COALESCE(MAX(ordem), -1) + 1 FROM banners')->fetchColumn();

        $campos = $dados + ['ordem' => $proximaOrdem];

        $stmt = $pdo->prepare(
            'INSERT INTO banners (
                imagem_desktop_path, imagem_mobile_path, imagem_alt, cor_fundo,
                conteudo_html, conteudo_alinhamento, cta_titulo, cta_destino_tipo, cta_destino_valor, cta_posicao,
                cta_efeito_hover, ordem, ativo
            ) VALUES (
                :imagem_desktop_path, :imagem_mobile_path, :imagem_alt, :cor_fundo,
                :conteudo_html, :conteudo_alinhamento, :cta_titulo, :cta_destino_tipo, :cta_destino_valor, :cta_posicao,
                :cta_efeito_hover, :ordem, :ativo
            )'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'banners', $id, null, $campos);

        return $id;
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();

        $stmt = $pdo->prepare(
            'UPDATE banners SET
                imagem_desktop_path = :imagem_desktop_path,
                imagem_mobile_path = :imagem_mobile_path,
                imagem_alt = :imagem_alt,
                cor_fundo = :cor_fundo,
                conteudo_html = :conteudo_html,
                conteudo_alinhamento = :conteudo_alinhamento,
                cta_titulo = :cta_titulo,
                cta_destino_tipo = :cta_destino_tipo,
                cta_destino_valor = :cta_destino_valor,
                cta_posicao = :cta_posicao,
                cta_efeito_hover = :cta_efeito_hover,
                ativo = :ativo
             WHERE id = :id'
        );
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar', 'banners', $id, $antes, $dados);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM banners WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'banners', $id, $antes, null);
    }

    public function reordenar(array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE banners SET ordem = :ordem WHERE id = :id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'banners', null, null, ['ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
