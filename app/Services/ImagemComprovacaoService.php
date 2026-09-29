<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 56: imagem de comprovacao de divulgacao enviada pelo participante.
 *
 * Existe separado de ImagemService porque aquele grava em
 * assets/uploads/conteudo/, que o servidor web entrega direto por endereco,
 * sem controle de acesso nenhum. Uma imagem de tela do Instagram traz nome,
 * foto e publicacao de terceiros, entao ela precisa da area privada
 * (storage/uploads/), entregue so' por controlador que confere quem pede.
 *
 * A validacao de tipo pelo conteudo e o redimensionamento sao copia
 * proposital do padrao de ImagemService (que e' codigo ativo e nao se
 * reescreve, precedente das Fases 54 e 55). A divida esta' registrada na
 * pendencia 25 de SGSI/pendencias.md.
 *
 * ORDEM IMPORTA: a imagem e' validada e reduzida em arquivo temporario
 * proprio, o resumo criptografico e' calculado sobre o arquivo JA REDUZIDO,
 * e so' depois de quem chama conferir a duplicidade e' que o arquivo vai
 * para a area privada. move_uploaded_file() e rename() nao participam da
 * transacao do banco: arquivo gravado antes da hora vira resto em disco.
 */
class ImagemComprovacaoService
{
    const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    const TAMANHO_MAXIMO = 4 * 1024 * 1024;

    const LARGURA_MAXIMA = 1600;

    const ALTURA_MAXIMA = 1600;

    /**
     * Valida e reduz o envio, devolvendo
     * ['caminho_temporario' => ..., 'extensao' => ..., 'sha256' => ...,
     *  'bytes' => int, 'nome_original' => ...].
     *
     * Nada e' gravado na area privada aqui. Quem chama confere a duplicidade
     * com o resumo devolvido e, so' entao, chama gravar(). Em qualquer
     * recusa, lanca RuntimeException com a mensagem que vai para a tela, e o
     * arquivo temporario proprio ja foi removido.
     */
    public function prepararEnvio(array $arquivo)
    {
        if (!isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException('Envie a imagem da tela com a comprovação.');
        }

        if ($arquivo['error'] === UPLOAD_ERR_INI_SIZE || $arquivo['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new \RuntimeException('A imagem é maior que o limite de ' . $this->limiteMb() . 'MB.');
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Não foi possível receber a imagem. Tente enviar de novo.');
        }

        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new \RuntimeException('Envio inválido.');
        }

        if (filesize($arquivo['tmp_name']) > self::TAMANHO_MAXIMO) {
            throw new \RuntimeException('A imagem é maior que o limite de ' . $this->limiteMb() . 'MB.');
        }

        $informacoes = new \finfo(FILEINFO_MIME_TYPE);
        $tipoReal = $informacoes->file($arquivo['tmp_name']);

        if (!isset(self::TIPOS_PERMITIDOS[$tipoReal])) {
            throw new \RuntimeException('Envie uma imagem em JPEG, PNG ou WebP.');
        }

        $imagem = $this->carregar($arquivo['tmp_name'], $tipoReal);

        if ($imagem === false) {
            throw new \RuntimeException('Não foi possível ler a imagem enviada. Tente gerar a captura de tela outra vez.');
        }

        $imagem = $this->redimensionar($imagem, self::LARGURA_MAXIMA, self::ALTURA_MAXIMA);

        $temporario = tempnam(sys_get_temp_dir(), 'si_divulgacao_');

        if ($temporario === false) {
            imagedestroy($imagem);

            throw new \RuntimeException('Não foi possível preparar a imagem no servidor.');
        }

        // WebP quando a extensao existe (arquivo menor, mesma leitura);
        // senao PNG, que preserva a transparencia de captura de tela.
        if (function_exists('imagewebp')) {
            $extensao = 'webp';
            $gravou = imagewebp($imagem, $temporario, 82);
        } else {
            $extensao = 'png';
            $gravou = imagepng($imagem, $temporario);
        }

        imagedestroy($imagem);

        if (!$gravou) {
            @unlink($temporario);

            throw new \RuntimeException('Não foi possível preparar a imagem no servidor.');
        }

        return [
            'caminho_temporario' => $temporario,
            'extensao' => $extensao,
            'sha256' => hash_file('sha256', $temporario),
            'bytes' => filesize($temporario),
            'nome_original' => $this->nomeOriginal($arquivo),
        ];
    }

    /**
     * Move para a area privada o arquivo ja preparado. Chamado so' depois da
     * conferencia de duplicidade.
     */
    public function gravar(array $preparado, $eventoId)
    {
        return ArquivoPrivadoService::salvarImagem(
            $preparado['caminho_temporario'],
            'divulgacao/' . (int) $eventoId,
            $preparado['extensao']
        );
    }

    /**
     * Remove o arquivo temporario proprio. Chamado em toda saida que nao
     * grava: recusa de duplicidade, recusa da regua ou falha de banco antes
     * de o arquivo ir para a area privada.
     */
    public function descartar(array $preparado)
    {
        if (isset($preparado['caminho_temporario']) && is_file($preparado['caminho_temporario'])) {
            @unlink($preparado['caminho_temporario']);
        }
    }

    private function limiteMb()
    {
        return (int) (self::TAMANHO_MAXIMO / 1024 / 1024);
    }

    private function nomeOriginal(array $arquivo)
    {
        $nome = isset($arquivo['name']) ? (string) $arquivo['name'] : '';
        $nome = basename($nome);

        return $nome !== '' ? mb_substr($nome, 0, 255) : 'comprovacao';
    }

    private function carregar($caminho, $mime)
    {
        switch ($mime) {
            case 'image/jpeg':
                return imagecreatefromjpeg($caminho);
            case 'image/png':
                $imagem = imagecreatefrompng($caminho);

                if ($imagem !== false) {
                    imagesavealpha($imagem, true);
                }

                return $imagem;
            case 'image/webp':
                return imagecreatefromwebp($caminho);
        }

        throw new \RuntimeException('Envie uma imagem em JPEG, PNG ou WebP.');
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
