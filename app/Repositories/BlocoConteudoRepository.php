<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.4/3.6/3.7) - blocos de conteudo livre/extensiveis. Fase 19
 * (#84 v2): deixou de ser escopado por concurso - e' configuracao do site.
 * chave fixa ('sobre'/'premiacao') identifica os 2 blocos padrao
 * pre-criados (ver garantirBlocosPadrao()); chave NULL = bloco livre
 * criado pelo admin. secao_ancora alimenta o menu dinamico do cabecalho
 * e o scrollspy publico. mostrar_no_menu/mostrar_no_rodape (Fase 19
 * #86/#84 v2) controlam, independentemente um do outro, se o bloco vira
 * atalho no menu superior e/ou na coluna "Navegacao" do rodape.
 */
class BlocoConteudoRepository
{
    public const IMAGEM_POSICOES = ['esquerda', 'direita'];
    public const CTA_ALINHAMENTOS = ['esquerda', 'centro', 'direita'];

    private const BLOCOS_PADRAO = [
        'sobre' => ['titulo' => 'Sobre o Prêmio', 'ancora' => 'sobre', 'ordem' => 0],
        'premiacao' => ['titulo' => 'Premiação', 'ancora' => 'premiacao', 'ordem' => 1],
    ];

    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM blocos_conteudo ORDER BY ordem ASC, id ASC')->fetchAll();
    }

    public function listarAtivos()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM blocos_conteudo WHERE ativo = 1 ORDER BY ordem ASC, id ASC')->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM blocos_conteudo WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $bloco = $stmt->fetch();

        return $bloco !== false ? $bloco : null;
    }

    public function buscarPorChave($chave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM blocos_conteudo WHERE chave = :chave LIMIT 1');
        $stmt->execute(['chave' => $chave]);

        $bloco = $stmt->fetch();

        return $bloco !== false ? $bloco : null;
    }

    /**
     * Garante que os 2 blocos padrao ("Sobre"/"Premiação") existem -
     * chamado no inicio da tela admin desta entidade.
     */
    public function garantirBlocosPadrao()
    {
        foreach (self::BLOCOS_PADRAO as $chave => $definicao) {
            if ($this->buscarPorChave($chave) !== null) {
                continue;
            }

            $novoId = $this->criar([
                'chave' => $chave,
                'titulo' => $definicao['titulo'],
                'conteudo_html' => '',
                'imagem_path' => null,
                'imagem_alt' => null,
                'imagem_posicao' => 'esquerda',
                'cta_titulo' => null,
                'cta_link' => null,
                'cta_alinhamento' => 'esquerda',
                'secao_ancora' => $definicao['ancora'],
                'ativo' => 1,
                'mostrar_no_menu' => 0,
                'mostrar_no_rodape' => 1,
            ], $definicao['ordem']);

            // Fase 19 (#97): so' relevante numa instalacao nova - o
            // ambiente atual ja foi semeado com a ordem visual de hoje
            // pela migration 091 (que roda antes de qualquer bloco
            // padrao "faltar"). registrarBloco() sempre poe no fim da
            // lista, entao aqui os padroes entram no fim, nao nas
            // posicoes 1/4 que a migration usa - aceitavel pra uma
            // reinstalacao do zero.
            (new HomeSecaoOrdemRepository())->registrarBloco($novoId);
        }
    }

    public function criar(array $dados, $ordemForcada = null)
    {
        $pdo = Database::conexao();

        if ($ordemForcada !== null) {
            $proximaOrdem = $ordemForcada;
        } else {
            $proximaOrdem = (int) $pdo->query('SELECT COALESCE(MAX(ordem), -1) + 1 FROM blocos_conteudo')->fetchColumn();
        }

        $campos = $dados + ['ordem' => $proximaOrdem];
        $campos += ['chave' => null, 'mostrar_no_menu' => 0, 'mostrar_no_rodape' => 1];

        $stmt = $pdo->prepare(
            'INSERT INTO blocos_conteudo (
                chave, titulo, conteudo_html, imagem_path, imagem_alt, imagem_posicao,
                cta_titulo, cta_link, cta_alinhamento, secao_ancora, ordem, ativo, mostrar_no_menu, mostrar_no_rodape
            ) VALUES (
                :chave, :titulo, :conteudo_html, :imagem_path, :imagem_alt, :imagem_posicao,
                :cta_titulo, :cta_link, :cta_alinhamento, :secao_ancora, :ordem, :ativo, :mostrar_no_menu, :mostrar_no_rodape
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
                imagem_posicao = :imagem_posicao,
                cta_titulo = :cta_titulo,
                cta_link = :cta_link,
                cta_alinhamento = :cta_alinhamento,
                secao_ancora = :secao_ancora,
                ativo = :ativo,
                mostrar_no_menu = :mostrar_no_menu,
                mostrar_no_rodape = :mostrar_no_rodape
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

    public function reordenar(array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE blocos_conteudo SET ordem = :ordem WHERE id = :id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'blocos_conteudo', null, null, ['ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
