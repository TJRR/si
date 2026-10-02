<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 58: a soma geral de pontos do evento, lida na hora e nunca guardada
 * em tabela de saldo (decisao da fase: sem saldo, nao ha' saldo
 * desatualizado).
 *
 * As seis origens tem tabelas, colunas e regras de anulacao diferentes; a
 * consulta abaixo as reune com UNION ALL, cada uma com o seu filtro:
 *
 *   estandes     evento_estande_visitas       sem anulacao
 *   conexoes     evento_conexoes (dois lados) sem anulacao
 *   divulgacao   evento_divulgacao_comprovacoes  anulado_em e excluido_em
 *   bonus        evento_bonus_creditos           anulado_em
 *   presenca     evento_presenca_creditos        anulado_em
 *   competicoes  evento_competicao_participacoes anulado_em
 *
 * Somar linhas so' e' correto enquanto existirem as chaves unicas que
 * garantem uma linha por fato em cada tabela (visita por estande, conexao
 * por par, credito por bonus, credito por presenca, participacao por
 * competicao). Se alguma cair, a soma dobra em silencio.
 *
 * So' entram linhas com pontos maiores que zero: registro sem ponto (acima
 * de um limite, ou depois do encerramento) nao pode empurrar o "ultimo
 * ponto" de ninguem, que e' um criterio de desempate.
 *
 * Com a gincana encerrada, conta so' o que tem instante ate' o encerramento.
 * Ver Implantar.md, secao 13.17.
 *
 * Diferente dos resumoParticipante() das fases anteriores, falha de banco
 * aqui NAO vira zero: a excecao sobe e a tela diz "classificacao
 * indisponivel", porque um total errado numa classificacao e' pior que
 * nenhum total.
 */
class GamificacaoClassificacaoRepository
{
    const ORIGENS = ['presenca', 'competicoes', 'conexoes', 'estandes', 'divulgacao', 'bonus'];

    /**
     * Soma e ultimo instante por inscricao e por origem, na forma
     * [evento_inscricao_id => ['por_origem' => [origem => pontos],
     *  'total' => n, 'ultimo_ponto_em' => 'Y-m-d H:i:s']].
     * Com $inscricaoId, restringe a uma pessoa.
     */
    public function somas($eventoId, $encerramentoEm = null, $inscricaoId = null)
    {
        $parametros = [];
        $partes = [];

        $partes[] = $this->parte(
            "SELECT 'estandes' AS origem, v.evento_inscricao_id AS inscricao, v.pontos_creditados AS pontos, v.visitado_em AS instante
               FROM evento_estande_visitas v
               INNER JOIN evento_estandes e ON e.id = v.estande_id
              WHERE e.evento_id = :evento_estandes AND v.pontos_creditados > 0",
            'v.visitado_em', null, 'v.evento_inscricao_id', 'estandes', $eventoId, $encerramentoEm, $inscricaoId, $parametros
        );
        $partes[] = $this->parte(
            "SELECT 'conexoes' AS origem, c.inscricao_menor_id AS inscricao, c.pontos_creditados_menor AS pontos, c.conectado_em AS instante
               FROM evento_conexoes c
              WHERE c.evento_id = :evento_conexoes_menor AND c.pontos_creditados_menor > 0",
            'c.conectado_em', null, 'c.inscricao_menor_id', 'conexoes_menor', $eventoId, $encerramentoEm, $inscricaoId, $parametros
        );
        $partes[] = $this->parte(
            "SELECT 'conexoes' AS origem, c.inscricao_maior_id AS inscricao, c.pontos_creditados_maior AS pontos, c.conectado_em AS instante
               FROM evento_conexoes c
              WHERE c.evento_id = :evento_conexoes_maior AND c.pontos_creditados_maior > 0",
            'c.conectado_em', null, 'c.inscricao_maior_id', 'conexoes_maior', $eventoId, $encerramentoEm, $inscricaoId, $parametros
        );
        $partes[] = $this->parte(
            // excluido_em: a comprovacao excluida pela tela Comprovacoes sai
            // de toda soma na hora, inclusive depois do encerramento. Ao
            // contrario da anulacao, que o congelamento respeita pelo
            // instante, a exclusao nunca conta: ela e' a retirada de um
            // registro que nao devia existir, nao uma punicao datada.
            "SELECT 'divulgacao' AS origem, d.evento_inscricao_id AS inscricao, d.pontos_creditados AS pontos, d.enviado_em AS instante
               FROM evento_divulgacao_comprovacoes d
              WHERE d.evento_id = :evento_divulgacao AND d.pontos_creditados > 0 AND d.excluido_em IS NULL",
            'd.enviado_em', 'd.anulado_em', 'd.evento_inscricao_id', 'divulgacao', $eventoId, $encerramentoEm, $inscricaoId, $parametros
        );
        $partes[] = $this->parte(
            "SELECT 'bonus' AS origem, b.evento_inscricao_id AS inscricao, b.pontos_creditados AS pontos, b.creditado_em AS instante
               FROM evento_bonus_creditos b
              WHERE b.evento_id = :evento_bonus AND b.pontos_creditados > 0",
            'b.creditado_em', 'b.anulado_em', 'b.evento_inscricao_id', 'bonus', $eventoId, $encerramentoEm, $inscricaoId, $parametros
        );
        $partes[] = $this->parte(
            "SELECT 'presenca' AS origem, p.evento_inscricao_id AS inscricao, p.pontos_presenca + p.pontos_pontualidade AS pontos,
                    p.creditado_em AS instante
               FROM evento_presenca_creditos p
              WHERE p.evento_id = :evento_presenca AND p.pontos_presenca + p.pontos_pontualidade > 0",
            'p.creditado_em', 'p.anulado_em', 'p.evento_inscricao_id', 'presenca', $eventoId, $encerramentoEm, $inscricaoId, $parametros
        );
        $partes[] = $this->parte(
            "SELECT 'competicoes' AS origem, cp.evento_inscricao_id AS inscricao, cp.pontos_creditados AS pontos, cp.participou_em AS instante
               FROM evento_competicao_participacoes cp
              WHERE cp.evento_id = :evento_competicoes AND cp.pontos_creditados > 0",
            'cp.participou_em', 'cp.anulado_em', 'cp.evento_inscricao_id', 'competicoes', $eventoId, $encerramentoEm, $inscricaoId, $parametros
        );

        $sql = 'SELECT origem, inscricao, SUM(pontos) AS pontos, MAX(instante) AS ultimo
                  FROM (' . implode(' UNION ALL ', $partes) . ') todas
                 GROUP BY origem, inscricao';

        $pdo = Database::conexao();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        $somas = [];

        foreach ($stmt->fetchAll() as $linha) {
            $inscricao = (int) $linha['inscricao'];

            if (!isset($somas[$inscricao])) {
                $somas[$inscricao] = ['por_origem' => array_fill_keys(self::ORIGENS, 0), 'total' => 0, 'ultimo_ponto_em' => null];
            }

            $pontos = (int) $linha['pontos'];
            $somas[$inscricao]['por_origem'][$linha['origem']] += $pontos;
            $somas[$inscricao]['total'] += $pontos;

            if ($somas[$inscricao]['ultimo_ponto_em'] === null || $linha['ultimo'] > $somas[$inscricao]['ultimo_ponto_em']) {
                $somas[$inscricao]['ultimo_ponto_em'] = $linha['ultimo'];
            }
        }

        return $somas;
    }

