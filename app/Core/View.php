<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class View
{
    public static function renderizar($view, array $dados = [], $titulo = null, array $noAtual = null)
    {
        $conteudo = self::renderizarConteudo($view, $dados);

        $abasSecundarias = $noAtual !== null ? \App\Services\NavegacaoService::abasPara($noAtual['tipo'], $noAtual['id']) : null;
        $mecanismoAvaliacaoEtapa = $noAtual !== null ? \App\Services\NavegacaoService::mecanismoAvaliacaoEtapa($noAtual['tipo'], $noAtual['id']) : null;
        $caminhoArvore = $noAtual !== null ? \App\Services\NavegacaoService::caminhoAte($noAtual['tipo'], $noAtual['id']) : [];

        require __DIR__ . '/../Views/layout.php';
    }

    /**
     * Devolve so o fragmento de conteudo (sem layout.php) em JSON, para o JS de
     * navegacao da arvore trocar #conteudo-admin/#abas-admin sem reload.
     */
    public static function renderizarParcial($view, array $dados = [], $titulo = null, array $noAtual = null)
    {
        $conteudo = self::renderizarConteudo($view, $dados);
        $abasSecundarias = $noAtual !== null ? \App\Services\NavegacaoService::abasPara($noAtual['tipo'], $noAtual['id']) : null;
        $mecanismoAvaliacaoEtapa = $noAtual !== null ? \App\Services\NavegacaoService::mecanismoAvaliacaoEtapa($noAtual['tipo'], $noAtual['id']) : null;

        $flash = !empty($_SESSION['flash']) ? $_SESSION['flash'] : null;
        unset($_SESSION['flash']);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'titulo' => $titulo,
            'conteudo' => $conteudo,
            'abas' => $abasSecundarias,
            'mecanismoAvaliacaoEtapa' => $mecanismoAvaliacaoEtapa,
            'flash' => $flash,
        ]);
    }

    private static function renderizarConteudo($view, array $dados)
    {
        // Views "publico/*" montam o proprio <header> em vez de usar o
        // topbar do layout.php, entao precisam do logo aqui mesmo - senao
        // $logoAdminSrc fica indefinido dentro delas (o calculo em
        // layout.php roda so' depois, num escopo separado).
        if (strpos($view, 'publico/') === 0 && !isset($dados['logoAdminSrc'])) {
            $dados['logoAdminSrc'] = logoAtual();
        }

        extract($dados);

        $caminhoView = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($caminhoView)) {
            http_response_code(500);
            exit('View nao encontrada: ' . $view);
        }

        ob_start();
        require $caminhoView;

        return ob_get_clean();
    }
}
