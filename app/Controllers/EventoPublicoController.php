<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\ConfiguracaoVisualRepository;
use App\Repositories\ContatoConcursoRepository;
use App\Repositories\EventoBannerRepository;
use App\Repositories\EventoBlocoConteudoRepository;
use App\Repositories\EventoConfiguracaoVisualRepository;
use App\Repositories\EventoDocumentoRepository;
use App\Repositories\EventoSecaoOrdemRepository;
use App\Repositories\EventoSecaoCartoesRepository;
use App\Repositories\EventoSecaoContagemRepository;
use App\Repositories\EventoSecaoCronogramaRepository;
use App\Repositories\EventoSecaoDestaquesRepository;
use App\Repositories\EventoSecaoFaqRepository;
use App\Repositories\EventoSecaoLocalRepository;
use App\Repositories\EventoSecaoProgramacaoRepository;
use App\Repositories\EventoSlideRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TemaVisualRepository;

/**
 * Fase 50: pagina publica de entrada propria de cada Evento
 * (index.php?r=evento/index/{id}) - mesma estrutura da home do Concurso
 * (cabecalho/slideshow/faixas/blocos), mas cada evento tem seu proprio
 * conjunto (ver EventoSlideRepository/EventoBannerRepository/
 * EventoBlocoConteudoRepository). Zero integracao com o Concurso: nada
 * aqui referencia concursos/trilhas/temas, e a home do Concurso nao
 * referencia nada daqui (decisao confirmada no plano da fase).
 *
 * $id e sempre obrigatorio, sem "evento mais recente" como alternativa
 * (diferente de EventoInscricaoPublicaController::index($id = null)) -
 * cada evento tem sua propria pagina exclusiva, nunca um "padrao" quando
 * houver mais de um ativo. A pagina so responde enquanto
 * evento_configuracao_visual.publicado = 1 (o Admin controla isso na
 * sub-aba Cabecalho) - evento em configuracao nao fica exposto por id
 * antes da hora.
 */
class EventoPublicoController extends Controller
{
    public function index($id = null)
    {
        if ($id === null) {
            http_response_code(404);
            exit('Página não encontrada.');
        }

        $evento = (new SemanaInovacaoRepository())->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $configuracaoVisualEvento = (new EventoConfiguracaoVisualRepository())->buscarPorEvento($id);

        if ($configuracaoVisualEvento === null || (int) $configuracaoVisualEvento['publicado'] !== 1) {
            http_response_code(404);
            exit('Página ainda não disponível.');
        }

        $repositorioOrdem = new EventoSecaoOrdemRepository();
        $repositorioOrdem->garantirSecoesFixas($id);

        $slidesAtivos = (new EventoSlideRepository())->listarAtivos($id);
        $bannersAtivos = (new EventoBannerRepository())->listarAtivos($id);
        $blocosAtivos = (new EventoBlocoConteudoRepository())->listarAtivos($id);
        $blocosPorId = array_column($blocosAtivos, null, 'id');

        // Fase 51: a pagina inteira e montada a partir da ordem cadastrada
        // (quadros, faixas, cada bloco e cada componente), e nao mais de
        // uma sequencia fixa com os blocos no fim. Cada instancia de
        // componente ja vem com os itens dela resolvidos, para a view so
        // desenhar.
        $secoesOrdenadas = $this->carregarSecoes($id, $repositorioOrdem->listarAtivas($id));

        // Menu dinamico do cabecalho: sai da mesma lista de secoes, na
        // mesma ordem da pagina, mais o Contato do rodape.
        $menu = $repositorioOrdem->listarMenu($id);
        $menu[] = ['ancora' => 'contato', 'rotulo' => 'Contato'];

        // Tema visual (cores) e logo: compartilhados com o Concurso, mesma
        // logica de app/Views/home/index.php - logoAtual(true) resolve a
        // logo especifica do Evento (com fallback pra logo do site).
        $temaAtivo = (new TemaVisualRepository())->resolverAtivo(Auth::autenticado() ? Auth::usuarioId() : null);

        // Fase 51: a logo oficial do evento, quando cadastrada, vence a do
        // tema de cor - identidade visual aprovada nao pode variar conforme
        // o tema escolhido por quem visita a pagina.
        $logoSrc = !empty($configuracaoVisualEvento['logo_path'])
            ? config('base_path') . '/assets/' . $configuracaoVisualEvento['logo_path']
            : logoAtual(true);

        // Rodape: literalmente o mesmo partial de home/_rodape.php (rodape
        // e unico/compartilhado, decisao confirmada no plano da fase). Os
        // campos rodape_*/favicon_path que ele le vem da configuracao
        // GLOBAL (configuracoes_visuais); ja _cabecalho.php (tambem
        // reaproveitado) le os campos cabecalho_* deste evento
        // especifico - como as duas partials leem $configVisual pelo
        // mesmo nome mas de fontes diferentes, a solucao e mesclar as
        // duas: nao ha sobreposicao real de chaves entre cabecalho_* e
        // rodape_*/favicon_path. $menuRodape vazio porque as ancoras que
        // ele lista (#trilhas/#cronograma/#temas) nao existem nesta
        // pagina.
        $configVisualGlobal = (new ConfiguracaoVisualRepository())->buscar();
        $configVisual = array_merge(
            $configVisualGlobal !== false ? $configVisualGlobal : [],
            $configuracaoVisualEvento
        );
        $contato = (new ContatoConcursoRepository())->buscar();

        $this->renderizar('publico/evento_home', [
            'evento' => $evento,
            'slides' => $slidesAtivos,
            'banners' => $bannersAtivos,
            'blocosAtivos' => $blocosAtivos,
            'secoesOrdenadas' => $secoesOrdenadas,
            'blocosPorId' => $blocosPorId,
            'menu' => $menu,
            'menuRodape' => [],
            'temaAtivo' => $temaAtivo,
            'logoSrc' => $logoSrc,
            'altLogoTexto' => !empty($configuracaoVisualEvento['logo_alt']) ? $configuracaoVisualEvento['logo_alt'] : $evento['nome'],
            'temLogoEvento' => !empty($configuracaoVisualEvento['logo_path']),
            'urlFontesEvento' => EventoConfiguracaoVisualRepository::urlFontes($configuracaoVisualEvento),
            'fonteTituloEvento' => !empty($configuracaoVisualEvento['fonte_titulo']) ? $configuracaoVisualEvento['fonte_titulo'] : null,
            'fonteTextoEvento' => !empty($configuracaoVisualEvento['fonte_texto']) ? $configuracaoVisualEvento['fonte_texto'] : null,
            'configVisual' => $configVisual,
            'contato' => $contato,
        ], $evento['nome']);
    }

