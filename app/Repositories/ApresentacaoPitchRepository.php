<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 38B: agendamento das apresentacoes de pitch da Etapa 3 (Edital NPI
 * n.9/2026, item 2.3). Mesmo modelo "admin cria horario vago, equipe
 * reserva" de MentoriaRepository, copiado por estrutura (nao por heranca) -
 * ver plano da fase para a justificativa de nao generalizar as duas tabelas
 * agora.
 */
class ApresentacaoPitchRepository
{
    /**
     * Equipes classificadas na etapa ANTERIOR da trilha (mesmo critério que
     * já libera o acesso à etapa seguinte, EventoEtapaService/AcessoEtapaService)
     * que ainda não têm nenhum slot reservado nesta etapa - usadas pelo
     * Admin na atribuição manual (item 2.3.8 do edital).
     */
    public function listarEquipesClassificadasSemSlot($etapaId, $etapaAnteriorId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT e.id AS equipe_id, e.nome_equipe
             FROM equipes e
             JOIN submissoes s ON s.equipe_id = e.id AND s.etapa_id = :etapa_anterior_id
             JOIN resultados_etapa re ON re.submissao_id = s.id AND re.etapa_id = :etapa_anterior_id AND re.classificado = 1
             WHERE NOT EXISTS (
                 SELECT 1 FROM apresentacoes_pitch ap
                 WHERE ap.etapa_id = :etapa_id AND ap.equipe_id = e.id
             )
             ORDER BY e.nome_equipe'
        );
        $stmt->execute(['etapa_anterior_id' => $etapaAnteriorId, 'etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    public function listarPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ap.*, e.nome_equipe
             FROM apresentacoes_pitch ap
             LEFT JOIN equipes e ON e.id = ap.equipe_id
             WHERE ap.etapa_id = :etapa_id
             ORDER BY ap.data_inicio ASC'
        );
        $stmt->execute(['etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    public function listarVagosPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM apresentacoes_pitch
             WHERE etapa_id = :etapa_id AND equipe_id IS NULL
             ORDER BY data_inicio ASC'
        );
        $stmt->execute(['etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorEquipeNaEtapa($etapaId, $equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM apresentacoes_pitch WHERE etapa_id = :etapa_id AND equipe_id = :equipe_id LIMIT 1'
        );
        $stmt->execute(['etapa_id' => $etapaId, 'equipe_id' => $equipeId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM apresentacoes_pitch WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function criarSlot($etapaId, $dataInicio, $dataFim)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO apresentacoes_pitch (etapa_id, data_inicio, data_fim) VALUES (:etapa_id, :data_inicio, :data_fim)'
        );
        $dados = ['etapa_id' => $etapaId, 'data_inicio' => $dataInicio, 'data_fim' => $dataFim];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'apresentacoes_pitch', $id, null, $dados);

        return $id;
    }

    /**
     * Fase 38B (correcao pos-teste de fumaca): edicao das datas de um slot
     * ainda VAGO - quem chama garante equipe_id IS NULL antes de invocar.
     */
    public function atualizarDatas($id, $dataInicio, $dataFim)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE apresentacoes_pitch SET data_inicio = :data_inicio, data_fim = :data_fim WHERE id = :id');
        $dados = ['data_inicio' => $dataInicio, 'data_fim' => $dataFim];
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar', 'apresentacoes_pitch', $id, $antes, $dados);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM apresentacoes_pitch WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'apresentacoes_pitch', $id, $antes, null);
    }

    /**
     * Checagem otimista pelo proprio WHERE (equipe_id IS NULL): se
     * rowCount() vier 0, o slot ja foi reservado por outra equipe entre a
     * listagem e o clique - quem chama deve tratar como erro, nunca
     * sobrescrever. Mesmo mecanismo ja testado em producao por
     * MentoriaRepository::reservar().
     */
    public function reservar($id, $equipeId, $modalidade)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE apresentacoes_pitch SET equipe_id = :equipe_id, modalidade = :modalidade, reservado_em = NOW()
             WHERE id = :id AND equipe_id IS NULL'
        );
        $stmt->execute(['equipe_id' => $equipeId, 'modalidade' => $modalidade, 'id' => $id]);

        $sucesso = $stmt->rowCount() > 0;

        if ($sucesso) {
            Auditoria::registrar('reservar', 'apresentacoes_pitch', $id, null, ['equipe_id' => $equipeId, 'modalidade' => $modalidade]);
        }

