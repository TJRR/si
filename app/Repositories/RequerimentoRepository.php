<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 30: pedido que o lider de uma equipe classificada protocola a partir
 * de um modelo de documento - gera o PDF sem assinatura, assina fora do
 * sistema (gov.br), devolve assinado, entra num fluxo de analise que
 * espelha o Tira-Duvidas (ver DuvidaRepository), mas com desfecho
 * estruturado (aprovado/recusado/esclarecimento_solicitado/revogado) em vez
 * de uma conversa aberta. Ver RequerimentoRespostaRepository (historico de
 * respostas) e RequerimentoEscalonamentoRepository (historico de
 * escalonamento).
 *
 * 'aguardando_assinatura' = o lider ainda precisa gerar/assinar/enviar (ou
 * reenviar, apos esclarecimento_solicitado). 'recebido' = fila geral,
 * visivel a todo Administrador do concurso. 'escalado' = so' visivel a quem
 * esta em responsavel_atual_usuario_id. 'aprovado'/'recusado'/'revogado' =
 * definitivos, sem volta.
 */
class RequerimentoRepository
{
    public function criar($modeloDocumentoId, $etapaId, $trilhaId, $concursoId, $equipeId, $participanteId, $necessidade)
    {
        $pdo = Database::conexao();
        $dados = [
            'modelo_documento_id' => $modeloDocumentoId,
            'etapa_id' => $etapaId,
            'trilha_id' => $trilhaId,
            'concurso_id' => $concursoId,
            'equipe_id' => $equipeId,
            'participante_id' => $participanteId,
            'necessidade' => $necessidade,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO requerimentos (modelo_documento_id, etapa_id, trilha_id, concurso_id, equipe_id, participante_id, necessidade)
             VALUES (:modelo_documento_id, :etapa_id, :trilha_id, :concurso_id, :equipe_id, :participante_id, :necessidade)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'requerimentos', $id, null, $dados);

        return $id;
    }

    /**
     * Regrava o rascunho (ainda em aguardando_assinatura) com a necessidade
     * possivelmente ajustada - "Gerar PDF" de novo nunca cria pedido
     * duplicado, so' atualiza a mesma linha.
     */
    public function atualizarNecessidade($id, $necessidade)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare('UPDATE requerimentos SET necessidade = :necessidade WHERE id = :id')
            ->execute(['necessidade' => $necessidade, 'id' => $id]);

        Auditoria::registrar('atualizar_necessidade', 'requerimentos', $id, $antes, ['necessidade' => $necessidade]);
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT r.*, md.nome AS modelo_nome, e.nome AS etapa_nome, t.nome AS trilha_nome,
                    c.nome AS concurso_nome, eq.nome_equipe, p.nome AS participante_nome, p.cpf AS participante_cpf,
                    u.nome AS responsavel_nome
             FROM requerimentos r
             INNER JOIN modelos_documento md ON md.id = r.modelo_documento_id
             INNER JOIN etapas e ON e.id = r.etapa_id
             INNER JOIN trilhas t ON t.id = r.trilha_id
             INNER JOIN concursos c ON c.id = r.concurso_id
             INNER JOIN equipes eq ON eq.id = r.equipe_id
             INNER JOIN participantes p ON p.id = r.participante_id
             LEFT JOIN usuarios u ON u.id = r.responsavel_atual_usuario_id
             WHERE r.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $requerimento = $stmt->fetch();

