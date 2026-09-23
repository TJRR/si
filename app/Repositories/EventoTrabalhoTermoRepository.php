<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 51: termos de aceite da submissao de Trabalhos, configuraveis por
 * evento. Antes disso o unico aceite do processo era a declaracao fixa do
 * formulario externo, fora do sistema: agora cada edicao cadastra os seus
 * (aceite regulamentar, autorizacao de publicacao nos Anais, o que mais o
 * edital exigir), sem nada fixo em codigo.
 */
class EventoTrabalhoTermoRepository
{
    public function listar($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_trabalho_termos WHERE evento_id = :evento_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function listarAtivos($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_trabalho_termos WHERE evento_id = :evento_id AND ativo = 1 ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_trabalho_termos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function buscarDoEvento($eventoId, $id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_trabalho_termos WHERE id = :id AND evento_id = :evento_id LIMIT 1');
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM evento_trabalho_termos WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO evento_trabalho_termos (evento_id, rotulo, texto_html, obrigatorio, ativo, ordem)
             VALUES (:evento_id, :rotulo, :texto_html, :obrigatorio, :ativo, :ordem)'
        );
        $stmt->execute([
            'evento_id' => $eventoId,
            'rotulo' => $dados['rotulo'],
            'texto_html' => $dados['texto_html'],
            'obrigatorio' => !empty($dados['obrigatorio']) ? 1 : 0,
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'ordem' => $proximaOrdem,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_trabalho_termos', $id, null, $dados + ['evento_id' => $eventoId]);

        return $id;
    }

    public function atualizar($eventoId, $id, array $dados)
    {
        $antes = $this->buscarDoEvento($eventoId, $id);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_trabalho_termos
             SET rotulo = :rotulo, texto_html = :texto_html, obrigatorio = :obrigatorio, ativo = :ativo
             WHERE id = :id AND evento_id = :evento_id'
        );
        $stmt->execute([
            'rotulo' => $dados['rotulo'],
            'texto_html' => $dados['texto_html'],
            'obrigatorio' => !empty($dados['obrigatorio']) ? 1 : 0,
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'id' => $id,
            'evento_id' => $eventoId,
        ]);

        Auditoria::registrar('atualizar', 'evento_trabalho_termos', $id, $antes, $dados);
    }

    /**
     * Termo ja aceito por alguma submissao nunca e' apagado de verdade: o
     * aceite guarda o texto congelado, mas manter a linha preserva o vinculo
     * historico. A tela oferece desativar no lugar da exclusao nesse caso.
     */
    public function possuiAceites($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM trabalho_termos_aceitos WHERE termo_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function remover($eventoId, $id)
    {
        $antes = $this->buscarDoEvento($eventoId, $id);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_trabalho_termos WHERE id = :id AND evento_id = :evento_id');
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        Auditoria::registrar('remover', 'evento_trabalho_termos', $id, $antes, null);
    }

    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_trabalho_termos SET ordem = :ordem WHERE id = :id AND evento_id = :evento_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento_id' => $eventoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'evento_trabalho_termos', null, null, ['evento_id' => $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
