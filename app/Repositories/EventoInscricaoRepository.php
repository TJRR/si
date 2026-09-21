<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 39: inscricao de um usuario (conta ja' existente, sem duplicar
 * cadastro/senha) num evento da Semana de Inovacao - FK simples pra
 * usuarios.id, UNIQUE (evento_id, usuario_id) evita inscricao repetida.
 *
 * Fase 39 (correcao pos-teste): as respostas dos campos configuraveis
 * pelo Admin (EventoCampoInscricaoRepository, inclusive "Tipo de
 * documento"/"Cargo"/"Órgão de origem") ficam em `respostas_json`, chave =
 * id do campo, mesmo padrao de submissoes.dados_json.
 *
 * Fase 49B: "Documento" NUNCA fica em evento_inscricoes - e' dado da
 * PESSOA (usuarios_perfil), nao do vinculo dela com este evento
 * especifico. listarPorEvento()/buscarPorEventoEUsuario() trazem
 * perfil_documento/perfil_tipo_documento/perfil_cargo/perfil_orgao_origem
 * via LEFT JOIN, e EventoInscricaoPublicaController::inscrever() e' quem
 * grava ali (UsuarioPerfilRepository::atualizarParcial()), nunca este
 * repositorio.
 */
class EventoInscricaoRepository
{
    public function buscarPorEventoEUsuario($eventoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ei.*, up.documento AS perfil_documento, up.tipo_documento AS perfil_tipo_documento,
                    up.cargo AS perfil_cargo, up.orgao_origem AS perfil_orgao_origem
             FROM evento_inscricoes ei
             LEFT JOIN usuarios_perfil up ON up.usuario_id = ei.usuario_id
             WHERE ei.evento_id = :evento_id AND ei.usuario_id = :usuario_id
             LIMIT 1'
        );
        $stmt->execute(['evento_id' => $eventoId, 'usuario_id' => $usuarioId]);

        $inscricao = $stmt->fetch();

        return $inscricao !== false ? $inscricao : null;
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ei.*, up.documento AS perfil_documento, up.tipo_documento AS perfil_tipo_documento,
                    up.cargo AS perfil_cargo, up.orgao_origem AS perfil_orgao_origem
             FROM evento_inscricoes ei
             LEFT JOIN usuarios_perfil up ON up.usuario_id = ei.usuario_id
             WHERE ei.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $inscricao = $stmt->fetch();

        return $inscricao !== false ? $inscricao : null;
    }

    public function estaInscrito($eventoId, $usuarioId)
    {
        return $this->buscarPorEventoEUsuario($eventoId, $usuarioId) !== null;
    }

    /**
     * Fase 40 (correcao pos-teste de fumaca): em quais eventos esta conta
     * esta inscrita, com o nome do evento - usado em usuarios/index para
     * mostrar "Inscrito em evento" de forma concreta (nome do evento) em
     * vez do generico "(Global)" que so' faz sentido para perfis escopados
     * por concurso.
     */
    public function listarPorUsuario($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ei.*, e.nome AS evento_nome
             FROM evento_inscricoes ei
             JOIN eventos e ON e.id = ei.evento_id
             WHERE ei.usuario_id = :usuario_id
             ORDER BY e.data_inicio DESC'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ei.*, u.nome AS usuario_nome, u.email AS usuario_email,
                    up.documento AS perfil_documento, up.tipo_documento AS perfil_tipo_documento,
                    up.cargo AS perfil_cargo, up.orgao_origem AS perfil_orgao_origem
             FROM evento_inscricoes ei
             JOIN usuarios u ON u.id = ei.usuario_id
             LEFT JOIN usuarios_perfil up ON up.usuario_id = ei.usuario_id
             WHERE ei.evento_id = :evento_id
             ORDER BY ei.inscrito_em ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 39 (revisada): modo de credenciamento do evento decide se a
     * inscricao ja' nasce homologada ("automatico") ou pendente de acao do
     * Admin em Inscritos ("assistido") - ver homologar(). $respostas e' um
     * array [campo_id => valor] dos campos configuraveis do evento
     * (EventoCampoInscricaoRepository) - "Documento" e' estrutural, fica
     * fora desse array.
     */
    public function inscrever($eventoId, $usuarioId, array $respostas, $modoCredenciamento)
    {
        if ($this->estaInscrito($eventoId, $usuarioId)) {
            return false;
        }

        $campos = [
            'evento_id' => $eventoId,
            'usuario_id' => $usuarioId,
            'respostas_json' => json_encode($respostas),
            'homologado_em' => $modoCredenciamento === 'automatico' ? date('Y-m-d H:i:s') : null,
            'codigo_credenciamento' => $this->gerarCodigoCredenciamentoUnico(),
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_inscricoes (evento_id, usuario_id, respostas_json, homologado_em, codigo_credenciamento)
             VALUES (:evento_id, :usuario_id, :respostas_json, :homologado_em, :codigo_credenciamento)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('inscrever', 'evento_inscricoes', $id, null, $campos);

        return true;
    }

    /**
     * Fase 42: codigo curto (6 caracteres, Crockford Base32), pensado pra
     * digitacao manual de fallback se a leitura do QR falhar - o formato
     * original (32 hex) era ilegivel pra esse uso. Fase 46: geracao/alfabeto/
     * checagem de unicidade extraidos para CodigoUnicoService (compartilhado
     * com evento_atividades.codigo_atividade) - este metodo vira wrapper fino,
     * mantendo database/gerar_codigos_credenciamento_pendentes.php e
     * buscarPorCodigo() funcionando sem alteracao.
     */
    public function gerarCodigoCredenciamentoUnico()
    {
        return \App\Services\CodigoUnicoService::gerar('evento_inscricoes', 'codigo_credenciamento');
    }

    /**
     * Fase 43: busca de uma inscricao pelo codigo de credenciamento lido
     * (QR ou digitacao manual) - SEMPRE restrita ao evento do LEITOR, nunca
     * uma busca global. Isso, por si so', garante que um codigo de outro
     * evento simplesmente "nao e' encontrado", sem precisar de nenhuma
     * logica extra para nao vazar a existencia dele.
     */
    public function buscarPorCodigo($eventoId, $codigo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ei.*, u.nome AS usuario_nome
             FROM evento_inscricoes ei
             JOIN usuarios u ON u.id = ei.usuario_id
             WHERE ei.evento_id = :evento_id AND ei.codigo_credenciamento = :codigo
             LIMIT 1'
        );
        $stmt->execute(['evento_id' => $eventoId, 'codigo' => $codigo]);

        $inscricao = $stmt->fetch();

        return $inscricao !== false ? $inscricao : null;
    }

    public function homologar($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE evento_inscricoes SET homologado_em = :homologado_em WHERE id = :id AND homologado_em IS NULL');
        $stmt->execute(['homologado_em' => date('Y-m-d H:i:s'), 'id' => $id]);

        Auditoria::registrar('homologar', 'evento_inscricoes', $id, null, ['homologado_em' => date('Y-m-d H:i:s')]);
    }

    /**
     * Fase 39 (correcao pos-teste): Admin/Suporte pode corrigir ou
     * preencher a resposta de um campo especifico (ex.: "Tipo de vinculo")
     * direto na tela de Inscritos, sem depender do participante reenviar o
     * formulario - read-modify-write simples sobre o JSON.
     */
    public function atualizarResposta($inscricaoId, $campoId, $valor)
    {
        $inscricao = $this->buscarPorId($inscricaoId);

        if ($inscricao === null) {
            return;
        }

        $respostas = $inscricao['respostas_json'] !== null ? json_decode($inscricao['respostas_json'], true) : [];
        $antes = $respostas;
        $respostas[$campoId] = $valor !== '' ? $valor : null;

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE evento_inscricoes SET respostas_json = :respostas_json WHERE id = :id');
        $stmt->execute(['respostas_json' => json_encode($respostas), 'id' => $inscricaoId]);

        Auditoria::registrar('atualizar_resposta', 'evento_inscricoes', $inscricaoId, $antes, $respostas);
    }
}
