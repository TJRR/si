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
use App\Repositories\DesafioRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\EventoCronogramaRepository;
use App\Repositories\FaqConcursoRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\HomeSecaoOrdemRepository;
use App\Repositories\HomologacaoPublicaRepository;
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
        $desafios = new DesafioRepository();
        $formularios = new FormularioDinamicoRepository();
        $servicoResultadoEtapa = new ResultadoEtapaService();

        $trilhasAtivas = $trilhas->listarPorConcurso($concursoId);
        $homologacaoPublica = new HomologacaoPublicaRepository();

        $cronograma = [];
        $temasPorTrilha = [];
        $trilhasComInscricaoAberta = [];
        $etapasComResultadoPublicado = [];
        $trilhasComHomologacaoPublicada = [];

        foreach ($trilhasAtivas as $trilha) {
            // Fase 19 (#17): "Equipes Homologadas" e' um conceito diferente
            // de "Resultados" (ranking avaliado) - a Etapa de Cadastro nao
            // tem avaliacao, entao nao entra em $etapasComResultadoPublicado.
            if ($homologacaoPublica->jaPublicado($trilha['id'])) {
                $trilhasComHomologacaoPublicada[] = [
                    'trilha_id' => $trilha['id'],
                    'trilha_nome' => $trilha['nome'],
                ];
            }

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

            // Fase 19 (#103): cada Tema ja carrega os proprios Desafios
            // ativos, pra aparecerem dentro do card do Tema na home.
            $temasDaTrilha = $temas->listarAtivosPorTrilha($trilha['id']);
            foreach ($temasDaTrilha as &$temaComDesafios) {
                $temaComDesafios['desafios'] = array_values(array_filter(
                    $desafios->listarPorTema($temaComDesafios['id']),
                    function ($desafio) {
                        return (int) $desafio['ativo'] === 1;
                    }
                ));
            }
            unset($temaComDesafios);

            $temasPorTrilha[] = [
                'trilha' => $trilha,
                'temas' => $temasDaTrilha,
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

        $blocosAtivos = (new BlocoConteudoRepository())->listarAtivos();
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

        // Fase 19 (#97): ordem das secoes do meio da home, definida pelo
        // Admin (aba "Ordenação") - $blocosPorId so' tem os blocos ATIVOS
        // (mesmo array de $blocosAtivos), entao um bloco inativo some da
        // home mesmo tendo uma linha em home_secoes_ordem.
        $secoesOrdenadas = (new HomeSecaoOrdemRepository())->listarOrdenado();
        $blocosPorId = array_column($blocosAtivos, null, 'id');

        $premios = (new PremioRepository())->listarPorConcurso($concursoId);
        $faqAtivas = (new FaqConcursoRepository())->listarAtivasPorConcurso($concursoId);
        $documentos = (new DocumentoRepository())->listarAtivosPorConcurso($concursoId);
        $contato = (new ContatoConcursoRepository())->buscar();
        $configVisual = (new ConfiguracaoVisualRepository())->buscar();

        // Menu dinamico do cabecalho (3.1/5): so' entram secoes que
        // realmente tem conteudo/estao ativas - nenhuma ancora hardcoded.
        // Fase 19 (#86): "Trilhas" e "Cronograma" sairam do menu - o
        // cronograma agora tem acesso rapido pelo icone de calendario no
        // cabecalho (ver _painel_cronograma.php), e "Trilhas" nao tinha
        // uso real como atalho de menu.
        $menu = [];

        if ($blocoSobre !== null) {
            $menu[] = ['ancora' => 'sobre', 'rotulo' => $blocoSobre['titulo']];
        }

        if (!empty($temasPorTrilha)) {
            $menu[] = ['ancora' => 'temas', 'rotulo' => 'Desafios'];
        }

        if ($blocoPremiacao !== null || !empty($premios)) {
            $menu[] = ['ancora' => 'premiacao', 'rotulo' => $blocoPremiacao !== null ? $blocoPremiacao['titulo'] : 'Premiação'];
        }

        foreach ($blocosLivres as $bloco) {
            if ($bloco['mostrar_no_menu']) {
                $menu[] = ['ancora' => $bloco['secao_ancora'], 'rotulo' => $bloco['titulo']];
            }
        }

        if (!empty($faqAtivas)) {
            $menu[] = ['ancora' => 'faq', 'rotulo' => 'Dúvidas'];
        }

        $menu[] = ['ancora' => 'contato', 'rotulo' => 'Contato'];

        // Fase 19 (#84 v2): coluna "Navegação" do rodapé tem visibilidade
        // independente do menu superior - o admin escolhe pela aba
        // "Rodapé" quais dessas secoes aparecem la, mesmo que nao
        // apareçam mais no menu de cima (caso de Trilhas/Cronograma,
        // removidos do menu no #86).
        $menuRodape = [];

        if (!empty($configVisual['rodape_mostrar_trilhas'])) {
            $menuRodape[] = ['ancora' => 'trilhas', 'rotulo' => 'Trilhas'];
        }

        if ($blocoSobre !== null && $blocoSobre['mostrar_no_rodape']) {
            $menuRodape[] = ['ancora' => 'sobre', 'rotulo' => $blocoSobre['titulo']];
        }

        if (!empty($configVisual['rodape_mostrar_cronograma'])) {
            $menuRodape[] = ['ancora' => 'cronograma', 'rotulo' => 'Cronograma'];
        }

        if (!empty($configVisual['rodape_mostrar_desafios']) && !empty($temasPorTrilha)) {
            $menuRodape[] = ['ancora' => 'temas', 'rotulo' => 'Desafios'];
        }

        $mostrarPremiacaoRodape = $blocoPremiacao !== null ? $blocoPremiacao['mostrar_no_rodape'] : !empty($premios);
        if ($mostrarPremiacaoRodape) {
            $menuRodape[] = ['ancora' => 'premiacao', 'rotulo' => $blocoPremiacao !== null ? $blocoPremiacao['titulo'] : 'Premiação'];
        }

        foreach ($blocosLivres as $bloco) {
            if ($bloco['mostrar_no_rodape']) {
                $menuRodape[] = ['ancora' => $bloco['secao_ancora'], 'rotulo' => $bloco['titulo']];
            }
        }

        if (!empty($configVisual['rodape_mostrar_contato'])) {
            $menuRodape[] = ['ancora' => 'contato', 'rotulo' => 'Contato'];
        }

        $this->renderizar(
            'home/index',
            [
                'concursoAtivo' => $concursoAtivo,
                'configVisual' => $configVisual,
                'menu' => $menu,
                'menuRodape' => $menuRodape,
                'slides' => (new SlideRepository())->listarAtivos(),
                'banners' => (new BannerRepository())->listarAtivos(),
                'blocoSobre' => $blocoSobre,
                'blocoPremiacao' => $blocoPremiacao,
                'blocosLivres' => $blocosLivres,
                'secoesOrdenadas' => $secoesOrdenadas,
                'blocosPorId' => $blocosPorId,
                'premios' => $premios,
                'trilhasAtivas' => $trilhasAtivas,
                'documentos' => $documentos,
                'cronograma' => $cronograma,
                'temasPorTrilha' => $temasPorTrilha,
                'trilhasComInscricaoAberta' => $trilhasComInscricaoAberta,
                'etapasComResultadoPublicado' => $etapasComResultadoPublicado,
                'trilhasComHomologacaoPublicada' => $trilhasComHomologacaoPublicada,
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
    public function enviarContato()
    {
        $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
        $mensagem = trim(isset($_POST['mensagem']) ? $_POST['mensagem'] : '');

        if ($nome === '' || $email === '' || $mensagem === '') {
            $_SESSION['flash'] = 'Preencha nome, e-mail e mensagem.';
            $this->redirecionar('home/index#contato');
            return;
        }

        (new MensagemContatoRepository())->criar($nome, $email, $mensagem);

        $contato = (new ContatoConcursoRepository())->buscar();

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
        // Fase 19 (#108): exigir() checa o perfil sem concurso_id (null) -
        // falha pra quem e' administrador/suporte de UM concurso especifico
        // (nao globalmente), porque Auth::temPerfil() so' aceita concurso_id
        // null (global) ou igual ao concurso informado. Este painel lista
        // TODOS os concursos, entao so' precisamos saber que o usuario tem
        // o perfil em algum lugar - mesmo padrao ja usado em
        // AvaliacaoController::index() pro mesmo motivo (perfil escopado).
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);

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
