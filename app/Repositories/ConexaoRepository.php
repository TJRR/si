<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 55: conexao entre dois participantes do mesmo Evento, gravada quando
 * um le o codigo do cracha do outro no aplicativo.
 *
 * O par e' guardado em ordem canonica (menor id de inscricao primeiro), o
 * que faz "A leu B" e "B leu A" caírem na mesma linha e deixa a chave unica
 * do banco recusar a segunda gravacao em qualquer sentido. Os pontos de cada
 * lado ficam congelados no valor vigente no instante da leitura, mesma regra
 * de EstandeVisitaRepository (Fase 54).
 *
 * Este repositorio nunca abre transacao: quem faz isso e' ConexaoService,
 * porque a contagem do teto e a gravacao precisam acontecer sob o mesmo
 * bloqueio.
 */
class ConexaoRepository
{
    use OperacaoEmLote;

    /**
     * Ordem canonica do par: menor id primeiro. Unico lugar do sistema que
     * decide essa ordem - qualquer consulta ou gravacao passa por aqui.
     */
    public static function parCanonico($inscricaoA, $inscricaoB)
    {
        $a = (int) $inscricaoA;
        $b = (int) $inscricaoB;

        return $a <= $b ? [$a, $b] : [$b, $a];
    }

    public function buscarPorPar($inscricaoA, $inscricaoB)
    {
        list($menor, $maior) = self::parCanonico($inscricaoA, $inscricaoB);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_conexoes
             WHERE inscricao_menor_id = :menor AND inscricao_maior_id = :maior LIMIT 1'
        );
        $stmt->execute(['menor' => $menor, 'maior' => $maior]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Gravacao crua, sempre chamada de dentro da transacao aberta por
     * ConexaoService (mesma convencao de nome de
     * TrabalhoAutorRepository::atualizarCpfDoUsuarioNaTransacaoAtual()).
     * Nao trata erro de chave unica: quem chama e' que decide o que fazer
     * com a leitura simultanea.
     */
    public function inserirNaTransacaoAtual($eventoId, $menor, $maior, $iniciador, $pontosMenor, $pontosMaior)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_conexoes
                (evento_id, inscricao_menor_id, inscricao_maior_id, iniciador_inscricao_id,
                 pontos_creditados_menor, pontos_creditados_maior, conectado_em)
             VALUES (:evento_id, :menor, :maior, :iniciador, :pontos_menor, :pontos_maior, NOW())'
        );
        $stmt->execute([
            'evento_id' => (int) $eventoId,
            'menor' => (int) $menor,
            'maior' => (int) $maior,
            'iniciador' => (int) $iniciador,
            'pontos_menor' => (int) $pontosMenor,
            'pontos_maior' => (int) $pontosMaior,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Conexoes em que ESTE lado recebeu pontos maiores que zero - e' o que o
     * teto por pessoa limita. Chamada de dentro da transacao, depois do
     * bloqueio das duas inscricoes: contar antes do bloqueio permitiria que
     * duas leituras simultaneas da mesma pessoa passassem do teto.
     */
    public function contarPontuadasDaInscricao($inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM evento_conexoes
             WHERE (inscricao_menor_id = :inscricao AND pontos_creditados_menor > 0)
                OR (inscricao_maior_id = :inscricao2 AND pontos_creditados_maior > 0)'
        );
        $stmt->execute(['inscricao' => (int) $inscricaoId, 'inscricao2' => (int) $inscricaoId]);

        return (int) $stmt->fetchColumn();
    }

    public function contarDaInscricao($inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM evento_conexoes
             WHERE inscricao_menor_id = :inscricao OR inscricao_maior_id = :inscricao2'
        );
        $stmt->execute(['inscricao' => (int) $inscricaoId, 'inscricao2' => (int) $inscricaoId]);

        return (int) $stmt->fetchColumn();
    }

