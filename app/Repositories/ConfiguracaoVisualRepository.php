<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class ConfiguracaoVisualRepository
{
    public const CABECALHO_EFEITOS_TRANSICAO = ['onda', 'diagonal_esquerda', 'diagonal_direita'];
    public const CABECALHO_EFEITOS_ENTRADA = ['nenhum', 'fade', 'subir', 'zoom'];

    public function buscar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM configuracoes_visuais WHERE id = 1')->fetch();
    }

    public function atualizar($corPrimariaInicio, $corPrimariaFim, $corSecundaria)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE configuracoes_visuais
             SET cor_primaria_inicio = :inicio, cor_primaria_fim = :fim, cor_secundaria = :secundaria
             WHERE id = 1'
        );
        $depois = [
            'cor_primaria_inicio' => $corPrimariaInicio,
            'cor_primaria_fim' => $corPrimariaFim,
            'cor_secundaria' => $corSecundaria,
        ];
        $stmt->execute(['inicio' => $corPrimariaInicio, 'fim' => $corPrimariaFim, 'secundaria' => $corSecundaria]);

        Auditoria::registrar('atualizar', 'configuracoes_visuais', 1, $antes, $depois);
    }

    public function atualizarLogo($caminhoRelativo)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_visuais SET logo_path = :logo_path WHERE id = 1');
        $stmt->execute(['logo_path' => $caminhoRelativo]);

        Auditoria::registrar('atualizar_logo', 'configuracoes_visuais', 1, $antes, ['logo_path' => $caminhoRelativo]);
    }

    public function atualizarFavicon($caminhoRelativo)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_visuais SET favicon_path = :favicon_path WHERE id = 1');
        $stmt->execute(['favicon_path' => $caminhoRelativo]);

        Auditoria::registrar('atualizar_favicon', 'configuracoes_visuais', 1, $antes, ['favicon_path' => $caminhoRelativo]);
    }

    /**
     * Fase 19 (#84 v2): imagem de fundo do cabecalho + logo clara -
     * global desde que Slideshow/Banners/Blocos/Contato/Tema deixaram de
     * ser escopados por concurso (a logo principal continua em
     * atualizarLogo(), so' mudou de tela - agora e' editada junto com
     * estes 2 campos na aba "Cabecalho").
     */
    public function atualizarCabecalho($cabecalhoImagemPath, $cabecalhoLogoClaroPath, $cabecalhoTituloHtml, $cabecalhoEfeitoTransicao, $cabecalhoOverlayOpacidade, $cabecalhoImagemPosicao, $cabecalhoEfeitoEntrada)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE configuracoes_visuais
             SET cabecalho_imagem_path = :cabecalho_imagem_path,
                 cabecalho_logo_claro_path = :cabecalho_logo_claro_path,
                 cabecalho_titulo_html = :cabecalho_titulo_html,
                 cabecalho_efeito_transicao = :cabecalho_efeito_transicao,
                 cabecalho_overlay_opacidade = :cabecalho_overlay_opacidade,
                 cabecalho_imagem_posicao = :cabecalho_imagem_posicao,
                 cabecalho_efeito_entrada = :cabecalho_efeito_entrada
             WHERE id = 1'
        );
        $dados = [
            'cabecalho_imagem_path' => $cabecalhoImagemPath,
            'cabecalho_logo_claro_path' => $cabecalhoLogoClaroPath,
            'cabecalho_titulo_html' => $cabecalhoTituloHtml,
            'cabecalho_efeito_transicao' => $cabecalhoEfeitoTransicao,
            'cabecalho_overlay_opacidade' => $cabecalhoOverlayOpacidade,
            'cabecalho_imagem_posicao' => $cabecalhoImagemPosicao,
            'cabecalho_efeito_entrada' => $cabecalhoEfeitoEntrada,
        ];
        $stmt->execute($dados);

        Auditoria::registrar('atualizar_cabecalho', 'configuracoes_visuais', 1, $antes, $dados);
    }

    /**
     * Fase 19 (#84 v2): logo especifica do rodape (fallback pra logo
     * principal se nao enviada) + quais atalhos de navegacao aparecem na
     * coluna "Navegacao" do rodape - independente do menu superior.
     */
    public function atualizarRodape($rodapeLogoPath, $mostrarTrilhas, $mostrarCronograma, $mostrarDesafios, $mostrarContato)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE configuracoes_visuais
             SET rodape_logo_path = :rodape_logo_path,
                 rodape_mostrar_trilhas = :rodape_mostrar_trilhas,
                 rodape_mostrar_cronograma = :rodape_mostrar_cronograma,
                 rodape_mostrar_desafios = :rodape_mostrar_desafios,
                 rodape_mostrar_contato = :rodape_mostrar_contato
             WHERE id = 1'
        );
        $dados = [
            'rodape_logo_path' => $rodapeLogoPath,
            'rodape_mostrar_trilhas' => $mostrarTrilhas,
            'rodape_mostrar_cronograma' => $mostrarCronograma,
            'rodape_mostrar_desafios' => $mostrarDesafios,
            'rodape_mostrar_contato' => $mostrarContato,
        ];
        $stmt->execute($dados);

        Auditoria::registrar('atualizar_rodape', 'configuracoes_visuais', 1, $antes, $dados);
    }
}
