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
            throw new \RuntimeException('Envio inválido.');
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

    /**
     * Fase 41: icone do aplicativo web instalavel (PWA). Diferente de
     * salvar() (que preserva a proporcao original e converte pra WebP),
     * aqui a saida precisa ser SEMPRE em PNG - apple-touch-icon e' o que o
     * iOS/Safari le para o icone da tela inicial e nao reconhece WebP - e em
     * tamanhos EXATOS fixos (192/512/512 com margem "maskable"), porque e'
     * isso que o manifesto declara. Por isso nao reaproveita redimensionar()
     * (que so' limita o maximo, sem forcar dimensao): exige entrada quadrada
     * e gera os 3 arquivos com nome fixo, sobrescritos a cada novo upload
     * (paths fixos referenciados pelo manifesto - ConfiguracaoSistemaRepository::
     * marcarIconeAppAtualizado() cuida so' do cache-buster).
     */
    public function salvarIconeApp(array $arquivo, $corFundoMaskable)
    {
        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new \RuntimeException('Envio inválido.');
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            throw new \RuntimeException('Imagem maior que o limite de 4MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($arquivo['tmp_name']);

        if (!isset(self::TIPOS_PERMITIDOS[$mime])) {
            throw new \RuntimeException('Formato de imagem não suportado (use JPG, PNG, WEBP ou GIF).');
        }

        if (!function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('Este ambiente não tem suporte a processamento de imagem (GD): não é possível gerar o ícone do aplicativo.');
        }

        $origem = $this->carregar($arquivo['tmp_name'], $mime);
        $largura = imagesx($origem);
        $altura = imagesy($origem);

        if ($largura !== $altura) {
            imagedestroy($origem);
            throw new \RuntimeException('A imagem precisa ser quadrada (largura igual à altura).');
        }

        if ($largura < 512) {
            imagedestroy($origem);
            throw new \RuntimeException('A imagem precisa ter pelo menos 512×512 pixels.');
        }

        $pastaFisica = __DIR__ . '/../../assets/uploads/conteudo/icone-app';

        if (!is_dir($pastaFisica) && !mkdir($pastaFisica, 0755, true) && !is_dir($pastaFisica)) {
            imagedestroy($origem);
            throw new \RuntimeException('Não foi possível criar a pasta de destino do ícone.');
        }

        $tamanhos = [192 => 'icon-192.png', 512 => 'icon-512.png'];

        foreach ($tamanhos as $tamanho => $nomeArquivo) {
            $redimensionada = imagecreatetruecolor($tamanho, $tamanho);
            imagealphablending($redimensionada, false);
            imagesavealpha($redimensionada, true);
            imagecopyresampled($redimensionada, $origem, 0, 0, 0, 0, $tamanho, $tamanho, $largura, $altura);
            imagepng($redimensionada, $pastaFisica . '/' . $nomeArquivo);
            imagedestroy($redimensionada);
        }

        // Versao "maskable": ~20% de margem de seguranca em cada lado sobre a
        // cor de tema (fundo solido, sem transparencia) - launchers Android
        // recortam o icone em circulo/squircle e cortariam um desenho sem essa
        // folga.
        $tamanhoMaskable = 512;
        $margem = (int) round($tamanhoMaskable * 0.2);
        $areaUtil = $tamanhoMaskable - (2 * $margem);

        $maskable = imagecreatetruecolor($tamanhoMaskable, $tamanhoMaskable);
        [$r, $g, $b] = $this->hexParaRgb($corFundoMaskable);
        $corFundo = imagecolorallocate($maskable, $r, $g, $b);
        imagefill($maskable, 0, 0, $corFundo);
        imagecopyresampled($maskable, $origem, $margem, $margem, 0, 0, $areaUtil, $areaUtil, $largura, $altura);
        imagepng($maskable, $pastaFisica . '/icon-512-maskable.png');
        imagedestroy($maskable);

        imagedestroy($origem);
    }

    private function hexParaRgb($hex)
    {
        $hex = ltrim((string) $hex, '#');

        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return [255, 102, 0];
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    protected function carregar($caminho, $mime)
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