        return $requerimento !== false ? $requerimento : null;
    }

    public function listarPorEquipe($equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT r.*, md.nome AS modelo_nome
             FROM requerimentos r
             INNER JOIN modelos_documento md ON md.id = r.modelo_documento_id
             WHERE r.equipe_id = :equipe_id
             ORDER BY r.criado_em DESC'
        );
        $stmt->execute(['equipe_id' => $equipeId]);

        return $stmt->fetchAll();
    }

    /**
     * Impede duas solicitacoes em andamento em paralelo pro mesmo modelo -
     * depois de um desfecho definitivo (aprovado/recusado/revogado) a
     * equipe pode protocolar de novo, so' nao duas ao mesmo tempo.
     */
    public function buscarEmAndamentoPorEquipeEModelo($equipeId, $modeloDocumentoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT * FROM requerimentos
             WHERE equipe_id = :equipe_id AND modelo_documento_id = :modelo_documento_id
               AND status NOT IN ('aprovado', 'recusado', 'revogado')
             LIMIT 1"
        );
        $stmt->execute(['equipe_id' => $equipeId, 'modelo_documento_id' => $modeloDocumentoId]);
        $requerimento = $stmt->fetch();

        return $requerimento !== false ? $requerimento : null;
    }

    /**
     * Fila geral + filtro de status, restrita por concurso - mesmo padrao
     * de DuvidaRepository::listarTodas(). $statusFiltro:
     *   null/''  -> oculta os tres desfechos definitivos por padrao
     *   'todos'  -> sem filtro nenhum
     *   um status valido -> so' aquele
     */
    public function listarTodas($concursoIds = null, $statusFiltro = null)
    {
        $pdo = Database::conexao();
        $params = [];
        $sql = "SELECT r.*, md.nome AS modelo_nome, eq.nome_equipe, p.nome AS participante_nome,
                       u.nome AS responsavel_nome
                FROM requerimentos r
                INNER JOIN modelos_documento md ON md.id = r.modelo_documento_id
                INNER JOIN equipes eq ON eq.id = r.equipe_id
                INNER JOIN participantes p ON p.id = r.participante_id
                LEFT JOIN usuarios u ON u.id = r.responsavel_atual_usuario_id
                WHERE 1 = 1" . $this->clausulaConcursoIn($concursoIds, $params);

        $statusValidos = ['aguardando_assinatura', 'recebido', 'escalado', 'esclarecimento_solicitado', 'aprovado', 'recusado', 'revogado'];

        if (in_array($statusFiltro, $statusValidos, true)) {
            $sql .= ' AND r.status = :status_filtro';
            $params['status_filtro'] = $statusFiltro;
        } elseif ($statusFiltro !== 'todos') {
            $sql .= " AND r.status IN ('recebido', 'escalado')";
        }

        $sql .= ' ORDER BY r.criado_em DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function listarEscaladasPara($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT r.*, md.nome AS modelo_nome, eq.nome_equipe, p.nome AS participante_nome
             FROM requerimentos r
             INNER JOIN modelos_documento md ON md.id = r.modelo_documento_id
             INNER JOIN equipes eq ON eq.id = r.equipe_id
             INNER JOIN participantes p ON p.id = r.participante_id
             WHERE r.status = 'escalado' AND r.responsavel_atual_usuario_id = :usuario_id
             ORDER BY r.criado_em ASC"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function listarPorResponsavel($usuarioId, $statusFiltro = null)
    {
        $pdo = Database::conexao();
        $params = ['usuario_id' => $usuarioId];
        $sql = "SELECT r.*, md.nome AS modelo_nome, eq.nome_equipe, p.nome AS participante_nome
                FROM requerimentos r
                INNER JOIN modelos_documento md ON md.id = r.modelo_documento_id
                INNER JOIN equipes eq ON eq.id = r.equipe_id
                INNER JOIN participantes p ON p.id = r.participante_id
                WHERE r.responsavel_atual_usuario_id = :usuario_id";

        if (in_array($statusFiltro, ['escalado', 'aprovado', 'recusado', 'revogado'], true)) {
            $sql .= ' AND r.status = :status_filtro';
            $params['status_filtro'] = $statusFiltro;
        } elseif ($statusFiltro !== 'todos') {
            $sql .= " AND r.status = 'escalado'";
        }

        $sql .= ' ORDER BY r.criado_em DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contarPorStatus($concursoIds = null)
    {
        $pdo = Database::conexao();
        $params = [];
        $sql = 'SELECT r.status, COUNT(*) AS total FROM requerimentos r WHERE 1=1'
            . $this->clausulaConcursoIn($concursoIds, $params) . ' GROUP BY r.status';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $contagem = [
            'aguardando_assinatura' => 0, 'recebido' => 0, 'escalado' => 0,
            'esclarecimento_solicitado' => 0, 'aprovado' => 0, 'recusado' => 0, 'revogado' => 0,
        ];
        foreach ($stmt->fetchAll() as $linha) {
            $contagem[$linha['status']] = (int) $linha['total'];
        }

        return $contagem;
    }

    public function marcarEnviado($id, $pdfAssinadoPath, $pdfAssinadoNomeOriginal)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare(
            "UPDATE requerimentos
             SET status = 'recebido', pdf_assinado_path = :pdf_path, pdf_assinado_nome_original = :pdf_nome, enviado_em = NOW()
             WHERE id = :id"
        )->execute(['pdf_path' => $pdfAssinadoPath, 'pdf_nome' => $pdfAssinadoNomeOriginal, 'id' => $id]);

        Auditoria::registrar('marcar_enviado', 'requerimentos', $id, $antes, ['status' => 'recebido', 'pdf_assinado_path' => $pdfAssinadoPath]);
    }

    public function escalar($id, $novoResponsavelId)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare("UPDATE requerimentos SET status = 'escalado', responsavel_atual_usuario_id = :usuario_id WHERE id = :id")
            ->execute(['usuario_id' => $novoResponsavelId, 'id' => $id]);

        Auditoria::registrar('escalar', 'requerimentos', $id, $antes, ['responsavel_atual_usuario_id' => $novoResponsavelId]);
    }

    public function retomar($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare("UPDATE requerimentos SET status = 'recebido', responsavel_atual_usuario_id = NULL WHERE id = :id")
            ->execute(['id' => $id]);

        Auditoria::registrar('retomar', 'requerimentos', $id, $antes, ['status' => 'recebido']);
    }

    /**
     * $desfecho e' o proprio valor gravado em status - os dois usam a
     * mesma grafia (aprovado/recusado/esclarecimento_solicitado/revogado),
     * ver RequerimentoRespostaRepository::criar().
     */
    public function registrarDesfecho($id, $desfecho)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare('UPDATE requerimentos SET status = :status WHERE id = :id')
            ->execute(['status' => $desfecho, 'id' => $id]);

        Auditoria::registrar('registrar_desfecho', 'requerimentos', $id, $antes, ['status' => $desfecho]);
    }

    public function reabrirParaNovoEnvio($id, $necessidade)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare(
            "UPDATE requerimentos
             SET status = 'aguardando_assinatura', necessidade = :necessidade, responsavel_atual_usuario_id = NULL, reaberta_em = NOW()
             WHERE id = :id"
        )->execute(['necessidade' => $necessidade, 'id' => $id]);

        Auditoria::registrar('reabrir_para_novo_envio', 'requerimentos', $id, $antes, ['status' => 'aguardando_assinatura']);
    }

    /**
     * So' permitida enquanto nada foi protocolado ainda (enviado_em NULL) -
     * exclusao definitiva, sem soft-delete: um rascunho nunca chegado ao
     * TJRR nao tem valor de historico a preservar na propria linha (o fato
     * de ter existido fica so' no log de auditoria geral).
     */
    public function descartar($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare('DELETE FROM requerimentos WHERE id = :id')->execute(['id' => $id]);

        Auditoria::registrar('descartar', 'requerimentos', $id, $antes, null);
    }

    /**
     * Requerimentos em estado terminal (aprovado/recusado/revogado) de uma
     * etapa, com pdf_assinado_path preenchido e ainda nao expurgado - usados
     * por ModeloDocumentoAdminController::expurgar().
     */
    public function listarTerminaisNaoExpurgadosPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT * FROM requerimentos
             WHERE etapa_id = :etapa_id
               AND status IN ('aprovado', 'recusado', 'revogado')
               AND pdf_assinado_path IS NOT NULL
               AND expurgado_em IS NULL"
        );
        $stmt->execute(['etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    /**
     * Conta quantos requerimentos da etapa ainda nao estao em estado
     * terminal - usado pela tela de expurgo pra informar "X ficaram de
     * fora por ainda estarem em analise".
     */
    public function contarNaoTerminaisPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM requerimentos
             WHERE etapa_id = :etapa_id AND status NOT IN ('aprovado', 'recusado', 'revogado')"
        );
        $stmt->execute(['etapa_id' => $etapaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Abre a janela de validacao automatica no ITI: grava so' o hash do
     * token (sha256) - o token bruto nunca fica no banco, so' na URL que o
     * proprio servidor monta e usa na hora (ver RequerimentoAdminController::
     * validarIti()). iti_token_usado_em volta a NULL a cada nova tentativa.
     */
    public function gravarTokenValidacaoIti($id, $tokenHash, $expiraEm)
    {
        $pdo = Database::conexao();
        $pdo->prepare(
            'UPDATE requerimentos SET iti_token_hash = :hash, iti_token_expira_em = :expira, iti_token_usado_em = NULL WHERE id = :id'
        )->execute(['hash' => $tokenHash, 'expira' => $expiraEm, 'id' => $id]);
    }

    /**
     * Reivindicacao atomica de uso unico: so' devolve true (e marca
     * iti_token_usado_em) se o hash bate, o token ainda nao expirou e ainda
     * nao foi usado - UPDATE com WHERE cobrindo as tres condicoes evita
     * corrida entre duas chamadas simultaneas reivindicando o mesmo token.
     */
    public function reivindicarTokenValidacaoIti($id, $tokenHash)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "UPDATE requerimentos
             SET iti_token_usado_em = NOW()
             WHERE id = :id AND iti_token_hash = :hash
               AND iti_token_expira_em > NOW() AND iti_token_usado_em IS NULL"
        );
        $stmt->execute(['id' => $id, 'hash' => $tokenHash]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Fecha a janela assim que a chamada ao ITI termina (sucesso ou falha) -
     * nao espera o token expirar sozinho. Chamado sempre, mesmo se o ITI
     * nunca chegou a reivindicar o token (erro de rede, timeout).
     */
    public function invalidarTokenValidacaoIti($id)
    {
        $pdo = Database::conexao();
        $pdo->prepare('UPDATE requerimentos SET iti_token_hash = NULL, iti_token_expira_em = NULL WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /**
     * So' log de auditoria - nenhuma coluna persistente guarda o resultado
     * (nao e' um dado que precise sobreviver alem do historico geral). Nulo
     * quando o formato da resposta veio diferente do esperado.
     */
    public function registrarValidacaoIti($id, array $resultado = null)
    {
        Auditoria::registrar('validar_iti', 'requerimentos', $id, null, ['resultado' => $resultado]);
    }

    public function marcarExpurgado($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare('UPDATE requerimentos SET expurgado_em = NOW() WHERE id = :id')->execute(['id' => $id]);

        Auditoria::registrar('marcar_expurgado', 'requerimentos', $id, $antes, ['expurgado_em' => date('Y-m-d H:i:s')]);
    }

    /**
     * $concursoIds null = sem filtro (administrador global, ve tudo).
     * Array vazio = usuario sem acesso a nenhum concurso, forca zero
     * resultados em vez de devolver tudo por engano.
     */
    private function clausulaConcursoIn($concursoIds, array &$params)
    {
        if ($concursoIds === null) {
            return '';
        }

        if (empty($concursoIds)) {
            return ' AND 1 = 0';
        }

        $marcadores = [];
        foreach (array_values($concursoIds) as $indice => $concursoId) {
            $chave = 'concurso' . $indice;
            $marcadores[] = ':' . $chave;
            $params[$chave] = (int) $concursoId;
        }

        return ' AND r.concurso_id IN (' . implode(',', $marcadores) . ')';
    }
}
