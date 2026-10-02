<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\View;
use App\Repositories\EventoAnaisComissaoRepository;
use App\Repositories\EventoAnaisMontagemRepository;
use App\Repositories\EventoAnaisRepository;
use App\Repositories\EventoAnaisVersaoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoConfigRepository;

/**
 * Fase 54: monta o volume dos Anais a partir do pedido da tela "Montagem
 * dos Anais". Chamado SO' pela rotina database/gerar_anais.php: juntar
 * dezenas de PDFs leva minutos e centenas de megabytes, o que o servidor web
 * nao aguenta (memoria de 40M, sem programa externo).
 *
 * Ordem do volume: capa enviada (so' a 1a pagina) ou capa simples gerada,
 * paginas iniciais (folha de rosto, ficha, expediente, comissoes,
 * apresentacao), sumario e os trabalhos. Toda pagina conta na sequencia; o
 * numero so' e' carimbado no rodape a partir da primeira pagina do primeiro
 * trabalho, e o sumario aponta para esses numeros.
 *
 * O resultado vira uma versao em rascunho da Fase 53 (nada e' publicado
 * aqui). Arquivos temporarios sao apagados em qualquer desfecho.
 */
class EventoAnaisGeradorService
{
    private const PASTA_TEMPORARIA = 'montagem-temporaria';

    private $totalPaginas = 0;

    /**
     * Quantidade de paginas do ultimo volume gerado por esta instancia.
     */
    public function totalDePaginas()
    {
        return $this->totalPaginas;
    }

    /**
     * Apaga pastas de trabalho que sobraram de uma execucao que morreu no
     * meio (erro fatal nao passa pelo finally de gerar()). So' chamar com a
     * trava da rotina, quando nenhuma geracao esta em andamento.
     */
    public function limparSobrasTemporarias()
    {
        $base = realpath(__DIR__ . '/../../storage/uploads');

        if ($base === false) {
            return;
        }

        $pastas = glob($base . '/anais/*/' . self::PASTA_TEMPORARIA, GLOB_ONLYDIR);

        foreach ($pastas !== false ? $pastas : [] as $pasta) {
            $this->limparPastaTemporaria($pasta);
        }
    }

