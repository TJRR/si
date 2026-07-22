<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\ConcursoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\TrilhaRepository;

/**
 * Unica fonte de verdade da hierarquia Concurso > Trilha > Etapa usada pela
 * arvore lateral e pelas abas do painel admin (substitui os montarBreadcrumb()
 * que antes ficavam duplicados em cada controller). Cada "no" da arvore/abas e
 * identificado por um par (tipo, id) — ver app/Views/admin/_arvore.php para o
 * desenho da hierarquia completa.
 */
class NavegacaoService
{
    private static $abasPorGrupo = [
        'trilha' => [
            ['tipo' => 'trilha', 'rotulo' => 'Dados Gerais', 'rota' => 'trilhas/editar'],
            ['tipo' => 'temas', 'rotulo' => 'Temas/Desafios', 'rota' => 'temas/index'],
            ['tipo' => 'inscritos', 'rotulo' => 'Inscritos', 'rota' => 'homologacao/index'],
            ['tipo' => 'apuracao', 'rotulo' => 'Apuração', 'rota' => 'apuracao/index'],
        ],
        'etapa' => [
            ['tipo' => 'etapa', 'rotulo' => 'Dados Gerais', 'rota' => 'etapas/editar'],
            ['tipo' => 'criterios', 'rotulo' => 'Critérios', 'rota' => 'criterios/index'],
            ['tipo' => 'formula_etapa', 'rotulo' => 'Fórmula', 'rota' => 'formulas/etapa'],
            ['tipo' => 'designacoes', 'rotulo' => 'Avaliadores', 'rota' => 'designacoes/index'],
            ['tipo' => 'vagas_avaliador', 'rotulo' => 'Vagas por categoria', 'rota' => 'vagasAvaliador/index'],
            ['tipo' => 'resultado_etapa', 'rotulo' => 'Resultado', 'rota' => 'resultados/etapa'],
            ['tipo' => 'formulario_vinculado', 'rotulo' => 'Formulário vinculado', 'rota' => 'etapas/formularioVinculado'],
        ],
        /**
         * Fase 19 (#84 v2): Tema/Slideshow/Banners/Blocos/Contato deixaram
         * de ser escopados por concurso - viram sub-abas globais da tela
         * "Configuração", sem id (ver abasPara() abaixo).
         */
        'configuracao' => [
            ['tipo' => 'configuracaoGeral', 'rotulo' => 'Geral', 'rota' => 'configuracoes/index'],
            ['tipo' => 'configuracaoTema', 'rotulo' => 'Tema', 'rota' => 'tema/index'],
            ['tipo' => 'configuracaoMidia', 'rotulo' => 'Mídia', 'rota' => 'midia/index'],
            ['tipo' => 'configuracaoCabecalho', 'rotulo' => 'Cabeçalho', 'rota' => 'tema/cabecalho'],
            ['tipo' => 'configuracaoRodape', 'rotulo' => 'Rodapé', 'rota' => 'tema/rodape'],
            ['tipo' => 'configuracaoSlides', 'rotulo' => 'Slideshow', 'rota' => 'slides/index'],
            ['tipo' => 'configuracaoBanners', 'rotulo' => 'Banners', 'rota' => 'banners/index'],
            ['tipo' => 'configuracaoBlocos', 'rotulo' => 'Blocos de conteúdo', 'rota' => 'blocos/index'],
            ['tipo' => 'configuracaoContato', 'rotulo' => 'Contato', 'rota' => 'contatosConcurso/index'],
            ['tipo' => 'configuracaoOrdenacao', 'rotulo' => 'Ordenação', 'rota' => 'ordenacaoHome/index'],
        ],
    ];

    /**
     * Abas do grupo "etapa" que só fazem sentido quando mecanismo_avaliacao
     * está salvo como 'avaliadores' — usado tanto para a filtragem inicial
     * (servidor) quanto para marcar o data-attribute que o JS usa pra
     * reagir à troca do select #campo-mecanismo-avaliacao antes de salvar
     * (ver assets/js/navegacao-arvore.js).
     */
    private static $tiposSomenteAvaliadores = ['criterios', 'formula_etapa', 'designacoes', 'vagas_avaliador', 'resultado_etapa'];

