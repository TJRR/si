<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 49: validacao e armazenamento de arquivo de Trabalhos - classe
 * NOVA e isolada, inspirada no padrao de
 * ArquivoService::TIPOS_PERMITIDOS (app/Services/ArquivoService.php),
 * mas SEM alterar aquele arquivo, que continua servindo so' o Concurso
 * (Duvidas/Documentos) e a Midia, como antes. A lista de extensoes
 * ACEITAS para uma submissao especifica vem sempre de
 * evento_trabalhos_config.extensoes_editavel_json (configuracao do
 * evento), nunca fixa aqui - o que e' fixo aqui e' so' a capacidade
 * TECNICA de reconhecer cada formato (qual assinatura de conteudo real
 * esperar), que e' infraestrutura, nao regra de negocio de uma edicao.
 *
 * Ressalva registrada no plano da fase: para .pages e .wpd nao existe
 * assinatura de conteudo confiavel (ambos aceitam application/zip ou
 * application/octet-stream como fallback), por isso essas 2 extensoes
 * ficam desmarcadas por padrao na tela de configuracao do Admin - a
 * validacao aqui e' so' a melhor defesa possivel, nao uma garantia.
 * Para .docx/.odt (tambem ZIP por dentro), a validacao confere o MIME
 * real via finfo, mas nao abre o ZIP para conferir o manifesto interno
 * (deixaria a validacao mais forte, nao implementado nesta fase).
 */
class TrabalhoArquivoValidador
{
    private static $mimesPorExtensao = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
        'odt' => [
            'application/vnd.oasis.opendocument.text',
            'application/zip',
        ],
        'rtf' => ['text/rtf', 'application/rtf'],
        'pages' => ['application/vnd.apple.pages', 'application/zip', 'application/octet-stream'],
        'wpd' => ['application/vnd.wordperfect', 'application/octet-stream'],
    ];

    private static $contentTypePorExtensao = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'rtf' => 'application/rtf',
        'pages' => 'application/octet-stream',
        'wpd' => 'application/octet-stream',
    ];

    public static function extensoesReconhecidasPeloSistema()
    {
        return array_keys(self::$mimesPorExtensao);
    }

    public static function validar(array $arquivo, array $extensoesPermitidas, $limiteBytes)
    {
        if (!isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            return ['valido' => false, 'mensagem' => 'Nenhum arquivo enviado.'];
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return ['valido' => false, 'mensagem' => 'Falha no envio do arquivo.'];
        }

        if (!is_uploaded_file($arquivo['tmp_name'])) {
            return ['valido' => false, 'mensagem' => 'Arquivo inválido.'];
        }

        if ($arquivo['size'] > $limiteBytes) {
            $limiteMb = (int) round($limiteBytes / 1024 / 1024);

            return ['valido' => false, 'mensagem' => "Arquivo maior que o limite de {$limiteMb}MB."];
        }

        $extensaoEnviada = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extensaoEnviada, $extensoesPermitidas, true)) {
            return ['valido' => false, 'mensagem' => 'Extensão de arquivo não aceita para esta submissão.'];
        }

        if (!isset(self::$mimesPorExtensao[$extensaoEnviada])) {
            return ['valido' => false, 'mensagem' => 'Extensão de arquivo não suportada pelo sistema.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($arquivo['tmp_name']);

        if (!in_array($mimeReal, self::$mimesPorExtensao[$extensaoEnviada], true)) {
            return ['valido' => false, 'mensagem' => 'O conteúdo do arquivo não corresponde à extensão informada.'];
        }

        return ['valido' => true, 'mensagem' => null, 'extensao' => $extensaoEnviada];
    }

    /**
     * Salva fora da pasta publica, mesmo espirito de
     * ArquivoPrivadoService (storage/, nao assets/). Nome aleatorio, uma
     * subpasta por trabalho.
     */
    public static function salvar(array $arquivo, $extensao, $trabalhoId)
    {
        $pastaBase = __DIR__ . '/../../storage/uploads/trabalhos';
        $pastaFisica = $pastaBase . '/' . (int) $trabalhoId;

        if (!is_dir($pastaFisica) && !mkdir($pastaFisica, 0755, true) && !is_dir($pastaFisica)) {
            throw new \RuntimeException('Não foi possível criar a pasta de destino do arquivo.');
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
        $caminhoRelativo = (int) $trabalhoId . '/' . $nomeArquivo;
        $caminhoFisicoDestino = $pastaBase . '/' . $caminhoRelativo;

        $baseReal = realpath($pastaBase);
        $pastaReal = realpath($pastaFisica);

        if ($baseReal === false || $pastaReal === false || strpos($pastaReal, $baseReal) !== 0) {
            throw new \RuntimeException('Caminho de destino fora da área permitida.');
        }

        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoFisicoDestino)) {
            throw new \RuntimeException('Falha ao salvar o arquivo no servidor.');
        }

        return $caminhoRelativo;
    }

    public static function caminhoFisico($caminhoRelativo)
    {
        $pastaBase = __DIR__ . '/../../storage/uploads/trabalhos';
        $baseReal = realpath($pastaBase);
        $caminhoCompleto = realpath($pastaBase . '/' . $caminhoRelativo);

        if ($baseReal === false || $caminhoCompleto === false || strpos($caminhoCompleto, $baseReal) !== 0 || !is_file($caminhoCompleto)) {
            return null;
        }

        return $caminhoCompleto;
    }

    public static function contentTypePara($caminhoRelativo)
    {
        $extensao = strtolower(pathinfo($caminhoRelativo, PATHINFO_EXTENSION));

        return isset(self::$contentTypePorExtensao[$extensao]) ? self::$contentTypePorExtensao[$extensao] : 'application/octet-stream';
    }

    public static function remover($caminhoRelativo)
    {
        $caminho = self::caminhoFisico($caminhoRelativo);

        if ($caminho !== null) {
            unlink($caminho);
        }
    }
}