    /**
     * $pedido: linha de evento_anais_geracoes ja reservada. Devolve o id da
     * versao criada. Lanca RuntimeException com mensagem clara (qual
     * trabalho ou arquivo falhou) para o historico da tela.
     */
    public function gerar(array $pedido)
    {
        $this->totalPaginas = 0;
        $eventoId = (int) $pedido['evento_id'];

        $evento = (new SemanaInovacaoRepository())->buscarPorId($eventoId);

        if ($evento === null) {
            throw new \RuntimeException('O evento deste pedido não existe mais.');
        }

        $config = (new TrabalhoConfigRepository())->buscarPorEvento($eventoId);

        if ($config === null || empty($config['resultado_publicado_em'])) {
            throw new \RuntimeException('O resultado de Trabalhos não está publicado. Publique-o na aba Resultado e peça a geração de novo.');
        }

        $anais = (new EventoAnaisRepository())->buscarPorEvento($eventoId);

        if ($anais === null || trim((string) $anais['titulo']) === '') {
            throw new \RuntimeException('Salve o título dos Anais na aba Anais e peça a geração de novo.');
        }

        $montagem = (new EventoAnaisMontagemRepository())->buscarPorEvento($eventoId);
        $montagem = $montagem !== null ? $montagem : [];
        $trabalhos = (new EventoAnaisMontagemService())->montarTrabalhos($eventoId);

        if (empty($trabalhos)) {
            throw new \RuntimeException('Nenhum trabalho consta nos Anais. Confira a aba Trabalhos nos Anais.');
        }

        $capa = $this->conferirCapa($montagem);
        $conferidos = $this->conferirTrabalhos($trabalhos);

        $pasta = $this->prepararPastaTemporaria($eventoId);

        try {
            $htmlIniciais = View::renderizarString('pdf/anais_pretextuais', [
                'gerarCapa' => $capa === null,
                'titulo' => trim((string) $anais['titulo']),
                'subtitulo' => $this->textoDe($montagem, 'subtitulo'),
                'eventoNome' => (string) $evento['nome'],
                'localAno' => $this->textoDe($montagem, 'local_ano'),
                'identificador' => EventoAnaisRepository::rotuloIdentificador($anais),
                'organizadoresHtml' => $this->htmlParaPdf($montagem, 'organizadores_html'),
                'fichaHtml' => $this->htmlParaPdf($montagem, 'ficha_catalografica_html'),
                'expedienteHtml' => $this->htmlParaPdf($montagem, 'expediente_html'),
                'apresentacaoHtml' => $this->htmlParaPdf($montagem, 'apresentacao_html'),
                'comissoes' => (new EventoAnaisComissaoRepository())->listarComMembros($eventoId),
            ]);

            $iniciais = $this->renderizarPdf($htmlIniciais);
            $caminhoIniciais = $pasta . '/pretextuais.pdf';
            $this->gravarTemporario($caminhoIniciais, $iniciais['conteudo']);
            $paginasIniciais = $iniciais['paginas'];
            unset($iniciais, $htmlIniciais);

            $paginasCapa = $capa !== null ? 1 : 0;
            $sumario = $this->renderizarSumario($trabalhos, $conferidos, $paginasCapa + $paginasIniciais);
            $caminhoSumario = $pasta . '/sumario.pdf';
            $this->gravarTemporario($caminhoSumario, $sumario['conteudo']);
            $paginasSumario = $sumario['paginas'];
            $ligacoesDoSumario = $this->ligacoesDoSumario($sumario['posicoes'], $sumario['destinos']);
            unset($sumario);

            $pdf = new EventoAnaisPdf();
            $pdf->SetAutoPageBreak(false);
            $pdf->SetTitle(trim((string) $anais['titulo']), true);

            if ($capa !== null) {
                $this->anexar($pdf, $capa, 1, false, 'a capa', [['Capa', 0]]);
            }

            $this->anexar($pdf, $caminhoIniciais, null, false, 'as páginas iniciais', [['Páginas iniciais', 0]]);
            $this->anexar($pdf, $caminhoSumario, null, false, 'o sumário', [['Sumário', 0]], $ligacoesDoSumario);

            if ($pdf->PageNo() !== $paginasCapa + $paginasIniciais + $paginasSumario) {
                throw new \RuntimeException('A contagem das páginas iniciais não bateu com o sumário. Peça a geração de novo; se repetir, avise o suporte técnico.');
            }

            $eixoAtual = null;

            foreach ($trabalhos as $trabalho) {
                $id = (int) $trabalho['id'];
                $descricao = 'o PDF final do trabalho ' . $this->rotuloTrabalho($trabalho);
                $eixo = $trabalho['eixo_id'] !== null ? (int) $trabalho['eixo_id'] : 0;
                $marcadores = [];

                if ($eixo !== 0 && $eixo !== $eixoAtual) {
                    $marcadores[] = [(string) $trabalho['eixo_nome'], 0];
                }

                $marcadores[] = [(string) $trabalho['titulo'], $eixo !== 0 ? 1 : 0];
                $eixoAtual = $eixo;
                $anexadas = $this->anexar($pdf, $conferidos[$id]['caminho'], null, true, $descricao, $marcadores);

                if ($anexadas !== $conferidos[$id]['paginas']) {
                    throw new \RuntimeException('O PDF final do trabalho ' . $this->rotuloTrabalho($trabalho) . ' mudou durante a geração. Peça a geração de novo.');
                }
            }

            $this->totalPaginas = (int) $pdf->PageNo();
            $caminhoVolume = $pasta . '/volume.pdf';

            try {
                $pdf->Output('F', $caminhoVolume);
            } catch (\Throwable $e) {
                error_log('[Anais] falha ao gravar o volume: ' . $e->getMessage());

                throw new \RuntimeException('Não foi possível gravar o volume no servidor (espaço em disco ou permissão da pasta). Avise o suporte técnico.');
            }

            unset($pdf);

            return $this->guardarComoVersao($eventoId, $caminhoVolume, $pedido);
        } finally {
            $this->limparPastaTemporaria($pasta);
        }
    }

