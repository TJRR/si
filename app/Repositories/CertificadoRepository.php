<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 59: os certificados EMITIDOS (migration 199). Guarda o documento, nao
 * o recalcula: a apuracao fica em CertificadoElegibilidadeService e a
 * geracao em CertificadoEmissaoService.
 *
 * O nome, o documento, a carga horaria e as condicoes ficam gravados na
 * linha, e e' deles que vivem a tela administrativa e a pagina publica de
 * conferencia. Nenhuma consulta daqui refaz a conta de elegibilidade, de
 * proposito: o documento entregue tem que continuar explicavel mesmo depois
 * de uma presenca ser removida ou de a regua mudar.
 */
class CertificadoRepository
{
    use OperacaoEmLote;

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_certificados WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * A busca pela chave de unicidade da migration 199. E' o caminho normal
     * de "esta pessoa ja' tem este certificado?", e tambem o caminho de volta
     * de quem perde a corrida no INSERT.
     */
    public function buscarPorChave($eventoId, $chave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_certificados
              WHERE evento_id = :evento_id AND chave_unicidade = :chave LIMIT 1'
        );
        $stmt->execute(['evento_id' => (int) $eventoId, 'chave' => $chave]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Consulta da pagina publica de conferencia, com o nome do evento. Falha
     * de banco nunca derruba uma pagina aberta a quem nao tem conta: devolve
     * null, e a tela responde o mesmo texto de codigo invalido.
     */
    public function buscarPorCodigo($codigo)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT c.*, e.nome AS evento_nome, a.nome AS atividade_nome, t.titulo AS trabalho_titulo
                   FROM evento_certificados c
                   INNER JOIN eventos e ON e.id = c.evento_id
                   LEFT JOIN evento_atividades a ON a.id = c.atividade_id
                   LEFT JOIN trabalhos t ON t.id = c.trabalho_id
                  WHERE c.codigo_verificacao = :codigo LIMIT 1'
            );
            $stmt->execute(['codigo' => $codigo]);
            $linha = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('[Certificado] Falha ao conferir um codigo: ' . $e->getMessage());

