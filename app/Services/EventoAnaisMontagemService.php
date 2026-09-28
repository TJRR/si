<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EventoAnaisComissaoRepository;
use App\Repositories\EventoAnaisGeracaoRepository;
use App\Repositories\EventoAnaisMontagemRepository;
use App\Repositories\EventoAnaisRepository;
use App\Repositories\EventoAnaisTrabalhoRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoEixoTematicoRepository;
use App\Repositories\TrabalhoRepository;

/**
 * Fase 54: regras da tela "Montagem dos Anais" (lado do Administrador):
 * prazo e instrucoes do PDF final dos autores, dados editoriais, capa,
 * comissoes, ordem dos trabalhos e o pedido de geracao do volume. Nada e'
 * gerado aqui: o pedido entra na fila e a rotina database/gerar_anais.php
 * monta o volume (EventoAnaisGeradorService).
 */
class EventoAnaisMontagemService
{
    public const SUBTITULO_MAXIMO = 200;
    public const LOCAL_ANO_MAXIMO = 120;
    public const NOME_COMISSAO_MAXIMO = 150;
    public const NOME_MEMBRO_MAXIMO = 150;
    public const FUNCAO_MAXIMA = 120;
    public const INSTITUICAO_MAXIMA = 150;

    // Colunas TEXT guardam ate 65.535 bytes; a folga cobre o que o filtro
    // de HTML devolve a mais.
    private const TEXTO_MAXIMO_BYTES = 60000;

    private $montagem;
    private $comissoes;
    private $anais;
    private $registros;
    private $geracoes;

    public function __construct()
    {
        $this->montagem = new EventoAnaisMontagemRepository();
        $this->comissoes = new EventoAnaisComissaoRepository();
        $this->anais = new EventoAnaisRepository();
        $this->registros = new EventoAnaisTrabalhoRepository();
        $this->geracoes = new EventoAnaisGeracaoRepository();
    }

    /**
     * Confere e limpa o bloco "Envio do PDF final pelos autores". Devolve
     * ['erro' => mensagem ou null, 'dados' => campos prontos, inclusive o
     * prazo como foi digitado, para a tela voltar preenchida].
     */
    public function validarPrazo(array $post)
    {
        $digitado = trim(isset($post['prazo_pdf_final']) ? (string) $post['prazo_pdf_final'] : '');
        $prazo = $digitado !== '' ? self::converterDataHora($digitado) : null;
        $instrucoes = self::htmlOuNulo($post, 'instrucoes_pdf_final_html');
        $mensagem = self::htmlOuNulo($post, 'mensagem_aviso_pdf_final_html');

        $erro = null;

        if ($digitado !== '' && $prazo === null) {
            $erro = 'Informe o prazo com data e hora válidas.';
        } elseif ($instrucoes !== null && strlen($instrucoes) > self::TEXTO_MAXIMO_BYTES) {
            $erro = 'As instruções para os autores ficaram longas demais. Encurte o texto.';
        } elseif ($mensagem !== null && strlen($mensagem) > self::TEXTO_MAXIMO_BYTES) {
            $erro = 'O texto do aviso ficou longo demais. Encurte o texto.';
        }

        return [
            'erro' => $erro,
            'dados' => [
                'prazo_pdf_final' => $prazo,
                'prazo_digitado' => $digitado,
                'instrucoes_pdf_final_html' => $instrucoes,
                'mensagem_aviso_pdf_final_html' => $mensagem,
                'avisar_autores' => !empty($post['avisar_autores']),
            ],
        ];
    }

    /**
     * Grava se estiver valido. Devolve o mesmo retorno de validarPrazo().
     */
    public function salvarPrazo($eventoId, array $post)
    {
        $resultado = $this->validarPrazo($post);

        if ($resultado['erro'] === null) {
            $dados = $resultado['dados'];
            $this->montagem->salvarPrazo($eventoId, $dados['prazo_pdf_final'], $dados['instrucoes_pdf_final_html'], $dados['mensagem_aviso_pdf_final_html']);
        }

        return $resultado;
    }

