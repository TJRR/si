<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Controllers\ApuracaoAdminController;
use App\Controllers\AuditoriaAdminController;
use App\Controllers\AuthController;
use App\Controllers\AvaliacaoController;
use App\Controllers\BannerAdminController;
use App\Controllers\BlocoConteudoAdminController;
use App\Controllers\CadastroController;
use App\Controllers\CampoAdminController;
use App\Controllers\CategoriaAvaliadorAdminController;
use App\Controllers\ConcursoAdminController;
use App\Controllers\ConfiguracaoAdminController;
use App\Controllers\ContatoConcursoAdminController;
use App\Controllers\ConteudoAdminController;
use App\Controllers\CriterioAvaliacaoAdminController;
use App\Controllers\DesignacaoAdminController;
use App\Controllers\DocumentoAdminController;
use App\Controllers\DuvidaAdminController;
use App\Controllers\DuvidaController;
use App\Controllers\EdicaoPublicaController;
use App\Controllers\EditorMidiaAdminController;
use App\Controllers\EtapaAdminController;
use App\Controllers\EventoCronogramaAdminController;
use App\Controllers\FaqAdminController;
use App\Controllers\FaqConcursoAdminController;
use App\Controllers\FormulaPontuacaoAdminController;
use App\Controllers\FormularioAdminController;
use App\Controllers\HomeController;
use App\Controllers\HomeSecaoOrdemAdminController;
use App\Controllers\HomologacaoController;
use App\Controllers\HomologacaoPublicaController;
use App\Controllers\InscricaoController;
use App\Controllers\MentoriaAdminController;
use App\Controllers\MentoriaController;
use App\Controllers\MentoriaPublicaController;
use App\Controllers\MeuPerfilController;
use App\Controllers\MidiaAdminController;
use App\Controllers\ModeloDocumentoAdminController;
use App\Controllers\NavegacaoController;
use App\Controllers\NotificacaoPainelController;
use App\Controllers\OficinaAdminController;
use App\Controllers\OficinaController;
use App\Controllers\OficinaPublicaController;
use App\Controllers\ParticipanteController;
use App\Controllers\PremioAdminController;
use App\Controllers\RegraDesempateAdminController;
use App\Controllers\RequerimentoAdminController;
use App\Controllers\RequerimentoController;
use App\Controllers\ResultadoAdminController;
use App\Controllers\ResultadoPublicoController;
use App\Controllers\SessaoController;
use App\Controllers\SlideAdminController;
use App\Controllers\SubmissaoController;
use App\Controllers\TemaAdminController;
use App\Controllers\TemaDesafioAdminController;
use App\Controllers\TrilhaAdminController;
use App\Controllers\UsuarioAdminController;
use App\Controllers\ValidacaoPublicaController;
use App\Controllers\VagaAvaliadorAdminController;
use App\Repositories\ConfiguracaoSistemaRepository;

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
        'homologacaoPublica' => HomologacaoPublicaController::class,
        'participante' => ParticipanteController::class,
        'mentoriaAdmin' => MentoriaAdminController::class,
        'mentoria' => MentoriaController::class,
        'mentoriaPublica' => MentoriaPublicaController::class,
        'oficinaAdmin' => OficinaAdminController::class,
        'oficina' => OficinaController::class,
        'oficinaPublica' => OficinaPublicaController::class,
        'duvidaAdmin' => DuvidaAdminController::class,
        'duvida' => DuvidaController::class,
        'modelosDocumento' => ModeloDocumentoAdminController::class,
        'requerimento' => RequerimentoController::class,
        'requerimentoAdmin' => RequerimentoAdminController::class,
        'validacaoPublica' => ValidacaoPublicaController::class,
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
        'auditoria' => AuditoriaAdminController::class,
        'configuracoes' => ConfiguracaoAdminController::class,
        'meuPerfil' => MeuPerfilController::class,
        'sessao' => SessaoController::class,
        'editorMidia' => EditorMidiaAdminController::class,
        'slides' => SlideAdminController::class,
        'banners' => BannerAdminController::class,
        'blocos' => BlocoConteudoAdminController::class,
        'contatosConcurso' => ContatoConcursoAdminController::class,
        'ordenacaoHome' => HomeSecaoOrdemAdminController::class,
        'premios' => PremioAdminController::class,
        'faq' => FaqAdminController::class,
        'faqConcurso' => FaqConcursoAdminController::class,
        'documentos' => DocumentoAdminController::class,
        'midia' => MidiaAdminController::class,
        'eventosCronograma' => EventoCronogramaAdminController::class,
        'edicoes' => EdicaoPublicaController::class,
    ];

    public function despachar($r)
    {
        $partes = explode('/', trim($r, '/'));
        $modulo = $partes[0] !== '' ? $partes[0] : 'home';
        $acao = isset($partes[1]) && $partes[1] !== '' ? $partes[1] : 'index';
        $parametros = array_slice($partes, 2);

        try {
            $configuracao = (new ConfiguracaoSistemaRepository())->buscar();
            $timeoutMinutos = $configuracao !== false ? (int) $configuracao['sessao_timeout_minutos'] : 30;
            // isset() (nao so' $configuracao !== false): a coluna sistema_desativado
            // (migration 100) pode ainda nao existir no banco - acessar uma chave
            // ausente sem isset() gera Notice, que e' impresso ANTES de qualquer
            // header(), quebrando todo header('Location: ...') dali pra frente
            // (ex.: redirecionamento do login com Google) com "headers already
            // sent". Ausente = sistema ativo (fail-open), nunca desativado por engano.
            $sistemaDesativado = $configuracao !== false
                && isset($configuracao['sistema_desativado'])
                && (int) $configuracao['sistema_desativado'] === 1;
        } catch (\PDOException $e) {
            // Tabela configuracoes_sistema pode ainda nao existir (migration 053
            // nao aplicada) - nunca derrubar a aplicacao inteira por isso.
            $timeoutMinutos = 30;
            $sistemaDesativado = false;
        }

        // Fase 25: modo de manutencao. Bloqueia TUDO exceto o modulo 'auth'
        // (pro administrador conseguir entrar) e um administrador ja
        // autenticado. HTTP e' sem estado - nao da' pra "derrubar" uma aba ja
        // aberta na hora, so' bloquear a proxima acao dela; sessao de quem
        // nao e' administrador e' encerrada nesse momento.
        if ($sistemaDesativado && $modulo !== 'auth') {
            $ehAdministrador = Auth::autenticado() && Auth::temPerfil('administrador');

            if (!$ehAdministrador) {
                if (Auth::autenticado()) {
                    Auth::logout();
                }
                http_response_code(503);
                require __DIR__ . '/../Views/erro/manutencao.php';
                exit;
            }
        }

        if (Auth::autenticado()) {
            if (!Auth::validarAtividade($timeoutMinutos * 60)) {
                header('Location: ' . url('auth/login') . '&expirado=1');
                exit;
            }

            // Fase 17 (Melhoria 2): durante "visualizar como outro usuario",
            // so' leitura (GET) e' permitida - qualquer outra requisicao e'
            // bloqueada, exceto a propria rota de sair da visualizacao.
            $ehRotaPararVisualizacao = $modulo === 'meuPerfil' && $acao === 'pararVisualizacao';

            if (Auth::estaVisualizandoComoOutro() && $_SERVER['REQUEST_METHOD'] !== 'GET' && !$ehRotaPararVisualizacao) {
                http_response_code(403);
                exit('Ação bloqueada: modo de visualização somente leitura. Volte para sua conta de administrador para realizar ações.');
            }

            // Fase 31 (Auditoria de Seguranca, achado #1/#3): protecao CSRF
            // central - cobre toda acao de escrita de uma vez, sem precisar
            // validar em cada Controller. Token aceito via campo de form
            // (campoCsrf(), em app/helpers.php) ou header X-CSRF-Token
            // (usado pelos 2 pontos que enviam POST via fetch() em vez de
            // <form>: editor-rico.js e reordenar-arrastar.js).
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $tokenRecebido = isset($_POST['csrf_token'])
                    ? $_POST['csrf_token']
                    : (isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '');

                if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                    http_response_code(403);
                    exit('Sessão expirada ou requisição inválida. Recarregue a página e tente novamente.');
                }
            }
        }

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