    /**
     * Para cada secao ativa, junta o que ela precisa para ser desenhada.
     * Quadros, faixas e blocos ja foram carregados de uma vez; os
     * componentes sao buscados aqui, um por instancia, porque cada tipo tem
     * tabela propria (e o Admin pode ter varias instancias do mesmo tipo).
     */
    private function carregarSecoes($eventoId, array $secoes)
    {
        $repositorios = [
            'contagem' => new EventoSecaoContagemRepository(),
            'cronograma' => new EventoSecaoCronogramaRepository(),
            'cartoes' => new EventoSecaoCartoesRepository(),
            'destaques' => new EventoSecaoDestaquesRepository(),
            'programacao' => new EventoSecaoProgramacaoRepository(),
            'faq' => new EventoSecaoFaqRepository(),
            'local' => new EventoSecaoLocalRepository(),
        ];

        $resolvidas = [];

        foreach ($secoes as $secao) {
            $tipo = $secao['tipo'];

            if (!isset($repositorios[$tipo])) {
                $resolvidas[] = $secao;
                continue;
            }

            $repositorio = $repositorios[$tipo];
            $dados = $repositorio->buscarDoEvento($eventoId, (int) $secao['referencia_id']);

            if ($dados === null) {
                continue;
            }

            $secao['dados'] = $dados;
            $secao['itens'] = [];

            if ($tipo === 'cartoes') {
                $secao['itens'] = $repositorio->listarItensResolvidos((int) $secao['referencia_id']);
            } elseif ($tipo === 'destaques') {
                $secao['itens'] = $dados['fonte'] === 'atividades'
                    ? $repositorio->listarAtividadesDestacadas($eventoId)
                    : $repositorio->listarItensResolvidos((int) $secao['referencia_id']);
            } elseif ($tipo === 'programacao') {
                $secao['itens'] = $dados['fonte'] === 'atividades'
                    ? $repositorio->listarAtividadesDoEvento($eventoId)
                    : $repositorio->listarItens((int) $secao['referencia_id']);
            } elseif ($tipo === 'cronograma') {
                $secao['itens'] = $repositorio->listarItens((int) $secao['referencia_id']);
                $secao['dados']['botao1_url'] = $this->destinoBotaoDocumento($eventoId, $dados, 'botao1');
                $secao['dados']['botao3_url'] = $this->destinoBotaoDocumento($eventoId, $dados, 'botao3');
            } elseif ($tipo === 'faq') {
                $secao['itens'] = $repositorio->listarItensAtivos((int) $secao['referencia_id']);
            } elseif ($tipo !== 'local') {
                $secao['itens'] = $repositorio->listarItens((int) $secao['referencia_id']);
            }

            $resolvidas[] = $secao;
        }

        return $resolvidas;
    }

    /**
     * Reabertura da Fase 51: destino de um botao com documento da secao de
     * submissao ($prefixo 'botao1' ou 'botao3'). Com documento escolhido,
     * abre a versao vigente e publicada dele (retificacao nova entra
     * sozinha); documento despublicado ou removido esconde o botao, em vez de
     * cair no endereco digitado. Sem documento, vale o endereco digitado.
     */
    private function destinoBotaoDocumento($eventoId, array $dados, $prefixo = 'botao1')
    {
        if (!empty($dados[$prefixo . '_documento_id'])) {
            $documento = (new EventoDocumentoRepository())->buscarVigentePublicado($eventoId, (int) $dados[$prefixo . '_documento_id']);

            return $documento !== null ? config('base_path') . '/assets/' . $documento['arquivo_path'] : '';
        }

        return linkPublico(isset($dados[$prefixo . '_link']) ? $dados[$prefixo . '_link'] : '');
    }
}
