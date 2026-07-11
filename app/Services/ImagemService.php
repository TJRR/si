<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Trata uploads de imagem do CMS do site publico (logo, imagens de secao):
 * redimensiona sem ampliar e converte para WebP quando o GD do ambiente
 * suporta (imagewebp() disponivel). Sem suporte a WebP, ou quando o chamador
 * passa $converterParaWebp=false (ex.: favicon, que precisa continuar em
 * PNG/ICO para o navegador reconhecer), guarda o arquivo como o admin
 * enviou, sem redimensionar nem reconverter.
 */
class ImagemService
{
    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private const TAMANHO_MAXIMO = 4 * 1024 * 1024;

    public function salvar(array $arquivo, $pasta, $larguraMax, $alturaMax, $converterParaWebp = true)
    {
        if (!preg_match('/^[a-z0-9_\-]+$/', $pasta)) {
            throw new \RuntimeException('Chave de destino inválida.');
        }

        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new \RuntimeException('Upload inválido.');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            throw new \RuntimeException('Imagem maior que o limite de 4MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($arquivo['tmp_name']);

        if (!isset(self::TIPOS_PERMITIDOS[$mime])) {
            throw new \RuntimeException('Formato de imagem não suportado (use JPG, PNG, WEBP ou GIF).');
        }

        $pastaBase = __DIR__ . '/../../assets/uploads/conteudo';
        $pastaFisica = $pastaBase . '/' . $pasta;

        if (!is_dir($pastaFisica) && !mkdir($pastaFisica, 0755, true) && !is_dir($pastaFisica)) {
            throw new \RuntimeException('Não foi possível criar a pasta de destino da imagem.');
        }

        if (!is_writable($pastaFisica)) {
            throw new \RuntimeException('Pasta de destino da imagem sem permissão de escrita.');
        }

        $baseReal = realpath($pastaBase);
        $pastaReal = realpath($pastaFisica);

        if ($baseReal === false || $pastaReal === false || strpos($pastaReal, $baseReal) !== 0) {
            throw new \RuntimeException('Caminho de destino fora da área permitida.');
        }

        $nomeArquivo = bin2hex(random_bytes(8));

        if (!$converterParaWebp || !function_exists('imagewebp')) {
            $extensao = self::TIPOS_PERMITIDOS[$mime];
            $caminhoRelativo = 'uploads/conteudo/' . $pasta . '/' . $nomeArquivo . '.' . $extensao;

            if (!move_uploaded_file($arquivo['tmp_name'], __DIR__ . '/../../assets/' . $caminhoRelativo)) {
                throw new \RuntimeException('Falha ao salvar a imagem no servidor.');
            }

            return $caminhoRelativo;
        }

        $origem = $this->carregar($arquivo['tmp_name'], $mime);
        $imagem = $this->redimensionar($origem, $larguraMax, $alturaMax);

        $caminhoRelativo = 'uploads/conteudo/' . $pasta . '/' . $nomeArquivo . '.webp';
        $gravado = imagewebp($imagem, __DIR__ . '/../../assets/' . $caminhoRelativo, 82);
        imagedestroy($imagem);

        if (!$gravado) {
            throw new \RuntimeException('Falha ao salvar a imagem convertida no servidor.');
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

    private function carregar($caminho, $mime)
    {
        switch ($mime) {
            case 'image/jpeg':
                return imagecreatefromjpeg($caminho);
            case 'image/png':
                $imagem = imagecreatefrompng($caminho);
                imagesavealpha($imagem, true);
                return $imagem;
            case 'image/webp':
                return imagecreatefromwebp($caminho);
            case 'image/gif':
                return imagecreatefromgif($caminho);
        }

        throw new \RuntimeException('Formato de imagem não suportado.');
    }

    private function redimensionar($imagem, $larguraMax, $alturaMax)
    {
        $largura = imagesx($imagem);
        $altura = imagesy($imagem);
        $escala = min($larguraMax / $largura, $alturaMax / $altura, 1);

        if ($escala >= 1) {
            return $imagem;
        }

        $novaLargura = max(1, (int) round($largura * $escala));
        $novaAltura = max(1, (int) round($altura * $escala));

        $destino = imagecreatetruecolor($novaLargura, $novaAltura);
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagecopyresampled($destino, $imagem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);
        imagedestroy($imagem);

        return $destino;
    }
}
