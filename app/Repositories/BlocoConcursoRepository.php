<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 38 (#247): bloco de conteudo rico opcional por concurso (Classificacao
 * Geral etc.), exibido na pagina publica da edicao encerrada. No maximo 1
 * linha por concurso (UNIQUE em concurso_id) - mesmo padrao de upsert do
 * ContatoConcursoRepository::salvar(), mas filtrado por concurso_id.
 */
class BlocoConcursoRepository
{
    public function buscarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM blocos_concurso WHERE concurso_id = :concurso_id LIMIT 1');
        $stmt->execute(['concurso_id' => $concursoId]);
        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function salvar($concursoId, array $dados)
    {
        $antes = $this->buscarPorConcurso($concursoId);
        $pdo = Database::conexao();

        $parametros = [
            'concurso_id' => $concursoId,
            'titulo' => $dados['titulo'],
            'conteudo_html' => $dados['conteudo_html'],
            'ativo' => $dados['ativo'],
        ];

        if ($antes === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO blocos_concurso (concurso_id, titulo, conteudo_html, ativo)
                 VALUES (:concurso_id, :titulo, :conteudo_html, :ativo)'
            );
            $stmt->execute($parametros);
            $id = (int) $pdo->lastInsertId();
        } else {
            $id = (int) $antes['id'];
            $stmt = $pdo->prepare(
                'UPDATE blocos_concurso SET titulo = :titulo, conteudo_html = :conteudo_html, ativo = :ativo
                 WHERE id = :id'
            );
            $stmt->execute(['titulo' => $dados['titulo'], 'conteudo_html' => $dados['conteudo_html'], 'ativo' => $dados['ativo'], 'id' => $id]);
        }

        Auditoria::registrar('salvar', 'blocos_concurso', $id, $antes, $dados);
    }
}
