<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Texto editavel dos avisos por correio eletronico exclusivos do Evento
 * (migration 205). Sem linha, o aviso usa o texto padrao do codigo.
 */
class EventoModeloAvisoRepository
{
    public function buscarPorChave($chave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_modelos_aviso WHERE chave = :chave LIMIT 1');
        $stmt->execute(['chave' => (string) $chave]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Todas as linhas gravadas, indexadas pela chave do aviso.
     */
    public function listarPorChave()
    {
        $pdo = Database::conexao();
        $stmt = $pdo->query('SELECT * FROM evento_modelos_aviso');

        $porChave = [];

        foreach ($stmt->fetchAll() as $linha) {
            $porChave[$linha['chave']] = $linha;
        }

        return $porChave;
    }

    public function salvar($chave, $assunto, $corpoHtml, $usuarioId)
    {
        $antes = $this->buscarPorChave($chave);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_modelos_aviso (chave, assunto, corpo_html, atualizado_por)
             VALUES (:chave, :assunto, :corpo_html, :atualizado_por)
             ON DUPLICATE KEY UPDATE assunto = VALUES(assunto), corpo_html = VALUES(corpo_html), atualizado_por = VALUES(atualizado_por)'
        );
        $stmt->execute([
            'chave' => (string) $chave,
            'assunto' => (string) $assunto,
            'corpo_html' => (string) $corpoHtml,
            'atualizado_por' => $usuarioId !== null ? (int) $usuarioId : null,
        ]);

        $depois = $this->buscarPorChave($chave);
        Auditoria::registrar('salvar', 'evento_modelos_aviso', $depois !== null ? (int) $depois['id'] : null, $antes, $depois);
    }

    /**
     * Apaga a linha: o aviso volta ao texto padrao do codigo.
     */
    public function remover($chave)
    {
        $antes = $this->buscarPorChave($chave);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_modelos_aviso WHERE chave = :chave');
        $stmt->execute(['chave' => (string) $chave]);

        Auditoria::registrar('remover', 'evento_modelos_aviso', (int) $antes['id'], $antes, null);
    }
}
