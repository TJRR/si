<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: autoria de Trabalhos, tabela N-para-N (ver migration 151) -
 * suporta qualquer quantidade de autores configurada em
 * evento_trabalhos_config.quantidade_maxima_autores, nunca fixa em 2.
 * cpfJaExisteNoEvento() so' deve ser chamado de dentro da transacao com
 * FOR UPDATE em evento_trabalhos_config (ver TrabalhoSubmissaoService).
 */
class TrabalhoAutorRepository
{
    public function inserir($trabalhoId, $ehAutorPrincipal, $usuarioId, $nome, $cpf, $email, $cargo, $orgaoOrigem)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO trabalho_autores (trabalho_id, eh_autor_principal, usuario_id, nome, cpf, email, cargo, orgao_origem)
             VALUES (:trabalho_id, :eh_autor_principal, :usuario_id, :nome, :cpf, :email, :cargo, :orgao_origem)'
        );
        $stmt->execute([
            'trabalho_id' => $trabalhoId,
            'eh_autor_principal' => $ehAutorPrincipal ? 1 : 0,
            'usuario_id' => $usuarioId,
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => $email,
            'cargo' => $cargo,
            'orgao_origem' => $orgaoOrigem,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'trabalho_autores', $id, null, ['trabalho_id' => $trabalhoId, 'eh_autor_principal' => $ehAutorPrincipal]);

        return $id;
    }

    public function listarPorTrabalho($trabalhoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM trabalho_autores WHERE trabalho_id = :trabalho_id ORDER BY eh_autor_principal DESC, id ASC'
        );
        $stmt->execute(['trabalho_id' => $trabalhoId]);

        return $stmt->fetchAll();
    }

    public function buscarAutorPrincipal($trabalhoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM trabalho_autores WHERE trabalho_id = :trabalho_id AND eh_autor_principal = 1 LIMIT 1'
        );
        $stmt->execute(['trabalho_id' => $trabalhoId]);

        $autor = $stmt->fetch();

        return $autor !== false ? $autor : null;
    }

    public function removerPorTrabalho($trabalhoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM trabalho_autores WHERE trabalho_id = :trabalho_id');
        $stmt->execute(['trabalho_id' => $trabalhoId]);
    }

    /**
     * So' chamar dentro da transacao com FOR UPDATE em
     * evento_trabalhos_config (ver TrabalhoConfigRepository::buscarPorEventoParaAtualizar()).
     * Considera qualquer autor (principal ou coautor) de qualquer trabalho
     * do evento, exceto o proprio $trabalhoIdExcluir (usado ao editar um
     * trabalho ja existente, para nao acusar duplicidade contra si mesmo).
     */
    public function cpfJaExisteNoEvento($eventoId, $cpf, $trabalhoIdExcluir = null)
    {
        $pdo = Database::conexao();
        $sql = 'SELECT COUNT(*)
                FROM trabalho_autores a
                INNER JOIN trabalhos t ON t.id = a.trabalho_id
                WHERE t.evento_id = :evento_id AND a.cpf = :cpf';
        $parametros = ['evento_id' => $eventoId, 'cpf' => $cpf];

        if ($trabalhoIdExcluir !== null) {
            $sql .= ' AND a.trabalho_id != :trabalho_id_excluir';
            $parametros['trabalho_id_excluir'] = $trabalhoIdExcluir;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Usado por EventoAppController::index() para desviar, antes do
     * fallback de "sem inscricao", quem ganhou o perfil `inscrito` so' por
     * ser autor (principal ou coautor) de algum trabalho - mesmo padrao ja resolvido
     * para o Facilitador na Fase 48
     * (EventoAtividadeFacilitadorRepository::listarPorUsuarioEmQualquerEvento()).
     */
    public function possuiTrabalhoEmQualquerEvento($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM trabalho_autores WHERE usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Fase 49B, achado do teste de fumaça (item 6.a): impede designar
     * como avaliador alguém que já é autor (principal ou coautor com
     * conta vinculada) do próprio trabalho - TrabalhoAdminController::
     * recebidoDesignar() consulta antes de gravar em trabalho_designacoes.
     * Coautor sem usuario_id (sem conta) nunca bate aqui, o que é
     * inofensivo: sem conta, essa pessoa não pode ser escolhida no
     * formulário de designação de qualquer forma.
     */
    public function usuarioEhAutor($trabalhoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM trabalho_autores WHERE trabalho_id = :trabalho_id AND usuario_id = :usuario_id'
        );
        $stmt->execute(['trabalho_id' => $trabalhoId, 'usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Fase 49B, achado do teste de fumaça (item 6.b): usado por
     * TrabalhoAvaliadorConviteService::convidar() e por
     * TrabalhoSubmissaoService::submeter() para bloquear a exclusão mútua
     * autor/avaliador dentro do mesmo evento, nas duas direções. Verifica
     * por E-MAIL (não por usuario_id), porque cobre os dois papéis com um
     * único critério: autor principal sempre tem conta/usuario_id, mas
     * coautor não tem obrigatoriamente - o e-mail, esse sim, é campo
     * obrigatório de trabalho_autores para qualquer papel. Escopado ao
     * evento (a mesma pessoa pode ser autora num evento e avaliadora
     * avulsa de outro, sem conflito).
     */
    public function possuiTrabalhoNoEventoPorEmail($eventoId, $email)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM trabalho_autores a
             INNER JOIN trabalhos t ON t.id = a.trabalho_id
             WHERE t.evento_id = :evento_id AND a.email = :email'
        );
        $stmt->execute(['evento_id' => $eventoId, 'email' => $email]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Tela "meus trabalhos" (trabalho/meusTrabalhos) - lista, para a
     * pessoa logada, todo trabalho em que ela e' autora principal, em
     * qualquer evento.
     */
    public function listarTrabalhosDoUsuario($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT t.*, ev.nome AS evento_nome, a.eh_autor_principal AS sou_autor_principal
             FROM trabalho_autores a
             INNER JOIN trabalhos t ON t.id = a.trabalho_id
             INNER JOIN eventos ev ON ev.id = t.evento_id
             WHERE a.usuario_id = :usuario_id
             ORDER BY t.submetido_em DESC'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }
}
