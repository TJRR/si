<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.4/3.6/3.7) - blocos de conteudo livre/extensiveis, escopados por
 * concurso. chave fixa ('sobre'/'premiacao') identifica os 2 blocos padrao
 * pre-criados por edicao (ver garantirBlocosPadrao()); chave NULL = bloco
 * livre criado pelo admin. secao_ancora alimenta o menu dinamico do
 * cabecalho e o scrollspy publico.
 */
class BlocoConteudoRepository
{
    private const BLOCOS_PADRAO = [
        'sobre' => ['titulo' => 'Sobre o Prêmio', 'ancora' => 'sobre', 'ordem' => 0],
        'premiacao' => ['titulo' => 'Premiação', 'ancora' => 'premiacao', 'ordem' => 1],
    ];

    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM blocos_conteudo WHERE concurso_id = :concurso_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function listarAtivosPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM blocos_conteudo WHERE concurso_id = :concurso_id AND ativo = 1 ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM blocos_conteudo WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $bloco = $stmt->fetch();

        return $bloco !== false ? $bloco : null;
    }

    public function buscarPorConcursoEChave($concursoId, $chave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM blocos_conteudo WHERE concurso_id = :concurso_id AND chave = :chave LIMIT 1');
        $stmt->execute(['concurso_id' => $concursoId, 'chave' => $chave]);

        $bloco = $stmt->fetch();

        return $bloco !== false ? $bloco : null;
    }

    /**
     * Garante que os 2 blocos padrao ("Sobre"/"Premiação") existem para o
     * concurso - chamado no inicio de toda tela admin desta entidade, para
     * que concursos ja existentes (inclusive os criados antes desta fase)
     * ganhem os blocos automaticamente, sem precisar de um passo manual.
     */
    public function garantirBlocosPadrao($concursoId)
    {
        foreach (self::BLOCOS_PADRAO as $chave => $definicao) {
            if ($this->buscarPorConcursoEChave($concursoId, $chave) !== null) {
                continue;
            }

            $this->criar($concursoId, [
                'chave' => $chave,
                'titulo' => $definicao['titulo'],
                'conteudo_html' => '',
                'imagem_path' => null,
                'imagem_alt' => null,
                'cta_titulo' => null,
                'cta_link' => null,
                'secao_ancora' => $definicao['ancora'],
                'ativo' => 1,
            ], $definicao['ordem']);
        }
    }

    public function criar($concursoId, array $dados, $ordemForcada = null)
    {
        $pdo = Database::conexao();

        if ($ordemForcada !== null) {
            $proximaOrdem = $ordemForcada;
        } else {
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM blocos_conteudo WHERE concurso_id = :concurso_id');
            $stmt->execute(['concurso_id' => $concursoId]);
            $proximaOrdem = (int) $stmt->fetchColumn();
        }

        $campos = $dados + ['concurso_id' => $concursoId, 'ordem' => $proximaOrdem];
        $campos += ['chave' => null];

        $stmt = $pdo->prepare(
            'INSERT INTO blocos_conteudo (
                concurso_id, chave, titulo, conteudo_html, imagem_path, imagem_alt,
                cta_titulo, cta_link, secao_ancora, ordem, ativo
            ) VALUES (
                :concurso_id, :chave, :titulo, :conteudo_html, :imagem_path, :imagem_alt,
                :cta_titulo, :cta_link, :secao_ancora, :ordem, :ativo
            )'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'blocos_conteudo', $id, null, $campos);

        return $id;
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();

        $stmt = $pdo->prepare(
            'UPDATE blocos_conteudo SET
                titulo = :titulo,
                conteudo_html = :conteudo_html,
                imagem_path = :imagem_path,
                imagem_alt = :imagem_alt,
                cta_titulo = :cta_titulo,
                cta_link = :cta_link,
                secao_ancora = :secao_ancora,
                ativo = :ativo
             WHERE id = :id'
        );
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar', 'blocos_conteudo', $id, $antes, $dados);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM blocos_conteudo WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'blocos_conteudo', $id, $antes, null);
    }

    public function reordenar($concursoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE blocos_conteudo SET ordem = :ordem WHERE id = :id AND concurso_id = :concurso_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'concurso_id' => $concursoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'blocos_conteudo', null, null, ['concurso_id' => $concursoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
