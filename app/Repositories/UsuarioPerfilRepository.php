<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 48 (correcao pos-teste de fumaca): documento/cargo/categoria
 * profissional/orgao de origem/minicurriculo da PESSOA - 1:1 com usuarios,
 * reaproveitavel por qualquer contexto (hoje so' o cadastro de Facilitador
 * usa isso, ver AtividadeAdminController::vincularFacilitador()). Foto
 * continua em usuarios.foto_path (UsuarioRepository::atualizarFoto()),
 * nunca duplicada aqui.
 */
class UsuarioPerfilRepository
{
    /**
     * Fase 48 (correcao pos-teste de fumaca): lista fechada usada tanto na
     * tela "Meu Perfil" (o proprio usuario edita) quanto na tela de
     * vincular Facilitador (Admin edita quando ainda nao estiver
     * preenchido) - so' um lugar de verdade para essa lista.
     */
    const CATEGORIAS_PROFISSIONAIS = [
        'Magistrado', 'Promotor', 'Defensor', 'Advogado', 'Servidor do Judiciário',
        'Servidor Público', 'Terceirizado', 'Empresário', 'Estagiário', 'Estudante',
    ];

    const TIPOS_DOCUMENTO = ['CPF', 'RG', 'RNE', 'Passaporte'];

    public function buscarPorUsuarioId($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios_perfil WHERE usuario_id = :usuario_id LIMIT 1');
        $stmt->execute(['usuario_id' => $usuarioId]);

        $perfil = $stmt->fetch();

        return $perfil !== false ? $perfil : null;
    }

    /**
     * Upsert: quem chama (hoje so' vincularFacilitador()) sempre manda o
     * conjunto completo de campos - um segundo cadastro (outra atividade,
     * mesma pessoa) atualiza o mesmo registro, nunca cria um segundo.
     */
    public function salvar($usuarioId, array $dados)
    {
        $antes = $this->buscarPorUsuarioId($usuarioId);
        $campos = [
            'usuario_id' => $usuarioId,
            'documento' => $dados['documento'] !== '' ? $dados['documento'] : null,
            'tipo_documento' => $dados['tipo_documento'],
            'cargo' => $dados['cargo'] !== '' ? $dados['cargo'] : null,
            'categoria_profissional' => $dados['categoria_profissional'] !== '' ? $dados['categoria_profissional'] : null,
            'orgao_origem' => $dados['orgao_origem'] !== '' ? $dados['orgao_origem'] : null,
            'minicurriculo' => $dados['minicurriculo'] !== '' ? $dados['minicurriculo'] : null,
        ];

        $pdo = Database::conexao();

        if ($antes !== null) {
            $stmt = $pdo->prepare(
                'UPDATE usuarios_perfil
                 SET documento = :documento, tipo_documento = :tipo_documento, cargo = :cargo,
                     categoria_profissional = :categoria_profissional, orgao_origem = :orgao_origem,
                     minicurriculo = :minicurriculo
                 WHERE usuario_id = :usuario_id'
            );
            $stmt->execute($campos);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios_perfil
                    (usuario_id, documento, tipo_documento, cargo, categoria_profissional, orgao_origem, minicurriculo)
                 VALUES
                    (:usuario_id, :documento, :tipo_documento, :cargo, :categoria_profissional, :orgao_origem, :minicurriculo)'
            );
            $stmt->execute($campos);
        }

        Auditoria::registrar('salvar', 'usuarios_perfil', $usuarioId, $antes, $campos);
    }

    /**
     * Fase 49B: upsert PARCIAL - so' sobrescreve os campos passados em
     * $camposParciais, preservando os demais que ja existirem (diferente
     * de salvar(), que sempre espera o conjunto completo). Usado por todo
     * ponto do sistema que captura só um pedaço do perfil da pessoa
     * (documento na inscrição do evento, CPF na submissão de Trabalho),
     * para nunca apagar dado que outro fluxo já tenha preenchido -
     * exatamente o problema que motivou esta correção (ver
     * feedback_investigacao_duplicacao_precisa_ser_no_codigo, memória do
     * projeto).
     */
    public function atualizarParcial($usuarioId, array $camposParciais)
    {
        $atual = $this->buscarPorUsuarioId($usuarioId);
        $base = [
            'documento' => $atual !== null ? (string) $atual['documento'] : '',
            'tipo_documento' => $atual !== null && $atual['tipo_documento'] !== null ? $atual['tipo_documento'] : 'CPF',
            'cargo' => $atual !== null ? (string) $atual['cargo'] : '',
            'categoria_profissional' => $atual !== null ? (string) $atual['categoria_profissional'] : '',
            'orgao_origem' => $atual !== null ? (string) $atual['orgao_origem'] : '',
            'minicurriculo' => $atual !== null ? (string) $atual['minicurriculo'] : '',
        ];

        $this->salvar($usuarioId, array_merge($base, $camposParciais));
    }
}
