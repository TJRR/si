<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class CampoDinamicoRepository
{
    public function listarPorFormulario($formularioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM campos_dinamicos WHERE formulario_id = :formulario_id ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute(['formulario_id' => $formularioId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM campos_dinamicos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $campo = $stmt->fetch();

        return $campo !== false ? $campo : null;
    }

    public function contarPorFormulario($formularioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM campos_dinamicos WHERE formulario_id = :formulario_id');
        $stmt->execute(['formulario_id' => $formularioId]);

        return (int) $stmt->fetchColumn();
    }

    public function criar($formularioId, $rotulo, $tipo, $obrigatorio, array $config)
    {
        $pdo = Database::conexao();

        $stmtOrdem = $pdo->prepare(
            'SELECT COALESCE(MAX(ordem), 0) + 1 FROM campos_dinamicos WHERE formulario_id = :formulario_id'
        );
        $stmtOrdem->execute(['formulario_id' => $formularioId]);
        $ordem = (int) $stmtOrdem->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO campos_dinamicos (formulario_id, ordem, rotulo, tipo, obrigatorio, config_json)
             VALUES (:formulario_id, :ordem, :rotulo, :tipo, :obrigatorio, :config_json)'
        );
        $dados = [
            'formulario_id' => $formularioId,
            'ordem' => $ordem,
            'rotulo' => $rotulo,
            'tipo' => $tipo,
            'obrigatorio' => $obrigatorio,
            'config_json' => json_encode($config),
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'campos_dinamicos', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $rotulo, $tipo, $obrigatorio, array $config)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE campos_dinamicos
             SET rotulo = :rotulo, tipo = :tipo, obrigatorio = :obrigatorio, config_json = :config_json
             WHERE id = :id'
        );
        $depois = [
            'rotulo' => $rotulo,
            'tipo' => $tipo,
            'obrigatorio' => $obrigatorio,
            'config_json' => json_encode($config),
        ];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar', 'campos_dinamicos', $id, $antes, $depois);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM campos_dinamicos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'campos_dinamicos', $id, $antes, null);
    }

    public function mover($id, $direcao)
    {
        $pdo = Database::conexao();
        $campo = $this->buscarPorId($id);

        if ($campo === null) {
            return;
        }

        $operador = $direcao === 'cima' ? '<' : '>';
        $ordenacao = $direcao === 'cima' ? 'DESC' : 'ASC';

        $stmtVizinho = $pdo->prepare(
            "SELECT * FROM campos_dinamicos
             WHERE formulario_id = :formulario_id AND ordem {$operador} :ordem
             ORDER BY ordem {$ordenacao} LIMIT 1"
        );
        $stmtVizinho->execute(['formulario_id' => $campo['formulario_id'], 'ordem' => $campo['ordem']]);
        $vizinho = $stmtVizinho->fetch();

        if ($vizinho === false) {
            return;
        }

        $pdo->beginTransaction();

        try {
            $atualizarOrdem = $pdo->prepare('UPDATE campos_dinamicos SET ordem = :ordem WHERE id = :id');
            $atualizarOrdem->execute(['ordem' => $vizinho['ordem'], 'id' => $campo['id']]);
            $atualizarOrdem->execute(['ordem' => $campo['ordem'], 'id' => $vizinho['id']]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('mover', 'campos_dinamicos', $id, ['ordem' => $campo['ordem']], ['ordem' => $vizinho['ordem'], 'trocado_com_id' => $vizinho['id']]);
    }

    public function copiarTodosParaOutroFormulario($formularioOrigemId, $formularioDestinoId)
    {
        $campos = $this->listarPorFormulario($formularioOrigemId);

        foreach ($campos as $campo) {
            $config = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : [];
            $this->criar($formularioDestinoId, $campo['rotulo'], $campo['tipo'], $campo['obrigatorio'], $config);
        }
    }
}