    public function validarEditorial(array $post)
    {
        $dados = [
            'subtitulo' => self::textoOuNulo($post, 'subtitulo'),
            'local_ano' => self::textoOuNulo($post, 'local_ano'),
            'organizadores_html' => self::htmlOuNulo($post, 'organizadores_html'),
            'ficha_catalografica_html' => self::htmlOuNulo($post, 'ficha_catalografica_html'),
            'expediente_html' => self::htmlOuNulo($post, 'expediente_html'),
            'apresentacao_html' => self::htmlOuNulo($post, 'apresentacao_html'),
        ];

        $erro = null;

        if ($dados['subtitulo'] !== null && mb_strlen($dados['subtitulo']) > self::SUBTITULO_MAXIMO) {
            $erro = 'O subtítulo aceita até ' . self::SUBTITULO_MAXIMO . ' caracteres.';
        } elseif ($dados['local_ano'] !== null && mb_strlen($dados['local_ano']) > self::LOCAL_ANO_MAXIMO) {
            $erro = 'O campo "Local e ano" aceita até ' . self::LOCAL_ANO_MAXIMO . ' caracteres.';
        } elseif ($dados['organizadores_html'] !== null && strlen($dados['organizadores_html']) > self::TEXTO_MAXIMO_BYTES) {
            $erro = 'O texto dos organizadores ficou longo demais. Encurte o texto.';
        } elseif ($dados['ficha_catalografica_html'] !== null && strlen($dados['ficha_catalografica_html']) > self::TEXTO_MAXIMO_BYTES) {
            $erro = 'A ficha catalográfica ficou longa demais. Encurte o texto.';
        }

        return ['erro' => $erro, 'dados' => $dados];
    }

    /**
     * Grava se estiver valido. Devolve o mesmo retorno de validarEditorial().
     */
    public function salvarEditorial($eventoId, array $post)
    {
        $resultado = $this->validarEditorial($post);

        if ($resultado['erro'] === null) {
            $this->montagem->salvarEditorial($eventoId, $resultado['dados']);
        }

        return $resultado;
    }

    /**
     * Capa pronta, em PDF de uma pagina, conferida pela FPDI. Fica na area
     * privada; a capa anterior so' e' apagada depois da gravacao confirmada.
     * Lanca RuntimeException com mensagem para a tela.
     */
    public function enviarCapa($eventoId, array $arquivo)
    {
        $erroEnvio = isset($arquivo['error']) ? (int) $arquivo['error'] : UPLOAD_ERR_NO_FILE;

        if ($erroEnvio === UPLOAD_ERR_INI_SIZE || $erroEnvio === UPLOAD_ERR_FORM_SIZE) {
            throw new \RuntimeException('O arquivo é maior que o limite de ' . ArquivoService::limiteMaximoMB() . ' MB do servidor.');
        }

        if ($erroEnvio !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Escolha o arquivo PDF da capa.');
        }

        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new \RuntimeException('Envio inválido. Tente de novo.');
        }

        if ((int) $arquivo['size'] > ArquivoService::limiteMaximoBytes()) {
            throw new \RuntimeException('O arquivo é maior que o limite de ' . ArquivoService::limiteMaximoMB() . ' MB do servidor.');
        }

        if (!EventoAnaisPdfFinalService::ehPdfPeloConteudo($arquivo['tmp_name'])) {
            throw new \RuntimeException('O arquivo enviado não é um PDF.');
        }

        $paginas = EventoAnaisPdfFinalService::conferirPdfLegivel($arquivo['tmp_name']);

        if ($paginas !== 1) {
            throw new \RuntimeException('A capa precisa ter uma página só, e este arquivo tem ' . $paginas . '. Exporte só a página da capa e envie de novo.');
        }

        $caminho = ArquivoPrivadoService::salvar($arquivo, 'anais/' . (int) $eventoId . '/capa');

        try {
            $anterior = $this->montagem->gravarCapa($eventoId, $caminho, EventoAnaisPdfFinalService::nomeOriginal($arquivo));
        } catch (\Throwable $e) {
            ArquivoPrivadoService::remover($caminho);

            throw $e;
        }

