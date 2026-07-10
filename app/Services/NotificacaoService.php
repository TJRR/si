<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Mailer;
use App\Repositories\NotificacaoRepository;

class NotificacaoService
{
    private $notificacoes;

    public function __construct()
    {
        $this->notificacoes = new NotificacaoRepository();
    }

    public function confirmarSubmissao($destinatarioEmail, array $trilha, array $etapa, $submissaoId)
    {
        $assunto = 'Confirmação de submissão - ' . $trilha['nome'];
        $corpo = $this->montarCorpoConfirmacao($trilha, $etapa, $submissaoId);

        $id = $this->notificacoes->criar(
            'submissao_confirmada',
            'confirmacao_submissao',
            $destinatarioEmail,
            $assunto,
            $corpo
        );

        try {
            $resultado = Mailer::enviar($destinatarioEmail, $assunto, $corpo);
        } catch (\Exception $e) {
            $resultado = ['sucesso' => false, 'erro' => $e->getMessage()];
        }

        if ($resultado['sucesso']) {
            $this->notificacoes->marcarEnviada($id);
        } else {
            $this->notificacoes->marcarFalhou($id);
        }
    }

    private function montarCorpoConfirmacao(array $trilha, array $etapa, $submissaoId)
    {
        return sprintf(
            '<p>Olá,</p>'
            . '<p>Recebemos sua submissão para a <strong>%s</strong>, etapa <strong>%s</strong>, '
            . 'em %s.</p>'
            . '<p>Número de protocolo: <strong>%d</strong>.</p>'
            . '<p>Nenhuma ação adicional é necessária neste momento.</p>'
            . '<p>Prêmio de Inovação TJRR</p>',
            htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'),
            date('d/m/Y H:i'),
            $submissaoId
        );
    }

    public function acessoLiberado($destinatarioEmail, $nomeParticipante, $nomeEquipe, $linkDefinirSenha)
    {
        $assunto = 'Inscrição homologada — acesso liberado ao sistema';
        $corpo = $this->montarCorpoAcessoLiberado($nomeParticipante, $nomeEquipe, $linkDefinirSenha);

        $id = $this->notificacoes->criar(
            'inscricao_homologada',
            'acesso_liberado',
            $destinatarioEmail,
            $assunto,
            $corpo
        );

        try {
            $resultado = Mailer::enviar($destinatarioEmail, $assunto, $corpo);
        } catch (\Exception $e) {
            $resultado = ['sucesso' => false, 'erro' => $e->getMessage()];
        }

        if ($resultado['sucesso']) {
            $this->notificacoes->marcarEnviada($id);
        } else {
            $this->notificacoes->marcarFalhou($id);
        }
    }

    private function montarCorpoAcessoLiberado($nomeParticipante, $nomeEquipe, $linkDefinirSenha)
    {
        return sprintf(
            '<p>Olá, %s,</p>'
            . '<p>A inscrição da equipe <strong>%s</strong> foi homologada.</p>'
            . '<p>Você já pode acessar o sistema de duas formas:</p>'
            . '<ul>'
            . '<li>Clicando em <a href="%s">Definir minha senha</a> e entrando com e-mail e senha; ou</li>'
            . '<li>Usando o botão "Entrar com Google" com este mesmo e-mail.</li>'
            . '</ul>'
            . '<p>Prêmio de Inovação TJRR</p>',
            htmlspecialchars($nomeParticipante, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($nomeEquipe, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($linkDefinirSenha, ENT_QUOTES, 'UTF-8')
        );
    }

    public function conviteAdministrativo($destinatarioEmail, $nomeUsuario, $linkDefinirSenha)
    {
        $assunto = 'Você foi convidado para o Sistema do Prêmio de Inovação TJRR';
        $corpo = $this->montarCorpoConviteAdministrativo($nomeUsuario, $linkDefinirSenha);

        $id = $this->notificacoes->criar(
            'convite_administrativo',
            'convite_administrativo',
            $destinatarioEmail,
            $assunto,
            $corpo
        );

        try {
            $resultado = Mailer::enviar($destinatarioEmail, $assunto, $corpo);
        } catch (\Exception $e) {
            $resultado = ['sucesso' => false, 'erro' => $e->getMessage()];
        }

        if ($resultado['sucesso']) {
            $this->notificacoes->marcarEnviada($id);
        } else {
            $this->notificacoes->marcarFalhou($id);
        }
    }

    private function montarCorpoConviteAdministrativo($nomeUsuario, $linkDefinirSenha)
    {
        return sprintf(
            '<p>Olá, %s,</p>'
            . '<p>Você foi convidado(a) pelo Administrador a acessar o Sistema do Prêmio de Inovação TJRR.</p>'
            . '<p>Você já pode acessar o sistema de duas formas:</p>'
            . '<ul>'
            . '<li>Clicando em <a href="%s">Definir minha senha</a> e entrando com e-mail e senha; ou</li>'
            . '<li>Usando o botão "Entrar com Google" com este mesmo e-mail.</li>'
            . '</ul>'
            . '<p>Prêmio de Inovação TJRR</p>',
            htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($linkDefinirSenha, ENT_QUOTES, 'UTF-8')
        );
    }
}
