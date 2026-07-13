<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class FormularioDinamicoRepository
{
    public function listar($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM formularios_dinamicos WHERE concurso_id = :concurso_id ORDER BY nome ASC, versao ASC');
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM formularios_dinamicos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $formulario = $stmt->fetch();

        return $formulario !== false ? $formulario : null;
    }

    public function criar($concursoId, $nome, $descricao, $versao = 1, $status = 'rascunho')
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO formularios_dinamicos (concurso_id, nome, descricao, versao, status) VALUES (:concurso_id, :nome, :descricao, :versao, :status)'
        );
        $dados = [
            'concurso_id' => $concursoId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'versao' => $versao,
            'status' => $status,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'formularios_dinamicos', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $nome, $descricao)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE formularios_dinamicos SET nome = :nome, descricao = :descricao WHERE id = :id');
        $depois = [
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
        ];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar', 'formularios_dinamicos', $id, $antes, $depois);
    }

    public function atualizarStatus($id, $status)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE formularios_dinamicos SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        Auditoria::registrar('atualizar_status', 'formularios_dinamicos', $id, $antes, ['status' => $status]);
    }

    /**
     * Remocao real (sem soft-delete) — ver EtapaRepository::remover() para a
     * explicacao de por que a FK (sem CASCADE) ja protege contra remover um
     * formulario com campos, etapas vinculadas ou submissoes.
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM formularios_dinamicos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'formularios_dinamicos', $id, $antes, null);
    }
}
