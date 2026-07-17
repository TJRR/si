<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Core\Mailer;
use App\Middleware\RoleMiddleware;
use App\Repositories\BannerRepository;
use App\Repositories\BlocoConteudoRepository;
use App\Repositories\ConcursoRepository;
use App\Repositories\ConfiguracaoVisualRepository;
use App\Repositories\ContatoConcursoRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\EventoCronogramaRepository;
use App\Repositories\FaqConcursoRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\MensagemContatoRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\PremioRepository;
use App\Repositories\SlideRepository;
use App\Repositories\TemaRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioRepository;
use App\Services\ResultadoEtapaService;

class HomeController extends Controller
{
    public function index()
    {
        $concursos = new ConcursoRepository();
        $concursoAtivo = $concursos->buscarAtivo();

        if ($concursoAtivo === null) {
            $this->renderizar('home/index', [
                'concursoAtivo' => null,
            ], 'Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do TJRR');
            return;
        }

        $concursoId = $concursoAtivo['id'];

        $trilhas = new TrilhaRepository();
        $etapas = new EtapaRepository();
        $temas = new TemaRepository();
        $formularios = new FormularioDinamicoRepository();
        $servicoResultadoEtapa = new ResultadoEtapaService();

        $trilhasAtivas = $trilhas->listarPorConcurso($concursoId);

        $cronograma = [];
        $temasPorTrilha = [];
        $trilhasComInscricaoAberta = [];
        $etapasComResultadoPublicado = [];

        foreach ($trilhasAtivas as $trilha) {
            foreach ($etapas->listarPorTrilha($trilha['id']) as $etapa) {
                $cronograma[] = [
                    'tipo' => 'etapa',
                    'nome' => $etapa['nome'],
                    'trilha_nome' => $trilha['nome'],
                    'descricao' => $etapa['descricao'],
                    'data_inicio' => $etapa['data_inicio'],
                    'data_fim' => $etapa['data_fim'],
                ];

                if ($servicoResultadoEtapa->jaPublicado($etapa['id'])) {
                    $etapasComResultadoPublicado[] = [
                        'etapa_id' => $etapa['id'],
                        'etapa_nome' => $etapa['nome'],
                        'trilha_nome' => $trilha['nome'],
                    ];
                }

                if ((int) $etapa['ordem'] === 1 && $etapa['captura_ativa']) {
                    $formularioDaEtapa = $etapa['formulario_dinamico_id'] !== null
                        ? $formularios->buscarPorId($etapa['formulario_dinamico_id'])
                        : null;

                    if ($formularioDaEtapa !== null && $formularioDaEtapa['status'] === 'publicado') {
                        $trilhasComInscricaoAberta[] = [
                            'trilha_nome' => $trilha['nome'],
                            'etapa_id' => $etapa['id'],
                        ];
                    }
                }
            }

            $temasPorTrilha[] = [
                'trilha' => $trilha,
                'temas' => $temas->listarAtivosPorTrilha($trilha['id']),
            ];
        }

        // Fase 18 (3.9): cronograma consolidado tambem recebe os eventos
        // avulsos cadastrados manualmente (cerimonia, live) - misturados
        // as Etapas reais e ordenados so' por data, sem distincao de fonte
        // na ordenacao (a view decide como exibir cada tipo).
        foreach ((new EventoCronogramaRepository())->listarPorConcurso($concursoId) as $evento) {
            $cronograma[] = [
                'tipo' => 'evento',
                'nome' => $evento['titulo'],
                'trilha_nome' => null,
                'descricao' => $evento['descricao'],
                'data_inicio' => $evento['data_inicio'],
                'data_fim' => $evento['data_fim'],
            ];
        }

        usort($cronograma, function ($a, $b) {
            return strcmp((string) $a['data_inicio'], (string) $b['data_inicio']);
        });

        $blocosAtivos = (new BlocoConteudoRepository())->listarAtivosPorConcurso($concursoId);
        $blocoSobre = null;
        $blocoPremiacao = null;
        $blocosLivres = [];

        foreach ($blocosAtivos as $bloco) {
            if ($bloco['chave'] === 'sobre') {
                $blocoSobre = $bloco;
            } elseif ($bloco['chave'] === 'premiacao') {
                $blocoPremiacao = $bloco;
            } else {
                $blocosLivres[] = $bloco;
            }
        }

        $premios = (new PremioRepository())->listarPorConcurso($concursoId);
        $faqAtivas = (new FaqConcursoRepository())->listarAtivasPorConcurso($concursoId);
        $documentos = (new DocumentoRepository())->listarAtivosPorConcurso($concursoId);
        $contato = (new ContatoConcursoRepository())->buscarPorConcurso($concursoId);
        $configVisual = (new ConfiguracaoVisualRepository())->buscarEfetivaPorConcurso($concursoId);

        // Menu dinamico do cabecalho (3.1/5): so' entram secoes que
        // realmente tem conteudo/estao ativas - nenhuma ancora hardcoded.
        $menu = [];
        $menu[] = ['ancora' => 'trilhas', 'rotulo' => 'Trilhas'];

        if ($blocoSobre !== null) {
            $menu[] = ['ancora' => 'sobre', 'rotulo' => $blocoSobre['titulo']];
        }

        $menu[] = ['ancora' => 'cronograma', 'rotulo' => 'Cronograma'];

        if (!empty($etapasComResultadoPublicado)) {
            $menu[] = ['ancora' => 'resultados', 'rotulo' => 'Resultados'];
        }

        if (!empty($temasPorTrilha)) {
            $menu[] = ['ancora' => 'temas', 'rotulo' => 'Desafios'];
        }

        if ($blocoPremiacao !== null || !empty($premios)) {
            $menu[] = ['ancora' => 'premiacao', 'rotulo' => $blocoPremiacao !== null ? $blocoPremiacao['titulo'] : 'Premiação'];
        }

        foreach ($blocosLivres as $bloco) {
            $menu[] = ['ancora' => $bloco['secao_ancora'], 'rotulo' => $bloco['titulo']];
        }

        if (!empty($faqAtivas)) {
            $menu[] = ['ancora' => 'faq', 'rotulo' => 'Dúvidas'];
        }

        $menu[] = ['ancora' => 'edicoes-anteriores', 'rotulo' => 'Edições Anteriores', 'externa' => true, 'url' => 'edicoes/index'];
        $menu[] = ['ancora' => 'contato', 'rotulo' => 'Contato'];

        $this->renderizar(
            'home/index',
            [
                'concursoAtivo' => $concursoAtivo,
                'configVisual' => $configVisual,
                'menu' => $menu,
                'slides' => (new SlideRepository())->listarAtivosPorConcurso($concursoId),
                'banners' => (new BannerRepository())->listarAtivosPorConcurso($concursoId),
                'blocoSobre' => $blocoSobre,
                'blocoPremiacao' => $blocoPremiacao,
                'blocosLivres' => $blocosLivres,
                'premios' => $premios,
                'trilhasAtivas' => $trilhasAtivas,
                'documentos' => $documentos,
                'cronograma' => $cronograma,
                'temasPorTrilha' => $temasPorTrilha,
                'trilhasComInscricaoAberta' => $trilhasComInscricaoAberta,
                'etapasComResultadoPublicado' => $etapasComResultadoPublicado,
                'faqAtivas' => $faqAtivas,
                'contato' => $contato,
            ],
            'Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do TJRR'
        );
    }

