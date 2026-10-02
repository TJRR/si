<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Grava, serve e remove arquivo protegido, fora de assets/: so' este ponto
 * de entrada decide quem baixa o que. Quem chama valida o formato do
 * arquivo ANTES de chamar salvar() (ex.: UploadPdfValidador); este servico
 * nao valida conteudo. Ver Implantar.md, secao 13.6.
 */
class ArquivoPrivadoService
{
    /**
     * Fase 56: tipos de imagem que servirImagem() entrega, indexados pela
     * extensao que salvarImagem() gravou. Tabela fixa de proposito: o tipo
     * servido nunca depende do que a pessoa enviou.
     */
    const TIPOS_IMAGEM = [
        'webp' => 'image/webp',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
    ];

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
            throw new \RuntimeException('Envio inválido.');
        }

        // Tipo real do arquivo conferido aqui: ver Implantar.md, secao 13.6.
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
     * Resolve e serve um arquivo dentro de storage/uploads/. Sai com 404 direto
     * se qualquer conferencia falhar; quem chama nao precisa tratar retorno de
     * erro. Ver Implantar.md, secao 13.6.
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

    /**
     * Fase 56: ACRESCIMO. Grava um arquivo que ESTE sistema ja produziu (a
     * imagem de comprovacao ja validada e reduzida por
     * ImagemComprovacaoService), e nao um envio cru do navegador - por isso
     * recebe um caminho temporario proprio e nao usa move_uploaded_file().
     * Nada do que ja existia nesta classe foi alterado: salvar() continua
     * exclusivo de PDF.
     *
     * $extensao e' definida por quem chama a partir do que ELE gravou
     * (webp, jpg ou png), nunca a partir do nome enviado pela pessoa.
     */
    public static function salvarImagem($caminhoTemporario, $subpasta, $extensao)
    {
        foreach (explode('/', $subpasta) as $segmento) {
            if (!preg_match('/^[a-z0-9_\-]+$/', $segmento)) {
                throw new \RuntimeException('Pasta de destino inválida.');
            }
        }

        if (!isset(self::TIPOS_IMAGEM[$extensao])) {
            throw new \RuntimeException('Formato de imagem não suportado.');
        }

        if (!is_file($caminhoTemporario)) {
            throw new \RuntimeException('Arquivo temporário não encontrado.');
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

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
        $caminhoRelativo = $subpasta . '/' . $nomeArquivo;
        $destino = $pastaBase . '/' . $caminhoRelativo;

        // rename() falha entre sistemas de arquivos diferentes (o diretorio
        // temporario do PHP pode estar em outro ponto de montagem), entao a
        // copia com remocao e' a alternativa, nao um detalhe opcional.
        if (!@rename($caminhoTemporario, $destino)) {
            if (!copy($caminhoTemporario, $destino)) {
                throw new \RuntimeException('Falha ao salvar a imagem no servidor.');
            }

            @unlink($caminhoTemporario);
        }

        return $caminhoRelativo;
    }

    /**
     * Fase 56: ACRESCIMO. Mesma resolucao segura de servir(), para imagem.
     *
     * O tipo de conteudo sai de uma tabela fixa indexada pela EXTENSAO DO
     * ARQUIVO GRAVADO, que salvarImagem() gerou, no modelo de
     * TrabalhoArquivoValidador::contentTypePara(). Nunca do nome enviado
     * pela pessoa, que so' aparece no cabecalho de disposicao e vai
     * higienizado (aspas, quebra de linha e caractere de controle fora).
     */
    public static function servirImagem($caminhoRelativo, $nomeOriginal)
    {
        $baseReal = realpath(self::baseFisica());
        $caminho = $baseReal !== false ? realpath($baseReal . '/' . $caminhoRelativo) : false;

        if ($caminho === false || strpos($caminho, $baseReal . DIRECTORY_SEPARATOR) !== 0 || !is_file($caminho)) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        $extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));

        if (!isset(self::TIPOS_IMAGEM[$extensao])) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        $nome = preg_replace('/[^A-Za-z0-9._\- ]/', '', basename((string) $nomeOriginal));

        if ($nome === '' || $nome === null) {
            $nome = 'comprovacao.' . $extensao;
        }

        header('Content-Type: ' . self::TIPOS_IMAGEM[$extensao]);
        header('Content-Disposition: inline; filename="' . $nome . '"');
        header('Content-Length: ' . filesize($caminho));
        readfile($caminho);
        exit;
    }
}
