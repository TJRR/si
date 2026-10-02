<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Repositories\BonusCreditoRepository;
use App\Repositories\BonusRepository;
use App\Repositories\CompeticaoParticipacaoRepository;
use App\Repositories\CredenciamentoLocalRepository;
use App\Repositories\DivulgacaoComprovacaoRepository;
use App\Repositories\EstandeVisitaRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\GamificacaoConfigRepository;
use App\Repositories\GamificacaoDesempateRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PresencaCreditoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\BonusApuracaoService;
use App\Services\GamificacaoService;
use App\Services\PresencaPontuacaoService;

/**
 * Fase 58: no "Gamificacao" do Evento - classificacao geral (soma de todas
 * as origens de pontos), credenciamento no local, cascata de desempate,
 * configuracao e encerramento da gincana.
 *
 * Suporte le, como nos demais modulos do Evento; toda gravacao exige
 * Administrador. Depois do encerramento, nada que mova pontos ou a ordem da
 * classificacao e' aceito (reconferencia, desempate); as opcoes de exibicao
 * continuam editaveis, porque nao mudam a ordem.
 */
class GamificacaoAdminController extends Controller
{
    private $eventos;
    private $config;
    private $desempate;
    private $credenciamento;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->config = new GamificacaoConfigRepository();
        $this->desempate = new GamificacaoDesempateRepository();
        $this->credenciamento = new CredenciamentoLocalRepository();
    }

    private function eventoOu404($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        return $evento;
    }

    /**
     * Classificacao completa, com o total por origem. Falha de banco mostra
     * "classificacao indisponivel", nunca numeros errados.
     */
    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $classificacao = [];
        $indisponivel = false;

        try {
            $classificacao = (new GamificacaoService())->classificacao($id);
        } catch (\Throwable $e) {
            error_log('[Gamificacao] Falha ao montar a classificacao do evento ' . $id . ': ' . $e->getMessage());
            $indisponivel = true;
        }

        $busca = isset($_GET['busca']) ? trim((string) $_GET['busca']) : '';

        $this->renderizar('admin/gamificacao/index', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'classificacao' => $this->filtrarClassificacao($classificacao, $busca),
            'totalClassificados' => count($classificacao),
            'filtros' => ['busca' => $busca],
            'indisponivel' => $indisponivel,
            'gincanaEncerrada' => GamificacaoService::encerrada($id),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Gamificação: ' . $evento['nome'], ['tipo' => 'gamificacao', 'id' => $id]);
    }

    /**
     * A busca filtra a lista JA' CLASSIFICADA, em PHP, como a tela de
     * Usuarios faz: a posicao de cada pessoa continua sendo a do evento
     * inteiro, e nao a da lista reduzida. Filtrar no banco mudaria a
     * posicao, que e' o dado principal desta tela.
     */
    private function filtrarClassificacao(array $classificacao, $busca)
    {
        if ($busca === '') {
            return $classificacao;
        }

        $procurado = mb_strtolower($busca);

        return array_values(array_filter($classificacao, function ($linha) use ($procurado) {
            return mb_strpos(mb_strtolower($linha['nome']), $procurado) !== false
                || mb_strpos(mb_strtolower((string) $linha['email']), $procurado) !== false;
        }));
    }

    /**
     * Extrato de uma pessoa: linha da classificacao, presencas e
     * participacoes pontuadas, bonus, divulgacao e estandes. Conexoes
     * aparecem so' em total, pela decisao registrada em
     * ConexaoAdminController (a administracao nao lista quem se conectou com
     * quem).
     */
    public function extrato($eventoId, $inscricaoId = null)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $inscricao = (new EventoInscricaoRepository())->buscarPorId((int) $inscricaoId);

        if ($inscricao === null || (int) $inscricao['evento_id'] !== $id) {
            http_response_code(404);
            exit('Inscrição não encontrada neste evento.');
        }

        $soma = null;

        try {
            $soma = (new GamificacaoService())->somaDaInscricao($id, (int) $inscricao['id']);
        } catch (\Throwable $e) {
            error_log('[Gamificacao] Falha ao somar os pontos da inscricao ' . (int) $inscricao['id'] . ': ' . $e->getMessage());
        }

        $nomesBonus = [];

        foreach ((new BonusRepository())->listarPorEvento($id) as $bonus) {
            $nomesBonus[(int) $bonus['id']] = $bonus['nome'];
        }

        $this->renderizar('admin/gamificacao/extrato', [
            'evento' => $evento,
            'inscricao' => $inscricao,
            'participante' => (new UsuarioRepository())->buscarPorId((int) $inscricao['usuario_id']),
            'soma' => $soma,
            'presencas' => (new PresencaCreditoRepository())->listarDaInscricao((int) $inscricao['id']),
            'participacoes' => (new CompeticaoParticipacaoRepository())->listarDaInscricao((int) $inscricao['id']),
            'bonus' => (new BonusCreditoRepository())->resumoParticipante((int) $inscricao['id']),
            'nomesBonus' => $nomesBonus,
            'divulgacao' => (new DivulgacaoComprovacaoRepository())->listarDaInscricao((int) $inscricao['id']),
            'estandes' => (new EstandeVisitaRepository())->resumoParticipante((int) $inscricao['id']),
        ], 'Extrato de pontos: ' . $evento['nome'], ['tipo' => 'gamificacao', 'id' => $id]);
    }

    /**
     * Classificacao em planilha de texto, no molde da exportacao de Bonus:
     * e' a lista do balcao de entrega das mudas.
     */
    public function exportar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        try {
            $classificacao = $this->filtrarClassificacao(
                (new GamificacaoService())->classificacao($id),
                isset($_GET['busca']) ? trim((string) $_GET['busca']) : ''
            );
        } catch (\Throwable $e) {
            error_log('[Gamificacao] Falha ao exportar a classificacao do evento ' . $id . ': ' . $e->getMessage());
            flashErro('A classificação está indisponível no momento. Tente de novo em alguns instantes.');
            $this->redirecionar('gamificacao/index/' . $id);
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="classificacao-evento-' . $id . '.csv"');

        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF"); // BOM UTF-8, pro Excel abrir acentuacao certo.
        fputcsv($saida, [
            'Posição', 'Empate', 'Nome', 'Correio eletrônico', 'Total',
            'Presença', 'Competições', 'Conexões', 'Estandes', 'Divulgação', 'Bônus', 'Último ponto em',
        ], ';');

        foreach ($classificacao as $linha) {
            fputcsv($saida, [
                (int) $linha['posicao'],
                $linha['empatado'] ? 'Sim' : 'Não',
                $linha['nome'],
                $linha['email'],
                (int) $linha['total'],
                (int) $linha['por_origem']['presenca'],
                (int) $linha['por_origem']['competicoes'],
                (int) $linha['por_origem']['conexoes'],
                (int) $linha['por_origem']['estandes'],
                (int) $linha['por_origem']['divulgacao'],
                (int) $linha['por_origem']['bonus'],
                $linha['ultimo_ponto_em'],
            ], ';');
        }

        fclose($saida);
        exit;
    }

    /**
     * Reconferencia do evento inteiro: cria o credito de presenca que falta
     * (pelo horario gravado da leitura) e apura os bonus, inclusive as
     * acoes do participante feitas antes de o bonus existir. As duas
     * apuracoes nao dependem uma da outra: os bonus leem as presencas, nao
     * os creditos de presenca.
     */
    public function reconferir($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('gamificacao/index/' . $id);
            return;
        }

        if (GamificacaoService::encerrada($id)) {
            flashErro('A gincana deste evento foi encerrada e a classificação está congelada: não há o que reconferir.');
            $this->redirecionar('gamificacao/index/' . $id);
            return;
        }

        $presencas = (new PresencaPontuacaoService())->reapurarEvento($evento);
        $bonus = (new BonusApuracaoService())->apurarEvento($evento);

        flashSucesso('Pontuação reconferida: ' . $presencas . ($presencas === 1 ? ' crédito de presença novo' : ' créditos de presença novos')
            . ' e ' . $bonus . ($bonus === 1 ? ' crédito de bônus novo.' : ' créditos de bônus novos.'));
        $this->redirecionar('gamificacao/index/' . $id);
    }

    /**
     * Credenciamento no local: liga e desliga, janela da leitura, codigo
     * (gerado ao ligar pela primeira vez) e a lista de quem se credenciou.
     */
    public function credenciamento($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);

            $inicio = $this->dataHoraOuNulo(isset($_POST['leitura_inicio']) ? $_POST['leitura_inicio'] : '');
            $fim = $this->dataHoraOuNulo(isset($_POST['leitura_fim']) ? $_POST['leitura_fim'] : '');

            if (($inicio === null) !== ($fim === null)) {
                flashErro('Informe o início e o fim da leitura, ou deixe os dois em branco para valerem os dias do evento.');
                $this->redirecionar('gamificacao/credenciamento/' . $id);
                return;
            }

            if ($inicio !== null && strtotime($fim) <= strtotime($inicio)) {
                flashErro('O fim da leitura precisa ser depois do início.');
                $this->redirecionar('gamificacao/credenciamento/' . $id);
                return;
            }

            $this->credenciamento->salvarConfig($id, [
                'ativo' => isset($_POST['ativo']),
                'leitura_inicio' => $inicio,
                'leitura_fim' => $fim,
            ]);

            flashSucesso('Credenciamento no local salvo.');
            $this->redirecionar('gamificacao/credenciamento/' . $id);
            return;
        }

        $filtros = $this->filtrosDeBuscaEPeriodo();

        $this->renderizar('admin/gamificacao/credenciamento', [
            'evento' => $evento,
            'config' => $this->credenciamento->configVigente($id),
            'credenciados' => $this->credenciamento->listarPorEvento($id, $filtros),
            'filtros' => $filtros,
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Credenciamento no local: ' . $evento['nome'], ['tipo' => 'gamificacaoCredenciamento', 'id' => $id]);
    }

    /**
     * Remove em lote o credenciamento marcado, para desfazer uma leitura feita
     * por engano. Lista vazia e' recusada.
     */
    public function credenciamentoRemover($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('gamificacao/credenciamento/' . $id);
            return;
        }

        $ids = isset($_POST['credenciamento_ids']) && is_array($_POST['credenciamento_ids']) ? $_POST['credenciamento_ids'] : [];

        if ($ids === []) {
            flashAlerta('Selecione ao menos uma pessoa.');
            $this->redirecionar('gamificacao/credenciamento/' . $id);
            return;
        }

        $removidos = $this->credenciamento->removerEmLote($id, $ids);

        if ($removidos === []) {
            flashAlerta('Nenhum dos credenciamentos marcados foi encontrado neste evento.');
            $this->redirecionar('gamificacao/credenciamento/' . $id);
            return;
        }

        // O bonus do tipo credenciamento deixa de ser devido para quem
        // perdeu o registro, e e' anulado PELO SISTEMA: se a pessoa se
        // credenciar de novo dentro da janela, ele volta sozinho. A
        // reapuracao roda num bloco de protecao proprio, porque a remocao e'
        // o fato principal e falha aqui nunca pode transformar uma remocao
        // bem sucedida em erro na tela.
        $mensagem = count($removidos) . ' credenciamento(s) removido(s).';

        if (!GamificacaoService::encerrada($id)) {
            try {
                $bonus = new BonusApuracaoService();
                $anulados = [];

                foreach ($removidos as $linha) {
                    foreach ($bonus->reapurarAposRemocaoDeCredenciamento($evento, (int) $linha['evento_inscricao_id']) as $credito) {
                        $anulados[] = ['usuario_id' => (int) $linha['usuario_id']] + $credito;
                    }
                }

                if ($anulados !== []) {
                    $this->avisarBonusAnulados($evento, $anulados);
                    $mensagem .= ' ' . count($anulados) . ' bônus de credenciamento foi(ram) anulado(s), e as pessoas atingidas foram avisadas.';
                }
            } catch (\Throwable $e) {
                error_log('[Credenciamento] Falha ao reapurar os bonus do evento ' . $id . ': ' . $e->getMessage());
                $mensagem .= ' Os bônus não puderam ser reconferidos agora; use "Reconferir agora" na Classificação.';
            }
        }

        flashSucesso($mensagem);
        $this->redirecionar('gamificacao/credenciamento/' . $id);
    }

    /**
     * Aviso no sino de quem perdeu o bonus de credenciamento. Um aviso por
     * bonus, com o motivo que o servico gerou, no molde de
     * AtividadeAdminController::avisarBonusAnulados().
     */
    private function avisarBonusAnulados(array $evento, array $anulados)
    {
        $sino = new NotificacaoPainelRepository();

        foreach ($anulados as $anulado) {
            if (empty($anulado['usuario_id'])) {
                continue;
            }

            $sino->criar(
                (int) $anulado['usuario_id'],
                'bonus_anulacao',
                'Pontos de bônus removidos',
                'O bônus "' . $anulado['nome'] . '" em ' . $evento['nome'] . ' foi anulado. ' . $anulado['motivo'],
                ['url' => url('eventoApp/index/' . (int) $evento['id'])]
            );
        }
    }

    /**
     * Exportacao de quem se credenciou, respeitando o filtro da tela.
     */
    public function credenciamentoExportar($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $linhas = $this->credenciamento->listarPorEvento($id, $this->filtrosDeBuscaEPeriodo());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="credenciamento-evento-' . $id . '.csv"');

        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF"); // BOM UTF-8, pro Excel abrir acentuacao certo.
        fputcsv($saida, ['Nome', 'Correio eletrônico', 'Credenciado em'], ';');

        foreach ($linhas as $linha) {
            fputcsv($saida, [
                $linha['participante_nome'],
                $linha['participante_email'],
                formatarDataHora($linha['credenciado_em']),
            ], ';');
        }

        fclose($saida);
        exit;
    }

    /**
     * Busca livre e periodo, a parte comum das barras de filtro desta aba.
     * Convencao do projeto: campo em branco vira '' e nao entra na consulta.
     */
    private function filtrosDeBuscaEPeriodo()
    {
        $filtros = [];

        foreach (['busca', 'data_inicio', 'data_fim'] as $campo) {
            $filtros[$campo] = isset($_GET[$campo]) ? trim((string) $_GET[$campo]) : '';
        }

        return $filtros;
    }

    /**
     * Cartaz do codigo do credenciamento, para as paredes do auditorio e o
     * balcao. Pagina solta, sem layout.php, no molde do cartaz de atividade.
     */
    public function credenciamentoCartaz($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $config = $this->credenciamento->configVigente((int) $evento['id']);

        if (empty($config['codigo'])) {
            flashAlerta('Ligue o credenciamento no local para gerar o código.');
            $this->redirecionar('gamificacao/credenciamento/' . (int) $evento['id']);
            return;
        }

        echo View::renderizarString('admin/gamificacao/cartaz_codigo', [
            'evento' => $evento,
            'titulo' => 'Credenciamento',
            'subtitulo' => 'Confirme a sua chegada ao evento',
            'codigo' => $config['codigo'],
            'instrucao' => 'Abra o aplicativo do evento, toque em "Ler código" e aponte a câmera para este código.',
        ]);
    }

    public function desempate($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $escolhidos = $this->desempate->listarCriterios($id);
        $usados = [];

        foreach ($escolhidos as $linha) {
            $usados[$linha['criterio']] = true;
        }

        $this->renderizar('admin/gamificacao/desempate', [
            'evento' => $evento,
            'escolhidos' => $escolhidos,
            'disponiveis' => array_diff_key(GamificacaoService::CRITERIOS_DESEMPATE, $usados),
            'gincanaEncerrada' => GamificacaoService::encerrada($id),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Desempate: ' . $evento['nome'], ['tipo' => 'gamificacaoDesempate', 'id' => $id]);
    }

    public function desempateAdicionar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $this->recusarDesempateSeEncerrada($id)) {
            $this->redirecionar('gamificacao/desempate/' . $id);
            return;
        }

        $criterio = isset($_POST['criterio']) ? (string) $_POST['criterio'] : '';

        if (!isset(GamificacaoService::CRITERIOS_DESEMPATE[$criterio])) {
            flashErro('Escolha um critério da lista.');
        } elseif (!$this->desempate->adicionar($id, $criterio)) {
            flashAlerta('Este critério já está na cascata.');
        } else {
            flashSucesso('Critério acrescentado ao fim da cascata.');
        }

        $this->redirecionar('gamificacao/desempate/' . $id);
    }

    public function desempateRemover($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $this->recusarDesempateSeEncerrada($id)) {
            $this->redirecionar('gamificacao/desempate/' . $id);
            return;
        }

        $this->desempate->remover($id, (int) (isset($_POST['id']) ? $_POST['id'] : 0));
        flashSucesso('Critério retirado da cascata.');
        $this->redirecionar('gamificacao/desempate/' . $id);
    }

    /**
     * Reordenacao por arrasto (assets/js/reordenar-arrastar.js), resposta
     * JSON. Depois do encerramento, recusa com 409: a ordem final da
     * classificacao nao pode mudar.
     */
    public function desempateReordenar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        header('Content-Type: application/json; charset=utf-8');

        if (GamificacaoService::encerrada((int) $evento['id'])) {
            http_response_code(409);
            echo json_encode(['ok' => false]);
            return;
        }

        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->desempate->reordenar((int) $evento['id'], $ids);

        echo json_encode(['ok' => true]);
    }

    public function configuracoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);

            $visivel = isset($_POST['classificacao_visivel']);
            $quantidade = isset($_POST['classificacao_quantidade']) ? trim((string) $_POST['classificacao_quantidade']) : '';
            $minutos = isset($_POST['minutos_pontualidade']) ? trim((string) $_POST['minutos_pontualidade']) : '';

            if ($visivel && (!ctype_digit($quantidade) || (int) $quantidade < 1 || (int) $quantidade > 10000)) {
                flashErro('Com a classificação visível, informe quantos primeiros aparecem para os inscritos (de 1 a 10000).');
                $this->redirecionar('gamificacao/configuracoes/' . $id);
                return;
            }

            if ($minutos !== '' && (!ctype_digit($minutos) || (int) $minutos > 240)) {
                flashErro('Os minutos de antecedência do extra de pontualidade vão de 0 a 240, ou em branco para o extra não valer.');
                $this->redirecionar('gamificacao/configuracoes/' . $id);
                return;
            }

            $this->config->salvar($id, [
                'ativo' => isset($_POST['ativo']),
                'classificacao_visivel' => $visivel,
                'classificacao_quantidade' => ctype_digit($quantidade) ? min(10000, (int) $quantidade) : 0,
                'classificacao_mostrar_nomes' => isset($_POST['classificacao_mostrar_nomes']),
                'minutos_pontualidade' => $minutos !== '' ? (int) $minutos : null,
                'texto_regras_html' => isset($_POST['texto_regras_html']) && trim((string) $_POST['texto_regras_html']) !== ''
                    ? sanitizarHtmlRico($_POST['texto_regras_html'])
                    : null,
            ]);

            flashSucesso('Configurações da gamificação salvas. Mudar os minutos do extra vale só para as próximas presenças.');
            $this->redirecionar('gamificacao/configuracoes/' . $id);
            return;
        }

        $this->renderizar('admin/gamificacao/configuracoes', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'gincanaEncerrada' => GamificacaoService::encerrada($id),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Configurações da gamificação: ' . $evento['nome'], ['tipo' => 'gamificacaoConfiguracoes', 'id' => $id]);
    }

    /**
     * Encerramento da gincana (decisao do dono): agendado para data e hora
     * futuras, ou imediato com o nome do evento redigitado. Enquanto o
     * instante nao chega, pode mudar ou ser retirado; quando chega, e'
     * definitivo - GamificacaoConfigRepository::definirEncerramento() recusa
     * no proprio UPDATE.
     */
    public function encerramento($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('gamificacao/configuracoes/' . $id);
            return;
        }

        if (GamificacaoService::encerrada($id)) {
            flashErro('A gincana já foi encerrada, e o encerramento é definitivo.');
            $this->redirecionar('gamificacao/configuracoes/' . $id);
            return;
        }

        $acao = isset($_POST['acao']) ? (string) $_POST['acao'] : '';
        $instante = null;

        if ($acao === 'agendar') {
            $instante = $this->dataHoraOuNulo(isset($_POST['encerramento_em']) ? $_POST['encerramento_em'] : '');

            if ($instante === null || strtotime($instante) <= time()) {
                flashErro('Informe uma data e hora futuras para o encerramento.');
                $this->redirecionar('gamificacao/configuracoes/' . $id);
                return;
            }
        } elseif ($acao === 'agora') {
            $confirmacao = trim(isset($_POST['confirmacao']) ? (string) $_POST['confirmacao'] : '');

            if ($confirmacao !== $evento['nome']) {
                flashErro('Confirmação incorreta: digite exatamente o nome do evento para encerrar a gincana agora.');
                $this->redirecionar('gamificacao/configuracoes/' . $id);
                return;
            }

            $instante = date('Y-m-d H:i:s');
        } elseif ($acao !== 'retirar') {
            $this->redirecionar('gamificacao/configuracoes/' . $id);
            return;
        }

        if (!$this->config->definirEncerramento($id, $instante, Auth::usuarioId())) {
            flashErro('O encerramento já aconteceu e não pode mais ser alterado.');
            $this->redirecionar('gamificacao/configuracoes/' . $id);
            return;
        }

        GamificacaoService::esquecerEncerramento($id);

        if ($acao === 'agora') {
            flashSucesso('Gincana encerrada. A partir de agora nada pontua, e a classificação está congelada.');
        } elseif ($acao === 'agendar') {
            flashSucesso('Encerramento agendado para ' . formatarDataHora($instante) . '. Até lá, ele pode ser alterado ou retirado.');
        } else {
            flashSucesso('Encerramento agendado retirado.');
        }

        $this->redirecionar('gamificacao/configuracoes/' . $id);
    }

    /**
     * Trava N4 do plano: depois do encerramento, mudar a cascata mudaria a
     * ordem final da classificacao e quem entra entre os primeiros.
     */
    private function recusarDesempateSeEncerrada($eventoId)
    {
        if (!GamificacaoService::encerrada($eventoId)) {
            return false;
        }

        flashErro('A gincana deste evento foi encerrada: a cascata de desempate não pode mais mudar.');

        return true;
    }

    /**
     * Converte o valor de um campo datetime-local ("2026-11-04T08:00") para
     * o formato do banco, ou null quando em branco ou invalido.
     */
    private function dataHoraOuNulo($valor)
    {
        $valor = is_string($valor) ? trim($valor) : '';

        if ($valor === '' || preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2})?$/', $valor) !== 1) {
            return null;
        }

        $instante = strtotime(str_replace('T', ' ', $valor));

        return $instante !== false ? date('Y-m-d H:i:s', $instante) : null;
    }
}
