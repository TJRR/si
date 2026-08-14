<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 30: modelos de documento (Requerimento, Termo etc.) cadastrados pelo
 * Administrador dentro de uma Etapa - pode haver mais de um por etapa. Ver
 * RequerimentoRepository para os pedidos que o lider protocola a partir de
 * um modelo.
 */
class ModeloDocumentoRepository
{
    /**
     * Ordem nunca vem de formulário - um modelo novo sempre entra no fim da
     * lista (mesmo padrão de CampoDinamicoRepository::criar()); a posição
     * definitiva é ajustada depois arrastando na lista (ver reordenar()).
     */
    public function criar($etapaId, $nome, $finalidade, $corpoHtml, $ativo)
    {
        $pdo = Database::conexao();

        $stmtOrdem = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM modelos_documento WHERE etapa_id = :etapa_id');
        $stmtOrdem->execute(['etapa_id' => $etapaId]);
        $ordem = (int) $stmtOrdem->fetchColumn();

        $dados = [
            'etapa_id' => $etapaId,
            'nome' => $nome,
            'finalidade' => $finalidade,
            'corpo_html' => $corpoHtml,
            'ativo' => $ativo,
            'ordem' => $ordem,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO modelos_documento (etapa_id, nome, finalidade, corpo_html, ativo, ordem)
             VALUES (:etapa_id, :nome, :finalidade, :corpo_html, :ativo, :ordem)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'modelos_documento', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $nome, $finalidade, $corpoHtml, $ativo)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE modelos_documento SET nome = :nome, finalidade = :finalidade, corpo_html = :corpo_html, ativo = :ativo
             WHERE id = :id'
        );
        $depois = [
            'nome' => $nome,
            'finalidade' => $finalidade,
            'corpo_html' => $corpoHtml,
            'ativo' => $ativo,
        ];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar', 'modelos_documento', $id, $antes, $depois);
    }

    /**
     * Grava a nova ordem em lote (índice do array = nova posição) - mesmo
     * padrão de DocumentoRepository::reordenar() (Fase 29, arrastar-e-soltar
     * de Documentos), usado pela lista de Modelos de Documento.
     */
    public function reordenar(array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE modelos_documento SET ordem = :ordem WHERE id = :id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'modelos_documento', null, null, ['ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM modelos_documento WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $modelo = $stmt->fetch();

        return $modelo !== false ? $modelo : null;
    }

    public function listarPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM modelos_documento WHERE etapa_id = :etapa_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    /**
     * Modelos ativos de qualquer etapa da trilha da equipe - o controller
     * ainda filtra cada etapa por AcessoEtapaService::motivoBloqueio(), que
     * precisa do equipe_id e por isso nao entra nesta consulta.
     */
    public function listarAtivosPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT md.*
             FROM modelos_documento md
             INNER JOIN etapas e ON e.id = md.etapa_id
             WHERE e.trilha_id = :trilha_id AND md.ativo = 1
             ORDER BY e.ordem ASC, md.ordem ASC, md.id ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM modelos_documento WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'modelos_documento', $id, $antes, null);
    }
}
