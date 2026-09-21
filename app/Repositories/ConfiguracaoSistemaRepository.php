<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class ConfiguracaoSistemaRepository
{
    public function buscar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM configuracoes_sistema WHERE id = 1')->fetch();
    }

    public function atualizarSessaoTimeoutMinutos($minutos)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_sistema SET sessao_timeout_minutos = :minutos WHERE id = 1');
        $stmt->execute(['minutos' => $minutos]);

        Auditoria::registrar('atualizar', 'configuracoes_sistema', 1, $antes, ['sessao_timeout_minutos' => $minutos]);
    }

    public function desativarSistema()
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $pdo->exec('UPDATE configuracoes_sistema SET sistema_desativado = 1 WHERE id = 1');

        Auditoria::registrar('desativar_sistema', 'configuracoes_sistema', 1, $antes, ['sistema_desativado' => 1]);
    }

    public function reativarSistema()
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $pdo->exec('UPDATE configuracoes_sistema SET sistema_desativado = 0 WHERE id = 1');

        Auditoria::registrar('reativar_sistema', 'configuracoes_sistema', 1, $antes, ['sistema_desativado' => 0]);
    }

    /**
     * Fase 41: nome do aplicativo web instalavel (PWA) do Evento - campos
     * 'name'/'short_name' do manifesto (EventoAppController::manifesto()).
     */
    public function atualizarNomeApp($nome, $nomeCurto)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_sistema SET nome_app = :nome_app, nome_app_curto = :nome_app_curto WHERE id = 1');
        $stmt->execute(['nome_app' => $nome, 'nome_app_curto' => $nomeCurto]);

        Auditoria::registrar('atualizar_nome_app', 'configuracoes_sistema', 1, $antes, ['nome_app' => $nome, 'nome_app_curto' => $nomeCurto]);
    }

    /**
     * Nome da instituição e da unidade responsável, usados em telas
     * publicas, e-mails e titulos no lugar de um nome fixo no codigo (ver
     * helpers nomeInstituicao()/nomeUnidadeResponsavel()).
     */
    public function atualizarIdentidadeInstitucional($instituicao, $instituicaoNomeCompleto, $unidadeResponsavel, $unidadeResponsavelNomeCompleto)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE configuracoes_sistema SET
                instituicao = :instituicao,
                instituicao_nome_completo = :instituicao_nome_completo,
                unidade_responsavel = :unidade_responsavel,
                unidade_responsavel_nome_completo = :unidade_responsavel_nome_completo
             WHERE id = 1'
        );
        $valores = [
            'instituicao' => $instituicao,
            'instituicao_nome_completo' => $instituicaoNomeCompleto,
            'unidade_responsavel' => $unidadeResponsavel,
            'unidade_responsavel_nome_completo' => $unidadeResponsavelNomeCompleto,
        ];
        $stmt->execute($valores);

        Auditoria::registrar('atualizar_identidade_institucional', 'configuracoes_sistema', 1, $antes, $valores);
    }

    /**
     * Fase 41: so' marca o momento do upload (cache-buster do manifesto/ícones
     * do PWA) - os arquivos em si tem nome fixo, ver ImagemService::salvarIconeApp().
     */
    public function marcarIconeAppAtualizado()
    {
        $antes = $this->buscar();
        $agora = date('Y-m-d H:i:s');
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_sistema SET icone_app_atualizado_em = :icone_app_atualizado_em WHERE id = 1');
        $stmt->execute(['icone_app_atualizado_em' => $agora]);

        Auditoria::registrar('atualizar_icone_app', 'configuracoes_sistema', 1, $antes, ['icone_app_atualizado_em' => $agora]);
    }
}
