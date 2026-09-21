<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: notas lancadas pelo avaliador, por criterio, por designacao -
 * mesmo padrao de upsert de NotaLancadaRepository do Concurso
 * (ON DUPLICATE KEY UPDATE), inclusive a mesma limitacao ja existente la'
 * (Auditoria::registrar() nao guarda o valor anterior da nota, so' o
 * valor novo - ver achado da revisao desta fase, nao corrigido porque nao
 * foi apontado como bloqueante, e' o mesmo padrao ja aceito no Concurso).
 */
class TrabalhoNotaRepository
{
    public function listarPorDesignacao($designacaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_notas WHERE designacao_id = :designacao_id ORDER BY criterio_id ASC');
        $stmt->execute(['designacao_id' => $designacaoId]);

        return $stmt->fetchAll();
    }

    public function contarCriteriosNotados($designacaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM trabalho_notas WHERE designacao_id = :designacao_id');
        $stmt->execute(['designacao_id' => $designacaoId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Nota total (soma dos criterios ja lancados) de UMA designacao -
     * usado por TrabalhoResultadoService para compor a nota por avaliador
     * antes de agregar entre avaliadores.
     */
    public function somaPorDesignacao($designacaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(nota), 0) FROM trabalho_notas WHERE designacao_id = :designacao_id');
        $stmt->execute(['designacao_id' => $designacaoId]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Media das notas lancadas por qualquer avaliador designado a um
     * trabalho, num criterio especifico - usado por TrabalhoResultadoService
     * como valor de desempate por criterio (mesmo papel de
     * ResultadoEtapaService::valorDesempatePorSubmissao() no Concurso).
     * Retorna null se nenhum avaliador ainda lancou nota nesse criterio.
     */
    public function mediaPorTrabalhoECriterio($trabalhoId, $criterioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT AVG(n.nota)
             FROM trabalho_notas n
             INNER JOIN trabalho_designacoes d ON d.id = n.designacao_id
             WHERE d.trabalho_id = :trabalho_id AND n.criterio_id = :criterio_id'
        );
        $stmt->execute(['trabalho_id' => $trabalhoId, 'criterio_id' => $criterioId]);
        $media = $stmt->fetchColumn();

        return $media !== null ? (float) $media : null;
    }

    public function salvar($designacaoId, $criterioId, $nota)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO trabalho_notas (designacao_id, criterio_id, nota)
             VALUES (:designacao_id, :criterio_id, :nota)
             ON DUPLICATE KEY UPDATE nota = VALUES(nota)'
        );
        $stmt->execute(['designacao_id' => $designacaoId, 'criterio_id' => $criterioId, 'nota' => $nota]);

        Auditoria::registrar('salvar', 'trabalho_notas', $designacaoId, null, ['criterio_id' => $criterioId, 'nota' => $nota]);
    }
}