    private static $grupoPorTipo = [
        'trilha' => 'trilha',
        'temas' => 'trilha',
        'inscritos' => 'trilha',
        'apuracao' => 'trilha',
        'etapa' => 'etapa',
        'criterios' => 'etapa',
        'formula_etapa' => 'etapa',
        'designacoes' => 'etapa',
        'vagas_avaliador' => 'etapa',
        'resultado_etapa' => 'etapa',
        'formulario_vinculado' => 'etapa',
        'configuracaoGeral' => 'configuracao',
        'configuracaoTema' => 'configuracao',
        'configuracaoMidia' => 'configuracao',
        'configuracaoCabecalho' => 'configuracao',
        'configuracaoRodape' => 'configuracao',
        'configuracaoSlides' => 'configuracao',
        'configuracaoBanners' => 'configuracao',
        'configuracaoBlocos' => 'configuracao',
        'configuracaoContato' => 'configuracao',
        'configuracaoOrdenacao' => 'configuracao',
    ];

    /**
     * Abas horizontais do no atual (null se o tipo nao pertence a nenhum grupo
     * com abas — ex.: concurso, formularios, trilhas, etapas).
     */
    public static function abasPara($tipo, $id)
    {
        if (!isset(self::$grupoPorTipo[$tipo])) {
            return null;
        }

        $grupo = self::$grupoPorTipo[$tipo];
        $definicoes = self::$abasPorGrupo[$grupo];

        if ($grupo === 'etapa' && !\App\Core\Auth::possuiPerfil('administrador')) {
            // Perfil não-admin nunca edita mecanismo_avaliacao (select vem
            // desabilitado), então aqui a filtragem pode continuar fixa —
            // sem abas somente-avaliadores para reagir a nada client-side.
            $definicoes = array_values(array_filter($definicoes, function ($definicao) {
                return $definicao['tipo'] === 'etapa';
            }));
        }

        $abas = [];

        foreach ($definicoes as $definicao) {
            $abas[] = [
                'rotulo' => $definicao['rotulo'],
                'url' => $id !== null ? $definicao['rota'] . '/' . (int) $id : $definicao['rota'],
                'ativa' => $definicao['tipo'] === $tipo,
                'somenteAvaliadores' => $grupo === 'etapa' && in_array($definicao['tipo'], self::$tiposSomenteAvaliadores, true),
            ];
        }

        return $abas;
    }

    /**
     * Valor persistido de mecanismo_avaliacao da etapa do no atual (null se o
     * tipo não pertence ao grupo "etapa" ou a etapa não existe) — usado pelo
     * JS para decidir a visibilidade inicial das abas somente-avaliadores nas
     * páginas do grupo "etapa" que não têm o select #campo-mecanismo-avaliacao
     * na tela (só a aba "Dados Gerais" tem).
     */
    public static function mecanismoAvaliacaoEtapa($tipo, $id)
    {
        if (!isset(self::$grupoPorTipo[$tipo]) || self::$grupoPorTipo[$tipo] !== 'etapa') {
            return null;
        }

        $etapa = (new EtapaRepository())->buscarPorId($id);

        return $etapa !== null ? $etapa['mecanismo_avaliacao'] : null;
    }

