<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EventoAnaisMontagemRepository;
use App\Repositories\EventoAnaisRepository;
use App\Repositories\EventoAnaisTrabalhoRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoRepository;
use setasign\Fpdi\Fpdi;

/**
 * Fase 54: versao final do trabalho para o volume dos Anais, enviada pelo
 * PROPRIO autor principal no aplicativo, dentro do prazo configurado na
 * Montagem dos Anais. O Administrador so' acompanha; nao envia em nome do
 * autor.
 *
 * Todo PDF e' aberto com a FPDI no momento do envio: o leitor gratuito dela
 * recusa PDF com tabela de referencia cruzada comprimida (comum em PDF
 * exportado por editores recentes), e descobrir isso so' na hora de gerar o
 * volume travaria a geracao inteira.
 */
class EventoAnaisPdfFinalService
{
    public const MENSAGEM_PDF_ILEGIVEL = 'Não foi possível ler este PDF. Salve o arquivo de novo como PDF/A-1b, sem senha de proteção, e envie outra vez. No LibreOffice: Arquivo, Exportar como PDF, marque "Arquivo PDF/A" e escolha PDF/A-1b. No Word: Salvar como, escolha o tipo PDF, clique em Opções e marque "Compatível com ISO 19005-1 (PDF/A)".';

    private const MENSAGEM_FALHA_TECNICA = 'Não foi possível receber o arquivo agora. Tente de novo em alguns minutos; se persistir, procure a organização do evento.';

    private $registros;

    public function __construct()
    {
        $this->registros = new EventoAnaisTrabalhoRepository();
    }