    /**
     * Quantas atividades diferentes cada inscricao tem com presenca valida,
     * na forma [evento_inscricao_id => n] - criterio de desempate. Mesma
     * exclusao dos bonus (Fase 58, N1): a atividade que a pessoa facilita
     * nao conta. Com a gincana encerrada, o que foi lido ou removido depois
     * do encerramento nao muda a contagem.
     */
    public function atividadesComPresenca($eventoId, $encerramentoEm = null)
    {
        $sql =
            'SELECT c.evento_inscricao_id, COUNT(*) AS total
               FROM evento_checkins c
               INNER JOIN evento_atividades a ON a.id = c.atividade_id
               INNER JOIN evento_inscricoes ei ON ei.id = c.evento_inscricao_id
              WHERE a.evento_id = :evento
                AND NOT EXISTS (
                    SELECT 1 FROM evento_atividade_facilitadores f
                     WHERE f.atividade_id = c.atividade_id AND f.usuario_id = ei.usuario_id AND f.removido_em IS NULL
                )';
        $parametros = ['evento' => (int) $eventoId];

        if ($encerramentoEm !== null) {
            $sql .= ' AND c.checkin_em <= :encerramento_leitura AND (c.removido_em IS NULL OR c.removido_em > :encerramento_remocao)';
            $parametros['encerramento_leitura'] = $encerramentoEm;
            $parametros['encerramento_remocao'] = $encerramentoEm;
        } else {
            $sql .= ' AND c.removido_em IS NULL';
        }

        $sql .= ' GROUP BY c.evento_inscricao_id';

        $pdo = Database::conexao();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['evento_inscricao_id']] = (int) $linha['total'];
        }

        return $mapa;
    }

    /**
     * Dados de exibicao de cada inscricao do evento, na forma
     * [evento_inscricao_id => ['nome', 'email', 'inscrito_em', 'usuario_id']].
     */
    public function inscricoesDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT i.id, i.usuario_id, i.inscrito_em, u.nome, u.email
               FROM evento_inscricoes i
               INNER JOIN usuarios u ON u.id = i.usuario_id
              WHERE i.evento_id = :evento'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['id']] = [
                'usuario_id' => (int) $linha['usuario_id'],
                'nome' => $linha['nome'],
                'email' => $linha['email'],
                'inscrito_em' => $linha['inscrito_em'],
            ];
        }

        return $mapa;
    }

    /**
     * Monta uma das partes do UNION ALL, acrescentando os filtros de
     * encerramento e de inscricao. Cada parte usa nomes de parametro
     * proprios ($sufixo), porque o mesmo nome repetido na consulta so'
     * funciona com a emulacao de preparo ligada, e isso nao deve ser
     * premissa desta consulta.
     */
    private function parte($sql, $colunaInstante, $colunaAnulacao, $colunaInscricao, $sufixo, $eventoId, $encerramentoEm, $inscricaoId, array &$parametros)
    {
        $nomeEvento = null;

        if (preg_match('/:(evento_[a-z_]+)/', $sql, $achado) === 1) {
            $nomeEvento = $achado[1];
        }

        $parametros[$nomeEvento] = (int) $eventoId;

        if ($encerramentoEm !== null) {
            $sql .= ' AND ' . $colunaInstante . ' <= :enc_instante_' . $sufixo;
            $parametros['enc_instante_' . $sufixo] = $encerramentoEm;

            if ($colunaAnulacao !== null) {
                $sql .= ' AND (' . $colunaAnulacao . ' IS NULL OR ' . $colunaAnulacao . ' > :enc_anulacao_' . $sufixo . ')';
                $parametros['enc_anulacao_' . $sufixo] = $encerramentoEm;
            }
        } elseif ($colunaAnulacao !== null) {
            $sql .= ' AND ' . $colunaAnulacao . ' IS NULL';
        }

        if ($inscricaoId !== null) {
            $sql .= ' AND ' . $colunaInscricao . ' = :inscricao_' . $sufixo;
            $parametros['inscricao_' . $sufixo] = (int) $inscricaoId;
        }

        return $sql;
    }
}
