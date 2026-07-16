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
