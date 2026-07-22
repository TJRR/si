<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 19 (#97): ordem das secoes do meio da home (entre Banners e o
 * rodape), definida pelo Admin - mistura secoes "fixas" (estruturais,
 * sem linha propria de dado: trilhas/cronograma/temas/faq) e "bloco"
 * (uma linha por blocos_conteudo, incluindo Sobre/Premiacao/livres).
 */
class HomeSecaoOrdemRepository
{
    public function listarOrdenado()
    {
        $pdo = Database::conexao();

        return $pdo->query(
            'SELECT h.*, b.titulo AS bloco_titulo, b.chave AS bloco_chave
             FROM home_secoes_ordem h
             LEFT JOIN blocos_conteudo b ON b.id = h.bloco_id
             ORDER BY h.ordem ASC, h.id ASC'
        )->fetchAll();
    }

    /**
     * Chamado toda vez que um bloco de conteudo novo e' criado, pra
     * manter o invariante "todo bloco tem uma linha aqui" sem precisar
     * de fallback defensivo na query de listagem - sempre entra no fim.
     */
    public function registrarBloco($blocoId)
    {
        $pdo = Database::conexao();
        $proximaOrdem = (int) $pdo->query('SELECT COALESCE(MAX(ordem), -1) + 1 FROM home_secoes_ordem')->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO home_secoes_ordem (tipo, bloco_id, ordem) VALUES (\'bloco\', :bloco_id, :ordem)'
        );
        $stmt->execute(['bloco_id' => $blocoId, 'ordem' => $proximaOrdem]);

        Auditoria::registrar('registrar_bloco', 'home_secoes_ordem', (int) $pdo->lastInsertId(), null, ['bloco_id' => $blocoId, 'ordem' => $proximaOrdem]);
    }

    public function reordenar(array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE home_secoes_ordem SET ordem = :ordem WHERE id = :id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'home_secoes_ordem', null, null, ['ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
