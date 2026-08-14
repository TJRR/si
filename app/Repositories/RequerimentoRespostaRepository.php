<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 30: historico de respostas de um requerimento - "desfecho" e' a
 * diferenca central em relacao a duvida_respostas (Fase 29): toda resposta
 * aqui carrega uma de quatro vias (aprovado/recusado/esclarecimento_
 * solicitado/revogado), que e' tambem o proprio valor gravado em
 * requerimentos.status logo em seguida (ver RequerimentoRepository::
 * registrarDesfecho()/reabrirParaNovoEnvio()).
 */
class RequerimentoRespostaRepository
{
    public function criar($requerimentoId, $usuarioId, $desfecho, $resposta, $assinaturaConferida, $anexoPath, $anexoNomeOriginal)
    {
        $pdo = Database::conexao();
        $dados = [
            'requerimento_id' => $requerimentoId,
            'usuario_id' => $usuarioId,
            'desfecho' => $desfecho,
            'resposta' => $resposta,
            'assinatura_conferida' => $assinaturaConferida,
            'anexo_path' => $anexoPath,
            'anexo_nome_original' => $anexoNomeOriginal,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO requerimento_respostas (requerimento_id, usuario_id, desfecho, resposta, assinatura_conferida, anexo_path, anexo_nome_original)
             VALUES (:requerimento_id, :usuario_id, :desfecho, :resposta, :assinatura_conferida, :anexo_path, :anexo_nome_original)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'requerimento_respostas', $id, null, $dados);

        return $id;
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM requerimento_respostas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $resposta = $stmt->fetch();

        return $resposta !== false ? $resposta : null;
    }

    public function listarPorRequerimento($requerimentoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT rr.*, u.nome AS usuario_nome
             FROM requerimento_respostas rr
             INNER JOIN usuarios u ON u.id = rr.usuario_id
             WHERE rr.requerimento_id = :requerimento_id
             ORDER BY rr.criado_em ASC'
        );
        $stmt->execute(['requerimento_id' => $requerimentoId]);

        return $stmt->fetchAll();
    }

    /**
     * Respostas com anexo ainda nao expurgado, de um requerimento - usada
     * por ModeloDocumentoAdminController::expurgar() (so' remove o que ja
     * existia ate' o momento do expurgo, nunca uma resposta futura - ver
     * RequerimentoRepository, comentario da tabela requerimento_respostas).
     */
    public function listarComAnexoNaoExpurgadoPorRequerimento($requerimentoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM requerimento_respostas
             WHERE requerimento_id = :requerimento_id AND anexo_path IS NOT NULL AND anexo_expurgado_em IS NULL'
        );
        $stmt->execute(['requerimento_id' => $requerimentoId]);

        return $stmt->fetchAll();
    }

    public function marcarAnexoExpurgado($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare('UPDATE requerimento_respostas SET anexo_expurgado_em = NOW() WHERE id = :id')->execute(['id' => $id]);

        Auditoria::registrar('marcar_anexo_expurgado', 'requerimento_respostas', $id, $antes, ['anexo_expurgado_em' => date('Y-m-d H:i:s')]);
    }
}
