<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.12/4.9) - contato/canais por edicao. redes_sociais e' um mapa
 * rede->link (ex.: {"instagram": "https://...", "facebook": "..."}),
 * guardado como JSON. Upsert (UNIQUE concurso_id) - nao existe "criar" vs
 * "atualizar" do ponto de vista do admin, so' "salvar".
 */
class ContatoConcursoRepository
{
    public const REDES_SUPORTADAS = ['instagram', 'facebook', 'youtube', 'linkedin', 'x'];

    public function buscarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM contatos_concurso WHERE concurso_id = :concurso_id LIMIT 1');
        $stmt->execute(['concurso_id' => $concursoId]);

        $linha = $stmt->fetch();

        if ($linha === false) {
            return null;
        }

        $linha['redes_sociais'] = $linha['redes_sociais'] !== null ? json_decode($linha['redes_sociais'], true) : [];

        return $linha;
    }

    public function salvar($concursoId, array $dados)
    {
        $antes = $this->buscarPorConcurso($concursoId);
        $pdo = Database::conexao();

        $parametros = [
            'concurso_id' => $concursoId,
            'email' => $dados['email'],
            'telefone' => $dados['telefone'],
            'whatsapp' => $dados['whatsapp'],
            'endereco' => $dados['endereco'],
            'redes_sociais' => json_encode($dados['redes_sociais']),
            'formulario_contato_ativo' => $dados['formulario_contato_ativo'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO contatos_concurso (concurso_id, email, telefone, whatsapp, endereco, redes_sociais, formulario_contato_ativo)
             VALUES (:concurso_id, :email, :telefone, :whatsapp, :endereco, :redes_sociais, :formulario_contato_ativo)
             ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                telefone = VALUES(telefone),
                whatsapp = VALUES(whatsapp),
                endereco = VALUES(endereco),
                redes_sociais = VALUES(redes_sociais),
                formulario_contato_ativo = VALUES(formulario_contato_ativo)'
        );
        $stmt->execute($parametros);

        Auditoria::registrar('salvar', 'contatos_concurso', (int) $concursoId, $antes, $dados);
    }
}
