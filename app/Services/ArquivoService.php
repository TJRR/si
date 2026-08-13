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

    public function salvar(array $arquivo, $pasta)
    {
        if (!preg_match('/^[a-z0-9_\-]+$/', $pasta)) {
            throw new \RuntimeException('Chave de destino inválida.');
        }

        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new \RuntimeException('Upload inválido.');
        }

        if ($arquivo['size'] > self::limiteMaximoBytes()) {
            throw new \RuntimeException('Arquivo maior que o limite de ' . self::limiteMaximoMB() . 'MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($arquivo['tmp_name']);

        if (!isset(self::TIPOS_PERMITIDOS[$mime])) {
            throw new \RuntimeException('Formato de arquivo não suportado (use PDF, imagem ou MP4).');
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

        $extensao = self::TIPOS_PERMITIDOS[$mime];
        $nomeArquivo = bin2hex(random_bytes(16));
        $caminhoRelativo = 'uploads/arquivos/' . $pasta . '/' . $nomeArquivo . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], __DIR__ . '/../../assets/' . $caminhoRelativo)) {
            throw new \RuntimeException('Falha ao salvar o arquivo no servidor.');
        }

        return $caminhoRelativo;
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
