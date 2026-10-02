<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\View;
use setasign\Fpdi\Fpdi;

/**
 * Fase 59: geracao, gravacao e juncao dos PDF de certificado.
 *
 * Servico proprio, e nao PdfService::streamar(): aquele e' codigo ativo do
 * Concurso (o PDF do requerimento) e sempre ENVIA o arquivo ao navegador e
 * encerra a requisicao. Aqui o PDF precisa ser devolvido como conteudo, para
 * ser gravado e guardado, porque por decisao do dono o documento e' emitido
 * uma vez e sempre entregue igual. As opcoes do Dompdf sao as mesmas de
 * EventoAnaisGeradorService::renderizarPdf(), que tambem gera para gravar:
 * sem acesso remoto, fonte DejaVu Sans e a pasta assets/ liberada.
 *
 * A duplicacao proposital esta' registrada na pendencia 25.
 *
 * Sobre a arte em WebP, que e' o formato em que a Biblioteca de midia guarda
 * imagem (ImagemService converte no envio): o Dompdf nao escreve WebP
 * direto, ele converte para PNG antes (Adapter\CPDF::image(), caso "webp"),
 * e essa conversao depende do GD com suporte a WebP, que o
 * docker/php/Dockerfile instala. Sem esse suporte a arte sairia como imagem
 * quebrada, nao como erro - por isso vale conferir o fundo no Teste Cego, e
 * nao so' a existencia do arquivo.
 */
class CertificadoPdfService
{
    /**
     * Pasta dos certificados dentro da area privada. Nao fica em assets/
     * porque o servidor web entregaria o arquivo a quem descobrisse o
     * endereco, e o certificado traz nome e documento de uma pessoa.
     */
    const PASTA = 'certificados';

    /**
     * Monta o PDF de um certificado e devolve o conteudo. A folha e' A4 em
     * paisagem, que e' o formato de certificado da escola.
     */
    public function renderizar(array $dados)
    {
        $html = View::renderizarString('pdf/certificado', $dados);

        ini_set('memory_limit', '256M');

        $opcoes = ['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans'];
        $assets = realpath(__DIR__ . '/../../assets');

        if ($assets !== false) {
            $opcoes['chroot'] = [$assets];
        }

        $dompdf = new \Dompdf\Dompdf($opcoes);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Grava o conteudo na area privada e devolve o caminho relativo, o
     * tamanho e o resumo do arquivo, no molde de evento_anais_versoes.
     *
     * O nome do arquivo e' aleatorio, nunca derivado do nome da pessoa nem do
     * codigo de conferencia: quem descobrir um caminho nao deduz os outros.
     */
    public function gravar($eventoId, $conteudo)
    {
        $base = realpath(__DIR__ . '/../../storage/uploads');

        if ($base === false) {
            throw new \RuntimeException('A pasta privada de arquivos não foi encontrada no servidor. Avise o suporte técnico.');
        }

        $pasta = $base . '/' . self::PASTA . '/' . (int) $eventoId;

        if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
            throw new \RuntimeException('Não foi possível criar a pasta dos certificados no servidor. Avise o suporte técnico.');
        }

        $nome = bin2hex(random_bytes(16)) . '.pdf';

        if (file_put_contents($pasta . '/' . $nome, $conteudo) === false) {
            throw new \RuntimeException('Não foi possível gravar o certificado no servidor. Avise o suporte técnico.');
        }

        return [
            'arquivo_path' => self::PASTA . '/' . (int) $eventoId . '/' . $nome,
            'tamanho_bytes' => strlen($conteudo),
            'sha256' => hash('sha256', $conteudo),
        ];
    }