    /**
     * Cadeia de nos ancestrais (sem incluir a raiz "Concursos", que nunca e
     * lazy) ate o no que deve ficar destacado na arvore para o tipo/id atual —
     * usada para pre-expandir a arvore no primeiro carregamento (deep-link).
     */
    public static function caminhoAte($tipo, $id)
    {
        $concursos = new ConcursoRepository();
        $trilhas = new TrilhaRepository();
        $etapas = new EtapaRepository();

        if (in_array($tipo, ['concurso', 'formularios', 'trilhas', 'categorias_avaliador', 'premios', 'faqConcurso', 'documentos', 'eventosCronograma', 'mentorias'], true)) {
            $concurso = $concursos->buscarPorId($id);

            if ($concurso === null) {
                return [];
            }

            $caminho = [self::noConcurso($concurso)];

            if ($tipo === 'formularios') {
                $caminho[] = self::noFormularios($concurso);
            } elseif ($tipo === 'trilhas') {
                $caminho[] = self::noTrilhas($concurso);
            } elseif ($tipo === 'categorias_avaliador') {
                $caminho[] = self::noCategoriasAvaliador($concurso);
            } elseif ($tipo === 'premios') {
                $caminho[] = self::noPremios($concurso);
            } elseif ($tipo === 'faqConcurso') {
                $caminho[] = self::noFaqConcurso($concurso);
            } elseif ($tipo === 'documentos') {
                $caminho[] = self::noDocumentos($concurso);
            } elseif ($tipo === 'eventosCronograma') {
                $caminho[] = self::noEventosCronograma($concurso);
            } elseif ($tipo === 'mentorias') {
                $caminho[] = self::noMentorias($concurso);
            }

            return $caminho;
        }

        if (isset(self::$grupoPorTipo[$tipo]) && self::$grupoPorTipo[$tipo] === 'trilha') {
            $trilha = $trilhas->buscarPorId($id);

            if ($trilha === null) {
                return [];
            }

            $concurso = $concursos->buscarPorId($trilha['concurso_id']);

            return [self::noConcurso($concurso), self::noTrilhas($concurso), self::noTrilha($trilha)];
        }

        if ($tipo === 'etapas') {
            $trilha = $trilhas->buscarPorId($id);

            if ($trilha === null) {
                return [];
            }

            $concurso = $concursos->buscarPorId($trilha['concurso_id']);

            return [self::noConcurso($concurso), self::noTrilhas($concurso), self::noTrilha($trilha), self::noEtapas($trilha)];
        }

        if (isset(self::$grupoPorTipo[$tipo]) && self::$grupoPorTipo[$tipo] === 'etapa') {
            $etapa = $etapas->buscarPorId($id);

            if ($etapa === null) {
                return [];
            }

            $trilha = $trilhas->buscarPorId($etapa['trilha_id']);
            $concurso = $concursos->buscarPorId($trilha['concurso_id']);

            return [
                self::noConcurso($concurso),
                self::noTrilhas($concurso),
                self::noTrilha($trilha),
                self::noEtapas($trilha),
                self::noEtapa($etapa),
            ];
        }

        return [];
    }

    /**
     * Filhos diretos de um no da arvore, usados tanto na pre-expansao (modo
     * completo) quanto no endpoint de carregamento lazy (NavegacaoController).
     */
    public static function filhosDe($tipo, $id)
    {
        switch ($tipo) {
            case 'raiz':
                $lista = [];
                foreach ((new ConcursoRepository())->listar() as $concurso) {
                    $lista[] = self::noConcurso($concurso);
                }

                return $lista;

            case 'concurso':
                $concurso = (new ConcursoRepository())->buscarPorId($id);

                if ($concurso === null) {
                    return [];
                }

                if (!\App\Core\Auth::possuiPerfil('administrador')) {
                    return [self::noTrilhas($concurso), self::noMentorias($concurso)];
                }

                return [
                    self::noCategoriasAvaliador($concurso),
                    self::noFormularios($concurso),
                    self::noPremios($concurso),
                    self::noFaqConcurso($concurso),
                    self::noDocumentos($concurso),
                    self::noEventosCronograma($concurso),
                    self::noMentorias($concurso),
                    self::noTrilhas($concurso),
                ];

            case 'trilhas':
                $lista = [];
                foreach ((new TrilhaRepository())->listarPorConcurso($id) as $trilha) {
                    $lista[] = self::noTrilha($trilha);
                }

                return $lista;

            case 'trilha':
                $trilha = (new TrilhaRepository())->buscarPorId($id);

                if ($trilha === null) {
                    return [];
                }

                return [self::noEtapas($trilha)];

            case 'etapas':
                $lista = [];
                foreach ((new EtapaRepository())->listarPorTrilha($id) as $etapa) {
                    $lista[] = self::noEtapa($etapa);
                }

                return $lista;

            default:
                return [];
        }
    }

