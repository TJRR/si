<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EventoAnaisMontagemRepository;
use App\Repositories\EventoAnaisRepository;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoConfigRepository;

/**
 * Fase 54: aviso aos autores principais quando o Administrador abre ou
 * altera o prazo do PDF final dos Anais com a caixa "Avisar os autores"
 * marcada. Mesmo desenho de EventoAnaisAvisoService (Fase 53):
 *
 * - sino do aplicativo: uma notificacao por autor principal com conta e por
 *   trabalho, com o titulo e o link para a tela do trabalho;
 * - e-mail: UMA campanha na fila da Fase 45 (dez por execucao do agendador),
 *   um e-mail por pessoa. Como a campanha tem um corpo so' para todos, o
 *   corpo e' generico (evento, prazo e o caminho ate a tela do trabalho),
 *   mais o texto editavel mensagem_aviso_pdf_final_html.
 *
 * So' o autor principal recebe: e' ele quem envia. Nenhum aviso sai antes de
 * o resultado de Trabalhos ser publicado (o aviso revelaria quem foi
 * aprovado). Uma falha aqui nunca desfaz o prazo ja salvo (quem chama avisa
 * o Administrador).
 */
class EventoAnaisPdfFinalAvisoService
{
    private $eventos;
    private $anais;
    private $montagem;
    private $autores;
    private $painel;
    private $comunicacoes;

    public function __construct()
    {
        $this->eventos = new SemanaInovacaoRepository();
        $this->anais = new EventoAnaisRepository();
        $this->montagem = new EventoAnaisMontagemRepository();
        $this->autores = new TrabalhoAutorRepository();
        $this->painel = new NotificacaoPainelRepository();
        $this->comunicacoes = new EventoComunicacaoRepository();
    }

    /**
     * Devolve a quantidade de avisos criados: ['sinos' => n, 'emails' => n].
     * Lanca RuntimeException (texto para a tela) quando nao ha o que avisar.
     */
    public function avisarAutores($eventoId, $adminUsuarioId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            throw new \RuntimeException('o evento não foi encontrado.');
        }

        $config = (new TrabalhoConfigRepository())->buscarPorEvento($eventoId);

        if ($config === null || empty($config['resultado_publicado_em'])) {
            throw new \RuntimeException('o resultado de Trabalhos ainda não foi publicado. Avise os autores depois de publicá-lo.');
        }

        $montagem = $this->montagem->buscarPorEvento($eventoId);

        if ($montagem === null || empty($montagem['prazo_pdf_final'])) {
            throw new \RuntimeException('o prazo não está preenchido.');
        }

        if (strtotime($montagem['prazo_pdf_final']) < time()) {
            throw new \RuntimeException('o prazo informado já passou.');
        }

        $prazo = formatarDataHora($montagem['prazo_pdf_final']) . ' ' . sufixoFusoHorario();
        $sinos = 0;
        $destinatarios = [];

        foreach ($this->anais->listarTrabalhosIncluidos($eventoId) as $trabalho) {
            $autor = $this->autores->buscarAutorPrincipal($trabalho['id']);

            if ($autor === null) {
                continue;
            }

            if (!empty($autor['usuario_id'])) {
                $this->painel->criar(
                    (int) $autor['usuario_id'],
                    'anais_pdf_final',
                    'Versão final para os Anais',
                    'Envie até ' . $prazo . ' a versão final em PDF do trabalho "' . $trabalho['titulo'] . '".',
                    ['url' => url('trabalho/ver/' . (int) $trabalho['id'])]
                );
                $sinos++;
            }

            $email = trim((string) $autor['email']);

            if ($email === '') {
                continue;
            }

            $chave = strtolower($email);

            if (!isset($destinatarios[$chave])) {
                $destinatarios[$chave] = [
                    'usuario_id' => !empty($autor['usuario_id']) ? (int) $autor['usuario_id'] : null,
                    'email' => $email,
                    'nome' => $autor['nome'],
                ];
            } elseif ($destinatarios[$chave]['usuario_id'] === null && !empty($autor['usuario_id'])) {
                $destinatarios[$chave]['usuario_id'] = (int) $autor['usuario_id'];
            }
        }

        if ($sinos === 0 && empty($destinatarios)) {
            throw new \RuntimeException('nenhum trabalho consta nos Anais.');
        }

        if (!empty($destinatarios)) {
            $this->comunicacoes->criarCampanhaAvulsa([
                'evento_id' => (int) $eventoId,
                'autor_usuario_id' => $adminUsuarioId,
                'tipo' => 'comunicado',
                'assunto' => 'Versão final para os Anais: ' . $evento['nome'],
                'corpo_html' => $this->montarCorpo($evento, $prazo, $montagem),
            ], array_values($destinatarios));
        }

        return ['sinos' => $sinos, 'emails' => count($destinatarios)];
    }

    private function montarCorpo(array $evento, $prazo, array $montagem)
    {
        $esc = function ($texto) {
            return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
        };

        $enderecoApp = urlAbsoluta('trabalho/meusTrabalhos');

        $partes = [
            '<p>Olá,</p>',
            '<p>O seu trabalho aprovado no evento <strong>' . $esc($evento['nome']) . '</strong> consta nos Anais. '
                . 'O autor principal deve enviar a versão final do trabalho, em PDF, até <strong>' . $esc($prazo) . '</strong>.</p>',
            '<p>Para enviar, entre com o seu e-mail e a sua senha (ou com a sua conta Google), abra o trabalho em Meus trabalhos '
                . 'e use o quadro "Versão final para os Anais":<br>'
                . '<a href="' . $esc($enderecoApp) . '">' . $esc($enderecoApp) . '</a></p>',
            '<p>Se o arquivo já foi enviado, não é preciso fazer nada: ele pode ser trocado até o fim do prazo.</p>',
        ];

        $mensagem = !empty($montagem['mensagem_aviso_pdf_final_html']) ? sanitizarHtmlRico($montagem['mensagem_aviso_pdf_final_html']) : '';

        $partes[] = trim(strip_tags($mensagem, '<img>')) !== ''
            ? $mensagem
            : '<p>Em caso de dúvida, procure a organização do evento.</p>';

        return implode('', $partes);
    }
}
