<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: submissoes de Trabalhos. Os metodos "ParaAvaliador"/
 * "DesignadosParaUsuario" recebem $sigiloCego explicitamente e SO' trazem
 * os campos de autoria (trabalho_autores) quando $sigiloCego for false -
 * defesa em profundidade: a ocultacao acontece na propria consulta, nao so'
 * filtrada depois na view (achado corrigido na revisao desta fase: a
 * versao anterior descrevia a ocultacao como incondicional, o que fazia
 * "sigilo_cego desligado" nao mudar nada de verdade).
 */
class TrabalhoRepository
{
    public function criar(array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO trabalhos
                (evento_id, eixo_tematico_id, natureza_id, titulo, telefone_contato, metodo_submissao,
                 conteudo_html, link_avaliacao, link_publicacao, arquivo_avaliacao_path, arquivo_publicacao_path)
             VALUES
                (:evento_id, :eixo_tematico_id, :natureza_id, :titulo, :telefone_contato, :metodo_submissao,
                 :conteudo_html, :link_avaliacao, :link_publicacao, :arquivo_avaliacao_path, :arquivo_publicacao_path)'
        );
        $stmt->execute([
            'evento_id' => $dados['evento_id'],
            'eixo_tematico_id' => isset($dados['eixo_tematico_id']) ? $dados['eixo_tematico_id'] : null,
            'natureza_id' => isset($dados['natureza_id']) ? $dados['natureza_id'] : null,
            'titulo' => $dados['titulo'],
            'telefone_contato' => isset($dados['telefone_contato']) ? $dados['telefone_contato'] : null,
            'metodo_submissao' => $dados['metodo_submissao'],
            'conteudo_html' => isset($dados['conteudo_html']) ? $dados['conteudo_html'] : null,
            'link_avaliacao' => isset($dados['link_avaliacao']) ? $dados['link_avaliacao'] : null,
            'link_publicacao' => isset($dados['link_publicacao']) ? $dados['link_publicacao'] : null,
            'arquivo_avaliacao_path' => isset($dados['arquivo_avaliacao_path']) ? $dados['arquivo_avaliacao_path'] : null,
            'arquivo_publicacao_path' => isset($dados['arquivo_publicacao_path']) ? $dados['arquivo_publicacao_path'] : null,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'trabalhos', $id, null, ['evento_id' => $dados['evento_id'], 'titulo' => $dados['titulo']]);

        return $id;
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalhos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $trabalho = $stmt->fetch();

        return $trabalho !== false ? $trabalho : null;
    }

