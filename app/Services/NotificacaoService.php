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
        $assunto = 'Confirmacao de submissao - ' . $trilha['nome'];
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
            '<p>Ola,</p>'
            . '<p>Recebemos sua submissao para a <strong>%s</strong>, etapa <strong>%s</strong>, '
            . 'em %s.</p>'
            . '<p>Numero de protocolo: <strong>%d</strong>.</p>'
            . '<p>Nenhuma acao adicional e necessaria neste momento.</p>'
            . '<p>Premio de Inovacao TJRR</p>',
            htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'),
            date('d/m/Y H:i'),
            $submissaoId
        );
    }
}
