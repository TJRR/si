<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 30: extração best-effort (sem validar cadeia de confiança nem
 * revogação) do nome/CPF que o certificado embutido na assinatura de um PDF
 * DECLARA ser o titular - ver plano da Fase 30, seção "Conferência da
 * assinatura e quem decide", item 2. Isto NUNCA confirma que a assinatura é
 * válida - é só um atalho visual pra facilitar a conferência manual do
 * Administrador, que continua obrigatória (checkbox "Confirmo que validei
 * no site oficial do gov.br").
 *
 * Sem nenhuma dependência nova: o ambiente de desenvolvimento deste projeto
 * não tem acesso de rede de saída (confirmado ao tentar instalar o
 * phpseclib, que era a biblioteca originalmente cogitada - o comando
 * `composer require` falhou por timeout de conexão), então este arquivo
 * implementa, na mão, só o pedaço mínimo de leitura DER/X.509 necessário
 * pra achar o Common Name do titular de um certificado - não é um parser
 * ASN.1 de propósito geral, só sabe navegar exatamente até esse campo.
 *
 * Fluxo: PDF -> bytes hexadecimais de /Contents (o bloco PKCS#7/CMS
 * assinado) -> varre o bloco procurando a primeira sequência DER que "se
 * parece" com um certificado X.509 válido o bastante pra extrair o Subject
 * -> devolve nome (e CPF, se o Common Name seguir a convenção ICP-Brasil
 * "NOME:CPF" documentada no DOC-ICP-04).
 *
 * IMPORTANTE - nunca testado contra um PDF assinado de verdade (não há
 * amostra disponível neste ambiente): pode simplesmente não funcionar para
 * o formato exato que o assinador do gov.br produz. Por isso cada etapa
 * devolve null em silêncio ao primeiro sinal de estrutura inesperada -
 * nunca lança exceção, nunca impede o envio/análise do documento. Se não
 * funcionar em produção, o fluxo continua 100% operante só com a
 * checagem estrutural (RequerimentoController::contemAssinaturaDigital())
 * e a conferência humana obrigatória.
 */
class CertificadoExtratorService
{
    public static function extrairDeclarado($caminhoArquivoPdf)
    {
        try {
            $conteudoPdf = file_get_contents($caminhoArquivoPdf);

            if ($conteudoPdf === false) {
                return null;
            }

            $der = self::extrairContentsDoSig($conteudoPdf);

            if ($der === null) {
                return null;
            }

            $commonName = self::extrairPrimeiroCommonName($der);

            return $commonName !== null ? self::interpretarCommonName($commonName) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * PDF assinado carrega, dentro do dicionário /Type /Sig, um campo
     * /Contents <hexadecimal> com o bloco PKCS#7/CMS assinado em DER. Pega
     * a primeira ocorrência (cenário comum é uma assinatura só) e decodifica
     * o hexadecimal pra binário.
     */
    private static function extrairContentsDoSig($conteudoPdf)
    {
        if (preg_match('/\/Contents\s*<([0-9A-Fa-f\s]+)>/', $conteudoPdf, $correspondencia) !== 1) {
            return null;
        }

        $hexadecimal = preg_replace('/\s+/', '', $correspondencia[1]);

        if (strlen($hexadecimal) % 2 !== 0) {
            $hexadecimal = substr($hexadecimal, 0, -1);
        }

        $binario = @hex2bin($hexadecimal);

        return $binario !== false && $binario !== '' ? $binario : null;
    }

    /**
     * O bloco PKCS#7/CMS carrega, junto da assinatura, um ou mais
     * certificados X.509 completos (cada um uma SEQUENCE DER autocontida).
     * Em vez de navegar a estrutura exata do PKCS#7 (que varia um pouco
     * entre assinadores - com/sem CRL, uma ou mais SignerInfo etc.), varre
     * o bloco procurando qualquer posição onde uma SEQUENCE (tag 0x30)
     * consiga ser lida como um TBSCertificate válido até' o campo Subject -
     * a primeira que funcionar é o certificado do assinante.
     */
    private static function extrairPrimeiroCommonName($der)
    {
        $tamanho = strlen($der);

        for ($posicao = 0; $posicao < $tamanho - 4; $posicao++) {
            if ($der[$posicao] !== "\x30") {
                continue;
            }

            $candidato = self::lerTlv($der, $posicao);

            if ($candidato === null) {
                continue;
            }

            $commonName = self::extrairSubjectCommonName(substr($der, $posicao, $candidato[2]));

            if ($commonName !== null) {
                return $commonName;
            }
        }

        return null;
    }

    /**
     * Certificate ::= SEQUENCE { tbsCertificate TBSCertificate, ... }
     * TBSCertificate ::= SEQUENCE {
     *     version [0] EXPLICIT INTEGER DEFAULT v1,  -- opcional, tag 0xA0
     *     serialNumber     INTEGER,
     *     signature        AlgorithmIdentifier,      -- SEQUENCE
     *     issuer           Name,                     -- SEQUENCE
     *     validity         Validity,                 -- SEQUENCE
     *     subject          Name,                     -- SEQUENCE  <- alvo
     *     ...
     * }
     * Como não precisamos do CONTEÚDO de version/serialNumber/signature/
     * issuer/validity, só pulamos cada um pelo próprio tamanho DER (TLV) até
     * chegar no subject - sem precisar entender o formato interno deles.
     */
    private static function extrairSubjectCommonName($certificadoDer)
    {
        $certificadoTlv = self::lerTlv($certificadoDer, 0);

        if ($certificadoTlv === null || $certificadoTlv[0] !== 0x30) {
            return null;
        }

        $tbsTlv = self::lerTlv($certificadoTlv[1], 0);

        if ($tbsTlv === null || $tbsTlv[0] !== 0x30) {
            return null;
        }

        $tbs = $tbsTlv[1];
        $posicao = 0;

        $campo = self::lerTlv($tbs, $posicao);
        if ($campo === null) {
            return null;
        }

        if ($campo[0] === 0xA0) { // version, opcional
            $posicao += $campo[2];
            $campo = self::lerTlv($tbs, $posicao);
            if ($campo === null) {
                return null;
            }
        }

        if ($campo[0] !== 0x02) { // serialNumber (INTEGER)
            return null;
        }
        $posicao += $campo[2];

        foreach (['signature', 'issuer', 'validity'] as $ignorado) {
            $campo = self::lerTlv($tbs, $posicao);
            if ($campo === null || $campo[0] !== 0x30) {
                return null;
            }
            $posicao += $campo[2];
        }

        $subject = self::lerTlv($tbs, $posicao); // Name (subject)

        if ($subject === null || $subject[0] !== 0x30) {
            return null;
        }

        return self::buscarCommonNameNoSubject($subject[1]);
    }

    /**
     * subject (RDNSequence) ::= SEQUENCE OF RelativeDistinguishedName
     * RelativeDistinguishedName ::= SET OF AttributeTypeAndValue
     * AttributeTypeAndValue ::= SEQUENCE { type OBJECT IDENTIFIER, value ANY }
     * Procura o AttributeTypeAndValue cujo OID é commonName (2.5.4.3 - bytes
     * DER "06 03 55 04 03") e devolve o valor bruto (string, seja
     * PrintableString/UTF8String/etc. - tratado como texto de qualquer jeito).
     */
    private static function buscarCommonNameNoSubject($rdnSequence)
    {
        $oidCommonName = "\x06\x03\x55\x04\x03";
        $posicaoRdn = 0;
        $tamanhoTotal = strlen($rdnSequence);

        while ($posicaoRdn < $tamanhoTotal) {
            $rdn = self::lerTlv($rdnSequence, $posicaoRdn);

            if ($rdn === null) {
                break;
            }

            if ($rdn[0] === 0x31) { // SET
                $posicaoAtv = 0;
                $conteudoRdn = $rdn[1];
                $tamanhoRdn = strlen($conteudoRdn);

                while ($posicaoAtv < $tamanhoRdn) {
                    $atv = self::lerTlv($conteudoRdn, $posicaoAtv);

                    if ($atv === null) {
                        break;
                    }

                    if ($atv[0] === 0x30 && strpos($atv[1], $oidCommonName) === 0) {
                        $valor = self::lerTlv($atv[1], strlen($oidCommonName));

                        if ($valor !== null) {
                            return $valor[1];
                        }
                    }

                    $posicaoAtv += $atv[2];
                }
            }

            $posicaoRdn += $rdn[2];
        }

        return null;
    }

    /**
     * Lê o próximo TLV (tag-length-value) DER a partir de $offset dentro de
     * $der. Devolve [tag (int, 1 byte), conteúdo (string), comprimento
     * total em bytes (cabeçalho + conteúdo)], ou null se não houver TLV
     * válido ali (fora dos limites, tamanho DER malformado etc.).
     */
    private static function lerTlv($der, $offset)
    {
        if ($offset < 0 || $offset >= strlen($der)) {
            return null;
        }

        $tag = ord($der[$offset]);
        $tamanhoDer = self::lerTamanhoDer(substr($der, $offset + 1, 5));

        if ($tamanhoDer === null) {
            return null;
        }

        list($tamanhoCabecalho, $tamanhoConteudo) = $tamanhoDer;
        $inicioConteudo = $offset + $tamanhoCabecalho;
        $fimConteudo = $inicioConteudo + $tamanhoConteudo;

        if ($fimConteudo > strlen($der)) {
            return null;
        }

        return [$tag, substr($der, $inicioConteudo, $tamanhoConteudo), $tamanhoCabecalho + $tamanhoConteudo];
    }

    /**
     * Formato de tamanho DER: forma curta (1 byte, valor < 0x80, é o
     * próprio tamanho) ou forma longa (0x80 | N, seguido de N bytes
     * big-endian com o tamanho real). $bytesAposTag são os bytes logo após
     * o byte de tag. Devolve [tamanho do cabeçalho (tag+tamanho), tamanho
     * do conteúdo], ou null se não for um tamanho DER válido (nunca trata
     * tamanho indefinido/0x80 sozinho - certificado X.509 é sempre DER
     * definido).
     */
    private static function lerTamanhoDer($bytesAposTag)
    {
        if ($bytesAposTag === '') {
            return null;
        }

        $primeiroByte = ord($bytesAposTag[0]);

        if ($primeiroByte < 0x80) {
            return [2, $primeiroByte];
        }

        $quantidadeBytesTamanho = $primeiroByte & 0x7F;

        if ($quantidadeBytesTamanho === 0 || $quantidadeBytesTamanho > 4 || strlen($bytesAposTag) < 1 + $quantidadeBytesTamanho) {
            return null;
        }

        $tamanho = 0;
        for ($i = 1; $i <= $quantidadeBytesTamanho; $i++) {
            $tamanho = ($tamanho << 8) | ord($bytesAposTag[$i]);
        }

        return [1 + 1 + $quantidadeBytesTamanho, $tamanho];
    }

    /**
     * ICP-Brasil (DOC-ICP-04) grava e-CPF tipicamente como "NOME:CPF" (CPF
     * com 11 dígitos, sem pontuação) no Common Name - convenção documentada,
     * mas nunca confirmada aqui contra um certificado real. Se o formato vier
     * diferente, devolve só o nome, sem CPF - nunca falha por causa disso.
     */
    private static function interpretarCommonName($commonName)
    {
        $commonName = trim($commonName);

        if (preg_match('/^(.+):(\d{11})$/', $commonName, $correspondencia) === 1) {
            return ['nome' => trim($correspondencia[1]), 'cpf' => $correspondencia[2]];
        }

        return ['nome' => $commonName, 'cpf' => null];
    }
}
