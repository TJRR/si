<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.12/4.9) - contato/canais do site. Fase 19 (#84 v2): deixou de
 * ser escopado por concurso - vira singleton global, mesmo padrao de
 * configuracoes_sistema/configuracoes_visuais (so' existe 0 ou 1 linha).
 * redes_sociais e' um mapa rede->link (ex.: {"instagram": "https://...",
 * "facebook": "..."}), guardado como JSON.
 */
class ContatoConcursoRepository
{
    public const REDES_SUPORTADAS = ['instagram', 'facebook', 'youtube', 'linkedin', 'x'];

    public function buscar()
    {
        $pdo = Database::conexao();
        $linha = $pdo->query('SELECT * FROM contatos_concurso LIMIT 1')->fetch();

        if ($linha === false) {
            return null;
        }

        $linha['redes_sociais'] = $linha['redes_sociais'] !== null ? json_decode($linha['redes_sociais'], true) : [];

        return $linha;
    }

    /**
     * Sem UNIQUE key pra ON DUPLICATE KEY UPDATE (a linha e' um singleton
     * por convencao de app, nao por constraint) - upsert explicito: UPDATE
     * se ja existir linha, INSERT senao.
     */
    public function salvar(array $dados)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();

        $parametros = [
            'email' => $dados['email'],
            'telefone' => $dados['telefone'],
            'whatsapp' => $dados['whatsapp'],
            'endereco' => $dados['endereco'],
            'texto_institucional' => $dados['texto_institucional'],
            'redes_sociais' => json_encode($dados['redes_sociais']),
            'formulario_contato_ativo' => $dados['formulario_contato_ativo'],
        ];

        if ($antes === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO contatos_concurso (email, telefone, whatsapp, endereco, texto_institucional, redes_sociais, formulario_contato_ativo)
                 VALUES (:email, :telefone, :whatsapp, :endereco, :texto_institucional, :redes_sociais, :formulario_contato_ativo)'
            );
            $stmt->execute($parametros);
            $id = (int) $pdo->lastInsertId();
        } else {
            $id = (int) $antes['id'];
            $stmt = $pdo->prepare(
                'UPDATE contatos_concurso SET
                    email = :email, telefone = :telefone, whatsapp = :whatsapp, endereco = :endereco,
                    texto_institucional = :texto_institucional, redes_sociais = :redes_sociais,
                    formulario_contato_ativo = :formulario_contato_ativo
                 WHERE id = :id'
            );
            $stmt->execute($parametros + ['id' => $id]);
        }

        Auditoria::registrar('salvar', 'contatos_concurso', $id, $antes, $dados);
    }
}
