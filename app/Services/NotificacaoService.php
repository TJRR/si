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
        $abertura = 'A inscrição da equipe <strong>' . htmlspecialchars($nomeEquipe, ENT_QUOTES, 'UTF-8') . '</strong> foi homologada.';
        $corpo = $this->montarCorpoAcesso($nomeParticipante, $abertura, $linkDefinirSenha);

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

    public function conviteAdministrativo($destinatarioEmail, $nomeUsuario, $linkDefinirSenha)
    {
        $assunto = 'Seu acesso ao Sistema do Prêmio de Inovação TJRR está liberado';
        $abertura = 'Seu acesso ao Sistema do Prêmio de Inovação do Tribunal de Justiça do Estado de Roraima está liberado.';
        $corpo = $this->montarCorpoAcesso($nomeUsuario, $abertura, $linkDefinirSenha);

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

    /**
     * Corpo compartilhado dos e-mails de "acesso liberado" (homologacao e
     * convite administrativo) — so muda a frase de abertura ($abertura), o
     * resto (destaque pro login Google, link de definir senha, aviso de
     * seguranca, contato) e identico nos dois fluxos.
     */
    private function montarCorpoAcesso($nomeDestinatario, $abertura, $linkDefinirSenha)
    {
        $linkGoogle = urlAbsoluta('auth/google');

        return sprintf(
            '<p>Olá, %s,</p>'
            . '<p>%s</p>'
            . '<p>Você já pode acessar o sistema de duas formas:</p>'
            . '<ul>'
            . '<li>🔵 Se este endereço de e-mail for de uma conta Google, clique em '
            . '<a href="%s">Entrar com Google</a>; ou</li>'
            . '<li>🔑 Clicando em <a href="%s">Definir minha senha</a> e entrando com este e-mail '
            . 'e uma senha que você deverá definir.</li>'
            . '</ul>'
            . '<p style="color:#555;font-size:0.9em;">Este e-mail foi enviado automaticamente. Não compartilhe sua senha '
            . 'com terceiros. Em caso de dúvida sobre a autenticidade deste e-mail, entre em contato pelos canais abaixo.</p>'
            . '<p>Atenciosamente,</p>'
            . '<p><strong>Organização do Prêmio de Inovação - TJRR</strong><br>'
            . '✉️ E-mail: npi@tjrr.jus.br<br>'
            . '💬 Fone: <a href="https://wa.me/5595931984194">(95) 3198-4194</a></p>',
            htmlspecialchars($nomeDestinatario, ENT_QUOTES, 'UTF-8'),
            $abertura,
            htmlspecialchars($linkGoogle, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($linkDefinirSenha, ENT_QUOTES, 'UTF-8')
        );
    }
}