    /**
     * Dados do quadro "Versao final para os Anais" na tela do trabalho, ou
     * null quando o quadro nao deve aparecer: antes do resultado publicado,
     * para trabalho que nao consta nos Anais, e enquanto nao houver prazo nem
     * arquivo enviado. Recurso opcional: falha de banco aqui nunca derruba a
     * tela do autor.
     */
    public function blocoParaAutor(array $trabalho, $usuarioId)
    {
        $trabalhoId = isset($trabalho['id']) ? (int) $trabalho['id'] : 0;
        $eventoId = isset($trabalho['evento_id']) ? (int) $trabalho['evento_id'] : 0;

        try {
            $config = (new TrabalhoConfigRepository())->buscarPorEvento($eventoId);

            if ($config === null || empty($config['resultado_publicado_em'])) {
                return null;
            }

            if (!$this->constaNosAnais($eventoId, $trabalhoId)) {
                return null;
            }

            $montagem = (new EventoAnaisMontagemRepository())->buscarPorEvento($eventoId);
            $prazo = $montagem !== null && !empty($montagem['prazo_pdf_final']) ? (string) $montagem['prazo_pdf_final'] : null;
            $registro = $this->registros->buscarPorTrabalho($trabalhoId);
            $temArquivo = $registro !== null && (int) $registro['evento_id'] === $eventoId && !empty($registro['arquivo_path']);

            if ($prazo === null && !$temArquivo) {
                return null;
            }

            $ehAutorPrincipal = $this->ehAutorPrincipal($trabalhoId, $usuarioId);
            $prazoEncerrado = $prazo !== null && strtotime($prazo) < time();

            return [
                'pode_enviar' => $ehAutorPrincipal && $prazo !== null && !$prazoEncerrado,
                'eh_autor_principal' => $ehAutorPrincipal,
                'prazo' => $prazo,
                'prazo_encerrado' => $prazoEncerrado,
                'instrucoes_html' => $montagem !== null && !empty($montagem['instrucoes_pdf_final_html'])
                    ? sanitizarHtmlRico($montagem['instrucoes_pdf_final_html'])
                    : '',
                'arquivo' => $temArquivo ? [
                    'nome_original' => (string) $registro['nome_original'],
                    'paginas' => (int) $registro['paginas'],
                    'enviado_em' => $registro['enviado_em'],
                ] : null,
                'limite_mb' => $this->limiteMb($config),
            ];
        } catch (\PDOException $e) {
            error_log('[Anais] falha ao montar o quadro da versao final do trabalho ' . $trabalhoId . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Recebe o PDF final enviado pelo autor principal. Confere tudo de novo
     * (nao confia no que a tela mostrou): trabalho, resultado publicado,
     * trabalho nos Anais, autor principal, prazo aberto e o proprio arquivo.
     * O arquivo anterior so' e' apagado depois da gravacao confirmada.
     *
     * Devolve ['ok' => bool, 'mensagem' => texto para a tela].
     */
    public function enviarPeloAutor(array $trabalho, array $arquivo, $usuarioId)
    {
        $trabalhoId = isset($trabalho['id']) ? (int) $trabalho['id'] : 0;
        $eventoId = isset($trabalho['evento_id']) ? (int) $trabalho['evento_id'] : 0;

        try {
            $impedimento = $this->motivoQueImpedeEnvio($eventoId, $trabalhoId, $usuarioId);

            if ($impedimento !== null) {
                return ['ok' => false, 'mensagem' => $impedimento];
            }

            $limiteMb = $this->limiteMb((new TrabalhoConfigRepository())->buscarPorEvento($eventoId));
            $erroArquivo = $this->conferirArquivoEnviado($arquivo, $limiteMb);

            if ($erroArquivo !== null) {
                return ['ok' => false, 'mensagem' => $erroArquivo];
            }

            $paginas = self::conferirPdfLegivel($arquivo['tmp_name']);

            $caminho = ArquivoPrivadoService::salvar($arquivo, 'anais/' . $eventoId . '/trabalhos');
            $fisico = ArquivoPrivadoService::caminhoFisico($caminho);

            try {
                $anterior = $this->registros->gravarArquivo($eventoId, $trabalhoId, [
                    'arquivo_path' => $caminho,
                    'nome_original' => self::nomeOriginal($arquivo),
                    'paginas' => $paginas,
                    'tamanho_bytes' => $fisico !== null ? (int) filesize($fisico) : (int) $arquivo['size'],
                    'sha256' => $fisico !== null ? hash_file('sha256', $fisico) : null,
                    'enviado_por' => (int) $usuarioId,
                ]);
            } catch (\Throwable $e) {
                ArquivoPrivadoService::remover($caminho);

                throw $e;
            }

            if ($anterior !== null && $anterior !== $caminho) {
                ArquivoPrivadoService::remover($anterior);
            }

            return [
                'ok' => true,
                'mensagem' => 'Versão final recebida (' . self::rotuloPaginas($paginas) . '). Até o fim do prazo, você pode trocar o arquivo, se precisar.',
            ];
        } catch (\PDOException $e) {
            error_log('[Anais] falha ao gravar a versao final do trabalho ' . $trabalhoId . ': ' . $e->getMessage());

            return ['ok' => false, 'mensagem' => self::MENSAGEM_FALHA_TECNICA];
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'mensagem' => $e->getMessage()];
        } catch (\Throwable $e) {
            error_log('[Anais] falha ao receber a versao final do trabalho ' . $trabalhoId . ': ' . get_class($e) . ': ' . $e->getMessage());

            return ['ok' => false, 'mensagem' => self::MENSAGEM_FALHA_TECNICA];
        }
    }

    /**
     * Abre o PDF com a FPDI e importa todas as paginas, do mesmo jeito que a
     * geracao do volume fara. Devolve a quantidade de paginas; lanca
     * RuntimeException com a orientacao de PDF/A-1b se nao conseguir ler.
     * Usado tambem pela capa e pela propria geracao.
     */
    public static function conferirPdfLegivel($caminho)
    {
        self::garantirMemoria(256);

        try {
            $leitor = new Fpdi();
            $paginas = (int) $leitor->setSourceFile($caminho);

            for ($pagina = 1; $pagina <= $paginas; $pagina++) {
                $leitor->importPage($pagina);
            }

            $leitor->cleanUp(true);
        } catch (\Throwable $e) {
            error_log('[Anais] PDF recusado pela leitura: ' . get_class($e) . ': ' . $e->getMessage());

            throw new \RuntimeException(self::MENSAGEM_PDF_ILEGIVEL);
        }

        if ($paginas < 1) {
            throw new \RuntimeException('O PDF enviado não tem nenhuma página.');
        }

        return $paginas;
    }

    /**
     * PDF pelo conteudo, nunca pelo nome. O tipo devolvido pela biblioteca
     * do servidor as vezes vem repetido e grudado (mesmo defeito tratado em
     * TrabalhoArquivoValidador), por isso a separacao antes de comparar.
     */
    public static function ehPdfPeloConteudo($caminho)
    {
        $bruto = (new \finfo(FILEINFO_MIME_TYPE))->file($caminho);

        if (!is_string($bruto) || $bruto === '') {
            return false;
        }

        foreach (preg_split('#(?=(?:application|text)/)#', $bruto, -1, PREG_SPLIT_NO_EMPTY) as $tipo) {
            if (trim($tipo) === 'application/pdf') {
                return true;
            }
        }

        return false;
    }

    /**
     * Nome do arquivo como a pessoa enviou, sem caminho, sem caracteres de
     * controle e sem bytes invalidos (a coluna e' utf8mb4).
     */
    public static function nomeOriginal(array $arquivo)
    {
        $nome = basename(isset($arquivo['name']) ? (string) $arquivo['name'] : '');
        $convertido = @iconv('UTF-8', 'UTF-8//IGNORE', $nome);
        $nome = preg_replace('/[\x00-\x1f\x7f]/u', '', $convertido !== false ? $convertido : '');

        return mb_substr($nome !== null ? $nome : '', 0, 255, 'UTF-8');
    }

    public static function rotuloPaginas($paginas)
    {
        return (int) $paginas === 1 ? '1 página' : (int) $paginas . ' páginas';
    }

    /**
     * So' eleva o limite de memoria, nunca reduz: a rotina de geracao roda
     * com um limite maior e tambem passa por conferirPdfLegivel().
     */
    private static function garantirMemoria($megabytes)
    {
        $atual = trim((string) ini_get('memory_limit'));

        if ($atual === '' || $atual === '-1') {
            return;
        }

        $bytes = (float) $atual;
        $unidade = strtolower(substr($atual, -1));

        if ($unidade === 'g') {
            $bytes *= 1073741824;
        } elseif ($unidade === 'm') {
            $bytes *= 1048576;
        } elseif ($unidade === 'k') {
            $bytes *= 1024;
        }

        if ($bytes < $megabytes * 1048576) {
            ini_set('memory_limit', $megabytes . 'M');
        }
    }

    private function motivoQueImpedeEnvio($eventoId, $trabalhoId, $usuarioId)
    {
        $trabalho = (new TrabalhoRepository())->buscarPorId($trabalhoId);

        if ($trabalho === null || (int) $trabalho['evento_id'] !== (int) $eventoId) {
            return 'Trabalho não encontrado.';
        }

        $config = (new TrabalhoConfigRepository())->buscarPorEvento($eventoId);

        if ($config === null || empty($config['resultado_publicado_em'])) {
            return 'O envio da versão final para os Anais ainda não está aberto.';
        }

        if (!$this->constaNosAnais($eventoId, $trabalhoId)) {
            return 'Este trabalho não consta nos Anais.';
        }

        if (!$this->ehAutorPrincipal($trabalhoId, $usuarioId)) {
            return 'Só o autor principal envia a versão final.';
        }

        $montagem = (new EventoAnaisMontagemRepository())->buscarPorEvento($eventoId);

        if ($montagem === null || empty($montagem['prazo_pdf_final'])) {
            return 'O envio da versão final não está aberto no momento.';
        }

        if (strtotime($montagem['prazo_pdf_final']) < time()) {
            return 'O prazo para o envio da versão final terminou em ' . formatarDataHora($montagem['prazo_pdf_final']) . '.';
        }

        return null;
    }

    private function conferirArquivoEnviado(array $arquivo, $limiteMb)
    {
        $erroEnvio = isset($arquivo['error']) ? (int) $arquivo['error'] : UPLOAD_ERR_NO_FILE;

        if ($erroEnvio === UPLOAD_ERR_NO_FILE) {
            return 'Escolha o arquivo PDF da versão final.';
        }

        if ($erroEnvio === UPLOAD_ERR_INI_SIZE || $erroEnvio === UPLOAD_ERR_FORM_SIZE) {
            return 'O arquivo é maior que o limite de ' . (int) $limiteMb . ' MB.';
        }

        if ($erroEnvio !== UPLOAD_ERR_OK) {
            return 'Falha no envio do arquivo. Tente de novo.';
        }

        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            return 'Envio inválido. Tente de novo.';
        }

        if ((int) $arquivo['size'] > (int) $limiteMb * 1048576) {
            return 'O arquivo é maior que o limite de ' . (int) $limiteMb . ' MB.';
        }

        if (!self::ehPdfPeloConteudo($arquivo['tmp_name'])) {
            return 'O arquivo enviado não é um PDF. Envie a versão final em PDF.';
        }

        return null;
    }

    /**
     * Consta nos Anais = aprovado e fora da lista de exclusoes (mesma regra
     * da Fase 53), independentemente de os Anais ja estarem publicados.
     */
    private function constaNosAnais($eventoId, $trabalhoId)
    {
        foreach ((new EventoAnaisRepository())->listarTrabalhosIncluidos($eventoId) as $linha) {
            if ((int) $linha['id'] === (int) $trabalhoId) {
                return true;
            }
        }

        return false;
    }

    private function ehAutorPrincipal($trabalhoId, $usuarioId)
    {
        $autor = (new TrabalhoAutorRepository())->buscarAutorPrincipal($trabalhoId);

        return $autor !== null && !empty($autor['usuario_id']) && (int) $autor['usuario_id'] === (int) $usuarioId;
    }

    /**
     * Limite de Trabalhos do evento (tamanho_maximo_mb), sem passar do que o
     * PHP do servidor aceita receber.
     */
    private function limiteMb(array $config = null)
    {
        $limite = $config !== null && (int) $config['tamanho_maximo_mb'] > 0 ? (int) $config['tamanho_maximo_mb'] : 15;
        $servidor = (int) floor(ArquivoService::limiteMaximoMB());

        if ($servidor > 0 && $servidor < $limite) {
            $limite = $servidor;
        }

        return $limite;
    }
}
