<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 17 (Bug 2): nivel "Desafio", filho de Tema (TemaRepository). Guarda o
 * texto integral da pergunta do desafio - e' isso que a equipe escolhe no
 * formulario de submissao e o avaliador le/compara com a resposta.
 */
class DesafioRepository
{
    public function listarPorTema($temaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM desafios WHERE tema_id = :tema_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['tema_id' => $temaId]);

        return $stmt->fetchAll();
    }

    /**
     * Todos os desafios de uma trilha (ativos e inativos), com o nome do tema
     * pai junto - usado pela resolucao de texto livre na importacao (nao deve
     * deixar de casar so' porque o Admin desativou o desafio depois).
     */
    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT d.*, t.nome AS tema_nome
             FROM desafios d
             JOIN temas t ON t.id = d.tema_id
             WHERE t.trilha_id = :trilha_id
             ORDER BY t.ordem ASC, t.nome ASC, d.ordem ASC, d.id ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    /**
     * Desafios ativos de uma trilha, com o nome do tema pai junto (usado para
     * popular o <select> do formulario publico, agrupado por tema via
     * <optgroup>).
     */
    public function listarAtivosPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT d.*, t.nome AS tema_nome
             FROM desafios d
             JOIN temas t ON t.id = d.tema_id
             WHERE t.trilha_id = :trilha_id AND d.ativo = 1 AND t.ativo = 1
             ORDER BY t.ordem ASC, t.nome ASC, d.ordem ASC, d.id ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM desafios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $desafio = $stmt->fetch();

        return $desafio !== false ? $desafio : null;
    }

    public function criar($temaId, $pergunta, $ativo, $icone = null, $ordem = 0)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO desafios (tema_id, pergunta, ativo, icone, ordem) VALUES (:tema_id, :pergunta, :ativo, :icone, :ordem)'
        );
        $dados = ['tema_id' => $temaId, 'pergunta' => $pergunta, 'ativo' => $ativo, 'icone' => $icone, 'ordem' => $ordem];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'desafios', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $pergunta, $ativo, $icone = null, $ordem = 0)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE desafios SET pergunta = :pergunta, ativo = :ativo, icone = :icone, ordem = :ordem WHERE id = :id');
        $depois = ['pergunta' => $pergunta, 'ativo' => $ativo, 'icone' => $icone, 'ordem' => $ordem];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar', 'desafios', $id, $antes, $depois);
    }

    /**
     * Remocao real (sem soft-delete) — a FK de "equipes.desafio_id" (sem
     * CASCADE) ja protege contra remover um desafio com equipes vinculadas;
     * quem chama deve capturar PDOException codigo 23000 (mesmo padrao de
     * TemaRepository::remover()/antigo TemaDesafioRepository::remover()).
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM desafios WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'desafios', $id, $antes, null);
    }

    /**
     * Fase 19 (#102): reordenacao em lote por arrastar/botoes, mesmo padrao
     * de BlocoConteudoRepository::reordenar()/SlideRepository::reordenar().
     */
    public function reordenar($temaId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE desafios SET ordem = :ordem WHERE id = :id AND tema_id = :tema_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'tema_id' => $temaId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'desafios', null, null, ['tema_id' => $temaId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
