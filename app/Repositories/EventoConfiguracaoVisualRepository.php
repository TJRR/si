<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 50: cabecalho da pagina publica propria de cada Evento
 * (evento_configuracao_visual, 1 linha por evento). Mesmo padrao de upsert
 * de BlocoConcursoRepository::salvar(), mas filtrado por evento_id.
 * `publicado` controla se evento/index/{id} responde publicamente (nasce
 * em 0, ver EventoPublicoController).
 */
class EventoConfiguracaoVisualRepository
{
    public const CABECALHO_EFEITOS_TRANSICAO = ['onda', 'diagonal_esquerda', 'diagonal_direita'];
    public const CABECALHO_EFEITOS_ENTRADA = ['nenhum', 'fade', 'subir', 'zoom'];
    public const CABECALHO_IMAGEM_POSICOES = [
        'superior_esquerda', 'superior_centro', 'superior_direita',
        'centro_esquerda', 'centro_centro', 'centro_direita',
        'inferior_esquerda', 'inferior_centro', 'inferior_direita',
    ];

    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_configuracao_visual WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);
        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function salvar($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $pdo = Database::conexao();

        $parametros = [
            'evento_id' => $eventoId,
            'publicado' => $dados['publicado'],
            'cabecalho_imagem_path' => $dados['cabecalho_imagem_path'],
            'cabecalho_logo_claro_path' => $dados['cabecalho_logo_claro_path'],
            'cabecalho_titulo_html' => $dados['cabecalho_titulo_html'],
            'cabecalho_efeito_transicao' => $dados['cabecalho_efeito_transicao'],
            'cabecalho_overlay_opacidade' => $dados['cabecalho_overlay_opacidade'],
            'cabecalho_imagem_posicao' => $dados['cabecalho_imagem_posicao'],
            'cabecalho_efeito_entrada' => $dados['cabecalho_efeito_entrada'],
            // Fase 51: logo oficial do evento (vence a do tema na pagina
            // publica dele) e avanco automatico dos Quadros de apresentacao.
            'logo_path' => $dados['logo_path'],
            'logo_alt' => $dados['logo_alt'],
            'quadros_avanco_automatico' => $dados['quadros_avanco_automatico'],
        ];

        if ($antes === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_configuracao_visual (
                    evento_id, publicado, cabecalho_imagem_path, cabecalho_logo_claro_path, cabecalho_titulo_html,
                    cabecalho_efeito_transicao, cabecalho_overlay_opacidade, cabecalho_imagem_posicao, cabecalho_efeito_entrada,
                    logo_path, logo_alt, quadros_avanco_automatico
                ) VALUES (
                    :evento_id, :publicado, :cabecalho_imagem_path, :cabecalho_logo_claro_path, :cabecalho_titulo_html,
                    :cabecalho_efeito_transicao, :cabecalho_overlay_opacidade, :cabecalho_imagem_posicao, :cabecalho_efeito_entrada,
                    :logo_path, :logo_alt, :quadros_avanco_automatico
                )'
            );
            $stmt->execute($parametros);
            $id = (int) $pdo->lastInsertId();
        } else {
            $id = (int) $antes['id'];
            $stmt = $pdo->prepare(
                'UPDATE evento_configuracao_visual SET
                    publicado = :publicado,
                    cabecalho_imagem_path = :cabecalho_imagem_path,
                    cabecalho_logo_claro_path = :cabecalho_logo_claro_path,
                    cabecalho_titulo_html = :cabecalho_titulo_html,
                    cabecalho_efeito_transicao = :cabecalho_efeito_transicao,
                    cabecalho_overlay_opacidade = :cabecalho_overlay_opacidade,
                    cabecalho_imagem_posicao = :cabecalho_imagem_posicao,
                    cabecalho_efeito_entrada = :cabecalho_efeito_entrada,
                    logo_path = :logo_path,
                    logo_alt = :logo_alt,
                    quadros_avanco_automatico = :quadros_avanco_automatico
                 WHERE id = :id'
            );
            $parametrosAtualizacao = $parametros;
            unset($parametrosAtualizacao['evento_id']);
            $stmt->execute($parametrosAtualizacao + ['id' => $id]);
        }

        Auditoria::registrar('salvar', 'evento_configuracao_visual', $id, $antes, $parametros);

        return $id;
    }
}
