<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 56: comprovacao de divulgacao em rede social enviada pelo
 * participante, com os pontos creditados no ato do envio e congelados no
 * valor vigente daquele instante, mesma regra de EstandeVisitaRepository
 * (Fase 54) e ConexaoRepository (Fase 55).
 *
 * TODA contagem de limite e toda soma de pontos filtra anulado_em IS NULL.
 * Isso e' deliberado: a anulacao feita pelo Administrador corrige, nao pune.
 * Ela tira os pontos e devolve a vaga do teto, e no caso de "acompanhar",
 * que admite uma linha por rede, devolve tambem a possibilidade de registrar
 * de novo - sem isso, uma anulacao feita por engano fecharia aquela acao
 * para sempre. O que a anulacao NAO devolve e' a prova: os resumos
 * (endereco_hash e arquivo_sha256) continuam gravados e barrados pelas
 * chaves unicas, entao reenviar a mesma comprovacao nunca devolve pontos.
 *
 * Este repositorio nunca abre transacao: quem faz isso e' DivulgacaoService,
 * porque a contagem dos tetos e a gravacao precisam acontecer sob o mesmo
 * bloqueio.
 */
class DivulgacaoComprovacaoRepository
{
    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_divulgacao_comprovacoes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Gravacao crua, sempre chamada de dentro da transacao aberta por
     * DivulgacaoService (mesma convencao de nome de
     * ConexaoRepository::inserirNaTransacaoAtual()). Nao trata erro de chave
     * unica: quem chama e' que decide o que responder.
     */
    public function inserirNaTransacaoAtual(array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_divulgacao_comprovacoes
                (evento_id, evento_inscricao_id, rede, tipo_acao, endereco, endereco_hash,
                 arquivo_path, arquivo_nome, arquivo_sha256, arquivo_bytes, pontos_creditados, enviado_em)
             VALUES (:evento_id, :inscricao, :rede, :tipo_acao, :endereco, :endereco_hash,
                 :arquivo_path, :arquivo_nome, :arquivo_sha256, :arquivo_bytes, :pontos, NOW())'
        );
        $stmt->execute([
            'evento_id' => (int) $dados['evento_id'],
            'inscricao' => (int) $dados['evento_inscricao_id'],
            'rede' => $dados['rede'],
            'tipo_acao' => $dados['tipo_acao'],
            'endereco' => isset($dados['endereco']) ? $dados['endereco'] : null,
            'endereco_hash' => isset($dados['endereco_hash']) ? $dados['endereco_hash'] : null,
            'arquivo_path' => isset($dados['arquivo_path']) ? $dados['arquivo_path'] : null,
            'arquivo_nome' => isset($dados['arquivo_nome']) ? $dados['arquivo_nome'] : null,
            'arquivo_sha256' => isset($dados['arquivo_sha256']) ? $dados['arquivo_sha256'] : null,
            'arquivo_bytes' => isset($dados['arquivo_bytes']) ? (int) $dados['arquivo_bytes'] : null,
            'pontos' => (int) $dados['pontos_creditados'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Publicacoes da pessoa naquela rede que pontuaram em um dia. Chamada de
     * dentro da transacao, depois do bloqueio da inscricao: contar antes do
     * bloqueio permitiria que dois envios simultaneos passassem do teto.
     */
    public function contarPublicacoesNoDia($inscricaoId, $rede, $dia)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM evento_divulgacao_comprovacoes
             WHERE evento_inscricao_id = :inscricao AND rede = :rede AND tipo_acao = "publicacao"
               AND pontos_creditados > 0 AND anulado_em IS NULL AND DATE(enviado_em) = :dia'
        );
        $stmt->execute(['inscricao' => (int) $inscricaoId, 'rede' => $rede, 'dia' => $dia]);

        return (int) $stmt->fetchColumn();
    }

    public function contarPublicacoesPontuadasNoEvento($inscricaoId, $rede)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM evento_divulgacao_comprovacoes
             WHERE evento_inscricao_id = :inscricao AND rede = :rede AND tipo_acao = "publicacao"
               AND pontos_creditados > 0 AND anulado_em IS NULL'
        );
        $stmt->execute(['inscricao' => (int) $inscricaoId, 'rede' => $rede]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * "Acompanhar" e' uma vez por rede. Linha anulada nao conta, pelo motivo
     * do cabecalho desta classe.
     */
    public function existeAcompanhar($inscricaoId, $rede)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM evento_divulgacao_comprovacoes
             WHERE evento_inscricao_id = :inscricao AND rede = :rede AND tipo_acao = "acompanhar"
               AND anulado_em IS NULL'
        );
        $stmt->execute(['inscricao' => (int) $inscricaoId, 'rede' => $rede]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Prova ja' usada, nos dois escopos das chaves unicas da tabela:
     * endereco no EVENTO inteiro (uma publicacao e' unica, e duas pessoas
     * nao publicaram a mesma coisa) e imagem por PESSOA (duas pessoas
     * enviando o mesmo arquivo e' caso legitimo, tratado como marca na tela
     * administrativa, nunca como recusa).
     *
     * Devolve 'endereco', 'imagem' ou null, para que a mensagem diga o que
     * de fato aconteceu: "esta publicacao ja foi registrada" e "voce ja
     * enviou esta imagem" sao situacoes diferentes para quem esta na tela.
     *
     * Diferente das contagens de teto, esta consulta NAO ignora linhas
     * anuladas: a prova recusada continua barrada para sempre, e e' isso que
     * impede reenviar a mesma comprovacao depois de uma anulacao. A chave
     * unica do banco e' a garantia final; esta consulta existe para
     * responder antes de o arquivo ir para a area privada.
     */
    public function provaJaUsada($eventoId, $inscricaoId, $enderecoHash, $arquivoSha256)
    {
        $pdo = Database::conexao();

        if ($enderecoHash !== null) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM evento_divulgacao_comprovacoes
                 WHERE evento_id = :evento AND endereco_hash = :hash'
            );
            $stmt->execute(['evento' => (int) $eventoId, 'hash' => $enderecoHash]);

            if ((int) $stmt->fetchColumn() > 0) {
                return 'endereco';
            }
        }