        return $sucesso;
    }

    /**
     * Atribuicao manual pelo Admin (item 2.3.8 do edital) - mesma trava
     * otimista de reservar(), mas sempre em modalidade online e sem exigir
     * que a equipe esteja logada.
     */
    public function atribuirManual($id, $equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "UPDATE apresentacoes_pitch SET equipe_id = :equipe_id, modalidade = 'online', reservado_em = NOW()
             WHERE id = :id AND equipe_id IS NULL"
        );
        $stmt->execute(['equipe_id' => $equipeId, 'id' => $id]);

        $sucesso = $stmt->rowCount() > 0;

        if ($sucesso) {
            Auditoria::registrar('atribuir_manual', 'apresentacoes_pitch', $id, null, ['equipe_id' => $equipeId, 'modalidade' => 'online']);
        }

        return $sucesso;
    }

    public function cancelarReserva($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE apresentacoes_pitch SET equipe_id = NULL, modalidade = NULL, reservado_em = NULL WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('cancelar_reserva', 'apresentacoes_pitch', $id, $antes, null);
    }

    public function atualizarGoogle($id, array $colunas)
    {
        $mapa = [
            'google_event_id' => 'google_event_id',
            'google_calendar_id' => 'google_calendar_id',
            'meet_link' => 'link_meet',
            'meet_link_origem' => 'meet_link_origem',
            'meet_pendente' => 'meet_pendente',
            'google_conference_id' => 'google_conference_id',
            'google_sincronizado_em' => 'google_sincronizado_em',
        ];

        $campos = [];

        foreach ($mapa as $chave => $coluna) {
            if (array_key_exists($chave, $colunas)) {
                $campos[$coluna] = $colunas[$chave];
            }
        }

        if (empty($campos)) {
            return;
        }

        $antes = $this->buscarPorId($id);
        $sets = [];

        foreach (array_keys($campos) as $coluna) {
            $sets[] = "$coluna = :$coluna";
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE apresentacoes_pitch SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($campos + ['id' => $id]);

        Auditoria::registrar('atualizar_google', 'apresentacoes_pitch', $id, $antes, $campos);
    }

    public function marcarIntegracaoGoogle($id, $integrado)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE apresentacoes_pitch SET integracao_google = :integracao_google WHERE id = :id');
        $stmt->execute(['integracao_google' => $integrado ? 1 : 0, 'id' => $id]);
    }

    /**
     * Mesma lógica/janela de MentoriaRepository::listarPendentesDePresenca(),
     * ver comentário lá - diferente dela, resolve o e-mail organizador via
     * JOIN com apresentacao_pitch_config (por etapa), já que não há um
     * "mentor" fixo por linha nesta tabela.
     */
    public function listarPendentesDePresenca($limite)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ap.*, c.email_organizador_google AS organizador_email
             FROM apresentacoes_pitch ap
             JOIN apresentacao_pitch_config c ON c.etapa_id = ap.etapa_id
             WHERE ap.integracao_google = 1
               AND ap.google_conference_id IS NOT NULL
               AND ap.presenca_status = \'pendente\'
               AND ap.data_fim <= (NOW() - INTERVAL 2 HOUR)
               AND (ap.presenca_ultima_tentativa_em IS NULL
                    OR ap.presenca_ultima_tentativa_em <= (NOW() - INTERVAL 30 MINUTE))
               AND c.email_organizador_google IS NOT NULL
             ORDER BY ap.data_fim ASC
             LIMIT :limite'
        );
        $stmt->bindValue('limite', (int) $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function atualizarPresenca($id, array $colunas)
    {
        $permitidas = ['presenca_status', 'presenca_tentativas', 'presenca_ultima_tentativa_em', 'presenca_capturada_em'];
        $campos = array_intersect_key($colunas, array_flip($permitidas));

        if (empty($campos)) {
            return;
        }

        $sets = [];

        foreach (array_keys($campos) as $coluna) {
            $sets[] = "$coluna = :$coluna";
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE apresentacoes_pitch SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($campos + ['id' => $id]);
    }

    public function buscarConfig($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM apresentacao_pitch_config WHERE etapa_id = :etapa_id LIMIT 1');
        $stmt->execute(['etapa_id' => $etapaId]);

        $config = $stmt->fetch();

        return $config !== false ? $config : null;
    }

    public function salvarConfig($etapaId, $janelaInicio, $janelaFim, $emailOrganizador, $enderecoPresencial)
    {
        $existente = $this->buscarConfig($etapaId);
        $dados = [
            'etapa_id' => $etapaId,
            'janela_escolha_inicio' => $janelaInicio,
            'janela_escolha_fim' => $janelaFim,
            'email_organizador_google' => $emailOrganizador,
            'endereco_presencial' => $enderecoPresencial,
        ];

        $pdo = Database::conexao();

        if ($existente === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO apresentacao_pitch_config (etapa_id, janela_escolha_inicio, janela_escolha_fim, email_organizador_google, endereco_presencial)
                 VALUES (:etapa_id, :janela_escolha_inicio, :janela_escolha_fim, :email_organizador_google, :endereco_presencial)'
            );
            $stmt->execute($dados);

            Auditoria::registrar('criar', 'apresentacao_pitch_config', $etapaId, null, $dados);

            return;
        }

        $stmt = $pdo->prepare(
            'UPDATE apresentacao_pitch_config
                SET janela_escolha_inicio = :janela_escolha_inicio, janela_escolha_fim = :janela_escolha_fim,
                    email_organizador_google = :email_organizador_google, endereco_presencial = :endereco_presencial
              WHERE etapa_id = :etapa_id'
        );
        $stmt->execute($dados);

        Auditoria::registrar('atualizar', 'apresentacao_pitch_config', $etapaId, $existente, $dados);
    }
}