    /**
     * Caminho fisico da capa enviada, ou null quando nao ha capa (o volume
     * ganha a capa simples gerada).
     */
    private function conferirCapa(array $montagem)
    {
        if (empty($montagem['capa_path'])) {
            return null;
        }

        $capa = ArquivoPrivadoService::caminhoFisico($montagem['capa_path']);

        if ($capa === null) {
            throw new \RuntimeException('O arquivo da capa não foi encontrado no servidor. Envie a capa de novo ou remova-a.');
        }

        try {
            EventoAnaisPdfFinalService::conferirPdfLegivel($capa);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('Não foi possível ler o PDF da capa. Envie a capa de novo em PDF/A-1b ou remova-a.');
        }

        return $capa;
    }

    /**
     * Confere de novo, antes de gerar qualquer pagina, que todo trabalho
     * tem PDF presente e legivel. Devolve, por id do trabalho, o caminho, a
     * quantidade real de paginas e os autores (nomes na ordem cadastrada,
     * nunca CPF nem e-mail).
     */
    private function conferirTrabalhos(array $trabalhos)
    {
        $autores = new TrabalhoAutorRepository();
        $conferidos = [];

        foreach ($trabalhos as $trabalho) {
            $id = (int) $trabalho['id'];
            $rotulo = $this->rotuloTrabalho($trabalho);

            if ($trabalho['arquivo'] === null) {
                throw new \RuntimeException('O trabalho ' . $rotulo . ' está sem a versão final em PDF. Prorrogue o prazo ou retire o trabalho na aba Trabalhos nos Anais.');
            }

            $caminho = ArquivoPrivadoService::caminhoFisico($trabalho['arquivo']['arquivo_path']);

            if ($caminho === null) {
                throw new \RuntimeException('O PDF final do trabalho ' . $rotulo . ' não foi encontrado no servidor. O autor principal precisa enviar de novo.');
            }

            try {
                $paginas = EventoAnaisPdfFinalService::conferirPdfLegivel($caminho);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException('Não foi possível ler o PDF final do trabalho ' . $rotulo . '. O autor principal precisa enviar de novo em PDF/A-1b, ou retire o trabalho na aba Trabalhos nos Anais.');
            }

            $nomes = [];

            foreach ($autores->listarPorTrabalho($id) as $autor) {
                $nomes[] = trim((string) $autor['nome']);
            }

            $conferidos[$id] = [
                'caminho' => $caminho,
                'paginas' => $paginas,
                'autores' => implode('; ', $nomes),
            ];
        }

        return $conferidos;
    }

    /**
     * O sumario ocupa paginas antes dos trabalhos, e a quantidade dessas
     * paginas pode mudar com os proprios numeros: o calculo se repete ate a
     * contagem estabilizar (no maximo cinco voltas).
     */
    private function renderizarSumario(array $trabalhos, array $conferidos, $paginasAntes)
    {
        $paginasSumario = 1;

        for ($volta = 1; $volta <= 5; $volta++) {
            $primeiraPagina = 1 + $paginasAntes + $paginasSumario;
            $grupos = $this->montarGruposDoSumario($trabalhos, $conferidos, $primeiraPagina);
            $html = View::renderizarString('pdf/anais_sumario', [
                'grupos' => $grupos,
            ]);
            $sumario = $this->renderizarPdf($html, 'sumario-item-');

            if ($sumario['paginas'] === $paginasSumario) {
                $sumario['destinos'] = [];

                foreach ($grupos as $grupo) {
                    foreach ($grupo['itens'] as $item) {
                        $sumario['destinos'][$item['indice']] = $item['pagina'];
                    }
                }

                return $sumario;
            }

            $paginasSumario = $sumario['paginas'];
        }

        throw new \RuntimeException('Não foi possível fechar a numeração do sumário. Peça a geração de novo; se repetir, avise o suporte técnico.');
    }

