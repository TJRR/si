<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConexaoConfigRepository;
use App\Repositories\ConexaoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\GamificacaoService;

/**
 * Fase 55: Conexoes entre participantes do Evento (no da arvore irmao de
 * Atividades, Trabalhos e Estandes, sem no por conexao). Suporte le tudo,
 * como nos demais modulos do Evento; toda gravacao exige Administrador.
 *
 * Ate' a Fase 57 o acompanhamento mostrava so' numeros do evento, sem lista
 * de quem se conectou com quem, porque o par e' dado pessoal de terceiro.
 * Em 01/10/2026 o dono decidiu que o Administrador passa a ver os pares,
 * para poder desfazer pela tela uma leitura feita por engano, no lugar do
 * script database/remover_conexao_evento.php. A ajuda da tela diz isso ao
 * Administrador, e a tela do participante continua mostrando so' as
 * conexoes dele.
 */
class ConexaoAdminController extends Controller
{
    private $eventos;
    private $conexoes;
    private $config;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->conexoes = new ConexaoRepository();
        $this->config = new ConexaoConfigRepository();
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

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $filtros = $this->filtrosDaRequisicao();

        $this->renderizar('admin/conexoes/index', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'totalConexoes' => $this->conexoes->contarPorEvento($id),
            'totalParticipantes' => $this->conexoes->contarParticipantesConectados($id),
            'totalPontos' => $this->conexoes->somarPontosPorEvento($id),
            'porDia' => $this->conexoes->contarPorDia($id),
            'conexoes' => $this->conexoes->listarPorEvento($id, $filtros),
            'filtros' => $filtros,
            'podeEditar' => \App\Core\Auth::possuiPerfil('administrador'),
            'gincanaEncerrada' => GamificacaoService::encerrada($id),
        ], 'Conexões: ' . $evento['nome'], ['tipo' => 'conexoes', 'id' => $id]);
    }

    /**
     * Busca pelas duas pessoas do par e periodo. Convencao do projeto: campo
     * em branco vira '' e nao entra na consulta.
     */
    private function filtrosDaRequisicao()
    {
        $filtros = [];

        foreach (['busca', 'data_inicio', 'data_fim'] as $campo) {
            $filtros[$campo] = isset($_GET[$campo]) ? trim((string) $_GET[$campo]) : '';
        }

        return $filtros;
    }

    /**
     * Fase 58: remove em lote as conexoes marcadas, para desfazer uma
     * leitura feita por engano. Apagar a linha devolve os pontos dos dois
     * lados, porque a classificacao soma as linhas existentes.
     *
     * Depois do encerramento da gincana a classificacao esta' congelada, e
     * remover uma conexao mudaria pontos ja' congelados: por isso a recusa.
     */
    public function removerEmLote($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('conexoes/index/' . $id);
            return;
        }

        if (GamificacaoService::encerrada($id)) {
            flashErro('A gincana deste evento foi encerrada e a classificação está congelada: nenhuma conexão pode ser removida.');
            $this->redirecionar('conexoes/index/' . $id);
            return;
        }

        $ids = isset($_POST['conexao_ids']) && is_array($_POST['conexao_ids']) ? $_POST['conexao_ids'] : [];

        if ($ids === []) {
            flashAlerta('Selecione ao menos uma conexão.');
            $this->redirecionar('conexoes/index/' . $id);
            return;
        }

        $removidas = $this->conexoes->removerEmLote($id, $ids);

        if ($removidas === []) {
            flashAlerta('Nenhuma das conexões marcadas foi encontrada neste evento.');
            $this->redirecionar('conexoes/index/' . $id);
            return;
        }

        flashSucesso(count($removidas) . ' conexão(ões) removida(s). Os pontos dos dois lados saíram da classificação.');
        $this->redirecionar('conexoes/index/' . $id);
    }

    /**
     * Exportacao da lista, respeitando o filtro da tela.
     */
    public function exportar($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $linhas = $this->conexoes->listarPorEvento($id, $this->filtrosDaRequisicao());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="conexoes-evento-' . $id . '.csv"');

        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF"); // BOM UTF-8, pro Excel abrir acentuacao certo.
        fputcsv($saida, ['Participante 1', 'Correio eletrônico 1', 'Pontos 1', 'Participante 2', 'Correio eletrônico 2', 'Pontos 2', 'Conectados em'], ';');

        foreach ($linhas as $linha) {
            fputcsv($saida, [
                $linha['nome_menor'],
                $linha['email_menor'],
                (int) $linha['pontos_creditados_menor'],
                $linha['nome_maior'],
                $linha['email_maior'],
                (int) $linha['pontos_creditados_maior'],
                formatarDataHora($linha['conectado_em']),
            ], ';');
        }

        fclose($saida);
        exit;
    }

    public function configuracoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);

            $this->config->salvar($id, [
                'ativo' => !empty($_POST['ativo']) ? 1 : 0,
                'pontos_por_conexao' => max(0, (int) (isset($_POST['pontos_por_conexao']) ? $_POST['pontos_por_conexao'] : 0)),
                'teto_conexoes_pontuadas' => max(0, (int) (isset($_POST['teto_conexoes_pontuadas']) ? $_POST['teto_conexoes_pontuadas'] : 0)),
            ]);

            flashSucesso('Configurações de Conexões salvas.');
            $this->redirecionar('conexoes/configuracoes/' . $id);
            return;
        }

        $this->renderizar('admin/conexoes/configuracoes', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'podeEditar' => \App\Core\Auth::possuiPerfil('administrador'),
        ], 'Configurações de Conexões: ' . $evento['nome'], ['tipo' => 'conexoesConfiguracoes', 'id' => $id]);
    }
}