    /**
     * Fase 18 (3.12): recebe o envio do formulario de contato nativo,
     * quando ativado (contatos_concurso.formulario_contato_ativo). Sempre
     * grava a mensagem primeiro - se o e-mail falhar (SMTP fora do ar), a
     * mensagem nao se perde, so' o aviso imediato ao admin que nao chega.
     */
    public function enviarContato($concursoId)
    {
        $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
        $mensagem = trim(isset($_POST['mensagem']) ? $_POST['mensagem'] : '');

        if ($nome === '' || $email === '' || $mensagem === '') {
            $_SESSION['flash'] = 'Preencha nome, e-mail e mensagem.';
            $this->redirecionar('home/index#contato');
            return;
        }

        (new MensagemContatoRepository())->criar($concursoId, $nome, $email, $mensagem);

        $contato = (new ContatoConcursoRepository())->buscarPorConcurso($concursoId);

        if ($contato !== null && !empty($contato['email'])) {
            $corpo = '<p><strong>Nome:</strong> ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><strong>E-mail:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><strong>Mensagem:</strong><br>' . nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8')) . '</p>';
            Mailer::enviar($contato['email'], 'Nova mensagem via formulário de contato', $corpo);
        }

        $_SESSION['flash'] = 'Mensagem enviada com sucesso. Em breve entraremos em contato.';
        $this->redirecionar('home/index#contato');
    }

    public function administrativo()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);

        $concursos = (new ConcursoRepository())->listar();
        $concursosAtivos = array_filter($concursos, function ($concurso) {
            return $concurso['status'] === 'ativo';
        });
        $concursosRealizados = array_filter($concursos, function ($concurso) {
            return $concurso['status'] === 'encerrado';
        });

        $this->renderizar('home/administrativo', [
            'totalParticipantes' => (new ParticipanteRepository())->contarTodos(),
            'totalEquipes' => count((new EquipeRepository())->listarComContagemParticipantes()),
            'totalAvaliadores' => (new PerfilRepository())->contarDistintosPorPerfil('avaliador'),
            'totalConcursosAtivos' => count($concursosAtivos),
            'totalConcursosRealizados' => count($concursosRealizados),
            'totalCadastrosPendentes' => count((new UsuarioRepository())->listarPendentes()),
        ], 'Painel');
    }

    /**
     * Rota de exemplo para validar o controle de acesso por concurso
     * (papel 'avaliador' restrito a um concurso especifico, nao global).
     */
    public function painel($concursoId = null)
    {
        RoleMiddleware::exigir(['administrador', 'avaliador'], $concursoId);
        $this->renderizar('home/index', ['concursoId' => $concursoId], 'Painel');
    }
}
