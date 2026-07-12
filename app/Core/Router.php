<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Controllers\ApuracaoAdminController;
use App\Controllers\AuthController;
use App\Controllers\AvaliacaoController;
use App\Controllers\CadastroController;
use App\Controllers\CampoAdminController;
use App\Controllers\CategoriaAvaliadorAdminController;
use App\Controllers\ConcursoAdminController;
use App\Controllers\ConteudoAdminController;
use App\Controllers\CriterioAvaliacaoAdminController;
use App\Controllers\DesignacaoAdminController;
use App\Controllers\EtapaAdminController;
use App\Controllers\FormulaPontuacaoAdminController;
use App\Controllers\FormularioAdminController;
use App\Controllers\HomeController;
use App\Controllers\HomologacaoController;
use App\Controllers\InscricaoController;
use App\Controllers\NavegacaoController;
use App\Controllers\NotificacaoPainelController;
use App\Controllers\ParticipanteController;
use App\Controllers\RegraDesempateAdminController;
use App\Controllers\ResultadoAdminController;
use App\Controllers\ResultadoPublicoController;
use App\Controllers\SubmissaoController;
use App\Controllers\TemaAdminController;
use App\Controllers\TemaDesafioAdminController;
use App\Controllers\TrilhaAdminController;
use App\Controllers\UsuarioAdminController;
use App\Controllers\VagaAvaliadorAdminController;

class Router
{
    private static $rotas = [
        'auth' => AuthController::class,
        'cadastro' => CadastroController::class,
        'usuarios' => UsuarioAdminController::class,
        'home' => HomeController::class,
        'concursos' => ConcursoAdminController::class,
        'trilhas' => TrilhaAdminController::class,
        'temas' => TemaDesafioAdminController::class,
        'etapas' => EtapaAdminController::class,
        'formularios' => FormularioAdminController::class,
        'campos' => CampoAdminController::class,
        'submissao' => SubmissaoController::class,
        'inscricao' => InscricaoController::class,
        'homologacao' => HomologacaoController::class,
        'participante' => ParticipanteController::class,
        'conteudo' => ConteudoAdminController::class,
        'tema' => TemaAdminController::class,
        'criterios' => CriterioAvaliacaoAdminController::class,
        'formulas' => FormulaPontuacaoAdminController::class,
        'desempate' => RegraDesempateAdminController::class,
        'designacoes' => DesignacaoAdminController::class,
        'categoriasAvaliador' => CategoriaAvaliadorAdminController::class,
        'vagasAvaliador' => VagaAvaliadorAdminController::class,
        'avaliacao' => AvaliacaoController::class,
        'resultados' => ResultadoAdminController::class,
        'resultadosPublicos' => ResultadoPublicoController::class,
        'apuracao' => ApuracaoAdminController::class,
        'navegacao' => NavegacaoController::class,
        'notificacoesPainel' => NotificacaoPainelController::class,
    ];

    public function despachar($r)
    {
        $partes = explode('/', trim($r, '/'));
        $modulo = $partes[0] !== '' ? $partes[0] : 'home';
        $acao = isset($partes[1]) && $partes[1] !== '' ? $partes[1] : 'index';
        $parametros = array_slice($partes, 2);

        if (!isset(self::$rotas[$modulo])) {
            $this->naoEncontrado();
            return;
        }

        $classe = self::$rotas[$modulo];
        $controller = new $classe();

        if (!is_callable([$controller, $acao])) {
            $this->naoEncontrado();
            return;
        }

        call_user_func_array([$controller, $acao], $parametros);
    }

    private function naoEncontrado()
    {
        http_response_code(404);
        echo 'Pagina nao encontrada.';
    }
}
