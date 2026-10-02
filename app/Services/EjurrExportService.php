<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Gera o arquivo de participantes no formato de colunas exigido pela
 * instituicao que certifica as horas. Formato .csv, e nao .xlsx: ver Implantar.md, secao 13.7.
 * Delimitador ';' e BOM UTF-8, para os acentos abrirem certo nas planilhas.
 *
 * So' formata a saida; nao busca nem resolve dado nenhum.
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