    /**
     * Apaga um arquivo que acabou de ser gravado e nao foi aproveitado - caso
     * de quem perdeu a corrida da chave unica e recebeu de volta o
     * certificado que a outra requisicao gravou. Falha aqui nunca interrompe
     * nada: o pior efeito e' um arquivo orfao de 50 kB.
     */
    public function descartar($caminhoRelativo)
    {
        $base = realpath(__DIR__ . '/../../storage/uploads');
        $caminho = $base !== false ? realpath($base . '/' . $caminhoRelativo) : false;

        if ($caminho === false || strpos($caminho, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($caminho)) {
            return;
        }

        unlink($caminho);
    }

    /**
     * Junta varios certificados ja' gravados num PDF unico, um por folha, e
     * devolve o conteudo.
     *
     * E' assim, e nao num arquivo compactado, porque a extensao de
     * compactacao do PHP nao esta' instalada no ambiente (ver
     * docker/php/Dockerfile) e nao ha' como confirmar que exista no servidor
     * de producao. A FPDI, que o projeto ja' usa para montar o volume dos
     * Anais, resolve sem dependencia nova.
     *
     * Arquivo ausente ou ilegivel e' PULADO, com registro no log: um
     * certificado perdido nao pode impedir a entrega dos outros 49. Devolve
     * null quando nenhum pode ser lido.
     */
    public function juntar(array $caminhosRelativos)
    {
        $base = realpath(__DIR__ . '/../../storage/uploads');

        if ($base === false) {
            return null;
        }

        $pdf = new Fpdi();
        $paginas = 0;

        foreach ($caminhosRelativos as $relativo) {
            $caminho = realpath($base . '/' . $relativo);

            if ($caminho === false || strpos($caminho, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($caminho)) {
                error_log('[Certificado] Arquivo ausente ao juntar: ' . $relativo);
                continue;
            }

            try {
                $total = $pdf->setSourceFile($caminho);

                for ($numero = 1; $numero <= $total; $numero++) {
                    $modelo = $pdf->importPage($numero);
                    $tamanho = $pdf->getTemplateSize($modelo);
                    $pdf->AddPage($tamanho['orientation'], [$tamanho['width'], $tamanho['height']]);
                    $pdf->useTemplate($modelo);
                    $paginas++;
                }
            } catch (\Throwable $e) {
                error_log('[Certificado] Falha ao juntar o arquivo ' . $relativo . ': ' . $e->getMessage());
            }
        }

        return $paginas > 0 ? $pdf->Output('S') : null;
    }

    /**
     * O texto do certificado com as imagens inseridas pelo Administrador
     * apontando para o arquivo local, porque o Dompdf nao busca endereco
     * remoto. Mesma rotina de EventoAnaisGeradorService::imagensLocais(),
     * que esta fase nao altera por ser codigo ativo dos Anais.
     *
     * Imagem de fora de assets/ fica como esta': o Dompdf a ignora e o
     * certificado sai sem ela, em vez de falhar.
     */
    public static function imagensLocais($html)
    {
        $assets = realpath(__DIR__ . '/../../assets');

        if ($assets === false) {
            return $html;
        }

        $prefixo = config('base_path') . '/assets/';

        $trocado = preg_replace_callback('/\bsrc\s*=\s*(["\'])(.*?)\1/i', function ($partes) use ($assets, $prefixo) {
            $endereco = html_entity_decode($partes[2], ENT_QUOTES, 'UTF-8');
            $caminho = parse_url($endereco, PHP_URL_PATH);

            if (!is_string($caminho) || strpos($caminho, $prefixo) !== 0) {
                return $partes[0];
            }

            $local = realpath($assets . '/' . rawurldecode(substr($caminho, strlen($prefixo))));

            if ($local === false || strpos($local, $assets . DIRECTORY_SEPARATOR) !== 0 || !is_file($local)) {
                return $partes[0];
            }

            return 'src=' . $partes[1] . htmlspecialchars($local, ENT_QUOTES, 'UTF-8') . $partes[1];
        }, $html);

        return $trocado !== null ? $trocado : $html;
    }

    /**
     * Caminho de arquivo da imagem de fundo escolhida na Biblioteca de
     * midia, ou null. Mesmo tratamento de
     * EventoAnaisGeradorService::imagensLocais(): o Dompdf nao busca endereco
     * remoto, entao o endereco guardado na configuracao e' convertido em
     * caminho dentro de assets/, e nada fora dessa pasta e' aceito.
     *
     * Fundo ausente, de outra origem ou ilegivel devolve null, e o
     * certificado sai sem arte - nunca com erro.
     */
    public static function caminhoLocalDoFundo($url)
    {
        $assets = realpath(__DIR__ . '/../../assets');
        $url = (string) $url;

        if ($assets === false || trim($url) === '') {
            return null;
        }

        $caminho = parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'), PHP_URL_PATH);
        $prefixo = config('base_path') . '/assets/';

        if (!is_string($caminho) || strpos($caminho, $prefixo) !== 0) {
            return null;
        }

        $local = realpath($assets . '/' . rawurldecode(substr($caminho, strlen($prefixo))));

        if ($local === false || strpos($local, $assets . DIRECTORY_SEPARATOR) !== 0 || !is_file($local)) {
            return null;
        }

        return $local;
    }
}
