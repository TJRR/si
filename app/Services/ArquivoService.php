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
 */
class ArquivoService
{
    private const TAMANHO_MAXIMO = 15 * 1024 * 1024;

    private const TIPOS_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'video/mp4' => 'mp4',
    ];

    public function salvar(array $arquivo, $pasta)
    {
        if (!preg_match('/^[a-z0-9_\-]+$/', $pasta)) {
            throw new \RuntimeException('Chave de destino inválida.');
        }

        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new \RuntimeException('Upload inválido.');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            throw new \RuntimeException('Arquivo maior que o limite de 15MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($arquivo['tmp_name']);

        if (!isset(self::TIPOS_PERMITIDOS[$mime])) {
            throw new \RuntimeException('Formato de arquivo não suportado (use PDF ou MP4).');
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
