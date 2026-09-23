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

    /**
     * Fase 50 (achado de seguranca): WHERE inclui formulario_id, nao so' id -
     * sem isso, um id de campo de OUTRO formulario (de outra etapa/trilha
     * que o mesmo administrador nao deveria mexer) seria aceito e teria sua
     * ordem alterada, sem nenhuma checagem de posse. Mesmo padrao ja usado
     * por PremioRepository/FaqConcursoRepository/TemaRepository/
     * DesafioRepository (que ja faziam certo antes desta fase).
     */
    public function reordenar($formularioId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE campos_dinamicos SET ordem = :ordem WHERE id = :id AND formulario_id = :formulario_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'formulario_id' => $formularioId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'campos_dinamicos', null, null, ['formulario_id' => $formularioId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
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
