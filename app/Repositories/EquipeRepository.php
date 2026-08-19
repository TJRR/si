<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class EquipeRepository
{
    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM equipes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $equipe = $stmt->fetch();

        return $equipe !== false ? $equipe : null;
    }

    public function buscarPorParticipante($participanteId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT e.*
             FROM equipes e
             INNER JOIN equipe_participante ep ON ep.equipe_id = e.id
             WHERE ep.participante_id = :participante_id
             LIMIT 1'
        );
        $stmt->execute(['participante_id' => $participanteId]);

        $equipe = $stmt->fetch();

        return $equipe !== false ? $equipe : null;
    }

    public function buscarPorTrilhaENome($trilhaId, $nomeEquipe)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM equipes WHERE trilha_id = :trilha_id AND nome_equipe = :nome_equipe LIMIT 1');
        $stmt->execute(['trilha_id' => $trilhaId, 'nome_equipe' => $nomeEquipe]);

        $equipe = $stmt->fetch();

        return $equipe !== false ? $equipe : null;
    }

    /**
     * Busca global por nome (sem escopar por trilha) - usada por scripts CLI
     * (ex.: migrar_equipe_trilha.php, Fase 17 Bug 9) que precisam achar a
     * equipe antes de saber em qual trilha ela esta hoje.
     */
    public function buscarPorNome($nomeEquipe)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM equipes WHERE nome_equipe = :nome_equipe LIMIT 1');
        $stmt->execute(['nome_equipe' => $nomeEquipe]);

        $equipe = $stmt->fetch();

        return $equipe !== false ? $equipe : null;
    }

    /**
     * Busca aproximada (LIKE, sem distincao de maiusculas/acentos exatos) -
     * usada pelos scripts CLI de database/ para sugerir candidatos quando
     * buscarPorNome() nao acha nada por nome exato (erro de digitacao,
     * espaco a mais, etc.), em vez de so' informar "nao encontrado" sem
     * nenhuma pista.
     */
    public function listarSemelhantesPorNome($nomeParcial)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM equipes WHERE nome_equipe LIKE :nome ORDER BY nome_equipe ASC LIMIT 15');
        $stmt->execute(['nome' => '%' . $nomeParcial . '%']);

        return $stmt->fetchAll();
    }

    public function criar($trilhaId, $nomeEquipe, $vinculoInstitucional, $observacoes)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO equipes (trilha_id, nome_equipe, vinculo_institucional, observacoes)
             VALUES (:trilha_id, :nome_equipe, :vinculo_institucional, :observacoes)'
        );
        $dados = [
            'trilha_id' => $trilhaId,
            'nome_equipe' => $nomeEquipe,
            'vinculo_institucional' => $vinculoInstitucional !== '' ? $vinculoInstitucional : null,
            'observacoes' => $observacoes !== '' ? $observacoes : null,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'equipes', $id, null, $dados);

        return $id;
    }

    public function atualizar($equipeId, $nomeEquipe, $vinculoInstitucional, $observacoes)
    {
        $antes = $this->buscarPorId($equipeId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE equipes SET nome_equipe = :nome_equipe, vinculo_institucional = :vinculo_institucional, observacoes = :observacoes
             WHERE id = :id'
        );
        $depois = [
            'nome_equipe' => $nomeEquipe,
            'vinculo_institucional' => $vinculoInstitucional !== '' ? $vinculoInstitucional : null,
            'observacoes' => $observacoes !== '' ? $observacoes : null,
        ];
        $stmt->execute($depois + ['id' => $equipeId]);

        Auditoria::registrar('atualizar', 'equipes', $equipeId, $antes, $depois);
    }

    /**
     * Fase 17 (Bug 2): grava o Desafio escolhido na propria equipe - antes
     * desta fase, "tema_desafio_id"/"desafio_id" nunca era escrito por nenhum
     * codigo (o valor ficava so' dentro do JSON da submissao). Chamado por
     * SubmissaoService::gravar() e pelo script de importacao do Google Forms.
     */
    public function definirDesafio($equipeId, $desafioId)
    {
        $antes = $this->buscarPorId($equipeId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE equipes SET desafio_id = :desafio_id WHERE id = :id');
        $stmt->execute(['desafio_id' => $desafioId, 'id' => $equipeId]);

        Auditoria::registrar('definir_desafio', 'equipes', $equipeId, $antes, ['desafio_id' => $desafioId]);
    }

    /**
     * Fase 17 (Bug 9): migra a equipe para outra trilha (via script CLI, sem
     * funcionalidade de interface - comprometeria a genericidade do sistema).
     * Zera desafio_id: o desafio escolhido pertence a um Tema/trilha antigos e
     * nao existe na trilha nova (Bug 2 escopa desafios por trilha).
     */
    public function migrarParaTrilha($equipeId, $novaTrilhaId)
    {
        $antes = $this->buscarPorId($equipeId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE equipes SET trilha_id = :trilha_id, desafio_id = NULL WHERE id = :id');
        $stmt->execute(['trilha_id' => $novaTrilhaId, 'id' => $equipeId]);

        Auditoria::registrar('migrar_trilha', 'equipes', $equipeId, $antes, ['trilha_id' => $novaTrilhaId, 'desafio_id' => null]);
    }

    public function alterarLider($equipeId, $novoLiderParticipanteId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $rebaixar = $pdo->prepare(
                "UPDATE equipe_participante SET papel = 'integrante' WHERE equipe_id = :equipe_id AND papel = 'lider'"
            );
            $rebaixar->execute(['equipe_id' => $equipeId]);

            $promover = $pdo->prepare(
                "UPDATE equipe_participante SET papel = 'lider' WHERE equipe_id = :equipe_id AND participante_id = :participante_id"
            );
            $promover->execute(['equipe_id' => $equipeId, 'participante_id' => $novoLiderParticipanteId]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('alterar_lider', 'equipes', $equipeId, null, ['novo_lider_participante_id' => $novoLiderParticipanteId]);
    }

    public function cpfJaInscritoNaTrilha($trilhaId, $cpf)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM equipe_participante ep
             INNER JOIN equipes e ON e.id = ep.equipe_id
             INNER JOIN participantes p ON p.id = ep.participante_id
             WHERE e.trilha_id = :trilha_id AND p.cpf = :cpf'
        );
        $stmt->execute(['trilha_id' => $trilhaId, 'cpf' => $cpf]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function vincularParticipante($equipeId, $participanteId, $papel)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO equipe_participante (equipe_id, participante_id, papel) VALUES (:equipe_id, :participante_id, :papel)'
        );
        $dados = [
            'equipe_id' => $equipeId,
            'participante_id' => $participanteId,
            'papel' => $papel,
        ];
        $stmt->execute($dados);

        Auditoria::registrar('vincular_participante', 'equipes', $equipeId, null, $dados);
    }

    public function desvincularParticipante($equipeId, $participanteId)
    {
        $antes = $this->buscarVinculo($equipeId, $participanteId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'DELETE FROM equipe_participante WHERE equipe_id = :equipe_id AND participante_id = :participante_id'
        );
        $stmt->execute(['equipe_id' => $equipeId, 'participante_id' => $participanteId]);

        Auditoria::registrar('desvincular_participante', 'equipes', $equipeId, $antes, null);
    }

    public function listarComContagemParticipantes($trilhaId = null)
    {
        $pdo = Database::conexao();

        $sql = 'SELECT e.*, t.nome AS trilha_nome, COUNT(ep.participante_id) AS total_participantes
                FROM equipes e
                JOIN trilhas t ON t.id = e.trilha_id
                LEFT JOIN equipe_participante ep ON ep.equipe_id = e.id';

        $parametros = [];

        if ($trilhaId !== null) {
            $sql .= ' WHERE e.trilha_id = :trilha_id';
            $parametros['trilha_id'] = $trilhaId;
        }

        $sql .= ' GROUP BY e.id ORDER BY t.nome ASC, e.nome_equipe ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Fase 31: e-mail de cada integrante de uma ou mais equipes (union),
     * pra recomputar a lista de attendees do Google Agenda de um horario
     * de mentoria (1 equipe) ou oficina (N equipes). Mesma fonte ja usada
     * em notificarEquipe() (MentoriaAdminController/OficinaAdminController)
     * - inclui integrante sem login vinculado e independente de status de
     * homologacao, pra nao deixar ninguem de fora silenciosamente. Chave do
     * array e' o e-mail (dedup automatico); valor e' o participante_id,
     * usado so' como rotulo de exibicao em google_convite_status.
     */
    public function listarEmailsPorEquipes(array $equipeIds)
    {
        if (empty($equipeIds)) {
            return [];
        }

        $pdo = Database::conexao();
        $placeholders = implode(',', array_fill(0, count($equipeIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT p.id AS participante_id, p.email
             FROM participantes p
             JOIN equipe_participante ep ON ep.participante_id = p.id
             WHERE ep.equipe_id IN ($placeholders) AND p.email IS NOT NULL AND p.email <> ''"
        );
        $stmt->execute(array_values($equipeIds));

        $porEmail = [];

        foreach ($stmt->fetchAll() as $linha) {
            $porEmail[$linha['email']] = (int) $linha['participante_id'];
        }

        return $porEmail;
    }

    /**
     * Fase 32: integrantes de uma ou mais equipes PRESERVANDO equipe_id -
     * listarEmailsPorEquipes() acima nao serve aqui porque devolve um mapa
     * achatado por e-mail, perdendo de qual equipe cada pessoa veio. Numa
     * Oficina (N equipes na mesma sala do Meet), a presenca capturada precisa
     * ser atribuida a equipe certa, entao a origem tem que sobreviver a
     * consulta.
     *
     * Traz tambem o nome, que e' a chave de casamento com quem entrou no Meet
     * (a Meet API nao devolve e-mail - ver App\Services\GoogleMeetService).
     * Inclui integrante sem e-mail, ao contrario do metodo acima: quem nao tem
     * login ainda assim pode ter entrado na sala.
     */
    public function listarParticipantesPorEquipes(array $equipeIds)
    {
        if (empty($equipeIds)) {
            return [];
        }

        $pdo = Database::conexao();
        $placeholders = implode(',', array_fill(0, count($equipeIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT p.id AS participante_id, p.nome, p.email, ep.equipe_id, e.nome_equipe
             FROM participantes p
             JOIN equipe_participante ep ON ep.participante_id = p.id
             JOIN equipes e ON e.id = ep.equipe_id
             WHERE ep.equipe_id IN ($placeholders)
             ORDER BY e.nome_equipe, p.nome"
        );
        $stmt->execute(array_values($equipeIds));

        return $stmt->fetchAll();
    }

    public function listarParticipantes($equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT p.*, ep.papel, ep.status_homologacao, ep.motivo_rejeicao
             FROM participantes p
             JOIN equipe_participante ep ON ep.participante_id = p.id
             WHERE ep.equipe_id = :equipe_id
             ORDER BY ep.papel ASC, p.nome ASC'
        );
        $stmt->execute(['equipe_id' => $equipeId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 19 (#17): equipes da trilha com integrantes homologados no
     * minimo configurado em trilhas.minimo_integrantes_homologados (o
     * limiar e' do Admin, nao hardcoded aqui) - usada pela pagina publica.
     * So' devolve nome de equipe e nome dos integrantes HOMOLOGADOS (nunca
     * cpf/email/telefone, e nunca quem esta pendente/rejeitado).
     */
    public function listarHomologadasPorTrilha($trilhaId, $minimoIntegrantesHomologados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT e.id AS equipe_id, e.nome_equipe
             FROM equipes e
             WHERE e.trilha_id = :trilha_id
               AND (
                   SELECT COUNT(*) FROM equipe_participante ep
                   WHERE ep.equipe_id = e.id AND ep.status_homologacao = 'homologado'
               ) >= :minimo
             ORDER BY e.nome_equipe ASC"
        );
        $stmt->execute(['trilha_id' => $trilhaId, 'minimo' => $minimoIntegrantesHomologados]);
        $equipes = $stmt->fetchAll();

        if (empty($equipes)) {
            return [];
        }

        $stmtIntegrantes = $pdo->prepare(
            "SELECT p.nome, ep.papel
             FROM participantes p
             JOIN equipe_participante ep ON ep.participante_id = p.id
             WHERE ep.equipe_id = :equipe_id AND ep.status_homologacao = 'homologado'
             ORDER BY ep.papel ASC, p.nome ASC"
        );

        foreach ($equipes as &$equipe) {
            $stmtIntegrantes->execute(['equipe_id' => $equipe['equipe_id']]);
            $equipe['integrantes'] = $stmtIntegrantes->fetchAll();
        }
        unset($equipe);

        return $equipes;
    }

    /**
     * Fase 31: equipes homologadas do concurso (mesmo criterio de
     * listarHomologadasPorTrilha() - minimo de integrantes homologados por
     * trilha) que ainda nao tem nenhuma participacao registrada em evento
     * (reserva de mentoria ou inscricao em oficina) - usada pelas telas de
     * Mentorias/Oficinas do admin pra identificar quem falta engajar.
     * $trilhaId opcional restringe a uma trilha so'.
     */
    public function listarHomologadasSemParticipacaoEmEventos($concursoId, $trilhaId = null)
    {
        $pdo = Database::conexao();
        $sql = "SELECT e.id AS equipe_id, e.nome_equipe, t.id AS trilha_id, t.nome AS trilha_nome
                FROM equipes e
                JOIN trilhas t ON t.id = e.trilha_id
                WHERE t.concurso_id = :concurso_id
                  AND (
                      SELECT COUNT(*) FROM equipe_participante ep
                      WHERE ep.equipe_id = e.id AND ep.status_homologacao = 'homologado'
                  ) >= t.minimo_integrantes_homologados
                  AND NOT EXISTS (
                      SELECT 1 FROM mentoria_horarios mh
                      WHERE mh.equipe_id = e.id AND mh.concurso_id = :concurso_id
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM oficina_inscricoes oi
                      JOIN oficina_horarios oh ON oh.id = oi.oficina_horario_id
                      WHERE oi.equipe_id = e.id AND oh.concurso_id = :concurso_id
                  )";
        $parametros = ['concurso_id' => $concursoId];

        if ($trilhaId !== null) {
            $sql .= ' AND e.trilha_id = :trilha_id';
            $parametros['trilha_id'] = $trilhaId;
        }

        $sql .= ' ORDER BY t.nome ASC, e.nome_equipe ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Fase 31: equipes CLASSIFICADAS na etapa anterior da trilha
     * (resultados_etapa.classificado = 1 pra submissao da equipe naquela
     * etapa - mesmo criterio que AcessoEtapaService::motivoBloqueio usa
     * pra liberar acesso a etapa atual) que ainda nao tem nenhuma
     * participacao registrada em evento (reserva de mentoria ou inscricao
     * em oficina) no concurso. Complementa
     * listarHomologadasSemParticipacaoEmEventos(): essa aqui e' o "aprovada"
     * de etapas > 1 (a homologacao de cadastro so' vale pra etapa 1) - quem
     * chama resolve $etapaAnteriorId via EtapaRepository::
     * buscarAnteriorNaTrilha() antes de chegar aqui.
     */
    public function listarClassificadasNaEtapaSemParticipacaoEmEventos($concursoId, $trilhaId, $etapaAnteriorId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT e.id AS equipe_id, e.nome_equipe, t.id AS trilha_id, t.nome AS trilha_nome
             FROM equipes e
             JOIN trilhas t ON t.id = e.trilha_id
             JOIN submissoes s ON s.equipe_id = e.id AND s.etapa_id = :etapa_anterior_id
             JOIN resultados_etapa re ON re.submissao_id = s.id AND re.etapa_id = :etapa_anterior_id AND re.classificado = 1
             WHERE e.trilha_id = :trilha_id
               AND NOT EXISTS (
                   SELECT 1 FROM mentoria_horarios mh
                   WHERE mh.equipe_id = e.id AND mh.concurso_id = :concurso_id
               )
               AND NOT EXISTS (
                   SELECT 1 FROM oficina_inscricoes oi
                   JOIN oficina_horarios oh ON oh.id = oi.oficina_horario_id
                   WHERE oi.equipe_id = e.id AND oh.concurso_id = :concurso_id
               )
             ORDER BY e.nome_equipe ASC"
        );
        $stmt->execute([
            'etapa_anterior_id' => $etapaAnteriorId,
            'trilha_id' => $trilhaId,
            'concurso_id' => $concursoId,
        ]);

        return $stmt->fetchAll();
    }

    public function listarPendentesHomologacaoPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT ep.id AS vinculo_id, ep.equipe_id, ep.participante_id, ep.papel,
                    e.nome_equipe, p.nome AS participante_nome, p.cpf, p.email, p.telefone
             FROM equipe_participante ep
             INNER JOIN equipes e ON e.id = ep.equipe_id
             INNER JOIN participantes p ON p.id = ep.participante_id
             WHERE e.trilha_id = :trilha_id AND ep.status_homologacao = 'pendente'
             ORDER BY e.nome_equipe ASC, ep.papel ASC"
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    /**
     * Lista TODAS as inscricoes (vinculos equipe_participante) de uma trilha,
     * independente do status de homologacao — ao contrario de
     * listarPendentesHomologacaoPorTrilha(), usada pela tela "Inscritos" para
     * nao esconder equipes ja homologadas. $status opcional filtra por um dos
     * valores de equipe_participante.status_homologacao.
     */
    public function listarTodosPorTrilha($trilhaId, $status = null)
    {
        $pdo = Database::conexao();

        $sql = "SELECT ep.id AS vinculo_id, ep.equipe_id, ep.participante_id, ep.papel, ep.status_homologacao,
                       ep.motivo_rejeicao, e.nome_equipe, p.nome AS participante_nome, p.cpf, p.email, p.telefone
                FROM equipe_participante ep
                INNER JOIN equipes e ON e.id = ep.equipe_id
                INNER JOIN participantes p ON p.id = ep.participante_id
                WHERE e.trilha_id = :trilha_id";

        $parametros = ['trilha_id' => $trilhaId];

        if ($status !== null && $status !== '') {
            $sql .= ' AND ep.status_homologacao = :status';
            $parametros['status'] = $status;
        }

        $sql .= ' ORDER BY e.nome_equipe ASC, ep.papel ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function buscarVinculoPorId($vinculoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM equipe_participante WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $vinculoId]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    public function buscarVinculo($equipeId, $participanteId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM equipe_participante WHERE equipe_id = :equipe_id AND participante_id = :participante_id LIMIT 1'
        );
        $stmt->execute(['equipe_id' => $equipeId, 'participante_id' => $participanteId]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    public function homologarVinculo($vinculoId, $usuarioId)
    {
        $antes = $this->buscarVinculoPorId($vinculoId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "UPDATE equipe_participante
             SET status_homologacao = 'homologado', homologado_por = :usuario_id, homologado_em = NOW(), motivo_rejeicao = NULL
             WHERE id = :id"
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'id' => $vinculoId]);

        Auditoria::registrar('homologar_vinculo', 'equipes', $vinculoId, $antes, [
            'usuario_id' => $usuarioId,
            'status_homologacao' => 'homologado',
        ]);
    }

    public function rejeitarVinculo($vinculoId, $usuarioId, $motivo)
    {
        $antes = $this->buscarVinculoPorId($vinculoId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "UPDATE equipe_participante
             SET status_homologacao = 'rejeitado', homologado_por = :usuario_id, homologado_em = NOW(), motivo_rejeicao = :motivo
             WHERE id = :id"
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'motivo' => $motivo, 'id' => $vinculoId]);

        Auditoria::registrar('rejeitar_vinculo', 'equipes', $vinculoId, $antes, [
            'usuario_id' => $usuarioId,
            'motivo' => $motivo,
            'status_homologacao' => 'rejeitado',
        ]);
    }

    public function voltarParaPendente($vinculoId)
    {
        $antes = $this->buscarVinculoPorId($vinculoId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "UPDATE equipe_participante
             SET status_homologacao = 'pendente', homologado_por = NULL, homologado_em = NULL, motivo_rejeicao = NULL
             WHERE id = :id"
        );
        $stmt->execute(['id' => $vinculoId]);

        Auditoria::registrar('voltar_para_pendente', 'equipes', $vinculoId, $antes, ['status_homologacao' => 'pendente']);
    }
}
