<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 40: bloco de divulgacao do Evento na home publica (sub-aba "Divulgacao
 * na home"). No maximo 1 linha por evento (UNIQUE em evento_id) - mesmo
 * padrao de upsert de BlocoConcursoRepository::salvar(), mas para
 * evento_divulgacao. Tabela satelite propria (nao reaproveita
 * blocos_conteudo/home_secoes_ordem) - ver migration 124 para o porque.
 */
class EventoDivulgacaoRepository
{
    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_divulgacao WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);
        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function salvar($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $pdo = Database::conexao();

        $parametros = [
            'evento_id' => $eventoId,
            'titulo' => $dados['titulo'],
            'conteudo_html' => $dados['conteudo_html'],
            'imagem_path' => $dados['imagem_path'],
            'imagem_alt' => $dados['imagem_alt'],
            'imagem_posicao' => $dados['imagem_posicao'],
            'cta_titulo' => $dados['cta_titulo'],
            'cta_alinhamento' => $dados['cta_alinhamento'],
            'ativo' => $dados['ativo'],
        ];

        if ($antes === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_divulgacao (evento_id, titulo, conteudo_html, imagem_path, imagem_alt, imagem_posicao, cta_titulo, cta_alinhamento, ativo)
                 VALUES (:evento_id, :titulo, :conteudo_html, :imagem_path, :imagem_alt, :imagem_posicao, :cta_titulo, :cta_alinhamento, :ativo)'
            );
            $stmt->execute($parametros);
            $id = (int) $pdo->lastInsertId();
        } else {
            $id = (int) $antes['id'];
            $stmt = $pdo->prepare(
                'UPDATE evento_divulgacao SET titulo = :titulo, conteudo_html = :conteudo_html, imagem_path = :imagem_path,
                    imagem_alt = :imagem_alt, imagem_posicao = :imagem_posicao, cta_titulo = :cta_titulo,
                    cta_alinhamento = :cta_alinhamento, ativo = :ativo
                 WHERE id = :id'
            );
            $stmt->execute([
                'titulo' => $dados['titulo'],
                'conteudo_html' => $dados['conteudo_html'],
                'imagem_path' => $dados['imagem_path'],
                'imagem_alt' => $dados['imagem_alt'],
                'imagem_posicao' => $dados['imagem_posicao'],
                'cta_titulo' => $dados['cta_titulo'],
                'cta_alinhamento' => $dados['cta_alinhamento'],
                'ativo' => $dados['ativo'],
                'id' => $id,
            ]);
        }

        Auditoria::registrar('salvar', 'evento_divulgacao', $id, $antes, $dados);

        return $id;
    }

    /**
     * Fase 40: alimenta app/Views/home/_bloco_evento.php - todos os eventos
     * com divulgacao ativa e titulo preenchido, mais recente primeiro (mesma
     * ordenacao de SemanaInovacaoRepository::listar()). Empilha quantos
     * eventos estiverem divulgados ao mesmo tempo.
     */
    public function listarAtivosParaHome()
    {
        $pdo = Database::conexao();
        $stmt = $pdo->query(
            'SELECT ed.*
             FROM evento_divulgacao ed
             INNER JOIN eventos e ON e.id = ed.evento_id
             WHERE ed.ativo = 1 AND ed.titulo IS NOT NULL AND ed.titulo <> \'\'
             ORDER BY e.data_inicio DESC, e.id DESC'
        );

        return $stmt->fetchAll();
    }
}
