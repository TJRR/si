<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\DivulgacaoComprovacaoRepository;
use App\Repositories\DivulgacaoConfigRepository;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\ArquivoPrivadoService;
use App\Services\DivulgacaoService;
use App\Services\GamificacaoService;

/**
 * Fase 56: Divulgacao (no da arvore irmao de Atividades, Trabalhos,
 * Estandes e Conexoes). Suporte le, como nos demais modulos do Evento; toda
 * gravacao exige Administrador.
 *
 * Duas diferencas em relacao a Conexoes, as duas deliberadas:
 *
 * 1. A lista aqui e' NOMINAL. Em Conexoes o par e' dado pessoal de terceiro
 *    e por isso a tela administrativa e' so' agregada; aqui a conferencia
 *    por amostragem prevista no documento da dinamica de pontos depende de
 *    comparar a prova com a conta que a pessoa cadastrou em "Meu Perfil",
 *    o que exige o nome ao lado da prova.
 *
 * 2. A imagem de comprovacao e' servida SO' ao Administrador. E' o unico
 *    lugar desta fase com dado pessoal de terceiro que nao escolheu estar
 *    ali (quem aparece na captura de tela), e a analise e' atribuicao do
 *    Administrador. Suporte ve lista, numeros, endereco e a marca de prova
 *    repetida, e nao abre imagem.
 */
class DivulgacaoAdminController extends Controller
{
    /**
     * Fase 58: a divulgacao nao tem limite de envios por pessoa, entao a
     * lista de comprovacoes e' a unica tela do Evento que pagina. Mesmo
     * tamanho de pagina da Auditoria.
     */
    const POR_PAGINA = 50;