    /**
     * Visao administrativa (Admin sempre ve tudo, sem sigilo): inclui nome
     * do autor principal, eixo, natureza.
     */
    public function buscarComDetalhes($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT t.*, ev.nome AS evento_nome, e.nome AS eixo_nome, n.nome AS natureza_nome,
                    a.nome AS autor_principal_nome, a.cpf AS autor_principal_cpf,
                    a.email AS autor_principal_email, a.usuario_id AS autor_principal_usuario_id
             FROM trabalhos t
             INNER JOIN eventos ev ON ev.id = t.evento_id
             LEFT JOIN trabalho_eixos_tematicos e ON e.id = t.eixo_tematico_id
             LEFT JOIN trabalho_naturezas n ON n.id = t.natureza_id
             LEFT JOIN trabalho_autores a ON a.trabalho_id = t.id AND a.eh_autor_principal = 1
             WHERE t.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $trabalho = $stmt->fetch();

        return $trabalho !== false ? $trabalho : null;
    }

    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT t.*, e.nome AS eixo_nome, n.nome AS natureza_nome, a.nome AS autor_principal_nome
             FROM trabalhos t
             LEFT JOIN trabalho_eixos_tematicos e ON e.id = t.eixo_tematico_id
             LEFT JOIN trabalho_naturezas n ON n.id = t.natureza_id
             LEFT JOIN trabalho_autores a ON a.trabalho_id = t.id AND a.eh_autor_principal = 1
             WHERE t.evento_id = :evento_id
             ORDER BY t.submetido_em DESC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE trabalhos SET
                eixo_tematico_id = :eixo_tematico_id, natureza_id = :natureza_id, titulo = :titulo,
                telefone_contato = :telefone_contato, metodo_submissao = :metodo_submissao,
                conteudo_html = :conteudo_html, link_avaliacao = :link_avaliacao, link_publicacao = :link_publicacao,
                arquivo_avaliacao_path = :arquivo_avaliacao_path, arquivo_publicacao_path = :arquivo_publicacao_path
             WHERE id = :id'
        );
        $stmt->execute([
            'eixo_tematico_id' => isset($dados['eixo_tematico_id']) ? $dados['eixo_tematico_id'] : null,
            'natureza_id' => isset($dados['natureza_id']) ? $dados['natureza_id'] : null,
            'titulo' => $dados['titulo'],
            'telefone_contato' => isset($dados['telefone_contato']) ? $dados['telefone_contato'] : null,
            'metodo_submissao' => $dados['metodo_submissao'],
            'conteudo_html' => isset($dados['conteudo_html']) ? $dados['conteudo_html'] : null,
            'link_avaliacao' => isset($dados['link_avaliacao']) ? $dados['link_avaliacao'] : null,
            'link_publicacao' => isset($dados['link_publicacao']) ? $dados['link_publicacao'] : null,
            'arquivo_avaliacao_path' => isset($dados['arquivo_avaliacao_path']) ? $dados['arquivo_avaliacao_path'] : null,
            'arquivo_publicacao_path' => isset($dados['arquivo_publicacao_path']) ? $dados['arquivo_publicacao_path'] : null,
            'id' => $id,
        ]);

        Auditoria::registrar('atualizar', 'trabalhos', $id, $antes, ['titulo' => $dados['titulo']]);
    }

    public function desclassificar($id, $motivo)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "UPDATE trabalhos SET status = 'desclassificado', motivo_desclassificacao = :motivo WHERE id = :id"
        );
        $stmt->execute(['motivo' => $motivo, 'id' => $id]);

        Auditoria::registrar('desclassificar', 'trabalhos', $id, $antes, ['motivo_desclassificacao' => $motivo]);
    }

    public function atualizarStatus($id, $status)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE trabalhos SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Passo 2 do fluxo de submissao por arquivo: o registro e' criado
     * primeiro (sem path, dentro da transacao) so' para existir um
     * trabalho_id, o arquivo e' salvo fisicamente usando esse id como
     * subpasta, e so' entao os caminhos sao gravados aqui - tudo dentro da
     * mesma transacao de TrabalhoSubmissaoService::submeter().
     */
    public function atualizarArquivos($id, $caminhoAvaliacao, $caminhoPublicacao)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE trabalhos SET arquivo_avaliacao_path = :arquivo_avaliacao_path, arquivo_publicacao_path = :arquivo_publicacao_path WHERE id = :id'
        );
        $stmt->execute([
            'arquivo_avaliacao_path' => $caminhoAvaliacao,
            'arquivo_publicacao_path' => $caminhoPublicacao,
            'id' => $id,
        ]);
    }

    public function marcarSelecionado($id, $selecionado)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE trabalhos SET selecionado = :selecionado WHERE id = :id');
        $stmt->execute(['selecionado' => $selecionado ? 1 : 0, 'id' => $id]);
    }

    /**
     * Atribuicao tardia (sob demanda), so' para quem ainda esta NULL -
     * mesmo padrao lazy de SubmissaoRepository::garantirNumerosSigilo() do
     * Concurso, disparado na primeira vez que TrabalhoAvaliacaoController
     * lista trabalhos daquele evento para um avaliador. So' e' chamado
     * quando evento_trabalhos_config.sigilo_cego esta ligado.
     */
    public function garantirNumerosSigilo($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT id FROM trabalhos WHERE evento_id = :evento_id AND numero_sigilo IS NULL');
        $stmt->execute(['evento_id' => $eventoId]);
        $idsSemNumero = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($idsSemNumero)) {
            return;
        }

        $stmtMaximo = $pdo->prepare('SELECT COALESCE(MAX(numero_sigilo), 0) FROM trabalhos WHERE evento_id = :evento_id');
        $stmtMaximo->execute(['evento_id' => $eventoId]);
        $proximoNumero = (int) $stmtMaximo->fetchColumn();

        shuffle($idsSemNumero);

        $upd = $pdo->prepare('UPDATE trabalhos SET numero_sigilo = :numero WHERE id = :id');
        foreach ($idsSemNumero as $trabalhoId) {
            $proximoNumero++;
            $upd->execute(['numero' => $proximoNumero, 'id' => $trabalhoId]);
        }
    }

    /**
     * Um unico trabalho, na visao do avaliador. $sigiloCego = true: nunca
     * traz trabalho_autores nem arquivo/link de publicacao, so' numero_sigilo.
     * $sigiloCego = false: traz autor principal normalmente, sem numero_sigilo.
     */
    public function buscarParaAvaliador($id, $sigiloCego)
    {
        $pdo = Database::conexao();

        if ($sigiloCego) {
            $stmt = $pdo->prepare(
                'SELECT t.id, t.evento_id, t.eixo_tematico_id, t.natureza_id, t.titulo, t.metodo_submissao,
                        t.conteudo_html, t.link_avaliacao, t.arquivo_avaliacao_path, t.numero_sigilo, t.status,
                        ev.nome AS evento_nome, e.nome AS eixo_nome, n.nome AS natureza_nome
                 FROM trabalhos t
                 INNER JOIN eventos ev ON ev.id = t.evento_id
                 LEFT JOIN trabalho_eixos_tematicos e ON e.id = t.eixo_tematico_id
                 LEFT JOIN trabalho_naturezas n ON n.id = t.natureza_id
                 WHERE t.id = :id
                 LIMIT 1'
            );
        } else {
            $stmt = $pdo->prepare(
                'SELECT t.id, t.evento_id, t.eixo_tematico_id, t.natureza_id, t.titulo, t.metodo_submissao,
                        t.conteudo_html, t.link_avaliacao, t.arquivo_avaliacao_path, t.status,
                        ev.nome AS evento_nome, e.nome AS eixo_nome, n.nome AS natureza_nome,
                        a.nome AS autor_principal_nome
                 FROM trabalhos t
                 INNER JOIN eventos ev ON ev.id = t.evento_id
                 LEFT JOIN trabalho_eixos_tematicos e ON e.id = t.eixo_tematico_id
                 LEFT JOIN trabalho_naturezas n ON n.id = t.natureza_id
                 LEFT JOIN trabalho_autores a ON a.trabalho_id = t.id AND a.eh_autor_principal = 1
                 WHERE t.id = :id
                 LIMIT 1'
            );
        }

        $stmt->execute(['id' => $id]);
        $trabalho = $stmt->fetch();

        return $trabalho !== false ? $trabalho : null;
    }

    /**
     * Todos os trabalhos de um evento, para calculo de resultado/ranking
     * (visao administrativa, sempre com autoria completa).
     */
    public function listarAprovaveis($eventoId)
    {
        return $this->listarPorEvento($eventoId);
    }
}
