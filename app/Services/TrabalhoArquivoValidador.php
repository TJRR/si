<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;

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

    /**
     * Tipo de conteudo que identifica, sem ambiguidade, cada formato aceito:
     * usado para decidir pelo CONTEUDO quando o nome do arquivo nao traz uma
     * extensao aceita. Tipos ambiguos (application/zip, octet-stream) ficam
     * de fora de proposito: so' valem quando a extensao do nome confirma.
     */
    private static $extensaoPeloTipo = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc',
        'application/pdf' => 'pdf',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'text/rtf' => 'rtf',
        'application/rtf' => 'rtf',
    ];

    /**
     * Reabertura da Fase 51 (achados da equipe de Teste Cego):
     *
     * 1. O tipo devolvido pelo finfo e' dividido nos pontos onde comeca um
     *    novo tipo (application/ ou text/) e a checagem vale para QUALQUER
     *    parte: com certos arquivos Word a biblioteca do servidor devolve o
     *    mesmo tipo escrito duas vezes seguidas, e a comparacao exata
     *    recusava um .docx verdadeiro ("o conteudo nao corresponde a
     *    extensao").
     * 2. Quando o nome nao traz uma extensao aceita (seletores de arquivo de
     *    celular entregam documentos da nuvem sem extensao ou com outro
     *    nome), a decisao passa a ser pelo conteudo: se e' inequivocamente
     *    Word ou PDF e o formato esta entre os aceitos, o arquivo entra e e'
     *    gravado com a extensao certa. Continua exigindo conteudo valido.
     * 3. Toda recusa fica na auditoria (nome, extensao lida, tipos
     *    detectados, tamanho, campo), para a proxima queixa ter fatos.
     */
    public static function validar(array $arquivo, array $extensoesPermitidas, $limiteBytes, $campo = null)
    {
        if (!isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            return ['valido' => false, 'mensagem' => 'Nenhum arquivo enviado.'];
        }

        $contexto = [
            'campo' => $campo,
            'nome_original' => self::nomeLimpo(isset($arquivo['name']) ? $arquivo['name'] : ''),
            'tamanho' => isset($arquivo['size']) ? (int) $arquivo['size'] : null,
            'extensoes_aceitas' => array_values($extensoesPermitidas),
        ];

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $contexto['codigo_erro_envio'] = (int) $arquivo['error'];

            return self::rejeitar('Falha no envio do arquivo. Tente novamente; se continuar, envie um arquivo menor.', $contexto);
        }

        if (!is_uploaded_file($arquivo['tmp_name'])) {
            return self::rejeitar('Arquivo inválido.', $contexto);
        }

        if ($arquivo['size'] > $limiteBytes) {
            $limiteMb = (int) round($limiteBytes / 1024 / 1024);

            return self::rejeitar("Arquivo maior que o limite de {$limiteMb}MB.", $contexto);
        }

        $extensaoEnviada = strtolower(pathinfo($contexto['nome_original'], PATHINFO_EXTENSION));
        $tipos = self::tiposDetectados($arquivo['tmp_name']);
        $contexto['extensao_lida'] = $extensaoEnviada;
        $contexto['tipos_detectados'] = $tipos;

        $nomeExibido = "'" . $contexto['nome_original'] . "'";
        $aceitas = self::rotuloExtensoes($extensoesPermitidas);

        if (in_array($extensaoEnviada, $extensoesPermitidas, true)) {
            if (!isset(self::$mimesPorExtensao[$extensaoEnviada])) {
                return self::rejeitar('Extensão de arquivo não suportada pelo sistema.', $contexto);
            }

            if (count(array_intersect($tipos, self::$mimesPorExtensao[$extensaoEnviada])) > 0) {
                return ['valido' => true, 'mensagem' => null, 'extensao' => $extensaoEnviada];
            }

            $porConteudo = self::extensaoPeloConteudo($tipos, $extensoesPermitidas);

            if ($porConteudo !== null) {
                return ['valido' => true, 'mensagem' => null, 'extensao' => $porConteudo];
            }

            return self::rejeitar(
                "O conteúdo do arquivo {$nomeExibido} não corresponde à extensão .{$extensaoEnviada}. Abra o arquivo no editor de textos, salve novamente no formato aceito ({$aceitas}) e envie de novo.",
                $contexto
            );
        }

        $porConteudo = self::extensaoPeloConteudo($tipos, $extensoesPermitidas);

        if ($porConteudo !== null) {
            return ['valido' => true, 'mensagem' => null, 'extensao' => $porConteudo];
        }

        $descricaoExtensao = $extensaoEnviada !== '' ? "tem a extensão .{$extensaoEnviada}" : 'não tem extensão';

        return self::rejeitar("O arquivo {$nomeExibido} {$descricaoExtensao}; são aceitas: {$aceitas}.", $contexto);
    }

    /**
     * Tipos que o finfo reconheceu, ja separados: o mesmo tipo repetido, ou
     * dois tipos grudados, viram itens distintos.
     */
    private static function tiposDetectados($caminho)
    {
        $bruto = (new \finfo(FILEINFO_MIME_TYPE))->file($caminho);

        if (!is_string($bruto) || $bruto === '') {
            return [];
        }

        $partes = preg_split('#(?=(?:application|text)/)#', $bruto, -1, PREG_SPLIT_NO_EMPTY);
        $tipos = [];

        foreach ($partes as $parte) {
            $parte = trim($parte);

            if ($parte !== '') {
                $tipos[$parte] = true;
            }
        }

        return array_keys($tipos);
    }

    /**
     * Extensao inequivoca do conteudo, se ela estiver entre as aceitas.
     */
    private static function extensaoPeloConteudo(array $tipos, array $extensoesPermitidas)
    {
        foreach ($tipos as $tipo) {
            if (isset(self::$extensaoPeloTipo[$tipo]) && in_array(self::$extensaoPeloTipo[$tipo], $extensoesPermitidas, true)) {
                return self::$extensaoPeloTipo[$tipo];
            }
        }

        return null;
    }

    private static function rotuloExtensoes(array $extensoes)
    {
        return implode(', ', array_map(function ($extensao) {
            return '.' . $extensao;
        }, $extensoes));
    }

    /**
     * Nome do arquivo pronto para aparecer em mensagem e na auditoria: sem
     * caracteres de controle, sem bytes invalidos, no maximo 80 caracteres.
     */
    private static function nomeLimpo($nome)
    {
        $nome = (string) $nome;
        $convertido = @iconv('UTF-8', 'UTF-8//IGNORE', $nome);
        $nome = $convertido !== false ? $convertido : '';
        $nome = preg_replace('/[\x00-\x1f\x7f]/u', '', $nome);
        $nome = $nome !== null ? $nome : '';

        return mb_strlen($nome, 'UTF-8') > 80 ? mb_substr($nome, 0, 77, 'UTF-8') . '...' : $nome;
    }

    private static function rejeitar($mensagem, array $contexto)
    {
        Auditoria::registrar('arquivo_trabalho_rejeitado', 'trabalhos', null, null, $contexto, $mensagem);

        return ['valido' => false, 'mensagem' => $mensagem];
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

    /**
     * Fase 51: mesma gravacao de salvar(), para arquivo que ja esta no disco
     * do servidor (importacao de trabalho recebido por canal alternativo).
     * move_uploaded_file() nao serve aqui, porque so aceita arquivo que veio
     * de um envio HTTP desta mesma requisicao.
     */
    public static function salvarArquivoLocal($caminhoOrigem, $extensao, $trabalhoId)
    {
        $pastaBase = __DIR__ . '/../../storage/uploads/trabalhos';
        $pastaFisica = $pastaBase . '/' . (int) $trabalhoId;

        if (!is_file($caminhoOrigem)) {
            throw new \RuntimeException('Arquivo de origem não encontrado: ' . $caminhoOrigem);
        }

        if (!is_dir($pastaFisica) && !mkdir($pastaFisica, 0755, true) && !is_dir($pastaFisica)) {
            throw new \RuntimeException('Não foi possível criar a pasta de destino do arquivo.');
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
        $caminhoRelativo = (int) $trabalhoId . '/' . $nomeArquivo;

        $baseReal = realpath($pastaBase);
        $pastaReal = realpath($pastaFisica);

        if ($baseReal === false || $pastaReal === false || strpos($pastaReal, $baseReal) !== 0) {
            throw new \RuntimeException('Caminho de destino fora da área permitida.');
        }

        if (!copy($caminhoOrigem, $pastaBase . '/' . $caminhoRelativo)) {
            throw new \RuntimeException('Falha ao copiar o arquivo para o servidor.');
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