    private $eventos;
    private $comprovacoes;
    private $config;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->comprovacoes = new DivulgacaoComprovacaoRepository();
        $this->config = new DivulgacaoConfigRepository();
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
     * Acompanhamento do modulo: estado, numeros e totais. A lista nominal
     * mudou de lugar na Fase 58, para a aba "Comprovações".
     */
    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $this->renderizar('admin/divulgacao/index', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'redes' => $this->config->redesIncluidas($id),
            'numeros' => $this->comprovacoes->numerosPorEvento($id),
            'porRedeETipo' => $this->comprovacoes->contarPorRedeETipo($id),
            'porDia' => $this->comprovacoes->contarPorDia($id),
            'janelaTexto' => (new DivulgacaoService())->janelaTexto($evento, $this->config->vigente($id)),
            'rotulosRede' => $this->rotulosDeTodasAsRedes(),
            'gincanaEncerrada' => GamificacaoService::encerrada($id),
        ], 'Divulgação: ' . $evento['nome'], ['tipo' => 'divulgacao', 'id' => $id]);
    }

    /**
     * Filtros da tela Comprovações. Convencao do projeto (AuditoriaAdmin):
     * campo em branco vira '' e simplesmente nao entra na consulta.
     */
    private function filtrosDaRequisicao()
    {
        $filtros = [];

        foreach (['busca', 'rede', 'tipo_acao', 'situacao', 'data_inicio', 'data_fim'] as $campo) {
            $filtros[$campo] = isset($_GET[$campo]) ? trim((string) $_GET[$campo]) : '';
        }

        return $filtros;
    }

    /**
     * Rotulo de toda rede da lista fechada. A lista aqui e' a completa, e
     * nao so' a das redes incluidas no evento, porque uma comprovacao antiga
     * pode ser de rede retirada depois, e a tela precisa saber nomea-la.
     */
    private function rotulosDeTodasAsRedes()
    {
        return array_map(function ($rede) {
            return $rede['rotulo'];
        }, DivulgacaoConfigRepository::REDES);
    }

    /**
     * Redes que de fato tem comprovacao neste evento, para o filtro. Sai de
     * contarPorRedeETipo(), que a tela de acompanhamento ja' usa: nenhuma
     * consulta nova.
     */
    private function redesComEnvio($eventoId)
    {
        $rotulos = $this->rotulosDeTodasAsRedes();
        $redes = [];

        foreach ($this->comprovacoes->contarPorRedeETipo($eventoId) as $linha) {
            $chave = $linha['rede'];
            $redes[$chave] = isset($rotulos[$chave]) ? $rotulos[$chave] : $chave;
        }

        return $redes;
    }

    private function acoesComEnvio($eventoId)
    {
        $rotulos = ['publicacao' => 'Publicação', 'acompanhar' => 'Passou a seguir'];
        $acoes = [];

        foreach ($this->comprovacoes->contarPorRedeETipo($eventoId) as $linha) {
            $chave = $linha['tipo_acao'];

            if (isset($rotulos[$chave])) {
                $acoes[$chave] = $rotulos[$chave];
            }
        }

        return $acoes;
    }

    /**
     * Exportacao em texto separado por ponto e virgula, respeitando o filtro
     * da tela, no molde de AuditoriaAdminController::exportarCsv().
     */
    public function exportar($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $rotulos = $this->rotulosDeTodasAsRedes();

        $linhas = $this->comprovacoes->listarPorEvento($id, $this->filtrosDaRequisicao());

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="comprovacoes-divulgacao-' . $id . '.csv"');

        $saida = fopen('php://output', 'w');
        // Marca de ordem de bytes, para a planilha abrir a acentuacao certa.
        fwrite($saida, "\xEF\xBB\xBF");
        fputcsv($saida, ['Participante', 'Correio eletrônico', 'Rede', 'Rede informada', 'Ação', 'Pontos', 'Enviado em', 'Situação', 'Motivo'], ';');

        foreach ($linhas as $linha) {
            fputcsv($saida, [
                $linha['participante_nome'],
                $linha['participante_email'],
                isset($rotulos[$linha['rede']]) ? $rotulos[$linha['rede']] : $linha['rede'],
                (string) $linha['rede_informada'],
                $linha['tipo_acao'] === 'acompanhar' ? 'Passou a seguir' : 'Publicação',
                (int) $linha['pontos_creditados'],
                formatarDataHora($linha['enviado_em']),
                $this->situacaoDaComprovacao($linha),
                (string) $linha['motivo_anulacao'],
            ], ';');
        }

        fclose($saida);
        exit;
    }

    private function situacaoDaComprovacao(array $linha)
    {
        if ($linha['excluido_em'] !== null) {
            return 'Excluída';
        }

        if ($linha['anulado_em'] !== null) {
            return 'Anulada';
        }

        return (int) $linha['pontos_creditados'] > 0 ? 'Válida' : 'Válida, sem pontos';
    }

    /**
     * Imagem de comprovacao, so' para o Administrador. Suporte, que entra no
     * construtor, para aqui.
     */
    public function imagem($eventoId, $comprovacaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $comprovacao = $this->comprovacoes->buscarPorId($comprovacaoId);

        if ($comprovacao === null || (int) $comprovacao['evento_id'] !== (int) $evento['id']) {
            http_response_code(404);
            exit('Comprovação não encontrada.');
        }

        if (empty($comprovacao['arquivo_path'])) {
            http_response_code(404);
            exit('Esta imagem foi apagada conforme a política de retenção de dados do evento.');
        }

        ArquivoPrivadoService::servirImagem($comprovacao['arquivo_path'], $comprovacao['arquivo_nome']);
    }

    /**
     * Anula a pontuacao de uma comprovacao, com justificativa obrigatoria.
     *
     * A linha nao e' apagada: ela sai de toda soma e de todo contador de
     * teto, o que devolve a vaga a pessoa e, no caso de "acompanhar",
     * devolve a possibilidade de registrar de novo. O que nao volta e' a
     * prova, que continua barrada pelos resumos gravados.
     */
    public function anular($eventoId, $comprovacaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        // Fase 58: depois do encerramento da gincana a classificacao fica
        // congelada, e nenhuma anulacao ou reversao move pontos.
        if (GamificacaoService::encerrada($id)) {
            flashErro('A gincana deste evento foi encerrada e a classificação está congelada: nenhuma pontuação pode ser alterada.');
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        $comprovacao = $this->comprovacoes->buscarPorId($comprovacaoId);

        if ($comprovacao === null || (int) $comprovacao['evento_id'] !== $id) {
            flashErro('Comprovação não encontrada neste evento.');
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        $motivo = trim(isset($_POST['motivo']) ? (string) $_POST['motivo'] : '');

        if ($motivo === '') {
            flashErro('Informe o motivo da anulação: ele é mostrado ao participante.');
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        $motivo = mb_substr($motivo, 0, 500);

        if (!$this->comprovacoes->anular((int) $comprovacao['id'], Auth::usuarioId(), $motivo)) {
            flashAlerta('Esta comprovação já estava anulada.');
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        $this->avisarParticipante($evento, $comprovacao, $motivo, true);

        flashSucesso('Pontuação anulada e participante avisado.');
        $this->redirecionar('divulgacao/comprovacoes/' . $id);
    }

    /**
     * Desfaz uma anulacao feita por engano. Sem este caminho, o unico
     * conserto seria pelo banco.
     */
    public function reverterAnulacao($eventoId, $comprovacaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        // Fase 58: depois do encerramento da gincana a classificacao fica
        // congelada, e nenhuma anulacao ou reversao move pontos.
        if (GamificacaoService::encerrada($id)) {
            flashErro('A gincana deste evento foi encerrada e a classificação está congelada: nenhuma pontuação pode ser alterada.');
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        $comprovacao = $this->comprovacoes->buscarPorId($comprovacaoId);

        if ($comprovacao === null || (int) $comprovacao['evento_id'] !== $id) {
            flashErro('Comprovação não encontrada neste evento.');
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        if (!$this->comprovacoes->reverterAnulacao((int) $comprovacao['id'], Auth::usuarioId())) {
            flashAlerta('Esta comprovação não está anulada.');
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        $this->avisarParticipante($evento, $comprovacao, null, true);

        flashSucesso('Anulação desfeita: a pontuação voltou a valer.');
        $this->redirecionar('divulgacao/comprovacoes/' . $id);
    }

    /**
     * Fase 58: aba "Comprovações", a tela de trabalho da auditoria. Segue o
     * padrao de tabela do projeto (admin/usuarios.php e admin/auditoria):
     * barra de filtros com botao de aplicar, linha de resumo com exportacao,
     * tabela com selecao e operacoes em lote, e paginacao.
     *
     * As opcoes de Rede e Acao saem do que o EVENTO tem, nao da lista
     * fechada: filtro que oferece o que ninguem enviou so' atrapalha.
     */
    public function comprovacoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $filtros = $this->filtrosDaRequisicao();
        $pagina = max(1, (int) (isset($_GET['pagina']) ? $_GET['pagina'] : 1));
        $total = $this->comprovacoes->contarPorEvento($id, $filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($pagina, $totalPaginas);

        $this->renderizar('admin/divulgacao/comprovacoes', [
            'evento' => $evento,
            'comprovacoes' => $this->comprovacoes->listarPorEvento($id, $filtros, self::POR_PAGINA, ($pagina - 1) * self::POR_PAGINA),
            'resumosRepetidos' => $this->comprovacoes->resumosRepetidosNoEvento($id),
            'filtros' => $filtros,
            'total' => $total,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'rotulosRede' => $this->rotulosDeTodasAsRedes(),
            'redesComEnvio' => $this->redesComEnvio($id),
            'acoesComEnvio' => $this->acoesComEnvio($id),
            'totalImagens' => $this->contarImagens($id),
            'eventoEncerrado' => $this->eventoEncerrado($evento),
            'podeEditar' => Auth::possuiPerfil('administrador'),
            'gincanaEncerrada' => GamificacaoService::encerrada($id),
        ], 'Comprovações de Divulgação: ' . $evento['nome'], ['tipo' => 'divulgacaoComprovacoes', 'id' => $id]);
    }

    /**
     * Anula em lote as comprovacoes marcadas, com um motivo so' para todas.
     */
    public function anularEmLote($eventoId)
    {
        $evento = $this->prepararLote($eventoId);

        if ($evento === null) {
            return;
        }

        $id = (int) $evento['id'];
        $motivo = trim(isset($_POST['motivo']) ? (string) $_POST['motivo'] : '');

        if ($motivo === '') {
            flashErro('Informe o motivo da anulação: ele é mostrado a cada participante atingido.');
            $this->voltarParaComprovacoes($id);
            return;
        }

        $motivo = mb_substr($motivo, 0, 500);
        $atingidas = $this->comprovacoes->anularEmLote($id, $this->identificadoresDoLote(), Auth::usuarioId(), $motivo);

        if ($atingidas === []) {
            flashAlerta('Nenhuma das comprovações marcadas podia ser anulada.');
            $this->voltarParaComprovacoes($id);
            return;
        }

        $destinatarios = [];

        foreach ($atingidas as $comprovacao) {
            $destinatarios = $this->juntarDestinatario($destinatarios, $this->avisarParticipante($evento, $comprovacao, $motivo, false));
        }

        $this->enfileirarCorreio($evento, $destinatarios, $motivo);

        flashSucesso(count($atingidas) . ' comprovação(ões) anulada(s), e os participantes atingidos foram avisados.');
        $this->voltarParaComprovacoes($id);
    }

    public function reverterAnulacaoEmLote($eventoId)
    {
        $evento = $this->prepararLote($eventoId);

        if ($evento === null) {
            return;
        }

        $id = (int) $evento['id'];
        $atingidas = $this->comprovacoes->reverterAnulacaoEmLote($id, $this->identificadoresDoLote());

        if ($atingidas === []) {
            flashAlerta('Nenhuma das comprovações marcadas estava anulada.');
            $this->voltarParaComprovacoes($id);
            return;
        }

        $destinatarios = [];

        foreach ($atingidas as $comprovacao) {
            $destinatarios = $this->juntarDestinatario($destinatarios, $this->avisarParticipante($evento, $comprovacao, null, false));
        }

        $this->enfileirarCorreio($evento, $destinatarios, null);

        flashSucesso(count($atingidas) . ' anulação(ões) desfeita(s): a pontuação voltou a valer.');
        $this->voltarParaComprovacoes($id);
    }

    /**
     * A linha sai da lista e de toda soma, mas nao e' apagada: continua na
     * trilha de auditoria. Ver Implantar.md, secao 13.17.
     */
    public function excluirEmLote($eventoId)
    {
        $evento = $this->prepararLote($eventoId);

        if ($evento === null) {
            return;
        }

        $id = (int) $evento['id'];
        $atingidas = $this->comprovacoes->excluirEmLote($id, $this->identificadoresDoLote(), Auth::usuarioId());

        if ($atingidas === []) {
            flashAlerta('Nenhuma das comprovações marcadas podia ser excluída.');
            $this->voltarParaComprovacoes($id);
            return;
        }

        flashSucesso(count($atingidas) . ' comprovação(ões) excluída(s). Elas saíram das somas e continuam na trilha de auditoria; a mesma imagem continua barrada.');
        $this->voltarParaComprovacoes($id);
    }

    public function restaurarEmLote($eventoId)
    {
        $evento = $this->prepararLote($eventoId);

        if ($evento === null) {
            return;
        }

        $id = (int) $evento['id'];
        $atingidas = $this->comprovacoes->restaurarEmLote($id, $this->identificadoresDoLote());

        if ($atingidas === []) {
            flashAlerta('Nenhuma das comprovações marcadas estava excluída.');
            $this->voltarParaComprovacoes($id);
            return;
        }

        flashSucesso(count($atingidas) . ' comprovação(ões) restaurada(s).');
        $this->voltarParaComprovacoes($id);
    }

    /**
     * Apaga do disco a imagem das comprovacoes marcadas. As linhas, os
     * pontos e os resumos continuam; so' o arquivo some, e nao volta.
     */
    public function apagarImagensEmLote($eventoId)
    {
        $evento = $this->prepararLote($eventoId, false);

        if ($evento === null) {
            return;
        }

        $id = (int) $evento['id'];
        $apagadas = $this->comprovacoes->marcarArquivosRemovidosEmLote($id, $this->identificadoresDoLote());

        if ($apagadas === []) {
            flashAlerta('Nenhuma das comprovações marcadas tinha imagem guardada.');
            $this->voltarParaComprovacoes($id);
            return;
        }

        foreach ($apagadas as $linha) {
            ArquivoPrivadoService::remover($linha['arquivo_path']);
        }

        flashSucesso(count($apagadas) . ' imagem(ns) apagada(s). As comprovações e os pontos continuam registrados.');
        $this->voltarParaComprovacoes($id);
    }

    /**
     * Conferencias comuns a toda operacao em lote. Devolve o evento quando
     * pode seguir, ou null depois de ja' ter redirecionado.
     *
     * A recusa de lista vazia e' deliberada: sem ela, o formulario enviado
     * sem nada marcado "executa" sobre nenhuma linha e responde em verde,
     * que e' o defeito do molde copiado do Concurso.
     */
    private function prepararLote($eventoId, $exigeGincanaAberta = true)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->voltarParaComprovacoes($id);
            return null;
        }

        if ($exigeGincanaAberta && GamificacaoService::encerrada($id)) {
            flashErro('A gincana deste evento foi encerrada e a classificação está congelada: nenhuma pontuação pode ser alterada.');
            $this->voltarParaComprovacoes($id);
            return null;
        }

        if ($this->identificadoresDoLote() === []) {
            flashAlerta('Selecione ao menos uma comprovação.');
            $this->voltarParaComprovacoes($id);
            return null;
        }

        return $evento;
    }

    private function identificadoresDoLote()
    {
        return isset($_POST['comprovacao_ids']) && is_array($_POST['comprovacao_ids']) ? $_POST['comprovacao_ids'] : [];
    }

    /**
     * Volta para a lista preservando o filtro e a pagina, que viajam em
     * campos ocultos do formulario de lote.
     */
    private function voltarParaComprovacoes($eventoId)
    {
        $parametros = [];

        foreach (['busca', 'rede', 'tipo_acao', 'situacao', 'data_inicio', 'data_fim', 'pagina'] as $campo) {
            if (isset($_POST[$campo]) && $_POST[$campo] !== '') {
                $parametros[$campo] = (string) $_POST[$campo];
            }
        }

        $rota = 'divulgacao/comprovacoes/' . (int) $eventoId;
        $this->redirecionar($parametros === [] ? $rota : $rota . '&' . http_build_query($parametros));
    }

    public function configuracoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);

            $this->config->salvar($id, [
                'ativo' => !empty($_POST['ativo']) ? 1 : 0,
                'data_inicio' => isset($_POST['data_inicio']) ? $_POST['data_inicio'] : null,
                'data_fim' => isset($_POST['data_fim']) ? $_POST['data_fim'] : null,
                'dias_retencao_imagens' => isset($_POST['dias_retencao_imagens']) ? trim((string) $_POST['dias_retencao_imagens']) : null,
            ]);

            $redes = isset($_POST['redes']) && is_array($_POST['redes']) ? $_POST['redes'] : [];
            $this->config->salvarRedes($id, $redes);

            flashSucesso('Configurações de Divulgação salvas.');
            $this->redirecionar('divulgacao/configuracoes/' . $id);
            return;
        }

        $this->renderizar('admin/divulgacao/configuracoes', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            // Fase 58: a tela desenha so' as redes incluidas neste evento, e
            // oferece as demais no seletor "Acrescentar rede".
            'redes' => $this->config->redesIncluidas($id),
            'disponiveis' => $this->config->redesDisponiveis($id),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Configurações de Divulgação: ' . $evento['nome'], ['tipo' => 'divulgacaoConfiguracoes', 'id' => $id]);
    }

    /**
     * Fase 58: acrescenta uma rede ao evento. Mesmo desenho de
     * GamificacaoAdminController::desempateAdicionar(): valida contra a
     * lista fechada, trata duplicata como aviso e volta sempre para a tela.
     */
    public function incluirRede($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('divulgacao/configuracoes/' . $id);
            return;
        }

        $rede = isset($_POST['rede']) ? (string) $_POST['rede'] : '';

        if (!isset(DivulgacaoConfigRepository::REDES[$rede])) {
            flashErro('Escolha uma rede da lista.');
        } elseif (!$this->config->incluirRede($id, $rede)) {
            flashAlerta('Esta rede já está no evento.');
        } else {
            flashSucesso(DivulgacaoConfigRepository::rotuloDaRede($rede) . ' entrou no evento. Marque o que ela aceita e quanto vale, e salve.');
        }

        $this->redirecionar('divulgacao/configuracoes/' . $id);
    }

    /**
     * Fase 58: retira a rede do evento. A linha nao e' apagada: pontos,
     * limites e canal ficam guardados, e as comprovacoes ja' enviadas
     * continuam na auditoria com os pontos congelados.
     */
    public function retirarRede($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('divulgacao/configuracoes/' . $id);
            return;
        }

        $rede = isset($_POST['rede']) ? (string) $_POST['rede'] : '';

        if (!isset(DivulgacaoConfigRepository::REDES[$rede])) {
            flashErro('Escolha uma rede da lista.');
        } elseif (!$this->config->retirarRede($id, $rede)) {
            flashAlerta('Esta rede já não está no evento.');
        } else {
            flashSucesso(DivulgacaoConfigRepository::rotuloDaRede($rede) . ' saiu do evento. Pontos e limites ficaram guardados.');
        }

        $this->redirecionar('divulgacao/configuracoes/' . $id);
    }

    /**
     * Apaga as imagens de comprovacao do evento. Irreversivel, por isso as
     * duas travas do molde de ModeloDocumentoAdminController::expurgar(): o
     * evento precisa ja ter passado da propria data final, e o Administrador
     * precisa redigitar o nome do evento.
     *
     * As linhas, os pontos e os resumos criptograficos permanecem: so' o
     * arquivo some, e a trava de prova repetida continua de pe'.
     */
    public function expurgarImagens($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        if (!$this->eventoEncerrado($evento)) {
            flashErro('Só é possível apagar as imagens de um evento que já passou da própria data final.');
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        $confirmacao = trim(isset($_POST['confirmacao']) ? (string) $_POST['confirmacao'] : '');

        if ($confirmacao !== $evento['nome']) {
            flashErro('Confirmação incorreta: digite exatamente o nome do evento para apagar as imagens.');
            $this->redirecionar('divulgacao/comprovacoes/' . $id);
            return;
        }

        $apagadas = 0;

        foreach ($this->comprovacoes->listarComArquivoDoEvento($id) as $linha) {
            ArquivoPrivadoService::remover($linha['arquivo_path']);
            $this->comprovacoes->marcarArquivoRemovido($linha['id']);
            $apagadas++;
        }

        flashSucesso($apagadas . ' imagem(ns) apagada(s). As comprovações e os pontos continuam registrados.');
        $this->redirecionar('divulgacao/comprovacoes/' . $id);
    }

    private function eventoEncerrado(array $evento)
    {
        if (empty($evento['data_fim'])) {
            return false;
        }

        return strtotime(substr((string) $evento['data_fim'], 0, 10) . ' 23:59:59') < time();
    }

    private function contarImagens($eventoId)
    {
        return count($this->comprovacoes->listarComArquivoDoEvento($eventoId));
    }

    /**
     * Aviso no sino da pessoa e, na operacao individual, tambem por correio
     * eletronico na hora. Na operacao em lote o correio sai depois, numa
     * campanha so' pela fila (enfileirarCorreio()), e por isso esta funcao
     * devolve o destinatario em vez de enviar.
     */
    private function avisarParticipante(array $evento, array $comprovacao, $motivo, $enviarCorreioAgora)
    {
        $inscricao = (new EventoInscricaoRepository())->buscarPorId($comprovacao['evento_inscricao_id']);

        if ($inscricao === null || empty($inscricao['usuario_id'])) {
            return null;
        }

        if ($motivo !== null) {
            $titulo = 'Pontos de divulgação removidos';
            $mensagem = 'Uma comprovação sua em ' . $evento['nome'] . ' foi anulada pela organização. Motivo: ' . $motivo;
        } else {
            $titulo = 'Pontos de divulgação restabelecidos';
            $mensagem = 'A anulação de uma comprovação sua em ' . $evento['nome'] . ' foi desfeita, e a pontuação voltou a valer.';
        }

        (new NotificacaoPainelRepository())->criar(
            (int) $inscricao['usuario_id'],
            'divulgacao_anulacao',
            $titulo,
            $mensagem,
            ['url' => url('eventoApp/divulgacao/' . (int) $evento['id'])]
        );

        $usuario = (new UsuarioRepository())->buscarPorId((int) $inscricao['usuario_id']);

        if ($usuario === null || empty($usuario['email'])) {
            return null;
        }

        if ($enviarCorreioAgora) {
            try {
                (new \App\Services\NotificacaoService())->avisoIndividualEvento(
                    $usuario['email'],
                    $evento,
                    $titulo . ': ' . $evento['nome'],
                    $this->corpoDoCorreio($evento, $motivo)
                );
            } catch (\Throwable $e) {
                error_log('[Divulgacao] falha ao enviar o aviso de anulacao: ' . $e->getMessage());
            }

            return null;
        }

        return ['usuario_id' => (int) $usuario['id'], 'email' => $usuario['email'], 'nome' => $usuario['nome']];
    }

    private function juntarDestinatario(array $destinatarios, $destinatario)
    {
        if ($destinatario !== null) {
            $destinatarios[strtolower($destinatario['email'])] = $destinatario;
        }

        return $destinatarios;
    }

    /**
     * Uma campanha so' para a operacao em lote, um e-mail por pessoa mesmo
     * que ela tenha mais de uma comprovacao atingida. Falha aqui nunca
     * desfaz a anulacao ja' gravada.
     */
    private function enfileirarCorreio(array $evento, array $destinatarios, $motivo)
    {
        if ($destinatarios === []) {
            return;
        }

        try {
            (new EventoComunicacaoRepository())->criarCampanhaAvulsa([
                'evento_id' => (int) $evento['id'],
                'autor_usuario_id' => Auth::usuarioId(),
                'tipo' => 'comunicado',
                'assunto' => ($motivo !== null ? 'Pontos de divulgação removidos: ' : 'Pontos de divulgação restabelecidos: ') . $evento['nome'],
                'corpo_html' => $this->corpoDoCorreio($evento, $motivo),
            ], array_values($destinatarios));
        } catch (\Throwable $e) {
            error_log('[Divulgacao] falha ao enfileirar o aviso de anulacao em lote: ' . $e->getMessage());
        }
    }

    private function corpoDoCorreio(array $evento, $motivo)
    {
        $esc = function ($texto) {
            return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
        };
        $endereco = urlAbsoluta('eventoApp/divulgacao/' . (int) $evento['id']);

        if ($motivo !== null) {
            $texto = '<p>Uma ou mais comprovações suas de divulgação em <strong>' . $esc($evento['nome']) . '</strong> foram anuladas pela organização, e os pontos delas deixaram de contar.</p>'
                . '<p>Motivo informado: ' . $esc($motivo) . '</p>';
        } else {
            $texto = '<p>A anulação de uma ou mais comprovações suas de divulgação em <strong>' . $esc($evento['nome']) . '</strong> foi desfeita, e os pontos voltaram a contar.</p>';
        }

        return '<p>Olá,</p>' . $texto
            . '<p>Veja a situação de cada comprovação no aplicativo do evento:<br><a href="' . $esc($endereco) . '">' . $esc($endereco) . '</a></p>';
    }
}
