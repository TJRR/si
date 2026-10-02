<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EventoAnaisAvisadoRepository;
use App\Repositories\EventoAnaisRepository;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoAutorRepository;

/**
 * Fase 53: aviso aos autores quando o Administrador publica os Anais. Mesmos
 * dois canais e mesmo desenho de TrabalhoResultadoAvisoService:
 *
 * - sino do aplicativo: uma notificacao por autor com conta e por trabalho,
 *   com o link para a tela do trabalho;
 * - e-mail: UMA campanha na fila da Fase 45 (dez por execucao do agendador),
 *   um e-mail por pessoa mesmo que ela seja autora de mais de um trabalho.
 *   O corpo e' igual para todos (nome do evento, titulo dos Anais e o
 *   endereco do PDF), mais o texto editavel em Anais, Identificacao
 *   (mensagem_publicacao_html).
 *
 * So' recebem o aviso os autores de trabalhos incluidos (aprovados e fora
 * da lista de exclusoes). Coautor sem conta entra na fila como destinatario
 * avulso (usuario_id nulo). Chamado depois que a publicacao ja foi
 * confirmada; uma falha aqui nunca desfaz a publicacao (quem chama avisa o
 * Administrador).
 */
class EventoAnaisAvisoService
{
    private $eventos;
    private $anais;
    private $autores;
    private $painel;
    private $comunicacoes;

    public function __construct()
    {
        $this->eventos = new SemanaInovacaoRepository();
        $this->anais = new EventoAnaisRepository();
        $this->autores = new TrabalhoAutorRepository();
        $this->painel = new NotificacaoPainelRepository();
        $this->comunicacoes = new EventoComunicacaoRepository();
    }

    /**
     * Devolve a quantidade de avisos criados: ['sinos' => n, 'emails' => n,
     * 'trabalhos' => n]. Com $modo 'novos', so' entram os trabalhos que
     * ainda nao estao em evento_anais_trabalhos_avisados; com 'todos', todos
     * os incluidos. Os dois registram os trabalhos avisados.
     */
    public function avisarAutores($eventoId, $adminUsuarioId, $modo = 'todos', $numeroVersao = 0)
    {
        $evento = $this->eventos->buscarPorId($eventoId);
        $publicado = $this->anais->buscarPublicadoParaParticipante($eventoId);
        $dadosAnais = $this->anais->buscarPorEvento($eventoId);

        if ($evento === null || $publicado === null || $dadosAnais === null) {
            throw new \RuntimeException('Anais não encontrados para avisar os autores.');
        }

        $sinos = 0;
        $destinatarios = [];
        $avisados = new EventoAnaisAvisadoRepository();
        $jaAvisados = $modo === 'novos' ? $avisados->idsAvisados($eventoId) : [];
        $trabalhosAvisados = [];

        foreach ($this->anais->listarTrabalhosIncluidos($eventoId) as $trabalho) {
            if (isset($jaAvisados[(int) $trabalho['id']])) {
                continue;
            }

            $trabalhosAvisados[] = (int) $trabalho['id'];

            foreach ($this->autores->listarPorTrabalho($trabalho['id']) as $autor) {
                if (!empty($autor['usuario_id'])) {
                    $this->painel->criar(
                        (int) $autor['usuario_id'],
                        'anais_publicados',
                        'Anais publicados',
                        'Os Anais do evento foram publicados e o trabalho "' . $trabalho['titulo'] . '" consta neles.',
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
                'assunto' => 'Anais publicados: ' . $evento['nome'],
                'corpo_html' => $this->montarCorpo($evento, $publicado, $dadosAnais),
            ], array_values($destinatarios));
        }

        $avisados->registrar($eventoId, $trabalhosAvisados, $numeroVersao);

        return ['sinos' => $sinos, 'emails' => count($destinatarios), 'trabalhos' => count($trabalhosAvisados)];
    }

    private function montarCorpo(array $evento, array $publicado, array $dadosAnais)
    {
        $esc = function ($texto) {
            return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
        };

        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $enderecoPdf = $esquema . '://' . $host . $publicado['url'];
        $enderecoApp = urlAbsoluta('trabalho/meusTrabalhos');

        $partes = [
            '<p>Olá,</p>',
            '<p>Os Anais do evento <strong>' . $esc($evento['nome']) . '</strong> foram publicados (<em>' . $esc($publicado['titulo']) . '</em>) e o seu trabalho consta neles.</p>',
            '<p>Baixe o volume em PDF:<br><a href="' . $esc($enderecoPdf) . '">' . $esc($enderecoPdf) . '</a></p>',
            '<p>Você também pode ver a indicação no seu trabalho, entrando com o seu e-mail e a sua senha (ou com a sua conta Google):<br>'
                . '<a href="' . $esc($enderecoApp) . '">' . $esc($enderecoApp) . '</a></p>',
        ];

        $partes[] = !empty($dadosAnais['mensagem_publicacao_html'])
            ? $dadosAnais['mensagem_publicacao_html']
            : '<p>Se você ainda não tem acesso ao sistema do evento, procure a organização.</p>';

        return implode('', $partes);
    }
}
