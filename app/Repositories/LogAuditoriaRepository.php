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

    public function listar(array $filtros, $limite, $offset, $ordenar = 'criado_em', $direcao = 'desc')
    {
        list($sqlWhere, $params) = $this->montarWhere($filtros);
        $orderBy = $this->montarOrderBy($ordenar, $direcao);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT l.*, u.nome AS usuario_nome
             FROM log_auditoria l
             LEFT JOIN usuarios u ON u.id = l.usuario_id
             $sqlWhere
             $orderBy
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
        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM log_auditoria l
             LEFT JOIN usuarios u ON u.id = l.usuario_id
             $sqlWhere"
        );
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Mesmos filtros de listar(), sem paginacao - usado so' pela exportacao
     * em CSV, que precisa de todos os registros que baterem com o filtro
     * atual, nao so' a pagina exibida na tela.
     */
    public function listarTodos(array $filtros, $ordenar = 'criado_em', $direcao = 'desc')
    {
        list($sqlWhere, $params) = $this->montarWhere($filtros);
        $orderBy = $this->montarOrderBy($ordenar, $direcao);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT l.*, u.nome AS usuario_nome
             FROM log_auditoria l
             LEFT JOIN usuarios u ON u.id = l.usuario_id
             $sqlWhere
             $orderBy"
        );
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function listarAcoesDistintas()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT DISTINCT acao FROM log_auditoria ORDER BY acao ASC')->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Lista branca de colunas ordenaveis (nunca monta ORDER BY com nome de
     * coluna vindo direto do GET) - usuario_nome vem do JOIN com usuarios.
     */
    private static $colunasOrdenaveis = [
        'criado_em' => 'l.criado_em',
        'usuario_nome' => 'u.nome',
        'acao' => 'l.acao',
        'entidade' => 'l.entidade',
        'ip_origem' => 'l.ip_origem',
    ];

    private function montarOrderBy($ordenar, $direcao)
    {
        $coluna = isset(self::$colunasOrdenaveis[$ordenar]) ? self::$colunasOrdenaveis[$ordenar] : 'l.criado_em';
        $direcaoSql = strtolower($direcao) === 'asc' ? 'ASC' : 'DESC';

        return "ORDER BY $coluna $direcaoSql, l.id $direcaoSql";
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
        if (!empty($filtros['busca'])) {
            $condicoes[] = '(u.nome LIKE :busca OR l.acao LIKE :busca OR l.entidade LIKE :busca OR l.ip_origem LIKE :busca OR l.mensagem LIKE :busca)';
            $params[':busca'] = '%' . $filtros['busca'] . '%';
        }

        $sql = count($condicoes) > 0 ? 'WHERE ' . implode(' AND ', $condicoes) : '';

        return [$sql, $params];
    }
}