    public function somarPontosDaInscricao($inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(CASE WHEN inscricao_menor_id = :inscricao THEN pontos_creditados_menor ELSE 0 END)
                            + SUM(CASE WHEN inscricao_maior_id = :inscricao2 THEN pontos_creditados_maior ELSE 0 END), 0)
             FROM evento_conexoes
             WHERE inscricao_menor_id = :inscricao3 OR inscricao_maior_id = :inscricao4'
        );
        $stmt->execute([
            'inscricao' => (int) $inscricaoId,
            'inscricao2' => (int) $inscricaoId,
            'inscricao3' => (int) $inscricaoId,
            'inscricao4' => (int) $inscricaoId,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Resumo do participante para o painel e para a tela "Minhas conexoes":
     * quantas conexoes e quantos pontos. Recurso opcional: falha de banco
     * devolve zero em vez de derrubar a tela.
     */
    public function resumoParticipante($inscricaoId)
    {
        $vazio = ['total_conexoes' => 0, 'total_pontos' => 0, 'total_pontuadas' => 0];

        try {
            return [
                'total_conexoes' => $this->contarDaInscricao($inscricaoId),
                'total_pontos' => $this->somarPontosDaInscricao($inscricaoId),
                'total_pontuadas' => $this->contarPontuadasDaInscricao($inscricaoId),
            ];
        } catch (\PDOException $e) {
            error_log('[Conexoes] Falha ao montar o resumo da inscricao ' . (int) $inscricaoId . ': ' . $e->getMessage());

            return $vazio;
        }
    }

    /**
     * Conexoes da pessoa, com os dados CRUS da outra ponta mais as seis
     * marcas de visibilidade. Nada e' filtrado aqui de proposito: quem
     * decide o que aparece e' PerfilVisibilidadeService, num lugar so'.
     * Filtrar no SQL espalharia a regra por dois pontos e, no dia em que um
     * campo novo entrasse, um deles ficaria para tras.
     *
     * Recurso opcional: falha de banco devolve lista vazia.
     */
    public function listarDaInscricao($inscricaoId)
    {
        $inscricaoId = (int) $inscricaoId;

        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT c.id, c.conectado_em, c.iniciador_inscricao_id,
                        CASE WHEN c.inscricao_menor_id = :inscricao THEN c.pontos_creditados_menor
                             ELSE c.pontos_creditados_maior END AS pontos_creditados,
                        CASE WHEN c.inscricao_menor_id = :inscricao2 THEN c.inscricao_maior_id
                             ELSE c.inscricao_menor_id END AS outra_inscricao_id,
                        u.id AS usuario_id, u.nome AS usuario_nome, u.email AS usuario_email, u.foto_path,
                        p.cargo, p.orgao_origem, p.minicurriculo, p.telefone, p.telefone_whatsapp,
                        p.redes_sociais, p.mostrar_foto, p.mostrar_cargo, p.mostrar_orgao_origem,
                        p.mostrar_minicurriculo, p.mostrar_telefone, p.mostrar_redes_sociais
                 FROM evento_conexoes c
                 JOIN evento_inscricoes i ON i.id = (CASE WHEN c.inscricao_menor_id = :inscricao3
                                                          THEN c.inscricao_maior_id ELSE c.inscricao_menor_id END)
                 JOIN usuarios u ON u.id = i.usuario_id
                 LEFT JOIN usuarios_perfil p ON p.usuario_id = u.id
                 WHERE c.inscricao_menor_id = :inscricao4 OR c.inscricao_maior_id = :inscricao5
                 ORDER BY c.conectado_em DESC, c.id DESC'
            );
            $stmt->execute([
                'inscricao' => $inscricaoId,
                'inscricao2' => $inscricaoId,
                'inscricao3' => $inscricaoId,
                'inscricao4' => $inscricaoId,
                'inscricao5' => $inscricaoId,
            ]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Conexoes] Falha ao listar as conexoes da inscricao ' . $inscricaoId . ': ' . $e->getMessage());

            return [];
        }
    }

    public function contarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_conexoes WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => (int) $eventoId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Quantas pessoas do evento ja se conectaram ao menos uma vez (cada
     * conexao conta duas). Sem nome e sem classificacao: o par e' dado
     * pessoal de terceiro, e o ranking e' da Fase 58.
     */
    public function contarParticipantesConectados($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM (
                SELECT inscricao_menor_id AS inscricao FROM evento_conexoes WHERE evento_id = :evento_id
                UNION
                SELECT inscricao_maior_id AS inscricao FROM evento_conexoes WHERE evento_id = :evento_id2
             ) AS participantes'
        );
        $stmt->execute(['evento_id' => (int) $eventoId, 'evento_id2' => (int) $eventoId]);

        return (int) $stmt->fetchColumn();
    }

    public function somarPontosPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(pontos_creditados_menor + pontos_creditados_maior), 0)
             FROM evento_conexoes WHERE evento_id = :evento_id'
        );
        $stmt->execute(['evento_id' => (int) $eventoId]);

        return (int) $stmt->fetchColumn();
    }

    public function contarPorDia($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT DATE(conectado_em) AS dia, COUNT(*) AS total
             FROM evento_conexoes WHERE evento_id = :evento_id
             GROUP BY DATE(conectado_em) ORDER BY dia ASC'
        );
        $stmt->execute(['evento_id' => (int) $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 58: lista nominal das conexoes do evento, com as duas pessoas de
     * cada par.
     *
     * Ate' a Fase 57 a tela administrativa mostrava so' numeros, porque o par
     * e' dado pessoal de terceiro. O dono decidiu em 01/10/2026 que o
     * Administrador passa a ver os pares, para poder desfazer uma leitura
     * feita por engano pela propria tela, no lugar do script de linha de
     * comando. A ajuda da tela diz isso.
     *
     * A busca alcanca as DUAS pessoas do par: procurar por um nome traz toda
     * conexao em que a pessoa aparece, de qualquer lado.
     */
    public function listarPorEvento($eventoId, array $filtros = [])
    {
        $sql =
            'SELECT c.*, um.nome AS nome_menor, um.email AS email_menor,
                    ux.nome AS nome_maior, ux.email AS email_maior
               FROM evento_conexoes c
               INNER JOIN evento_inscricoes im ON im.id = c.inscricao_menor_id
               INNER JOIN usuarios um ON um.id = im.usuario_id
               INNER JOIN evento_inscricoes ix ON ix.id = c.inscricao_maior_id
               INNER JOIN usuarios ux ON ux.id = ix.usuario_id
              WHERE c.evento_id = :evento';
        $parametros = ['evento' => (int) $eventoId];

        if (!empty($filtros['busca'])) {
            $sql .= ' AND (um.nome LIKE :busca OR um.email LIKE :busca OR ux.nome LIKE :busca OR ux.email LIKE :busca)';
            $parametros['busca'] = '%' . $filtros['busca'] . '%';
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= ' AND DATE(c.conectado_em) >= :data_inicio';
            $parametros['data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= ' AND DATE(c.conectado_em) <= :data_fim';
            $parametros['data_fim'] = $filtros['data_fim'];
        }

        $sql .= ' ORDER BY c.conectado_em DESC, c.id DESC';

        $pdo = Database::conexao();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Fase 58: remove em lote as conexoes marcadas. Apagar a linha devolve
     * os pontos dos dois lados sozinho, porque a classificacao soma as
     * linhas existentes: e' o mesmo efeito de
     * database/remover_conexao_evento.php, agora pela tela.
     *
     * Devolve as linhas removidas, para a mensagem.
     */
    public function removerEmLote($eventoId, array $ids)
    {
        $identificadores = $this->identificadoresDoLote($ids);

        if ($identificadores === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($identificadores), '?'));
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT id FROM evento_conexoes WHERE id IN (' . $marcadores . ') AND evento_id = ?'
        );
        $stmt->execute(array_merge($identificadores, [(int) $eventoId]));
        $alcancadas = $stmt->fetchAll();

        if ($alcancadas === []) {
            return [];
        }

        $alcancados = array_map(function ($linha) {
            return (int) $linha['id'];
        }, $alcancadas);

        $this->executarLote('DELETE FROM evento_conexoes', '', $eventoId, $alcancados, [], 'remover', 'evento_conexoes');

        return $alcancadas;
    }
}
