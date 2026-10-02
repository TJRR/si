<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Gerador de codigo curto (Crockford Base32, sem as letras que se
 * confundem com numeros), compartilhado pelas colunas de codigo do Evento.
 * $tabela e $coluna so' aceitam a lista fixa abaixo, nunca valor de entrada.
 */
class CodigoUnicoService
{
    const ALFABETO_CROCKFORD_BASE32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private static $combinacoesPermitidas = [
        'evento_inscricoes.codigo_credenciamento',
        'evento_atividades.codigo_atividade',
        'evento_atividades.codigo_presenca_online',
        'evento_estandes.codigo_estande',
        'evento_competicoes.codigo_participacao',
        'evento_credenciamento_config.codigo',
        // Fase 59: codigo de conferencia do certificado. Fica FORA de
        // $colunasCodigoFixoDoEvento de proposito: nao e' lido pelo leitor de
        // "Confirmar presenca", e sim digitado na pagina publica de
        // conferencia. Tambem e' o unico gerado com dez caracteres, e nao
        // seis: os de seis sao de uso presencial e lidos por camera, e este
        // fica impresso num documento que circula fora do evento.
        'evento_certificados.codigo_verificacao',
    ];

    /**
     * Fase 58: colunas cujos codigos de 6 caracteres sao lidos pelo MESMO
     * leitor de "Confirmar presenca" (EventoAppController::validarPresenca()):
     * a sala da atividade, a competicao e o credenciamento no local. O leitor
     * descobre o que foi lido procurando nas tres, entao um codigo nao pode
     * existir em mais de uma.
     */
    private static $colunasCodigoFixoDoEvento = [
        'evento_atividades.codigo_atividade',
        'evento_competicoes.codigo_participacao',
        'evento_credenciamento_config.codigo',
    ];

    public static function gerar($tabela, $coluna, $tamanho = 6)
    {
        if (!in_array($tabela . '.' . $coluna, self::$combinacoesPermitidas, true)) {
            throw new \InvalidArgumentException('Combinação de tabela/coluna não permitida para geração de código.');
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare("SELECT 1 FROM {$tabela} WHERE {$coluna} = :codigo");
        $tamanhoAlfabeto = strlen(self::ALFABETO_CROCKFORD_BASE32);

        do {
            $codigo = '';
            for ($i = 0; $i < $tamanho; $i++) {
                $codigo .= self::ALFABETO_CROCKFORD_BASE32[random_int(0, $tamanhoAlfabeto - 1)];
            }
            $stmt->execute(['codigo' => $codigo]);
        } while ($stmt->fetch() !== false);

        return $codigo;
    }

    /**
     * Fase 58: gera um codigo de 6 caracteres ausente das tres colunas de
     * $colunasCodigoFixoDoEvento. Usado na criacao de atividade, de
     * competicao e do credenciamento no local.
     */
    public static function gerarCodigoFixoDoEvento()
    {
        $pdo = Database::conexao();
        $consultas = [];

        foreach (self::$colunasCodigoFixoDoEvento as $combinacao) {
            list($tabela, $coluna) = explode('.', $combinacao);
            $consultas[] = "SELECT 1 FROM {$tabela} WHERE {$coluna} = :codigo" . count($consultas);
        }

        $stmt = $pdo->prepare(implode(' UNION ALL ', $consultas) . ' LIMIT 1');
        $tamanhoAlfabeto = strlen(self::ALFABETO_CROCKFORD_BASE32);

        do {
            $codigo = '';
            for ($i = 0; $i < 6; $i++) {
                $codigo .= self::ALFABETO_CROCKFORD_BASE32[random_int(0, $tamanhoAlfabeto - 1)];
            }

            $parametros = [];

            foreach (array_keys($consultas) as $indice) {
                $parametros['codigo' . $indice] = $codigo;
            }

            $stmt->execute($parametros);
        } while ($stmt->fetch() !== false);

        return $codigo;
    }
}
