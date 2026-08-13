<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 29 (Tira-Duvidas): canal estruturado entre participante e
 * organizacao - participante registra, Administrador responde ou escala pra
 * outro administrador/suporte. Ver DuvidaRespostaRepository (historico de
 * respostas) e DuvidaEscalonamentoRepository (historico de escalonamento)
 * para as tabelas relacionadas.
 *
 * "recebida" = fila geral, visivel a todo Administrador do concurso.
 * "escalada" = so' visivel a quem esta em responsavel_atual_usuario_id.
 * "respondida" = resolvida; so' volta a aparecer pra alguem agir via
 * DuvidaController::reabrir() (participante).
 */
class DuvidaRepository
{
    public function criar($participanteId, $equipeId, $concursoId, $trilhaId, $pergunta, $anexoPath, $anexoNomeOriginal)
    {
        $pdo = Database::conexao();
        $dados = [
            'participante_id' => $participanteId,
            'equipe_id' => $equipeId,
            'concurso_id' => $concursoId,
            'trilha_id' => $trilhaId,
            'pergunta' => $pergunta,
            'anexo_path' => $anexoPath,
            'anexo_nome_original' => $anexoNomeOriginal,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO duvidas (participante_id, equipe_id, concurso_id, trilha_id, pergunta, anexo_path, anexo_nome_original)
             VALUES (:participante_id, :equipe_id, :concurso_id, :trilha_id, :pergunta, :anexo_path, :anexo_nome_original)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'duvidas', $id, null, $dados);

        return $id;
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT d.*, p.nome AS participante_nome, e.nome_equipe, t.nome AS trilha_nome,
                    c.nome AS concurso_nome, u.nome AS responsavel_nome,
                    (SELECT u2.foto_path
                     FROM usuario_participante up
                     INNER JOIN usuarios u2 ON u2.id = up.usuario_id
                     WHERE up.participante_id = p.id
                     LIMIT 1) AS participante_foto_path
             FROM duvidas d
             INNER JOIN participantes p ON p.id = d.participante_id
             INNER JOIN equipes e ON e.id = d.equipe_id
             INNER JOIN trilhas t ON t.id = d.trilha_id
             INNER JOIN concursos c ON c.id = d.concurso_id
             LEFT JOIN usuarios u ON u.id = d.responsavel_atual_usuario_id
             WHERE d.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $duvida = $stmt->fetch();

        return $duvida !== false ? $duvida : null;
    }

