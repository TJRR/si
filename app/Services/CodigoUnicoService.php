<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 46: gerador de codigo curto (Crockford Base32, sem I/L/O/U - letras
 * que se confundem visualmente com 1/1/0/V) extraido de
 * EventoInscricaoRepository::gerarCodigoCredenciamentoUnico() (Fase 42) -
 * agora compartilhado entre evento_inscricoes.codigo_credenciamento e
 * evento_atividades.codigo_atividade, sem duplicar alfabeto/laco/checagem de
 * unicidade. $tabela/$coluna sao validados contra uma whitelist fixa (nomes
 * de identificador SQL nunca sao parametrizaveis via PDO) - nunca aceitam
 * valor vindo de entrada do usuario.
 */
class CodigoUnicoService
{
    const ALFABETO_CROCKFORD_BASE32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private static $combinacoesPermitidas = [
        'evento_inscricoes.codigo_credenciamento',
        'evento_atividades.codigo_atividade',
        'evento_atividades.codigo_presenca_online',
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
}