    /**
     * Agrupa por eixo tematico na ordem do volume: um grupo novo sempre que
     * o eixo muda. Com a ordem padrao cada eixo aparece uma vez; com ordem
     * manual que intercala eixos, o sumario mostra o volume como ele e'.
     */
    private function montarGruposDoSumario(array $trabalhos, array $conferidos, $primeiraPagina)
    {
        $grupos = [];
        $eixoAtual = null;
        $pagina = $primeiraPagina;
        $indice = 0;

        foreach ($trabalhos as $trabalho) {
            $id = (int) $trabalho['id'];
            $eixo = $trabalho['eixo_id'] !== null ? (int) $trabalho['eixo_id'] : 0;

            if (empty($grupos) || $eixo !== $eixoAtual) {
                $grupos[] = [
                    'eixo' => $trabalho['eixo_id'] !== null ? (string) $trabalho['eixo_nome'] : '',
                    'itens' => [],
                ];
                $eixoAtual = $eixo;
            }

            $grupos[count($grupos) - 1]['itens'][] = [
                'indice' => $indice++,
                'titulo' => $trabalho['titulo'],
                'autores' => $conferidos[$id]['autores'],
                'pagina' => $pagina,
            ];

            $pagina += $conferidos[$id]['paginas'];
        }

        return $grupos;
    }

    /**
     * Mesmas opcoes do PdfService (sem acesso remoto, fonte DejaVu Sans),
     * mais a pasta assets/ liberada para as imagens inseridas pelo editor.
     * Devolve ['conteudo' => PDF, 'paginas' => quantidade].
     */
    private function renderizarPdf($html, $prefixoPosicoes = null)
    {
        $opcoes = ['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans'];
        $assets = realpath(__DIR__ . '/../../assets');

        if ($assets !== false) {
            $opcoes['chroot'] = [$assets];
        }

        $dompdf = new \Dompdf\Dompdf($opcoes);
        $posicoes = [];

        // Pagina e retangulo de cada elemento cujo id comeca com o prefixo,
        // para as ligacoes internas do sumario: a FPDI importa so' o desenho
        // da pagina, e as ligacoes do Dompdf nao sobrevivem a montagem.
        if ($prefixoPosicoes !== null) {
            $dompdf->setCallbacks([[
                'event' => 'end_frame',
                'f' => function ($quadro, $tela) use ($prefixoPosicoes, &$posicoes) {
                    $no = $quadro->get_node();

                    if (!($no instanceof \DOMElement)) {
                        return;
                    }

                    $id = (string) $no->getAttribute('id');

                    if (strpos($id, $prefixoPosicoes) !== 0) {
                        return;
                    }

                    $caixa = $quadro->get_border_box();
                    $posicoes[] = [
                        'indice' => (int) substr($id, strlen($prefixoPosicoes)),
                        'pagina' => (int) $tela->get_page_number(),
                        'x' => (float) $caixa['x'],
                        'y' => (float) $caixa['y'],
                        'w' => (float) $caixa['w'],
                        'h' => (float) $caixa['h'],
                    ];
                },
            ]]);
        }

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return [
            'conteudo' => $dompdf->output(),
            'paginas' => (int) $dompdf->getCanvas()->get_page_count(),
            'posicoes' => $posicoes,
        ];
    }

    /**
     * Ligacoes do sumario por pagina do proprio sumario: retangulo em pontos
     * e pagina de destino no volume.
     */
    private function ligacoesDoSumario(array $posicoes, array $destinos)
    {
        $porPagina = [];

        foreach ($posicoes as $posicao) {
            if (!isset($destinos[$posicao['indice']]) || $posicao['w'] <= 0 || $posicao['h'] <= 0) {
                continue;
            }

            $porPagina[$posicao['pagina']][] = $posicao + ['destino' => (int) $destinos[$posicao['indice']]];
        }

        return $porPagina;
    }

