<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 54: configuracao de Estandes por evento. Hoje so' o texto editavel
 * que acompanha o convite ao representante; a linha nasce no primeiro
 * salvamento da tela Estandes, Configuracoes.
 */
class EstandeConfigRepository
{
    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_estandes_config WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function mensagemConvite($eventoId)
    {
        $config = $this->buscarPorEvento($eventoId);

        return $config !== null ? (string) $config['mensagem_convite_html'] : '';
    }

    public function salvar($eventoId, $mensagemConviteHtml)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_estandes_config (evento_id, mensagem_convite_html)
             VALUES (:evento_id, :mensagem)
             ON DUPLICATE KEY UPDATE mensagem_convite_html = VALUES(mensagem_convite_html)'
        );
        $stmt->execute(['evento_id' => $eventoId, 'mensagem' => $mensagemConviteHtml]);

        Auditoria::registrar('salvar', 'evento_estandes_config', (int) $eventoId, $antes, ['mensagem_convite_html' => $mensagemConviteHtml]);
    }
}
