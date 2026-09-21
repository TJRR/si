<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Mailer;
use App\Repositories\ContatoConcursoRepository;
use App\Repositories\NotificacaoRepository;

class NotificacaoService
{
    private $notificacoes;
    private $contatos;

    public function __construct()
    {
        $this->notificacoes = new NotificacaoRepository();
        $this->contatos = new ContatoConcursoRepository();
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

    /**
     * Fase 39 (correcao pos-teste #2): confirmacao de inscricao num Evento
     * (Semana de Inovacao) - $evento['mensagem_confirmacao_inscricao'] e'
     * texto rico configurado pelo Admin em Dados Gerais; vazio usa um
     * texto padrao generico.
     */
    public function confirmarInscricaoEvento($destinatarioEmail, $nomeParticipante, array $evento)
    {
        $assunto = 'Inscrição confirmada: ' . $evento['nome'];
        $corpo = $this->montarCorpoConfirmacaoEvento($nomeParticipante, $evento);

        $id = $this->notificacoes->criar(
            'evento_inscricao_confirmada',
            'confirmacao_inscricao_evento',
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
     * Fase 44: aviso individual generico de Evento (ex.: lembrete de uma
     * atividade especifica, a partir da Fase 46/47, quando a entidade
     * Atividade existir) - assunto/corpo sao montados por quem chama, sem
     * template fixo. Sem nenhum gatilho real ainda; decisao de quando/como
     * chamar fica para a fase que introduzir Atividade.
     */
    public function avisoIndividualEvento($destinatarioEmail, array $evento, $assunto, $corpoHtml)
    {
        $id = $this->notificacoes->criar(
            'evento_aviso_individual',
            'aviso_individual_evento',
            $destinatarioEmail,
            $assunto,
            $corpoHtml
        );

        try {
            $resultado = Mailer::enviar($destinatarioEmail, $assunto, $corpoHtml . $this->assinaturaContato());
        } catch (\Exception $e) {
            $resultado = ['sucesso' => false, 'erro' => $e->getMessage()];
        }

        if ($resultado['sucesso']) {
            $this->notificacoes->marcarEnviada($id);
        } else {
            $this->notificacoes->marcarFalhou($id);
        }

        return $resultado['sucesso'];
    }

    private function montarCorpoConfirmacaoEvento($nomeDestinatario, array $evento)
    {
        $mensagem = !empty($evento['mensagem_confirmacao_inscricao'])
            ? $evento['mensagem_confirmacao_inscricao']
            : '<p>Sua inscrição foi recebida com sucesso. Acompanhe as novidades do evento pelos canais oficiais.</p>';

        return sprintf(
            '<p>Olá, %s,</p>'
            . '<p>Sua inscrição em <strong>%s</strong> (%s a %s) foi confirmada.</p>'
            . '%s',
            htmlspecialchars($nomeDestinatario, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'),
            formatarData($evento['data_inicio']),
            formatarData($evento['data_fim']),
            $mensagem
        ) . $this->assinaturaContato();
    }

    private function montarCorpoConfirmacao(array $trilha, array $etapa, $submissaoId)
    {
        return sprintf(
            '<p>Olá,</p>'
            . '<p>Recebemos sua submissão para a <strong>%s</strong>, etapa <strong>%s</strong>, '
            . 'em %s.</p>'
            . '<p>Número de protocolo: <strong>%d</strong>.</p>'
            . '<p>Nenhuma ação adicional é necessária neste momento.</p>'
            . '<p>Prêmio de Inovação ' . htmlspecialchars(nomeInstituicao(), ENT_QUOTES, 'UTF-8') . '</p>',
            htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'),
            date('d/m/Y H:i'),
            $submissaoId
        );
    }

    /**
     * Fase 36 (Parte D): $contextoAnterior (HTML ja escapado, opcional) e'
     * concatenado a abertura quando o participante ja tinha sido
     * homologado/rejeitado antes (vinculo pendente-pos-correcao) - montado
     * por quem chama (HomologacaoController::homologarUmVinculo()), que e'
     * quem conhece o historico.
     */
    public function acessoLiberado($destinatarioEmail, $nomeParticipante, $nomeEquipe, $linkDefinirSenha, $contextoAnterior = null)
    {
        $assunto = 'Inscrição homologada: acesso liberado ao sistema';
        $abertura = 'A inscrição da equipe <strong>' . htmlspecialchars($nomeEquipe, ENT_QUOTES, 'UTF-8') . '</strong> foi homologada.';

        if ($contextoAnterior !== null) {
            $abertura .= ' ' . $contextoAnterior;
        }

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
        $assunto = 'Seu acesso ao Sistema do Prêmio de Inovação ' . nomeInstituicao() . ' está liberado';
        $abertura = 'Seu acesso ao Sistema do Prêmio de Inovação do ' . htmlspecialchars(nomeInstituicaoCompleto(), ENT_QUOTES, 'UTF-8') . ' está liberado.';
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
     * Fase 49: convite de avaliador avulso de Trabalhos de um Evento - texto
     * proprio, nunca reaproveitando a mensagem de conviteAdministrativo()
     * (que menciona "Prêmio de Inovação", contexto errado aqui). Mesmo
     * mecanismo de conta/token, texto isolado por classe de destinatario.
     */
    public function conviteAvaliadorTrabalhos($destinatarioEmail, $nomeUsuario, array $evento, $linkDefinirSenha)
    {
        $assunto = 'Convite para avaliar trabalhos: ' . $evento['nome'];
        $abertura = 'Você foi convidado a avaliar trabalhos submetidos ao evento "' . htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8') . '".';
        $corpo = $this->montarCorpoAcesso($nomeUsuario, $abertura, $linkDefinirSenha);

        $id = $this->notificacoes->criar(
            'convite_avaliador_trabalhos',
            'convite_avaliador_trabalhos',
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
     * Fase 49 (achado do usuário no teste de fumaça): variante do convite
     * de avaliador avulso para quem JÁ tem conta no sistema. Texto
     * diferente de propósito - a pessoa não vai se cadastrar nem definir
     * senha nova, só ganhou autorização nova (avaliar Trabalhos deste
     * evento) usando o acesso que já tem. Sem hiperlink de definir senha,
     * sem menção a criar conta.
     */
    public function conviteAvaliadorTrabalhosContaExistente($destinatarioEmail, $nomeUsuario, array $evento)
    {
        $assunto = 'Convite para avaliar trabalhos: ' . $evento['nome'];
        $mensagem = 'Você foi convidado a avaliar trabalhos submetidos ao evento "' . htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8') . '". Como você já tem conta neste sistema, não é preciso se cadastrar de novo: acesse normalmente com o e-mail e a senha que já usa (ou com sua conta Google, se for assim que costuma entrar).';
        $corpo = $this->montarCorpoAcessoExistente($nomeUsuario, $mensagem);

        $id = $this->notificacoes->criar(
            'convite_avaliador_trabalhos_conta_existente',
            'convite_avaliador_trabalhos_conta_existente',
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

    public function recuperacaoSenha($destinatarioEmail, $nomeUsuario, $linkDefinirSenha)
    {
        $assunto = 'Redefinição de senha: Sistema do Prêmio de Inovação ' . nomeInstituicao();
        $corpo = $this->montarCorpoRecuperacao($nomeUsuario, $linkDefinirSenha);

        $id = $this->notificacoes->criar(
            'recuperacao_senha',
            'solicitacao_recuperacao',
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

    private function montarCorpoRecuperacao($nomeDestinatario, $linkDefinirSenha)
    {
        return sprintf(
            '<p>Olá, %s,</p>'
            . '<p>Recebemos uma solicitação para redefinir a senha da sua conta no Sistema do Prêmio de Inovação ' . htmlspecialchars(nomeInstituicao(), ENT_QUOTES, 'UTF-8') . '.</p>'
            . '<p>Para definir uma nova senha, clique no hiperlink abaixo:</p>'
            . '<p><a href="%s">Redefinir minha senha</a></p>'
            . '<p style="color:#555;font-size:0.9em;">Se você não solicitou essa redefinição, ignore este e-mail. Sua senha atual '
            . 'continua válida. Não compartilhe sua senha com terceiros. Em caso de dúvida sobre a autenticidade deste e-mail, '
            . 'entre em contato pelos canais abaixo.</p>'
            . '<p>Atenciosamente,</p>',
            htmlspecialchars($nomeDestinatario, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($linkDefinirSenha, ENT_QUOTES, 'UTF-8')
        ) . $this->assinaturaContato();
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
            . '<p>Atenciosamente,</p>',
            htmlspecialchars($nomeDestinatario, ENT_QUOTES, 'UTF-8'),
            $abertura,
            htmlspecialchars($linkGoogle, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($linkDefinirSenha, ENT_QUOTES, 'UTF-8')
        ) . $this->assinaturaContato();
    }

    /**
     * Fase 49: variante de "acesso liberado" para quem já tem conta - sem
     * hiperlink de definir senha (a pessoa já tem uma), sem menção a
     * criar cadastro. $mensagem já vem pronta (com a explicação do que
     * mudou), diferente de montarCorpoAcesso() que só recebe a frase de
     * abertura porque o resto do corpo (Google/definir senha) é fixo.
     */
    private function montarCorpoAcessoExistente($nomeDestinatario, $mensagem)
    {
        $linkLogin = urlAbsoluta('auth/login');

        return sprintf(
            '<p>Olá, %s,</p>'
            . '<p>%s</p>'
            . '<p><a href="%s">Entrar no sistema</a></p>'
            . '<p style="color:#555;font-size:0.9em;">Este e-mail foi enviado automaticamente. Em caso de dúvida sobre a '
            . 'autenticidade deste e-mail, entre em contato pelos canais abaixo.</p>'
            . '<p>Atenciosamente,</p>',
            htmlspecialchars($nomeDestinatario, ENT_QUOTES, 'UTF-8'),
            $mensagem,
            htmlspecialchars($linkLogin, ENT_QUOTES, 'UTF-8')
        ) . $this->assinaturaContato();
    }

    /**
     * Bloco de assinatura dos e-mails automaticos, montado inteiro a partir
     * de Configuracoes > Contato. Nome do organizador, e-mail e telefone
     * estavam fixos no codigo (em dois metodos, e o href do WhatsApp tinha um
     * digito a mais que o numero escrito ao lado dele) - agora sao um dado
     * so', o mesmo que o rodape da home mostra.
     *
     * Concatenado DEPOIS do sprintf de proposito: dado vindo do banco dentro
     * da string de formato faria qualquer "%" digitado pelo Admin virar
     * especificador e quebrar a montagem do corpo.
     *
     * Linha sem dado cadastrado simplesmente nao sai, e nada cadastrado nao
     * deixa um <p> vazio no rodape do e-mail. O e-mail so' vira mailto se
     * passar por FILTER_VALIDATE_EMAIL, e o telefone so' vira link se
     * linkWhatsApp() conseguir normalizar - texto solto nunca vira href.
     *
     * isset() em nome_organizador_assinatura: coluna de migration nova - um
     * banco ainda nao migrado nao pode derrubar o envio de e-mail.
     */
    private function assinaturaContato()
    {
        $contato = $this->contatos->buscar();

        if ($contato === null) {
            return '';
        }

        $linhas = [];

        if (isset($contato['nome_organizador_assinatura']) && $contato['nome_organizador_assinatura'] !== '') {
            $linhas[] = '<strong>'
                . htmlspecialchars($contato['nome_organizador_assinatura'], ENT_QUOTES, 'UTF-8')
                . '</strong>';
        }

        if (!empty($contato['email']) && filter_var($contato['email'], FILTER_VALIDATE_EMAIL)) {
            $email = htmlspecialchars($contato['email'], ENT_QUOTES, 'UTF-8');
            $linhas[] = '✉️ E-mail: <a href="mailto:' . $email . '">' . $email . '</a>';
        }

        if (!empty($contato['whatsapp'])) {
            $linkWhats = linkWhatsApp($contato['whatsapp']);

            if ($linkWhats !== null) {
                $linhas[] = '💬 Fone: <a href="' . htmlspecialchars($linkWhats, ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($contato['whatsapp'], ENT_QUOTES, 'UTF-8') . '</a>';
            }
        }

        return $linhas !== [] ? '<p>' . implode('<br>', $linhas) . '</p>' : '';
    }
}
