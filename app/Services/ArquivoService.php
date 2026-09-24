<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 18: upload de arquivo publico nao-imagem (PDF de edital/documento,
 * video), reaproveitado por Documentos (4.6) e Biblioteca de Midia (4.5).
 * Mesma validacao de mime real (finfo, nunca confia no Content-Type do
 * navegador) e nome de arquivo aleatorio do padrao ja usado em
 * UploadPdfValidador/ImagemService - mas guarda em assets/uploads/arquivos
 * (publico, diferente do storage/ privado usado pelos PDFs de submissao,
 * ja que editais/documentos SAO para acesso publico direto).
 *
 * Fase 29 (Tira-Duvidas): passou a aceitar tambem imagem (anexo de duvida
 * pode ser print de tela) e o limite deixou de ser fixo (15MB) - agora vem
 * do proprio php.ini (upload_max_filesize/post_max_size), conforme pedido
 * explicito do requisito. So' pra cima ou igual ao limite fixo anterior nos
 * usos ja existentes (Documentos/Midia) - sem regressao.
 *
 * Reabertura da Fase 51 (segunda rodada): Documentos do Evento precisam
 * aceitar tambem documento editavel (modelo do resumo expandido, em Word).
 * Como este servico e' compartilhado com Documentos do Concurso, Midia e
 * Tira-Duvidas, a ampliacao e' opt-in por chamada ($aceitarDocumentosEditaveis
 * em salvar()): quem nao pede continua aceitando exatamente o que aceitava.
 */
class ArquivoService
{
    private const TIPOS_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'video/mp4' => 'mp4',
        'image/webp' => 'webp',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Documentos editaveis aceitos so' quando a chamada pede (Documentos do
     * Evento). O tipo vem do CONTEUDO (finfo), nunca do nome; o arquivo e'
     * gravado com a extensao decidida aqui, com nome aleatorio.
     */
    private const TIPOS_DOCUMENTO_EDITAVEL = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc',
        'application/vnd.oasis.opendocument.text' => 'odt',
    ];

    /**
     * .docx e .odt sao ZIP por dentro e algumas versoes da biblioteca de
     * deteccao devolvem so' application/zip para eles: esse tipo generico so'
     * vale quando a extensao do nome do arquivo confirma um dos dois.
     */
    private const EXTENSOES_ZIP_DOCUMENTO_EDITAVEL = ['docx', 'odt'];

    /**
     * Menor entre upload_max_filesize e post_max_size do php.ini em vigor -
     * e' o limite real que o PHP aplica antes mesmo do codigo rodar, entao
     * nao adianta validar so' um dos dois.
     */
    public static function limiteMaximoBytes()
    {
        return min(
            self::converterParaBytes(ini_get('upload_max_filesize')),
            self::converterParaBytes(ini_get('post_max_size'))
        );
    }

    public static function limiteMaximoMB()
    {
        return round(self::limiteMaximoBytes() / 1024 / 1024, 1);
    }

    private static function converterParaBytes($valorIni)
    {
        $valorIni = trim((string) $valorIni);
        $numero = (int) $valorIni;

        switch (strtolower(substr($valorIni, -1))) {
            case 'g':
                $numero *= 1024;
                // sem break: G tambem passa por M e K
            case 'm':
                $numero *= 1024;
                // sem break: M tambem passa por K
            case 'k':
                $numero *= 1024;
        }

        return $numero;
    }

    public function salvar(array $arquivo, $pasta, $aceitarDocumentosEditaveis = false)
    {
        if (!preg_match('/^[a-z0-9_\-]+$/', $pasta)) {
            throw new \RuntimeException('Chave de destino inválida.');
        }

        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new \RuntimeException('Envio inválido.');
        }

        if ($arquivo['size'] > self::limiteMaximoBytes()) {
            throw new \RuntimeException('Arquivo maior que o limite de ' . self::limiteMaximoMB() . 'MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($arquivo['tmp_name']);

        $extensao = isset(self::TIPOS_PERMITIDOS[$mime]) ? self::TIPOS_PERMITIDOS[$mime] : null;

        if ($extensao === null && $aceitarDocumentosEditaveis) {
            $extensao = self::extensaoDeDocumentoEditavel($mime, isset($arquivo['name']) ? $arquivo['name'] : '');
        }

        if ($extensao === null) {
            throw new \RuntimeException($aceitarDocumentosEditaveis
                ? 'Formato de arquivo não suportado (use PDF, documento do Word ou do LibreOffice, imagem ou MP4).'
                : 'Formato de arquivo não suportado (use PDF, imagem ou MP4).');
        }

        $pastaBase = __DIR__ . '/../../assets/uploads/arquivos';
        $pastaFisica = $pastaBase . '/' . $pasta;

        if (!is_dir($pastaFisica) && !mkdir($pastaFisica, 0755, true) && !is_dir($pastaFisica)) {
            throw new \RuntimeException('Não foi possível criar a pasta de destino do arquivo.');
        }

        if (!is_writable($pastaFisica)) {
            throw new \RuntimeException('Pasta de destino do arquivo sem permissão de escrita.');
        }

        $baseReal = realpath($pastaBase);
        $pastaReal = realpath($pastaFisica);

        if ($baseReal === false || $pastaReal === false || strpos($pastaReal, $baseReal) !== 0) {
            throw new \RuntimeException('Caminho de destino fora da área permitida.');
        }

        $nomeArquivo = bin2hex(random_bytes(16));
        $caminhoRelativo = 'uploads/arquivos/' . $pasta . '/' . $nomeArquivo . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], __DIR__ . '/../../assets/' . $caminhoRelativo)) {
            throw new \RuntimeException('Falha ao salvar o arquivo no servidor.');
        }

        return $caminhoRelativo;
    }

    /**
     * Extensao de um documento editavel a partir do tipo detectado, ou nulo.
     * O tipo e' dividido nos pontos onde comeca um novo tipo (application/ ou
     * text/): com certos arquivos Word a biblioteca do servidor devolve o
     * mesmo tipo escrito duas vezes seguidas, e a comparacao exata recusaria
     * um .docx verdadeiro (mesmo defeito tratado em TrabalhoArquivoValidador).
     */
    private static function extensaoDeDocumentoEditavel($mime, $nomeOriginal)
    {
        $tipos = preg_split('#(?=(?:application|text)/)#', (string) $mime, -1, PREG_SPLIT_NO_EMPTY);
        $tipos = array_map('trim', $tipos);

        foreach ($tipos as $tipo) {
            if (isset(self::TIPOS_DOCUMENTO_EDITAVEL[$tipo])) {
                return self::TIPOS_DOCUMENTO_EDITAVEL[$tipo];
            }
        }

        $extensaoDoNome = strtolower(pathinfo((string) $nomeOriginal, PATHINFO_EXTENSION));

        if (in_array('application/zip', $tipos, true) && in_array($extensaoDoNome, self::EXTENSOES_ZIP_DOCUMENTO_EDITAVEL, true)) {
            return $extensaoDoNome;
        }

        return null;
    }

    public function remover($caminhoRelativo)
    {
        if (empty($caminhoRelativo)) {
            return;
        }

        $caminhoFisico = __DIR__ . '/../../assets/' . $caminhoRelativo;

        if (is_file($caminhoFisico)) {
            unlink($caminhoFisico);
        }
    }
}
