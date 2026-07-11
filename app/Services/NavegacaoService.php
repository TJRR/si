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
            ['tipo' => 'resultado_etapa', 'rotulo' => 'Resultado', 'rota' => 'resultados/etapa'],
            ['tipo' => 'formulario_vinculado', 'rotulo' => 'Formulário vinculado', 'rota' => 'etapas/formularioVinculado'],
        ],
    ];

    private static $grupoPorTipo = [
        'trilha' => 'trilha',
        'temas' => 'trilha',
        'inscritos' => 'trilha',
        'apuracao' => 'trilha',
        'etapa' => 'etapa',
        'criterios' => 'etapa',
        'formula_etapa' => 'etapa',
        'designacoes' => 'etapa',
        'resultado_etapa' => 'etapa',
        'formulario_vinculado' => 'etapa',
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
        $abas = [];

        foreach (self::$abasPorGrupo[$grupo] as $definicao) {
            $abas[] = [
                'rotulo' => $definicao['rotulo'],
                'url' => $definicao['rota'] . '/' . (int) $id,
                'ativa' => $definicao['tipo'] === $tipo,
            ];
        }

        return $abas;
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

        if (in_array($tipo, ['concurso', 'formularios', 'trilhas'], true)) {
            $concurso = $concursos->buscarPorId($id);

            if ($concurso === null) {
                return [];
            }

            $caminho = [self::noConcurso($concurso)];

            if ($tipo === 'formularios') {
                $caminho[] = self::noFormularios($concurso);
            } elseif ($tipo === 'trilhas') {
                $caminho[] = self::noTrilhas($concurso);
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

                return [self::noFormularios($concurso), self::noTrilhas($concurso)];

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
