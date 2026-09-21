<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 48: vinculo entre um usuario JA EXISTENTE no sistema e uma Atividade
 * especifica, como instrutor/professor/palestrante (perfil_id, ver
 * EventoPerfilOrganizacaoRepository) - nunca cria conta nova (ver
 * AtividadeAdminController::vincularFacilitador()). N por atividade.
 *
 * So' guarda o VINCULO em si (quem, em qual atividade, com qual papel).
 * Documento/cargo/categoria profissional/orgao de origem/minicurriculo sao
 * atributos da PESSOA, nao da designacao - moram em usuarios_perfil (ver
 * UsuarioPerfilRepository), reaproveitados aqui via JOIN. Foto continua em
 * usuarios.foto_path (correcao pos-teste de fumaca: a primeira versao desta
 * tabela duplicava esses campos por designacao, errado - a mesma pessoa
 * teria que redigitar tudo a cada nova atividade).
 *
 * Diferente de EventoAtividadeInscricaoRepository::cancelar() (DELETE
 * fisico), remover() aqui e' sempre uma marcacao (removido_em) - a
 * designacao alimenta a exportacao EJURR, que pode ser gerada em qualquer
 * momento, inclusive apos a atividade ja ter acontecido; apagar de verdade
 * perderia quem realmente conduziu, mesmo espirito de preservacao
 * historica ja usado em evento_checkins (Fase 47).
 */
class EventoAtividadeFacilitadorRepository
{
    private static $selecaoComJoins = "
        SELECT f.*, u.nome AS usuario_nome, u.email AS usuario_email, u.foto_path,
               p.nome AS perfil_nome,
               up.documento, up.tipo_documento, up.cargo, up.categoria_profissional,
               up.orgao_origem, up.minicurriculo
        FROM evento_atividade_facilitadores f
        JOIN usuarios u ON u.id = f.usuario_id
        JOIN evento_perfis_organizacao p ON p.id = f.perfil_id
        LEFT JOIN usuarios_perfil up ON up.usuario_id = f.usuario_id
    ";

    /**
     * Independente de estar removido ou nao - a exportacao EJURR precisa
     * achar mesmo os removidos (ver listarTodosPorAtividade()).
     */
    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(self::$selecaoComJoins . ' WHERE f.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    /**
     * Traz o registro esteja ele ativo ou removido - criar() usa isso para
     * decidir entre inserir e reativar.
     */
    public function buscarPorAtividadeEUsuario($atividadeId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_atividade_facilitadores WHERE atividade_id = :atividade_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute(['atividade_id' => $atividadeId, 'usuario_id' => $usuarioId]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    /**
     * So' os ativos - usado pela tela de gestao admin/atividades/facilitadores.php.
     */
    public function listarPorAtividade($atividadeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(self::$selecaoComJoins . ' WHERE f.atividade_id = :atividade_id AND f.removido_em IS NULL ORDER BY f.designado_em ASC');
        $stmt->execute(['atividade_id' => $atividadeId]);

        return $stmt->fetchAll();
    }

    /**
     * Ativos e removidos - usado pela exportacao EJURR (quem ja conduziu a
     * atividade nunca desaparece do registro exportado).
     */
    public function listarTodosPorAtividade($atividadeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(self::$selecaoComJoins . ' WHERE f.atividade_id = :atividade_id ORDER BY f.designado_em ASC');
        $stmt->execute(['atividade_id' => $atividadeId]);

        return $stmt->fetchAll();
    }

    /**
     * So' ativos - usado por EventoAppController::facilitacoes() (o que
     * mostrar na tela "Minhas facilitacoes" da pessoa logada, dentro de um
     * evento especifico).
     */
    public function listarPorUsuarioNoEvento($usuarioId, $eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT f.*, a.nome AS atividade_nome, a.local, a.data_inicio, a.data_fim, a.modalidade, a.codigo_presenca_online
             FROM evento_atividade_facilitadores f
             JOIN evento_atividades a ON a.id = f.atividade_id
             WHERE f.usuario_id = :usuario_id AND a.evento_id = :evento_id AND f.removido_em IS NULL
             ORDER BY a.data_inicio ASC'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * So' ativos - usado por EventoAppController::index() para decidir se
     * uma pessoa sem nenhuma inscricao de participante ainda assim tem
     * acesso ao aplicativo do evento como facilitadora de alguma atividade.
     */
    public function listarPorUsuarioEmQualquerEvento($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT f.*, a.evento_id
             FROM evento_atividade_facilitadores f
             JOIN evento_atividades a ON a.id = f.atividade_id
             WHERE f.usuario_id = :usuario_id AND f.removido_em IS NULL
             ORDER BY f.designado_em ASC'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Se ja existir um registro removido para o par (atividade, usuario),
     * reativa (preserva designado_em original) em vez de duplicar - o
     * UNIQUE KEY (atividade_id, usuario_id) impediria um segundo INSERT.
     * Erro se ja existir um registro ATIVO (mesma pessoa nao pode ser
     * vinculada duas vezes a mesma atividade).
     */
    public function criar($atividadeId, $usuarioId, $perfilId)
    {
        $existente = $this->buscarPorAtividadeEUsuario($atividadeId, $usuarioId);

        if ($existente !== null && $existente['removido_em'] === null) {
            throw new \RuntimeException('Este usuário já está vinculado como facilitador desta atividade.');
        }

        $pdo = Database::conexao();

        if ($existente !== null) {
            $stmt = $pdo->prepare(
                'UPDATE evento_atividade_facilitadores SET perfil_id = :perfil_id, removido_em = NULL WHERE id = :id'
            );
            $stmt->execute(['perfil_id' => $perfilId, 'id' => $existente['id']]);
            $id = (int) $existente['id'];
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_atividade_facilitadores (atividade_id, usuario_id, perfil_id) VALUES (:atividade_id, :usuario_id, :perfil_id)'
            );
            $stmt->execute(['atividade_id' => $atividadeId, 'usuario_id' => $usuarioId, 'perfil_id' => $perfilId]);
            $id = (int) $pdo->lastInsertId();
        }

        $this->garantirPerfilInscrito($usuarioId);

        Auditoria::registrar('criar', 'evento_atividade_facilitadores', $id, null, [
            'atividade_id' => $atividadeId,
            'usuario_id' => $usuarioId,
            'perfil_id' => $perfilId,
        ]);

        return $id;
    }

    /**
     * UPDATE removido_em = NOW(), nunca DELETE - ver docblock da classe.
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);

        if ($antes === null || $antes['removido_em'] !== null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE evento_atividade_facilitadores SET removido_em = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'evento_atividade_facilitadores', $id, $antes, ['removido_em' => date('Y-m-d H:i:s')]);
    }

    /**
     * Fase 48: condicao necessaria para Auth::destinoPainel() levar o
     * facilitador ate' o aplicativo do evento (ver EventoAppController::index()) -
     * mesmo mecanismo de atribuicao ja usado em AuthService::cadastrarInscrito().
     * So' atribui se a pessoa ainda nao tiver o perfil (usuario_perfil_concurso
     * nao tem UNIQUE que impeca duplicar, PerfilRepository::possuiPerfil()
     * e' quem evita a duplicacao aqui).
     */
    private function garantirPerfilInscrito($usuarioId)
    {
        $perfis = new PerfilRepository();
        $perfilInscrito = $perfis->buscarPorChave('inscrito');

        if ($perfilInscrito === null) {
            return;
        }

        if (!$perfis->possuiPerfil($usuarioId, $perfilInscrito['id'], null)) {
            $perfis->atribuir($usuarioId, $perfilInscrito['id'], null);
        }
    }
}