        if ($arquivoSha256 !== null) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM evento_divulgacao_comprovacoes
                 WHERE evento_inscricao_id = :inscricao AND arquivo_sha256 = :sha'
            );
            $stmt->execute(['inscricao' => (int) $inscricaoId, 'sha' => $arquivoSha256]);

            if ((int) $stmt->fetchColumn() > 0) {
                return 'imagem';
            }
        }

        return null;
    }

    public function listarDaInscricao($inscricaoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT * FROM evento_divulgacao_comprovacoes
                 WHERE evento_inscricao_id = :inscricao
                 ORDER BY enviado_em DESC, id DESC'
            );
            $stmt->execute(['inscricao' => (int) $inscricaoId]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Divulgacao] Falha ao listar comprovacoes da inscricao ' . (int) $inscricaoId . ': ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Resumo do participante para o painel e para a tela "Divulgacao".
     * Recurso opcional: falha de banco devolve zeros em vez de derrubar o
     * painel de todo inscrito, mesma protecao de Estandes e Conexoes.
     */
    public function resumoParticipante($inscricaoId)
    {
        $vazio = ['total_comprovacoes' => 0, 'total_pontos' => 0, 'total_anuladas' => 0];

        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN anulado_em IS NULL THEN pontos_creditados ELSE 0 END), 0) AS pontos,
                        COALESCE(SUM(CASE WHEN anulado_em IS NOT NULL THEN 1 ELSE 0 END), 0) AS anuladas
                 FROM evento_divulgacao_comprovacoes
                 WHERE evento_inscricao_id = :inscricao'
            );
            $stmt->execute(['inscricao' => (int) $inscricaoId]);
            $linha = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('[Divulgacao] Falha ao resumir a inscricao ' . (int) $inscricaoId . ': ' . $e->getMessage());

            return $vazio;
        }

        if ($linha === false) {
            return $vazio;
        }

        return [
            'total_comprovacoes' => (int) $linha['total'],
            'total_pontos' => (int) $linha['pontos'],
            'total_anuladas' => (int) $linha['anuladas'],
        ];
    }

    public function anular($id, $usuarioId, $motivo)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_divulgacao_comprovacoes
             SET anulado_em = NOW(), anulado_por = :usuario, motivo_anulacao = :motivo
             WHERE id = :id AND anulado_em IS NULL'
        );
        $stmt->execute([
            'usuario' => (int) $usuarioId,
            'motivo' => $motivo,
            'id' => (int) $id,
        ]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar(
                'anular_comprovacao_divulgacao',
                'evento_divulgacao_comprovacoes',
                (int) $id,
                $antes !== null ? ['anulado_em' => null] : null,
                ['anulado_por' => (int) $usuarioId, 'motivo_anulacao' => $motivo]
            );
        }

        return $alterou;
    }

    public function reverterAnulacao($id, $usuarioId)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_divulgacao_comprovacoes
             SET anulado_em = NULL, anulado_por = NULL, motivo_anulacao = NULL
             WHERE id = :id AND anulado_em IS NOT NULL'
        );
        $stmt->execute(['id' => (int) $id]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar(
                'reverter_anulacao_divulgacao',
                'evento_divulgacao_comprovacoes',
                (int) $id,
                $antes !== null ? ['motivo_anulacao' => $antes['motivo_anulacao']] : null,
                ['revertido_por' => (int) $usuarioId]
            );
        }

        return $alterou;
    }

    public function marcarArquivoRemovido($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_divulgacao_comprovacoes
             SET arquivo_path = NULL, arquivo_removido_em = NOW()
             WHERE id = :id AND arquivo_path IS NOT NULL'
        );
        $stmt->execute(['id' => (int) $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Resumos de arquivo que aparecem em mais de uma inscricao do evento,
     * com quantas pessoas enviaram cada um. Nao recusa nada: e' a marca que
     * leva o caso ao Administrador, porque duas pessoas enviando o mesmo
     * arquivo tambem e' caso legitimo (o cartaz oficial da campanha e'
     * identico byte a byte para quem o baixou).
     */
    public function resumosRepetidosNoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT arquivo_sha256, COUNT(DISTINCT evento_inscricao_id) AS pessoas
             FROM evento_divulgacao_comprovacoes
             WHERE evento_id = :evento AND arquivo_sha256 IS NOT NULL
             GROUP BY arquivo_sha256
             HAVING pessoas > 1'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $linha) {
            $mapa[$linha['arquivo_sha256']] = (int) $linha['pessoas'];
        }

        return $mapa;
    }

    /**
     * Lista da tela administrativa: a comprovacao com o nome de quem enviou.
     * O nome aparece porque sem ele nao ha' como comparar a prova com a rede
     * que a pessoa cadastrou em "Meu Perfil", que e' exatamente a conferencia
     * por amostragem prevista no documento da dinamica de pontos.
     */
    public function listarPorEvento($eventoId, array $filtros = [])
    {
        $sql =
            'SELECT c.*, u.nome AS participante_nome, u.email AS participante_email,
                    up.redes_sociais AS participante_redes, a.nome AS anulado_por_nome
             FROM evento_divulgacao_comprovacoes c
             INNER JOIN evento_inscricoes i ON i.id = c.evento_inscricao_id
             INNER JOIN usuarios u ON u.id = i.usuario_id
             LEFT JOIN usuarios_perfil up ON up.usuario_id = u.id
             LEFT JOIN usuarios a ON a.id = c.anulado_por
             WHERE c.evento_id = :evento';
        $parametros = ['evento' => (int) $eventoId];

        if (!empty($filtros['rede'])) {
            $sql .= ' AND c.rede = :rede';
            $parametros['rede'] = $filtros['rede'];
        }

        if (!empty($filtros['tipo_acao'])) {
            $sql .= ' AND c.tipo_acao = :tipo_acao';
            $parametros['tipo_acao'] = $filtros['tipo_acao'];
        }

        if (isset($filtros['situacao']) && $filtros['situacao'] === 'anuladas') {
            $sql .= ' AND c.anulado_em IS NOT NULL';
        } elseif (isset($filtros['situacao']) && $filtros['situacao'] === 'validas') {
            $sql .= ' AND c.anulado_em IS NULL';
        }

        $sql .= ' ORDER BY c.enviado_em DESC, c.id DESC';

        $pdo = Database::conexao();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function listarComArquivoDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT id, arquivo_path FROM evento_divulgacao_comprovacoes
             WHERE evento_id = :evento AND arquivo_path IS NOT NULL'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Numeros da tela administrativa: total de comprovacoes validas, total de
     * pontos validos, quantas pessoas participaram e quantas anulacoes.
     */
    public function numerosPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN anulado_em IS NULL THEN 1 ELSE 0 END), 0) AS validas,
                    COALESCE(SUM(CASE WHEN anulado_em IS NOT NULL THEN 1 ELSE 0 END), 0) AS anuladas,
                    COALESCE(SUM(CASE WHEN anulado_em IS NULL THEN pontos_creditados ELSE 0 END), 0) AS pontos,
                    COUNT(DISTINCT evento_inscricao_id) AS pessoas
             FROM evento_divulgacao_comprovacoes WHERE evento_id = :evento'
        );
        $stmt->execute(['evento' => (int) $eventoId]);
        $linha = $stmt->fetch();

        if ($linha === false) {
            return ['total' => 0, 'validas' => 0, 'anuladas' => 0, 'pontos' => 0, 'pessoas' => 0];
        }

        return [
            'total' => (int) $linha['total'],
            'validas' => (int) $linha['validas'],
            'anuladas' => (int) $linha['anuladas'],
            'pontos' => (int) $linha['pontos'],
            'pessoas' => (int) $linha['pessoas'],
        ];
    }

    public function contarPorRedeETipo($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT rede, tipo_acao, COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN anulado_em IS NULL THEN pontos_creditados ELSE 0 END), 0) AS pontos
             FROM evento_divulgacao_comprovacoes
             WHERE evento_id = :evento
             GROUP BY rede, tipo_acao
             ORDER BY rede ASC, tipo_acao ASC'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        return $stmt->fetchAll();
    }

    public function contarPorDia($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT DATE(enviado_em) AS dia, COUNT(*) AS total
             FROM evento_divulgacao_comprovacoes
             WHERE evento_id = :evento
             GROUP BY DATE(enviado_em)
             ORDER BY dia ASC'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        return $stmt->fetchAll();
    }
}