    private static function noConcurso(array $concurso)
    {
        return [
            'tipo' => 'concurso',
            'id' => (int) $concurso['id'],
            'rotulo' => $concurso['nome'],
            'folha' => false,
            'url' => 'concursos/editar/' . (int) $concurso['id'],
        ];
    }

    private static function noFormularios(array $concurso)
    {
        return [
            'tipo' => 'formularios',
            'id' => (int) $concurso['id'],
            'rotulo' => 'Formulários',
            'folha' => true,
            'url' => 'formularios/index/' . (int) $concurso['id'],
        ];
    }

    private static function noCategoriasAvaliador(array $concurso)
    {
        return [
            'tipo' => 'categorias_avaliador',
            'id' => (int) $concurso['id'],
            'rotulo' => 'Categorias de avaliador',
            'folha' => true,
            'url' => 'categoriasAvaliador/index/' . (int) $concurso['id'],
        ];
    }

    /**
     * Fase 19 (#106): visivel pra Administrador e Suporte, os dois podem
     * ser mentores (auto-servico, sem perfil novo).
     */
    private static function noMentorias(array $concurso)
    {
        return [
            'tipo' => 'mentorias',
            'id' => (int) $concurso['id'],
            'rotulo' => 'Mentorias',
            'folha' => true,
            'url' => 'mentoriaAdmin/index/' . (int) $concurso['id'],
        ];
    }

    private static function noPremios(array $concurso)
    {
        return [
            'tipo' => 'premios',
            'id' => (int) $concurso['id'],
            'rotulo' => 'Premiação',
            'folha' => true,
            'url' => 'premios/index/' . (int) $concurso['id'],
        ];
    }

    private static function noFaqConcurso(array $concurso)
    {
        return [
            'tipo' => 'faqConcurso',
            'id' => (int) $concurso['id'],
            'rotulo' => 'FAQ desta edição',
            'folha' => true,
            'url' => 'faqConcurso/index/' . (int) $concurso['id'],
        ];
    }

    private static function noDocumentos(array $concurso)
    {
        return [
            'tipo' => 'documentos',
            'id' => (int) $concurso['id'],
            'rotulo' => 'Documentos',
            'folha' => true,
            'url' => 'documentos/index/' . (int) $concurso['id'],
        ];
    }

    private static function noEventosCronograma(array $concurso)
    {
        return [
            'tipo' => 'eventosCronograma',
            'id' => (int) $concurso['id'],
            'rotulo' => 'Cronograma (eventos)',
            'folha' => true,
            'url' => 'eventosCronograma/index/' . (int) $concurso['id'],
        ];
    }

    private static function noTrilhas(array $concurso)
    {
        return [
            'tipo' => 'trilhas',
            'id' => (int) $concurso['id'],
            'rotulo' => 'Trilhas',
            'folha' => false,
            'url' => 'trilhas/index/' . (int) $concurso['id'],
        ];
    }

    private static function noTrilha(array $trilha)
    {
        return [
            'tipo' => 'trilha',
            'id' => (int) $trilha['id'],
            'rotulo' => $trilha['nome'],
            'folha' => false,
            'url' => 'trilhas/editar/' . (int) $trilha['id'],
        ];
    }

    private static function noEtapas(array $trilha)
    {
        return [
            'tipo' => 'etapas',
            'id' => (int) $trilha['id'],
            'rotulo' => 'Etapas',
            'folha' => false,
            'url' => 'etapas/index/' . (int) $trilha['id'],
        ];
    }

    private static function noEtapa(array $etapa)
    {
        return [
            'tipo' => 'etapa',
            'id' => (int) $etapa['id'],
            'rotulo' => $etapa['nome'],
            'folha' => true,
            'url' => 'etapas/editar/' . (int) $etapa['id'],
        ];
    }
}