        if ($anterior !== null && $anterior !== $caminho) {
            ArquivoPrivadoService::remover($anterior);
        }
    }

    /**
     * Devolve true se havia capa para remover.
     */
    public function removerCapa($eventoId)
    {
        $anterior = $this->montagem->removerCapa($eventoId);

        if ($anterior === null) {
            return false;
        }

        ArquivoPrivadoService::remover($anterior);

        return true;
    }

    public function criarComissao($eventoId, $nome)
    {
        $nome = self::nomeObrigatorio($nome, self::NOME_COMISSAO_MAXIMO, 'Informe o nome da comissão.', 'O nome da comissão aceita até ' . self::NOME_COMISSAO_MAXIMO . ' caracteres.');

        return $this->comissoes->criar($eventoId, $nome);
    }

    public function atualizarComissao($eventoId, $id, $nome)
    {
        $nome = self::nomeObrigatorio($nome, self::NOME_COMISSAO_MAXIMO, 'Informe o nome da comissão.', 'O nome da comissão aceita até ' . self::NOME_COMISSAO_MAXIMO . ' caracteres.');

        if (!$this->comissoes->atualizar($eventoId, $id, $nome)) {
            throw new \RuntimeException('Comissão não encontrada.');
        }
    }

    public function removerComissao($eventoId, $id)
    {
        if (!$this->comissoes->remover($eventoId, $id)) {
            throw new \RuntimeException('Comissão não encontrada.');
        }
    }

    /**
     * Confere nome (obrigatorio), funcao e instituicao de um membro.
     * Devolve os dados prontos ou lanca RuntimeException.
     */
    public function validarMembro(array $post)
    {
        $nome = self::nomeObrigatorio(
            isset($post['nome']) ? $post['nome'] : '',
            self::NOME_MEMBRO_MAXIMO,
            'Informe o nome do membro.',
            'O nome do membro aceita até ' . self::NOME_MEMBRO_MAXIMO . ' caracteres.'
        );
        $funcao = self::textoOuNulo($post, 'funcao');
        $instituicao = self::textoOuNulo($post, 'instituicao');

        if ($funcao !== null && mb_strlen($funcao) > self::FUNCAO_MAXIMA) {
            throw new \RuntimeException('A função aceita até ' . self::FUNCAO_MAXIMA . ' caracteres.');
        }

        if ($instituicao !== null && mb_strlen($instituicao) > self::INSTITUICAO_MAXIMA) {
            throw new \RuntimeException('A instituição aceita até ' . self::INSTITUICAO_MAXIMA . ' caracteres.');
        }

        return ['nome' => $nome, 'funcao' => $funcao, 'instituicao' => $instituicao];
    }

    public function criarMembro($eventoId, array $post)
    {
        $dados = $this->validarMembro($post);
        $comissaoId = isset($post['comissao_id']) ? (int) $post['comissao_id'] : 0;
        $id = $this->comissoes->criarMembro($eventoId, $comissaoId, $dados);

        if ($id === null) {
            throw new \RuntimeException('Escolha a comissão do membro.');
        }

        return $id;
    }

    public function atualizarMembro($eventoId, $id, array $post)
    {
        $dados = $this->validarMembro($post);

        if (!$this->comissoes->atualizarMembro($eventoId, $id, $dados)) {
            throw new \RuntimeException('Membro não encontrado.');
        }
    }

    public function removerMembro($eventoId, $id)
    {
        if (!$this->comissoes->removerMembro($eventoId, $id)) {
            throw new \RuntimeException('Membro não encontrado.');
        }
    }

    /**
     * Trabalhos que constam nos Anais (aprovados e fora da lista de
     * exclusoes da Fase 53), na ordem do volume, com a situacao do PDF final
     * de cada um. Ordem manual primeiro (quando gravada); o resto, ou tudo
     * enquanto ninguem reordenou, segue a ordem dos eixos tematicos e, dentro
     * do eixo, o titulo. Sem CPF nem e-mail.
     */
    public function montarTrabalhos($eventoId)
    {
        $incluidos = [];

        foreach ($this->anais->listarTrabalhosIncluidos($eventoId) as $linha) {
            $incluidos[(int) $linha['id']] = true;
        }

        if (empty($incluidos)) {
            return [];
        }

        $posicaoEixo = [];

        foreach ((new TrabalhoEixoTematicoRepository())->listarPorEvento($eventoId) as $indice => $eixo) {
            $posicaoEixo[(int) $eixo['id']] = $indice;
        }

        $registros = $this->registros->listarPorEvento($eventoId);
        $linhas = [];

        foreach ((new TrabalhoRepository())->listarPorEvento($eventoId) as $trabalho) {
            $id = (int) $trabalho['id'];

            if (!isset($incluidos[$id])) {
                continue;
            }

            $registro = isset($registros[$id]) ? $registros[$id] : null;
            $eixoId = $trabalho['eixo_tematico_id'] !== null ? (int) $trabalho['eixo_tematico_id'] : null;

            $linhas[] = [
                'id' => $id,
                'titulo' => (string) $trabalho['titulo'],
                'eixo_id' => $eixoId,
                'eixo_nome' => $trabalho['eixo_nome'] !== null ? (string) $trabalho['eixo_nome'] : null,
                'autor_principal_nome' => $trabalho['autor_principal_nome'] !== null ? (string) $trabalho['autor_principal_nome'] : '',
                'ordem' => $registro !== null && $registro['ordem'] !== null ? (int) $registro['ordem'] : null,
                'arquivo' => $registro !== null && !empty($registro['arquivo_path']) ? [
                    'arquivo_path' => $registro['arquivo_path'],
                    'nome_original' => (string) $registro['nome_original'],
                    'paginas' => (int) $registro['paginas'],
                    'tamanho_bytes' => (int) $registro['tamanho_bytes'],
                    'enviado_em' => $registro['enviado_em'],
                ] : null,
                'posicao_eixo' => $eixoId !== null && isset($posicaoEixo[$eixoId]) ? $posicaoEixo[$eixoId] : PHP_INT_MAX,
                'titulo_comparacao' => normalizarNomeParaComparacao((string) $trabalho['titulo']),
            ];
        }

        usort($linhas, function ($a, $b) {
            if (($a['ordem'] === null) !== ($b['ordem'] === null)) {
                return $a['ordem'] === null ? 1 : -1;
            }

            if ($a['ordem'] !== null && $a['ordem'] !== $b['ordem']) {
                return $a['ordem'] < $b['ordem'] ? -1 : 1;
            }

            if ($a['posicao_eixo'] !== $b['posicao_eixo']) {
                return $a['posicao_eixo'] < $b['posicao_eixo'] ? -1 : 1;
            }

            $comparacao = strcmp($a['titulo_comparacao'], $b['titulo_comparacao']);

            if ($comparacao !== 0) {
                return $comparacao < 0 ? -1 : 1;
            }

            return $a['id'] < $b['id'] ? -1 : 1;
        });

        foreach ($linhas as &$linha) {
            unset($linha['posicao_eixo'], $linha['titulo_comparacao']);
        }
        unset($linha);

        return $linhas;
    }

    /**
     * Mensagem do que impede pedir a geracao agora, ou null. $trabalhos:
     * o retorno de montarTrabalhos(), quando quem chama ja o tem.
     */
    public function motivoQueImpedeGerar($eventoId, array $trabalhos = null)
    {
        $config = (new TrabalhoConfigRepository())->buscarPorEvento($eventoId);

        if ($config === null || empty($config['resultado_publicado_em'])) {
            return 'O resultado de Trabalhos ainda não foi publicado. Publique-o na aba Resultado antes de gerar o volume.';
        }

        $anais = $this->anais->buscarPorEvento($eventoId);

        if ($anais === null || trim((string) $anais['titulo']) === '') {
            return 'Salve o título dos Anais na aba Anais antes de gerar o volume.';
        }

        if ($trabalhos === null) {
            $trabalhos = $this->montarTrabalhos($eventoId);
        }

        if (empty($trabalhos)) {
            return 'Nenhum trabalho consta nos Anais. Confira a aba Trabalhos nos Anais.';
        }

        $pendentes = [];

        foreach ($trabalhos as $trabalho) {
            if ($trabalho['arquivo'] === null) {
                $pendentes[] = '"' . $trabalho['titulo'] . '" (nº ' . (int) $trabalho['id'] . ')';
            }
        }

        if (!empty($pendentes)) {
            $total = count($pendentes);
            $lista = implode('; ', array_slice($pendentes, 0, 10)) . ($total > 10 ? '; e mais ' . ($total - 10) : '');
            $inicio = $total === 1 ? 'Falta a versão final em PDF de 1 trabalho: ' : 'Falta a versão final em PDF de ' . $total . ' trabalhos: ';

            return $inicio . $lista . '. Prorrogue o prazo ou retire o trabalho na aba Trabalhos nos Anais.';
        }

        $montagem = $this->montagem->buscarPorEvento($eventoId);

        if ($montagem !== null && !empty($montagem['capa_path']) && ArquivoPrivadoService::caminhoFisico($montagem['capa_path']) === null) {
            return 'O arquivo da capa não foi encontrado no servidor. Envie a capa de novo ou remova-a.';
        }

        if ($this->geracoes->existeEmAndamento($eventoId)) {
            return 'Já existe um pedido de geração na fila ou em andamento. Aguarde ele terminar.';
        }

        return null;
    }

    /**
     * Confere tudo e registra o pedido na fila. Devolve o id do pedido;
     * lanca RuntimeException com mensagem para a tela.
     */
    public function solicitarGeracao($eventoId, $usuarioId)
    {
        $motivo = $this->motivoQueImpedeGerar($eventoId);

        if ($motivo !== null) {
            throw new \RuntimeException($motivo);
        }

        $id = $this->geracoes->solicitar($eventoId, $usuarioId);

        if ($id === null) {
            throw new \RuntimeException('Já existe um pedido de geração na fila ou em andamento para este evento. Aguarde ele terminar.');
        }

        return $id;
    }

    /**
     * O campo datetime-local chega como 'Y-m-d\TH:i' (com T, as vezes com
     * segundos); o banco guarda 'Y-m-d H:i:s'. Data impossivel (31 de
     * fevereiro, por exemplo) e' recusada, nunca ajustada em silencio.
     */
    private static function converterDataHora($texto)
    {
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $formato) {
            $data = \DateTime::createFromFormat('!' . $formato, $texto);

            if ($data === false) {
                continue;
            }

            $avisos = \DateTime::getLastErrors();

            if (is_array($avisos) && ($avisos['warning_count'] > 0 || $avisos['error_count'] > 0)) {
                continue;
            }

            return $data->format('Y-m-d H:i:s');
        }

        return null;
    }

    /**
     * Texto rico filtrado, ou null quando o editor volta vazio (so' espacos,
     * quebras ou marcacao sem texto). Imagem sozinha conta como conteudo.
     */
    private static function htmlOuNulo(array $post, $campo)
    {
        $html = isset($post[$campo]) ? (string) $post[$campo] : '';
        $texto = html_entity_decode(strip_tags($html, '<img>'), ENT_QUOTES, 'UTF-8');

        if (preg_replace('/[\s\x{00A0}]+/u', '', $texto) === '') {
            return null;
        }

        return sanitizarHtmlRico($html);
    }

    private static function textoOuNulo(array $post, $campo)
    {
        $texto = trim(isset($post[$campo]) ? (string) $post[$campo] : '');

        return $texto !== '' ? $texto : null;
    }

    private static function nomeObrigatorio($nome, $maximo, $mensagemVazio, $mensagemLongo)
    {
        $nome = trim((string) $nome);

        if ($nome === '') {
            throw new \RuntimeException($mensagemVazio);
        }

        if (mb_strlen($nome) > $maximo) {
            throw new \RuntimeException($mensagemLongo);
        }

        return $nome;
    }
}
