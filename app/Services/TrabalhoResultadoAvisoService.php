<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoRepository;

/**
 * Fase 52: aviso aos autores quando o Admin publica o resultado de
 * Trabalhos. Dois canais, os mesmos ja usados no restante do Evento:
 *
 * - sino do aplicativo: uma notificacao por autor com conta e por
 *   trabalho, com o link direto para a tela do trabalho;
 * - e-mail: UMA campanha na fila da Fase 45 (dez por execucao do agendador,
 *   para nao disparar dezenas de e-mails de uma vez pelo canal institucional
 *   compartilhado), um e-mail por pessoa mesmo que ela seja autora de mais
 *   de um trabalho. O corpo e' generico e igual para todos: nunca leva nome,
 *   situacao ou nota, so' o convite para abrir o aplicativo, mais o texto
 *   editavel em Trabalhos, Configuracoes (mensagem_resultado_html).
 *
 * Coautor sem conta entra na fila como destinatario avulso (usuario_id
 * nulo, com o e-mail e o nome guardados em trabalho_autores). Trabalho
 * desclassificado fica de fora: nao tem resultado a divulgar.
 *
 * Chamado depois que a publicacao ja foi confirmada; uma falha aqui nunca
 * desfaz a publicacao (quem chama avisa o Admin).
 */
class TrabalhoResultadoAvisoService
{
    private $eventos;
    private $config;
    private $trabalhos;
    private $autores;
    private $painel;
    private $comunicacoes;

    public function __construct()
    {
        $this->eventos = new SemanaInovacaoRepository();
        $this->config = new TrabalhoConfigRepository();
        $this->trabalhos = new TrabalhoRepository();
        $this->autores = new TrabalhoAutorRepository();
        $this->painel = new NotificacaoPainelRepository();
        $this->comunicacoes = new EventoComunicacaoRepository();
    }

    /**
     * Devolve a quantidade de avisos criados: ['sinos' => n, 'emails' => n].
     */
    public function avisarAutores($eventoId, $adminUsuarioId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);
        $config = $this->config->buscarPorEvento($eventoId);

        if ($evento === null) {
            throw new \RuntimeException('Evento não encontrado para avisar os autores.');
        }

        $sinos = 0;
        $destinatarios = [];

        foreach ($this->trabalhos->listarPorEvento($eventoId) as $trabalho) {
            if ($trabalho['status'] === 'desclassificado') {
                continue;
            }

            foreach ($this->autores->listarPorTrabalho($trabalho['id']) as $autor) {
                if (!empty($autor['usuario_id'])) {
                    $this->painel->criar(
                        (int) $autor['usuario_id'],
                        'resultado_trabalho',
                        'Resultado da avaliação publicado',
                        'O resultado do trabalho "' . $trabalho['titulo'] . '" já está disponível.',
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
        }

        if (!empty($destinatarios)) {
            $this->comunicacoes->criarCampanhaAvulsa([
                'evento_id' => (int) $eventoId,
                'autor_usuario_id' => $adminUsuarioId,
                'tipo' => 'comunicado',
                'assunto' => 'Resultado da avaliação dos trabalhos: ' . $evento['nome'],
                'corpo_html' => $this->montarCorpo($evento, $config),
            ], array_values($destinatarios));
        }

        return ['sinos' => $sinos, 'emails' => count($destinatarios)];
    }

    private function montarCorpo(array $evento, array $config = null)
    {
        $esc = function ($texto) {
            return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
        };

        $endereco = urlAbsoluta('trabalho/meusTrabalhos');

        $partes = [
            '<p>Olá,</p>',
            '<p>O resultado da avaliação dos trabalhos do evento <strong>' . $esc($evento['nome']) . '</strong> foi publicado.</p>',
            '<p>Para ver a situação do seu trabalho e os detalhes do resultado, entre com o seu e-mail e a sua senha (ou com a sua conta Google):<br>'
                . '<a href="' . $esc($endereco) . '">' . $esc($endereco) . '</a></p>',
        ];

        $partes[] = ($config !== null && !empty($config['mensagem_resultado_html']))
            ? $config['mensagem_resultado_html']
            : '<p>Se você ainda não tem acesso ao sistema do evento, procure a organização.</p>';

        return implode('', $partes);
    }
}
