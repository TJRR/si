<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class LogAuditoriaRepository
{
    public function registrar(array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO log_auditoria (usuario_id, acao, entidade, entidade_id, ip_origem, dados_antes, dados_depois, mensagem)
             VALUES (:usuario_id, :acao, :entidade, :entidade_id, :ip_origem, :dados_antes, :dados_depois, :mensagem)'
        );
        $stmt->execute([
            'usuario_id' => $dados['usuario_id'],
            'acao' => $dados['acao'],
            'entidade' => $dados['entidade'],
            'entidade_id' => $dados['entidade_id'],
            'ip_origem' => $dados['ip_origem'],
            'dados_antes' => $dados['dados_antes'],
            'dados_depois' => $dados['dados_depois'],
            'mensagem' => $dados['mensagem'],
        ]);
    }

    public function listar(array $filtros, $limite, $offset)
    {
        list($sqlWhere, $params) = $this->montarWhere($filtros);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT l.*, u.nome AS usuario_nome
             FROM log_auditoria l
             LEFT JOIN usuarios u ON u.id = l.usuario_id
             $sqlWhere
             ORDER BY l.criado_em DESC, l.id DESC
             LIMIT :limite OFFSET :offset"
        );
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->bindValue(':limite', (int) $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contar(array $filtros)
    {
        list($sqlWhere, $params) = $this->montarWhere($filtros);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM log_auditoria l $sqlWhere");
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function listarAcoesDistintas()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT DISTINCT acao FROM log_auditoria ORDER BY acao ASC')->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function montarWhere(array $filtros)
    {
        $condicoes = [];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $condicoes[] = 'l.usuario_id = :usuario_id';
            $params[':usuario_id'] = $filtros['usuario_id'];
        }
        if (!empty($filtros['acao'])) {
            $condicoes[] = 'l.acao = :acao';
            $params[':acao'] = $filtros['acao'];
        }
        if (!empty($filtros['data_inicio'])) {
            $condicoes[] = 'l.criado_em >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $condicoes[] = 'l.criado_em <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }

        $sql = count($condicoes) > 0 ? 'WHERE ' . implode(' AND ', $condicoes) : '';

        return [$sql, $params];
    }
}