    /**
     * Junta as paginas de um PDF ao volume, cada uma no tamanho original.
     * $numerar carimba o numero da pagina no volume (rodape, centralizado).
     * Devolve quantas paginas entraram.
     */
    private function anexar(EventoAnaisPdf $pdf, $caminho, $ultimaPagina, $numerar, $descricao, array $marcadores = [], array $ligacoes = [])
    {
        try {
            $total = (int) $pdf->setSourceFile($caminho);
            $limite = $ultimaPagina !== null ? min((int) $ultimaPagina, $total) : $total;
            $pontoParaMilimetro = 25.4 / 72;

            for ($pagina = 1; $pagina <= $limite; $pagina++) {
                $modelo = $pdf->importPage($pagina);
                $tamanho = $pdf->getTemplateSize($modelo);
                $pdf->AddPage($tamanho['orientation'], [$tamanho['width'], $tamanho['height']]);
                $pdf->useTemplate($modelo);

                if ($pagina === 1) {
                    foreach ($marcadores as $marcador) {
                        $pdf->marcador($marcador[0], $marcador[1]);
                    }
                }

                foreach (isset($ligacoes[$pagina]) ? $ligacoes[$pagina] : [] as $ligacao) {
                    $destino = $pdf->AddLink();
                    $pdf->SetLink($destino, 0, $ligacao['destino']);
                    $pdf->Link(
                        $ligacao['x'] * $pontoParaMilimetro,
                        $ligacao['y'] * $pontoParaMilimetro,
                        $ligacao['w'] * $pontoParaMilimetro,
                        $ligacao['h'] * $pontoParaMilimetro,
                        $destino
                    );
                }

                if ($numerar) {
                    $pdf->SetFont('Helvetica', '', 9);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetXY(0, $tamanho['height'] - 15);
                    $pdf->Cell($tamanho['width'], 6, (string) $pdf->PageNo(), 0, 0, 'C');
                }
            }
        } catch (\Throwable $e) {
            error_log('[Anais] falha ao juntar ' . $caminho . ': ' . get_class($e) . ': ' . $e->getMessage());

            throw new \RuntimeException('Não foi possível juntar ' . $descricao . ' ao volume. Esse arquivo precisa ser enviado de novo em PDF/A-1b; depois, peça a geração outra vez.');
        }

        return $limite;
    }

    /**
     * Move o volume para a pasta privada onde a Fase 53 guarda as versoes e
     * cria a versao em rascunho. Se a gravacao no banco falhar, o arquivo
     * movido e' apagado.
     */
    private function guardarComoVersao($eventoId, $caminhoVolume, array $pedido)
    {
        $base = realpath(__DIR__ . '/../../storage/uploads');

        if ($base === false) {
            throw new \RuntimeException('A pasta privada de arquivos não foi encontrada no servidor. Avise o suporte técnico.');
        }

        $pastaRelativa = 'anais/' . (int) $eventoId;
        $pastaFisica = $base . '/' . $pastaRelativa;

        if (!is_dir($pastaFisica) && !mkdir($pastaFisica, 0755, true) && !is_dir($pastaFisica)) {
            throw new \RuntimeException('Não foi possível criar a pasta dos Anais no servidor. Avise o suporte técnico.');
        }

        $relativo = $pastaRelativa . '/' . bin2hex(random_bytes(16)) . '.pdf';
        $destino = $base . '/' . $relativo;

        if (!rename($caminhoVolume, $destino)) {
            throw new \RuntimeException('Não foi possível mover o volume gerado para a pasta dos Anais. Avise o suporte técnico.');
        }

        try {
            $criada = (new EventoAnaisVersaoRepository())->criar($eventoId, [
                'arquivo_path' => $relativo,
                'nome_original' => 'anais-gerado.pdf',
                'tamanho_bytes' => (int) filesize($destino),
                'sha256' => hash_file('sha256', $destino),
                'observacao' => 'Gerada automaticamente',
                'enviado_por' => !empty($pedido['solicitado_por']) ? (int) $pedido['solicitado_por'] : null,
            ]);
        } catch (\Throwable $e) {
            if (is_file($destino)) {
                unlink($destino);
            }

            throw $e;
        }

        return (int) $criada['id'];
    }

