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

    /**
     * Reabertura da Fase 51: fontes que o Admin pode escolher para os
     * titulos e para o texto da pagina publica do evento. Lista fechada
     * (nunca nome digitado), porque cada nome vira um pedido ao servico de
     * fontes do Google; a chave e' o nome da familia, o valor e' o trecho
     * de pesos pedido ao servico.
     */
    public const FONTES = [
        'Poppins' => 'Poppins:wght@400;600;700',
        'Roboto' => 'Roboto:wght@400;500;700',
        'Montserrat' => 'Montserrat:wght@400;500;600;700',
        'Fredoka' => 'Fredoka:wght@500;600;700',
        'Open Sans' => 'Open+Sans:wght@400;600;700',
        'Lato' => 'Lato:wght@400;700',
        'Nunito' => 'Nunito:wght@400;600;700',
    ];

    /**
     * Endereco da folha de estilo do Google Fonts com as fontes escolhidas
     * para o evento, ou null quando nenhuma foi escolhida (vale a do site).
     */
    public static function urlFontes(array $configuracao)
    {
        $familias = [];

        foreach (['fonte_titulo', 'fonte_texto'] as $coluna) {
            $nome = isset($configuracao[$coluna]) ? $configuracao[$coluna] : null;

            if ($nome !== null && isset(self::FONTES[$nome])) {
                $familias[self::FONTES[$nome]] = true;
            }
        }

        if (empty($familias)) {
            return null;
        }

        return 'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_keys($familias)) . '&display=swap';
    }

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
            // publica dele) e, desde a reabertura, as fontes da pagina.
            'logo_path' => $dados['logo_path'],
            'logo_alt' => $dados['logo_alt'],
            'fonte_titulo' => $dados['fonte_titulo'],
            'fonte_texto' => $dados['fonte_texto'],
        ];

        if ($antes === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_configuracao_visual (
                    evento_id, publicado, cabecalho_imagem_path, cabecalho_logo_claro_path, cabecalho_titulo_html,
                    cabecalho_efeito_transicao, cabecalho_overlay_opacidade, cabecalho_imagem_posicao, cabecalho_efeito_entrada,
                    logo_path, logo_alt, fonte_titulo, fonte_texto
                ) VALUES (
                    :evento_id, :publicado, :cabecalho_imagem_path, :cabecalho_logo_claro_path, :cabecalho_titulo_html,
                    :cabecalho_efeito_transicao, :cabecalho_overlay_opacidade, :cabecalho_imagem_posicao, :cabecalho_efeito_entrada,
                    :logo_path, :logo_alt, :fonte_titulo, :fonte_texto
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
                    fonte_titulo = :fonte_titulo,
                    fonte_texto = :fonte_texto
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
