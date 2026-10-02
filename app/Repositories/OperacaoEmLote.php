<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Parte comum das operacoes em lote das telas do Evento (Comprovacoes de
 * Divulgacao, Credenciamento, Participacoes em competicoes, Acompanhamento
 * dos bonus e Conexoes). Ver Implantar.md, secao 13.17.
 *
 * O aviso ao participante continua sendo um por pessoa, em laco, no
 * controlador: a notificacao e' individual por natureza.
 *
 * Marcadores posicionais em todo o traco: o PDO nao aceita misturar
 * marcador com nome e marcador posicional na mesma consulta, e a ordem dos
 * valores segue a ordem em que eles aparecem no comando.
 */
trait OperacaoEmLote
{
    /**
     * Identificadores vindos do formulario: so' inteiros positivos, sem
     * repeticao. Lista vazia devolve lista vazia, e quem chama recusa com
     * aviso em vez de executar sobre nada.
     */
    protected function identificadoresDoLote(array $ids)
    {
        $limpos = [];

        foreach ($ids as $id) {
            $numero = (int) $id;

            if ($numero > 0 && !in_array($numero, $limpos, true)) {
                $limpos[] = $numero;
            }
        }

        return $limpos;
    }

    /**
     * Executa o comando sobre as linhas ja' conferidas por quem chamou, numa
     * transacao, e registra a auditoria uma unica vez.
     *
     * $comando e' o inicio do comando ate' antes do WHERE, por exemplo
     * "UPDATE evento_divulgacao_comprovacoes SET anulado_em = NOW()" ou
     * "DELETE FROM evento_credenciamentos". $condicao e' o que ainda limita
     * as linhas, sem apelido de tabela. $valores sao os do comando, na ordem
     * em que aparecem nele.
     */
    protected function executarLote($comando, $condicao, $eventoId, array $identificadores, array $valores, $acao, $tabela)
    {
        if ($identificadores === []) {
            return 0;
        }

        $marcadores = implode(', ', array_fill(0, count($identificadores), '?'));
        $sql = $comando . ' WHERE id IN (' . $marcadores . ') AND evento_id = ?';

        if ($condicao !== '') {
            $sql .= ' AND ' . $condicao;
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge(array_values($valores), $identificadores, [(int) $eventoId]));
            $alteradas = $stmt->rowCount();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar($acao, $tabela, (int) $eventoId, ['ids' => $identificadores], $valores !== [] ? $valores : null);

        return $alteradas;
    }
}