    public function listarPorEquipe($equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT d.*, p.nome AS participante_nome
             FROM duvidas d
             INNER JOIN participantes p ON p.id = d.participante_id
             WHERE d.equipe_id = :equipe_id
             ORDER BY d.criado_em DESC'
        );
        $stmt->execute(['equipe_id' => $equipeId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 29 (ajuste pedido pelo usuario): listagem unica pro painel de
     * gestao (fundido no dashboard, home/administrativo) - antes eram dois
     * metodos separados (so' recebidas / so' escaladas), agora e' "todas",
     * com filtro por status. $statusFiltro:
     *   null/''      -> oculta 'respondida' por padrao (recebida+escalada)
     *   'todas'      -> sem filtro de status nenhum, mostra tudo
     *   'recebida'|'escalada'|'respondida' -> so' aquele status
     * escalada_em (momento do ULTIMO escalonamento, NAO criado_em da
     * duvida - relogios diferentes) so' se aplica a quem esta 'escalada'.
     */
    public function listarTodas($concursoIds = null, $statusFiltro = null)
    {
        $pdo = Database::conexao();
        $params = [];
        $sql = "SELECT d.*, p.nome AS participante_nome, e.nome_equipe, t.nome AS trilha_nome,
                       u.nome AS responsavel_nome,
                       (SELECT MAX(de.criado_em) FROM duvida_escalonamentos de WHERE de.duvida_id = d.id) AS escalada_em
                FROM duvidas d
                INNER JOIN participantes p ON p.id = d.participante_id
                INNER JOIN equipes e ON e.id = d.equipe_id
                INNER JOIN trilhas t ON t.id = d.trilha_id
                LEFT JOIN usuarios u ON u.id = d.responsavel_atual_usuario_id
                WHERE 1 = 1" . $this->clausulaConcursoIn($concursoIds, $params);

        if (in_array($statusFiltro, ['recebida', 'escalada', 'respondida'], true)) {
            $sql .= ' AND d.status = :status_filtro';
            $params['status_filtro'] = $statusFiltro;
        } elseif ($statusFiltro !== 'todas') {
            $sql .= " AND d.status IN ('recebida', 'escalada')";
        }

        $sql .= ' ORDER BY d.criado_em DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function listarEscaladasPara($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT d.*, p.nome AS participante_nome, e.nome_equipe, t.nome AS trilha_nome
             FROM duvidas d
             INNER JOIN participantes p ON p.id = d.participante_id
             INNER JOIN equipes e ON e.id = d.equipe_id
             INNER JOIN trilhas t ON t.id = d.trilha_id
             WHERE d.status = 'escalada' AND d.responsavel_atual_usuario_id = :usuario_id
             ORDER BY d.criado_em ASC"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 29 (ajuste pos-push): "Minhas duvidas" (tela do Colaborador) -
     * responsavel_atual_usuario_id NAO muda quando responder() roda, entao
     * da' pra achar tambem o que esse usuario ja respondeu. Por padrao so'
     * as pendentes (status='escalada'), com filtro pra ver as respondidas
     * ou todas - mesmo padrao de listarTodas().
     */
    public function listarPorResponsavel($usuarioId, $statusFiltro = null)
    {
        $pdo = Database::conexao();
        $params = ['usuario_id' => $usuarioId];
        $sql = "SELECT d.*, p.nome AS participante_nome, e.nome_equipe, t.nome AS trilha_nome
                FROM duvidas d
                INNER JOIN participantes p ON p.id = d.participante_id
                INNER JOIN equipes e ON e.id = d.equipe_id
                INNER JOIN trilhas t ON t.id = d.trilha_id
                WHERE d.responsavel_atual_usuario_id = :usuario_id";

        if (in_array($statusFiltro, ['escalada', 'respondida'], true)) {
            $sql .= ' AND d.status = :status_filtro';
            $params['status_filtro'] = $statusFiltro;
        } elseif ($statusFiltro !== 'todas') {
            $sql .= " AND d.status = 'escalada'";
        }

        $sql .= ' ORDER BY d.criado_em DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contarPorStatus($concursoIds = null)
    {
        $pdo = Database::conexao();
        $params = [];
        $sql = 'SELECT d.status, COUNT(*) AS total FROM duvidas d WHERE 1=1'
            . $this->clausulaConcursoIn($concursoIds, $params) . ' GROUP BY d.status';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $contagem = ['recebida' => 0, 'escalada' => 0, 'respondida' => 0];
        foreach ($stmt->fetchAll() as $linha) {
            $contagem[$linha['status']] = (int) $linha['total'];
        }

        return $contagem;
    }

    public function escalar($id, $novoResponsavelId)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare("UPDATE duvidas SET status = 'escalada', responsavel_atual_usuario_id = :usuario_id WHERE id = :id")
            ->execute(['usuario_id' => $novoResponsavelId, 'id' => $id]);

        Auditoria::registrar('escalar', 'duvidas', $id, $antes, ['responsavel_atual_usuario_id' => $novoResponsavelId]);
    }

    public function responder($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare("UPDATE duvidas SET status = 'respondida' WHERE id = :id")->execute(['id' => $id]);

        Auditoria::registrar('responder', 'duvidas', $id, $antes, ['status' => 'respondida']);
    }

    /**
     * Admin puxando de volta uma escalada parada sem resposta (painel de
     * gestao) - diferente de reabrir(), que e' o participante reabrindo uma
     * ja respondida.
     */
    public function retomar($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare("UPDATE duvidas SET status = 'recebida', responsavel_atual_usuario_id = NULL WHERE id = :id")
            ->execute(['id' => $id]);

        Auditoria::registrar('retomar', 'duvidas', $id, $antes, ['status' => 'recebida']);
    }

    public function reabrir($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $pdo->prepare("UPDATE duvidas SET status = 'recebida', responsavel_atual_usuario_id = NULL, reaberta_em = NOW() WHERE id = :id")
            ->execute(['id' => $id]);

        Auditoria::registrar('reabrir', 'duvidas', $id, $antes, ['status' => 'recebida']);
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

        return ' AND d.concurso_id IN (' . implode(',', $marcadores) . ')';
    }
}