            return null;
        }

        return $linha !== false ? $linha : null;
    }

    /**
     * Grava o certificado emitido. Idempotente pela chave unica (evento_id,
     * chave_unicidade) da migration 199: no estado 23000, quem perdeu a
     * corrida devolve a linha que a outra requisicao gravou, sem erro e sem
     * auditar duas vezes. Mesmo tratamento de EventoCheckinRepository::
     * registrar() e EstandeVisitaRepository::registrar().
     *
     * Devolve a linha gravada, propria ou da outra requisicao, e quem chama
     * compara o arquivo_path para saber se precisa apagar o PDF que acabou de
     * gerar e nao foi aproveitado.
     */
    public function criar(array $dados)
    {
        $pdo = Database::conexao();
        $campos = [
            'evento_id' => (int) $dados['evento_id'],
            'tipo' => $dados['tipo'],
            'chave_unicidade' => $dados['chave_unicidade'],
            'usuario_id' => $dados['usuario_id'] !== null ? (int) $dados['usuario_id'] : null,
            'evento_inscricao_id' => $dados['evento_inscricao_id'] !== null ? (int) $dados['evento_inscricao_id'] : null,
            'atividade_id' => $dados['atividade_id'] !== null ? (int) $dados['atividade_id'] : null,
            'trabalho_id' => $dados['trabalho_id'] !== null ? (int) $dados['trabalho_id'] : null,
            'trabalho_autor_id' => $dados['trabalho_autor_id'] !== null ? (int) $dados['trabalho_autor_id'] : null,
            'condicoes' => $dados['condicoes'],
            'nome' => $dados['nome'],
            'documento' => $dados['documento'],
            'tipo_documento' => $dados['tipo_documento'],
            'carga_horaria_minutos' => $dados['carga_horaria_minutos'] !== null ? (int) $dados['carga_horaria_minutos'] : null,
            'periodo_inicio' => $dados['periodo_inicio'],
            'periodo_fim' => $dados['periodo_fim'],
            'codigo_verificacao' => $dados['codigo_verificacao'],
            'arquivo_path' => $dados['arquivo_path'],
            'tamanho_bytes' => (int) $dados['tamanho_bytes'],
            'sha256' => $dados['sha256'],
            'emitido_por' => $dados['emitido_por'] !== null ? (int) $dados['emitido_por'] : null,
        ];

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_certificados
                     (evento_id, tipo, chave_unicidade, usuario_id, evento_inscricao_id, atividade_id,
                      trabalho_id, trabalho_autor_id, condicoes, nome, documento, tipo_documento,
                      carga_horaria_minutos, periodo_inicio, periodo_fim, codigo_verificacao,
                      arquivo_path, tamanho_bytes, sha256, emitido_em, emitido_por)
                 VALUES (:evento_id, :tipo, :chave_unicidade, :usuario_id, :evento_inscricao_id, :atividade_id,
                         :trabalho_id, :trabalho_autor_id, :condicoes, :nome, :documento, :tipo_documento,
                         :carga_horaria_minutos, :periodo_inicio, :periodo_fim, :codigo_verificacao,
                         :arquivo_path, :tamanho_bytes, :sha256, NOW(), :emitido_por)'
            );
            $stmt->execute($campos);
            $id = (int) $pdo->lastInsertId();
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $existente = $this->buscarPorChave($dados['evento_id'], $dados['chave_unicidade']);

                if ($existente !== null) {
                    return $existente;
                }
            }

            throw $e;
        }

        Auditoria::registrar('emitir_certificado', 'evento_certificados', $id, null, [
            'tipo' => $campos['tipo'],
            'chave_unicidade' => $campos['chave_unicidade'],
            'condicoes' => $campos['condicoes'],
            'carga_horaria_minutos' => $campos['carga_horaria_minutos'],
            'codigo_verificacao' => $campos['codigo_verificacao'],
            'emitido_por' => $campos['emitido_por'],
        ]);

        return $this->buscarPorId($id);
    }

    /**
     * O que a pessoa ja' emitiu neste evento, na forma [chave => linha]. Uma
     * consulta so' para a tela do participante, que precisa saber de cada
     * item se o botao e' "Emitir" ou "Baixar".
     *
     * Falha de banco devolve lista vazia: a tela some em vez de derrubar.
     */
    public function mapaDoUsuarioNoEvento($eventoId, $usuarioId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT * FROM evento_certificados
                  WHERE evento_id = :evento_id AND usuario_id = :usuario_id'
            );
            $stmt->execute(['evento_id' => (int) $eventoId, 'usuario_id' => (int) $usuarioId]);
            $linhas = $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Certificado] Falha ao listar os certificados do usuario ' . (int) $usuarioId . ': ' . $e->getMessage());

            return [];
        }

        $mapa = [];

        foreach ($linhas as $linha) {
            $mapa[$linha['chave_unicidade']] = $linha;
        }

        return $mapa;
    }

    /**
     * Lista da tela administrativa. O nome vem da PROPRIA LINHA, nao de
     * usuarios: e' o nome que foi impresso, e e' esse que a organizacao
     * precisa comparar com o documento em maos. O correio eletronico vem por
     * juncao, e e' nulo no coautor sem conta.
     */
    public function listarPorEvento($eventoId, array $filtros = [], $limite = null, $deslocamento = 0)
    {
        $parametros = [];
        $sql =
            'SELECT c.*, u.email AS usuario_email, a.nome AS atividade_nome,
                    t.titulo AS trabalho_titulo, x.nome AS cancelado_por_nome
               FROM evento_certificados c
               LEFT JOIN usuarios u ON u.id = c.usuario_id
               LEFT JOIN evento_atividades a ON a.id = c.atividade_id
               LEFT JOIN trabalhos t ON t.id = c.trabalho_id
               LEFT JOIN usuarios x ON x.id = c.cancelado_por'
            . $this->ondeDosFiltros($filtros, $parametros, $eventoId)
            . ' ORDER BY c.emitido_em DESC, c.id DESC';

        $pdo = Database::conexao();

        if ($limite === null) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($parametros);

            return $stmt->fetchAll();
        }

        $stmt = $pdo->prepare($sql . ' LIMIT :limite OFFSET :deslocamento');

        foreach ($parametros as $nome => $valor) {
            $stmt->bindValue($nome, $valor);
        }

        $stmt->bindValue('limite', (int) $limite, \PDO::PARAM_INT);
        $stmt->bindValue('deslocamento', (int) $deslocamento, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Quantos certificados o filtro atual alcanca, para o resumo e para a
     * paginacao. Usa o mesmo ondeDosFiltros() da listagem, para que o numero
     * mostrado e a lista nunca discordem.
     */
    public function contarPorEvento($eventoId, array $filtros = [])
    {
        $parametros = [];
        $sql =
            'SELECT COUNT(*)
               FROM evento_certificados c
               LEFT JOIN usuarios u ON u.id = c.usuario_id'
            . $this->ondeDosFiltros($filtros, $parametros, $eventoId);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn();
    }

    /**
     * As linhas de um lote, conferidas contra o evento no proprio comando.
     * Devolve na ordem do nome, que e' a ordem em que a organizacao imprime.
     */
    public function listarPorIds($eventoId, array $ids)
    {
        if ($ids === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($ids), '?'));
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_certificados
              WHERE id IN (' . $marcadores . ') AND evento_id = ?
              ORDER BY nome ASC, id ASC'
        );
        $stmt->execute(array_merge($ids, [(int) $eventoId]));

        return $stmt->fetchAll();
    }

    /**
     * Cancela um certificado emitido por engano. O arquivo continua no
     * servidor, e o que muda e' que ele deixa de ser servido e a pagina
     * publica passa a dizer que o documento foi cancelado: apagar tornaria
     * irreproduzivel um documento que pode ja' ter sido juntado a um
     * processo.
     *
     * Idempotente: devolve false quando ja' estava cancelado, e quem chama
     * decide a mensagem.
     */
    public function cancelar($id, $usuarioId, $motivo)
    {
        $antes = $this->buscarPorId($id);

        if ($antes === null || $antes['cancelado_em'] !== null) {
            return false;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_certificados
                SET cancelado_em = NOW(), cancelado_por = :usuario, motivo_cancelamento = :motivo
              WHERE id = :id AND cancelado_em IS NULL'
        );
        $stmt->execute(['usuario' => (int) $usuarioId, 'motivo' => $motivo, 'id' => (int) $id]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        Auditoria::registrar('cancelar_certificado', 'evento_certificados', (int) $id, $antes, [
            'cancelado_por' => (int) $usuarioId,
            'motivo_cancelamento' => $motivo,
        ]);

        return true;
    }

    /**
     * Cancelamento em lote pelo traco OperacaoEmLote: um comando com IN,
     * escopo de evento no proprio comando, uma transacao e um registro de
     * auditoria por chamada.
     */
    public function cancelarEmLote($eventoId, array $ids, $usuarioId, $motivo)
    {
        return $this->executarLote(
            'UPDATE evento_certificados SET cancelado_em = NOW(), cancelado_por = ?, motivo_cancelamento = ?',
            'cancelado_em IS NULL',
            $eventoId,
            $this->identificadoresDoLote($ids),
            ['cancelado_por' => (int) $usuarioId, 'motivo_cancelamento' => $motivo],
            'cancelar_certificados_em_lote',
            'evento_certificados'
        );
    }

    /**
     * Quantos certificados existem por tipo no evento, para a linha de
     * resumo da tela. Cancelados contam separado.
     */
    public function resumoPorTipo($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT tipo, COUNT(*) AS total, SUM(cancelado_em IS NOT NULL) AS cancelados
               FROM evento_certificados
              WHERE evento_id = :evento_id
              GROUP BY tipo'
        );
        $stmt->execute(['evento_id' => (int) $eventoId]);

        $resumo = [];

        foreach ($stmt->fetchAll() as $linha) {
            $resumo[$linha['tipo']] = [
                'total' => (int) $linha['total'],
                'cancelados' => (int) $linha['cancelados'],
            ];
        }

        return $resumo;
    }

    private function ondeDosFiltros(array $filtros, &$parametros, $eventoId)
    {
        $parametros = ['evento' => (int) $eventoId];
        $sql = ' WHERE c.evento_id = :evento';

        if (isset($filtros['situacao']) && $filtros['situacao'] === 'cancelados') {
            $sql .= ' AND c.cancelado_em IS NOT NULL';
        } elseif (isset($filtros['situacao']) && $filtros['situacao'] === 'validos') {
            $sql .= ' AND c.cancelado_em IS NULL';
        }

        if (!empty($filtros['tipo'])) {
            $sql .= ' AND c.tipo = :tipo';
            $parametros['tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['condicao'])) {
            $sql .= ' AND c.condicoes LIKE :condicao';
            $parametros['condicao'] = '%' . $filtros['condicao'] . '%';
        }

        if (!empty($filtros['busca'])) {
            $sql .= ' AND (c.nome LIKE :busca OR c.codigo_verificacao LIKE :busca OR u.email LIKE :busca)';
            $parametros['busca'] = '%' . $filtros['busca'] . '%';
        }

        return $sql;
    }
}
