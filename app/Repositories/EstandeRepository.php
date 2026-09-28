<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;
use App\Services\CodigoUnicoService;

/**
 * Fase 54: estandes de expositor e de patrocinador de um Evento. O codigo
 * fixo (codigo_estande) e' gerado uma vez na criacao e nunca muda: e' ele
 * que o participante le no aplicativo para registrar a visita. Toda busca
 * por id que parte de dado enviado confere o evento junto.
 */
class EstandeRepository
{
    const CATEGORIAS = [
        'expositor' => 'Expositor',
        'patrocinador' => 'Patrocinador',
    ];

    /**
     * Lista administrativa: inclui inativos, a contagem de visitas e quem
     * representa cada estande.
     */
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM evento_estande_visitas v WHERE v.estande_id = e.id) AS total_visitas,
                    u.nome AS representante_nome, u.email AS representante_email
             FROM evento_estandes e
             LEFT JOIN evento_estande_representantes r ON r.estande_id = e.id
             LEFT JOIN usuarios u ON u.id = r.usuario_id
             WHERE e.evento_id = :evento_id
             ORDER BY e.ordem ASC, e.id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Estandes ativos para o aplicativo e para a pagina publica. Nunca traz
     * o codigo do estande: ele so' aparece no cartaz impresso e na tela do
     * Administrador.
     */
    public function listarAtivosPublico($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT id, evento_id, nome, categoria, descricao_html, logotipo_path, logotipo_alt, pontos_visita, ordem
             FROM evento_estandes
             WHERE evento_id = :evento_id AND ativo = 1
             ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Recurso opcional do painel do participante: falha de banco nunca
     * derruba o painel, so' esconde o botao.
     */
    public function existeAtivoNoEvento($eventoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare('SELECT 1 FROM evento_estandes WHERE evento_id = :evento_id AND ativo = 1 LIMIT 1');
            $stmt->execute(['evento_id' => $eventoId]);

            return $stmt->fetchColumn() !== false;
        } catch (\PDOException $e) {
            error_log('[Estandes] Falha ao consultar estandes do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return false;
        }
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_estandes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $estande = $stmt->fetch();

        return $estande !== false ? $estande : null;
    }

    /**
     * Busca pelo codigo lido, restrita ao evento de quem le. Nao filtra
     * ativo de proposito: quem chama distingue "codigo inexistente" de
     * "estande que nao recebe visitas agora".
     */
    public function buscarPorCodigo($eventoId, $codigo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_estandes WHERE evento_id = :evento_id AND codigo_estande = :codigo LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId, 'codigo' => $codigo]);

        $estande = $stmt->fetch();

        return $estande !== false ? $estande : null;
    }

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM evento_estandes WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        $campos = [
            'evento_id' => $eventoId,
            'nome' => $dados['nome'],
            'categoria' => $dados['categoria'],
            'descricao_html' => $dados['descricao_html'],
            'logotipo_path' => $dados['logotipo_path'],
            'logotipo_alt' => $dados['logotipo_alt'],
            'pontos_visita' => $dados['pontos_visita'],
            'codigo_estande' => CodigoUnicoService::gerar('evento_estandes', 'codigo_estande'),
            'ordem' => $proximaOrdem,
            'ativo' => $dados['ativo'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO evento_estandes
                (evento_id, nome, categoria, descricao_html, logotipo_path, logotipo_alt, pontos_visita, codigo_estande, ordem, ativo)
             VALUES
                (:evento_id, :nome, :categoria, :descricao_html, :logotipo_path, :logotipo_alt, :pontos_visita, :codigo_estande, :ordem, :ativo)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_estandes', $id, null, $campos);

        return $id;
    }

    /**
     * Gravacao pelo Administrador. O codigo nunca entra aqui.
     */
    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $campos = [
            'id' => $id,
            'nome' => $dados['nome'],
            'categoria' => $dados['categoria'],
            'descricao_html' => $dados['descricao_html'],
            'logotipo_path' => $dados['logotipo_path'],
            'logotipo_alt' => $dados['logotipo_alt'],
            'pontos_visita' => $dados['pontos_visita'],
            'ativo' => $dados['ativo'],
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_estandes
             SET nome = :nome, categoria = :categoria, descricao_html = :descricao_html,
                 logotipo_path = :logotipo_path, logotipo_alt = :logotipo_alt,
                 pontos_visita = :pontos_visita, ativo = :ativo
             WHERE id = :id'
        );
        $stmt->execute($campos);

        Auditoria::registrar('atualizar', 'evento_estandes', $id, $antes, $campos);
    }

    /**
     * Gravacao pelo representante: so' nome, descricao e logotipo. Codigo,
     * pontos, categoria e ativo sao do Administrador e nunca passam por
     * aqui, mesmo que venham num formulario manipulado.
     */
    public function atualizarPeloRepresentante($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $campos = [
            'id' => $id,
            'nome' => $dados['nome'],
            'descricao_html' => $dados['descricao_html'],
            'logotipo_path' => $dados['logotipo_path'],
            'logotipo_alt' => $dados['logotipo_alt'],
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_estandes
             SET nome = :nome, descricao_html = :descricao_html, logotipo_path = :logotipo_path, logotipo_alt = :logotipo_alt
             WHERE id = :id'
        );
        $stmt->execute($campos);

        Auditoria::registrar('atualizar_pelo_representante', 'evento_estandes', $id, $antes, $campos);
    }

    /**
     * Reordenacao por arrastar e soltar: so' grava a posicao de ids deste
     * evento (evento_id no WHERE).
     */
    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_estandes SET ordem = :ordem WHERE id = :id AND evento_id = :evento_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento_id' => $eventoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'evento_estandes', null, null, ['evento_id' => $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Remove o estande numa transacao so': recusa se ja houver visita
     * registrada, e desvincula o representante (retirando o perfil dele se
     * era o ultimo estande que representava) antes de apagar. Devolve o
     * caminho do logotipo para quem chama apagar o arquivo depois.
     */
    public function removerSemVisitas($eventoId, $id)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM evento_estandes WHERE id = :id AND evento_id = :evento_id FOR UPDATE');
            $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);
            $estande = $stmt->fetch();

            if ($estande === false) {
                $pdo->rollBack();

                return ['ok' => false, 'mensagem' => 'Estande não encontrado.', 'logotipo_path' => null];
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_estande_visitas WHERE estande_id = :id');
            $stmt->execute(['id' => $id]);

            if ((int) $stmt->fetchColumn() > 0) {
                $pdo->rollBack();

                return ['ok' => false, 'mensagem' => 'Não é possível remover: este estande já tem visitas registradas. Para tirá-lo do aplicativo e da página, desmarque "Ativo".', 'logotipo_path' => null];
            }

            (new EstandeRepresentanteRepository())->desvincularNaTransacaoAtual($id);

            $stmt = $pdo->prepare('DELETE FROM evento_estandes WHERE id = :id AND evento_id = :evento_id');
            $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

            $pdo->commit();
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        Auditoria::registrar('remover', 'evento_estandes', (int) $id, $estande, null);

        return ['ok' => true, 'mensagem' => 'Estande removido.', 'logotipo_path' => $estande['logotipo_path']];
    }
}
