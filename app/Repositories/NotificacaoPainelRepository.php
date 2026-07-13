<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Notificacoes do sino no painel (in-app) - nao confundir com
 * NotificacaoRepository, que e' a fila/log de envio de e-mail (tabela
 * `notificacoes`, migration 017).
 */
class NotificacaoPainelRepository
{
    public function criar($usuarioId, $tipo, $titulo, $mensagem, array $dados = null)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO notificacoes_painel (usuario_id, tipo, titulo, mensagem, dados)
             VALUES (:usuario_id, :tipo, :titulo, :mensagem, :dados)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'dados' => $dados !== null ? json_encode($dados) : null,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'notificacoes_painel', $id, null, [
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'dados' => $dados,
        ]);

        return $id;
    }

    /**
     * So cria se o usuario ainda nao tiver nenhuma notificacao desse tipo -
     * evita duplicar a cada acesso enquanto a condicao (ex.: CPF invalido)
     * continuar valendo.
     */
    public function garantirUnica($usuarioId, $tipo, $titulo, $mensagem, array $dados = null)
    {
        if ($this->existeDoTipo($usuarioId, $tipo)) {
            return;
        }

        $this->criar($usuarioId, $tipo, $titulo, $mensagem, $dados);
    }

    public function existeDoTipo($usuarioId, $tipo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM notificacoes_painel WHERE usuario_id = :usuario_id AND tipo = :tipo'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'tipo' => $tipo]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Auto-cura: remove notificacoes de um tipo quando a condicao que as
     * gerou deixa de existir (ex.: participante corrigiu o CPF).
     */
    public function removerPorTipo($usuarioId, $tipo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM notificacoes_painel WHERE usuario_id = :usuario_id AND tipo = :tipo');
        $stmt->execute(['usuario_id' => $usuarioId, 'tipo' => $tipo]);

        Auditoria::registrar('remover_por_tipo', 'notificacoes_painel', $usuarioId, null, ['tipo' => $tipo]);
    }

    public function listarRecentes($usuarioId, $limite = 10)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM notificacoes_painel WHERE usuario_id = :usuario_id ORDER BY registrado_em DESC LIMIT ' . (int) $limite
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function contarNaoLidas($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notificacoes_painel WHERE usuario_id = :usuario_id AND lida = 0');
        $stmt->execute(['usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM notificacoes_painel WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $notificacao = $stmt->fetch();

        return $notificacao !== false ? $notificacao : null;
    }

    public function marcarLida($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE notificacoes_painel SET lida = 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('marcar_lida', 'notificacoes_painel', $id, $antes, ['lida' => 1]);
    }

    public function marcarTodasLidas($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE notificacoes_painel SET lida = 1 WHERE usuario_id = :usuario_id AND lida = 0');
        $stmt->execute(['usuario_id' => $usuarioId]);

        Auditoria::registrar('marcar_todas_lidas', 'notificacoes_painel', $usuarioId, null, ['lida' => 1]);
    }
}
