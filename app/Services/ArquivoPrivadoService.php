<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 30: extrai o padrao de gravar/servir arquivo protegido (fora de
 * assets/, sem controle de acesso pelo servidor web - so' este ponto de
 * entrada decide quem baixa o que), ja usado antes em SubmissaoService
 * (grava) e AvaliacaoController::baixarArquivo() (serve, com a protecao
 * contra path traversal ja corrigida num commit anterior). Quem chama
 * valida o formato do arquivo ANTES de chamar salvar() (ex.:
 * UploadPdfValidador) - este servico so' grava/serve/remove, nao valida
 * conteudo.
 */
class ArquivoPrivadoService
{
    private static function baseFisica()
    {
        return __DIR__ . '/../../storage/uploads';
    }

    /**
     * $arquivo e' o array de $_FILES ja validado por quem chamou. $subpasta
     * pode conter '/' (ex.: "requerimentos/123") - cada segmento e'
     * validado separadamente, mesmo cuidado de ArquivoService::salvar().
     */
    public static function salvar(array $arquivo, $subpasta)
    {
        foreach (explode('/', $subpasta) as $segmento) {
            if (!preg_match('/^[a-z0-9_\-]+$/', $segmento)) {
                throw new \RuntimeException('Chave de destino inválida.');
            }
        }

        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new \RuntimeException('Upload inválido.');
        }

        // Fase 31 (Auditoria de Seguranca, achado #15): validacao de MIME
        // real (nao extensao/Content-Type declarado) direto aqui, em vez de
        // depender so' de quem chama (UploadPdfValidador) - fecha o
        // contrato implicito, ja que este metodo so' grava ".pdf" mesmo.
        $tipoReal = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $arquivo['tmp_name']);

        if ($tipoReal !== 'application/pdf') {
            throw new \RuntimeException('O arquivo enviado não é um PDF válido.');
        }

        $pastaBase = self::baseFisica();
        $pastaFisica = $pastaBase . '/' . $subpasta;

        if (!is_dir($pastaFisica) && !mkdir($pastaFisica, 0755, true) && !is_dir($pastaFisica)) {
            throw new \RuntimeException('Não foi possível criar a pasta de destino do arquivo.');
        }

        $baseReal = realpath($pastaBase);
        $pastaReal = realpath($pastaFisica);

        if ($baseReal === false || $pastaReal === false || strpos($pastaReal, $baseReal) !== 0) {
            throw new \RuntimeException('Caminho de destino fora da área permitida.');
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.pdf';
        $caminhoRelativo = $subpasta . '/' . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $pastaBase . '/' . $caminhoRelativo)) {
            throw new \RuntimeException('Falha ao salvar o arquivo no servidor.');
        }

        return $caminhoRelativo;
    }

    /**
     * Resolve e serve um arquivo dentro de storage/uploads/ - mesma
     * protecao contra path traversal ja corrigida em
     * AvaliacaoController::baixarArquivo(): realpath() da base e do alvo,
     * strpos() comparando prefixo (com DIRECTORY_SEPARATOR no final do
     * base, pra nao colar em falso-positivo tipo "uploads-outracoisa"),
     * is_file(). Sai com 404 direto se qualquer checagem falhar - quem
     * chama nao precisa tratar retorno de erro.
     */
    public static function servir($caminhoRelativo, $nomeOriginal)
    {
        $baseReal = realpath(self::baseFisica());
        $caminho = $baseReal !== false ? realpath($baseReal . '/' . $caminhoRelativo) : false;

        if ($caminho === false || strpos($caminho, $baseReal . DIRECTORY_SEPARATOR) !== 0 || !is_file($caminho)) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($nomeOriginal) . '"');
        header('Content-Length: ' . filesize($caminho));
        readfile($caminho);
        exit;
    }

    /**
     * Mesma resolucao segura de servir(), mas devolve o caminho fisico em
     * vez de servir o arquivo - usado quando algo precisa LER o conteudo do
     * arquivo no servidor (ex.: CertificadoExtratorService), nao so'
     * devolver pro navegador. Null se o arquivo nao existir/estiver fora da
     * area permitida.
     */
    public static function caminhoFisico($caminhoRelativo)
    {
        $baseReal = realpath(self::baseFisica());
        $caminho = $baseReal !== false ? realpath($baseReal . '/' . $caminhoRelativo) : false;

        if ($caminho === false || strpos($caminho, $baseReal . DIRECTORY_SEPARATOR) !== 0 || !is_file($caminho)) {
            return null;
        }

        return $caminho;
    }

    /**
     * Remove o arquivo fisico se existir, mesma resolucao segura de
     * servir() antes de apagar - usado so' pelo expurgo
     * (ModeloDocumentoAdminController::expurgar()).
     */
    public static function remover($caminhoRelativo)
    {
        $baseReal = realpath(self::baseFisica());
        $caminho = $baseReal !== false ? realpath($baseReal . '/' . $caminhoRelativo) : false;

        if ($caminho !== false && strpos($caminho, $baseReal . DIRECTORY_SEPARATOR) === 0 && is_file($caminho)) {
            unlink($caminho);
        }
    }
}