    /**
     * Pasta de trabalho dentro da area privada do evento (mesmo disco do
     * destino final, entao mover o volume e' so' renomear). A rotina roda um
     * pedido por vez, por isso sobras de uma execucao interrompida podem ser
     * apagadas no inicio.
     */
    private function prepararPastaTemporaria($eventoId)
    {
        $base = realpath(__DIR__ . '/../../storage/uploads');

        if ($base === false) {
            throw new \RuntimeException('A pasta privada de arquivos não foi encontrada no servidor. Avise o suporte técnico.');
        }

        $pasta = $base . '/anais/' . (int) $eventoId . '/' . self::PASTA_TEMPORARIA;

        if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
            throw new \RuntimeException('Não foi possível criar a pasta de trabalho da geração no servidor. Avise o suporte técnico.');
        }

        $this->apagarPdfs($pasta);

        return $pasta;
    }

    private function limparPastaTemporaria($pasta)
    {
        $this->apagarPdfs($pasta);

        if (is_dir($pasta)) {
            rmdir($pasta);
        }
    }

    private function apagarPdfs($pasta)
    {
        $arquivos = glob($pasta . '/*.pdf');

        foreach ($arquivos !== false ? $arquivos : [] as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }
    }

    private function gravarTemporario($caminho, $conteudo)
    {
        if (file_put_contents($caminho, $conteudo) === false) {
            throw new \RuntimeException('Não foi possível gravar um arquivo temporário da geração no servidor. Avise o suporte técnico.');
        }
    }

    private function textoDe(array $montagem, $campo)
    {
        return isset($montagem[$campo]) ? trim((string) $montagem[$campo]) : '';
    }

    /**
     * Texto rico filtrado de novo na saida, com as imagens do proprio
     * sistema apontando para o arquivo local (o Dompdf nao busca endereco
     * remoto). Vazio quando o campo nao tem texto.
     */
    private function htmlParaPdf(array $montagem, $campo)
    {
        $html = isset($montagem[$campo]) ? (string) $montagem[$campo] : '';

        if (trim(strip_tags($html, '<img>')) === '') {
            return '';
        }

        return $this->imagensLocais(sanitizarHtmlRico($html));
    }

    private function imagensLocais($html)
    {
        $assets = realpath(__DIR__ . '/../../assets');

        if ($assets === false) {
            return $html;
        }

        $prefixo = config('base_path') . '/assets/';

        $trocado = preg_replace_callback('/\bsrc\s*=\s*(["\'])(.*?)\1/i', function ($partes) use ($assets, $prefixo) {
            $endereco = html_entity_decode($partes[2], ENT_QUOTES, 'UTF-8');
            $caminho = parse_url($endereco, PHP_URL_PATH);

            if (!is_string($caminho) || strpos($caminho, $prefixo) !== 0) {
                return $partes[0];
            }

            $local = realpath($assets . '/' . rawurldecode(substr($caminho, strlen($prefixo))));

            if ($local === false || strpos($local, $assets . DIRECTORY_SEPARATOR) !== 0 || !is_file($local)) {
                return $partes[0];
            }

            return 'src=' . $partes[1] . htmlspecialchars($local, ENT_QUOTES, 'UTF-8') . $partes[1];
        }, $html);

        return $trocado !== null ? $trocado : $html;
    }

    private function rotuloTrabalho(array $trabalho)
    {
        return '"' . $trabalho['titulo'] . '" (nº ' . (int) $trabalho['id'] . ')';
    }
}
