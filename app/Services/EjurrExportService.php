<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 48: gera o arquivo de participantes no layout exato exigido pela
 * EJURR (Educa Enfam) - mesmas 13 colunas do modelo de referencia
 * (ListaParticipante_19-09-2025_09-52-54.xlsx). Formato .csv, nao .xlsx:
 * nao existe hoje versao do phpoffice/phpspreadsheet compativel com PHP 7.3
 * que nao esteja listada com vulnerabilidades de seguranca conhecidas
 * (decisao do usuario, ver plano da fase). Delimitador ';' (padrao que o
 * Excel em portugues reconhece sem assistente de importacao) + BOM UTF-8
 * (senao os acentos ficam corrompidos ao abrir direto no Excel).
 *
 * So' formata a saida - nao busca nem resolve dado nenhum (isso fica nos
 * Repositories/Controllers que montam o array de linhas).
 */
class EjurrExportService
{
    const CABECALHO = [
        'ID', 'Nome', 'E-mail', 'Data Inscrição', 'Número de Inscrição',
        'Categoria', 'Inscrição', 'Complementos', 'Documento', 'TipoDocumento',
        'Cargo', 'Categoria Profissional', 'Tribunal ou outro órgão de origem',
    ];

    /**
     * $linhas: array de arrays associativos, cada um com as chaves id, nome,
     * email, data_inscricao, numero_inscricao, categoria, inscricao,
     * complementos, documento, tipo_documento, cargo, categoria_profissional,
     * orgao_origem - todas como string (vazia quando nao houver dado).
     */
    public function escrever($handle, array $linhas)
    {
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::CABECALHO, ';');

        foreach ($linhas as $linha) {
            fputcsv($handle, [
                $linha['id'],
                $linha['nome'],
                $linha['email'],
                $linha['data_inscricao'],
                $linha['numero_inscricao'],
                $linha['categoria'],
                $linha['inscricao'],
                $linha['complementos'],
                $linha['documento'],
                $linha['tipo_documento'],
                $linha['cargo'],
                $linha['categoria_profissional'],
                $linha['orgao_origem'],
            ], ';');
        }
    }

    public function nomeArquivo()
    {
        return 'ListaParticipante_' . date('d-m-Y_H-i-s') . '.csv';
    }
}
