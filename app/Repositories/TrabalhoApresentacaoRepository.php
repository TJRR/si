<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * A marca de trabalho EFETIVAMENTE APRESENTADO (migration 201). So' se
 * grava o fato: trabalho sem linha e' trabalho nao apresentado.
 *
 * Arquivo novo, e nao metodos em TrabalhoRepository ou
 * TrabalhoAutorRepository, pelo precedente de isolamento; a duplicacao
 * proposital esta' registrada na pendencia 25.
 *
 * As operacoes em lote nao passam por OperacaoEmLote::executarLote(): aquele
 * traco monta o comando sobre a tabela alterada, e aqui os identificadores
 * da tela sao de trabalho. Os cuidados do traco estao escritos a mao em
 * cada metodo.
 */
class TrabalhoApresentacaoRepository
{
    use OperacaoEmLote;

    /**
     * Os trabalhos selecionados para apresentacao do evento, com a marca
     * quando existir. Base da tela Apresentacoes e da emissao dos
     * certificados de apresentacao.
     *
     * selecionado = 1 e' gravado por TrabalhoResultadoService::
     * publicarResultado() (migration 176), entao esta consulta nunca recalcula
     * selecao nenhuma.
     */
    public function listarSelecionadosDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT t.id, t.titulo, t.status, t.posicao, t.nota_final,
                    ap.apresentado_em, ap.observacao, u.nome AS apresentado_por_nome
               FROM trabalhos t
               LEFT JOIN evento_trabalho_apresentacoes ap ON ap.trabalho_id = t.id
               LEFT JOIN usuarios u ON u.id = ap.apresentado_por
              WHERE t.evento_id = :evento_id AND t.selecionado = 1
              ORDER BY t.posicao ASC, t.id ASC'
        );
        $stmt->execute(['evento_id' => (int) $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Identificadores dos trabalhos do evento com a marca de apresentado,
     * como chaves do array, para consulta direta por quem monta outra lista.
     */
    public function idsApresentadosDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ap.trabalho_id
               FROM evento_trabalho_apresentacoes ap
               INNER JOIN trabalhos t ON t.id = ap.trabalho_id
              WHERE t.evento_id = :evento_id'
        );
        $stmt->execute(['evento_id' => (int) $eventoId]);

        $ids = [];

        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $ids[(int) $id] = true;
        }

        return $ids;
    }

    /**
     * Os autores de trabalho apresentado, um por linha, com o que o
     * certificado precisa declarar. O coautor sem conta entra aqui com
     * usuario_id nulo (trabalho_autores.usuario_id so' e' preenchido para o
     * autor principal, migration 151) e e' alcancado apenas pela tela
     * administrativa.
     */
    public function listarAutoresApresentadosDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT au.id AS trabalho_autor_id, au.trabalho_id, au.usuario_id, au.nome, au.cpf,
                    au.eh_autor_principal, t.titulo AS trabalho_titulo, ex.nome AS eixo_nome,
                    ap.apresentado_em
               FROM evento_trabalho_apresentacoes ap
               INNER JOIN trabalhos t ON t.id = ap.trabalho_id
               INNER JOIN trabalho_autores au ON au.trabalho_id = t.id
               LEFT JOIN trabalho_eixos_tematicos ex ON ex.id = t.eixo_tematico_id
              WHERE ap.evento_id = :evento_id AND t.selecionado = 1
              ORDER BY t.posicao ASC, au.eh_autor_principal DESC, au.id ASC'
        );
        $stmt->execute(['evento_id' => (int) $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Os autores de trabalho apresentado que sao UMA pessoa com conta, para a
     * tela do participante. Falha de banco devolve lista vazia: o bloco some
     * em vez de derrubar a tela.
     */
    public function listarAutoriasApresentadasDoUsuario($eventoId, $usuarioId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT au.id AS trabalho_autor_id, au.trabalho_id, au.nome, au.cpf,
                        t.titulo AS trabalho_titulo, ex.nome AS eixo_nome
                   FROM evento_trabalho_apresentacoes ap
                   INNER JOIN trabalhos t ON t.id = ap.trabalho_id
                   INNER JOIN trabalho_autores au ON au.trabalho_id = t.id
                   LEFT JOIN trabalho_eixos_tematicos ex ON ex.id = t.eixo_tematico_id
                  WHERE ap.evento_id = :evento_id AND t.selecionado = 1 AND au.usuario_id = :usuario_id
                  ORDER BY t.posicao ASC, au.id ASC'
            );
            $stmt->execute(['evento_id' => (int) $eventoId, 'usuario_id' => (int) $usuarioId]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Certificado] Falha ao listar as autorias apresentadas do usuario ' . (int) $usuarioId . ': ' . $e->getMessage());

            return [];
        }
    }

    public function contarApresentados($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_trabalho_apresentacoes WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => (int) $eventoId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca como apresentados os trabalhos do lote. Um comando so', com a
     * origem do dado vindo do proprio SELECT: identificador de trabalho de
     * outro evento, ou de trabalho que nao foi selecionado para apresentacao,
     * nao alcanca nada, mesmo que alguem altere o formulario.
     *
     * INSERT IGNORE em vez de conferencia previa: a chave unica
     * (trabalho_id) ja' garante uma marca por trabalho, e marcar de novo o
     * que ja' estava marcado nao e' erro, e' repeticao do mesmo pedido.
     *
     * Devolve quantas marcas novas entraram.
     */
    public function marcarEmLote($eventoId, array $ids, $usuarioId, $observacao)
    {
        $identificadores = $this->identificadoresDoLote($ids);

        if ($identificadores === []) {
            return 0;
        }

        $marcadores = implode(', ', array_fill(0, count($identificadores), '?'));
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT IGNORE INTO evento_trabalho_apresentacoes (evento_id, trabalho_id, apresentado_por, observacao)
                 SELECT t.evento_id, t.id, ?, ?
                   FROM trabalhos t
                  WHERE t.id IN (' . $marcadores . ') AND t.evento_id = ? AND t.selecionado = 1'
            );
            $stmt->execute(array_merge(
                [(int) $usuarioId, $observacao !== '' ? $observacao : null],
                $identificadores,
                [(int) $eventoId]
            ));
            $gravadas = $stmt->rowCount();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar(
            'marcar_trabalhos_apresentados',
            'evento_trabalho_apresentacoes',
            (int) $eventoId,
            ['ids' => $identificadores],
            ['marcadas' => $gravadas, 'apresentado_por' => (int) $usuarioId, 'observacao' => $observacao]
        );

        return $gravadas;
    }

    /**
     * Retira a marca dos trabalhos do lote. DELETE da linha, mesmo espirito
     * de evento_anais_exclusoes e do resto do sistema; a trilha historica
     * fica na Auditoria.
     *
     * NAO mexe em certificado ja' emitido: por decisao do dono o documento
     * guardado nunca muda, e o caminho para desfazer uma emissao e' o
     * cancelamento na tela de Certificados. Quem chama avisa isso na tela.
     */
    public function desmarcarEmLote($eventoId, array $ids, $usuarioId)
    {
        $identificadores = $this->identificadoresDoLote($ids);

        if ($identificadores === []) {
            return 0;
        }

        $marcadores = implode(', ', array_fill(0, count($identificadores), '?'));
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'DELETE FROM evento_trabalho_apresentacoes
                  WHERE trabalho_id IN (' . $marcadores . ') AND evento_id = ?'
            );
            $stmt->execute(array_merge($identificadores, [(int) $eventoId]));
            $apagadas = $stmt->rowCount();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar(
            'desmarcar_trabalhos_apresentados',
            'evento_trabalho_apresentacoes',
            (int) $eventoId,
            ['ids' => $identificadores],
            ['apagadas' => $apagadas, 'desmarcado_por' => (int) $usuarioId]
        );

        return $apagadas;
    }
}
