<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoTrabalhoTermoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\TrabalhoAvaliadorRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoCriterioRepository;
use App\Repositories\TrabalhoDesignacaoRepository;
use App\Repositories\TrabalhoEixoTematicoRepository;
use App\Repositories\TrabalhoNaturezaRepository;
use App\Repositories\TrabalhoNotaRepository;
use App\Repositories\TrabalhoRegraDesempateRepository;
use App\Repositories\TrabalhoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\UsuarioRepository;
use App\Services\BonusApuracaoService;
use App\Services\TrabalhoArquivoValidador;
use App\Services\TrabalhoAvaliadorConviteService;
use App\Services\TrabalhoResultadoAvisoService;
use App\Services\TrabalhoResultadoService;
use App\Validation\CpfValidador;

/**
 * Fase 49: as 5 abas administrativas de Trabalhos. Leitura liberada a
 * administrador+suporte, escrita sensivel (salvar configuracao, convidar
 * avaliador, desclassificar, aplicar resultado, remover) restrita a
 * administrador - mesmo padrao generalizado ja usado em
 * AtividadeAdminController/EventoAdminController (confirmado na revisao
 * do plano desta fase, nenhuma granularidade nova inventada).
 */
class TrabalhoAdminController extends Controller
{
    private $eventos;
    private $config;
    private $eixos;
    private $naturezas;
    private $criterios;
    private $desempate;
    private $avaliadores;
    private $trabalhos;
    private $autores;
    private $designacoes;
    private $notas;
    private $termos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->config = new TrabalhoConfigRepository();
        $this->eixos = new TrabalhoEixoTematicoRepository();
        $this->naturezas = new TrabalhoNaturezaRepository();
        $this->criterios = new TrabalhoCriterioRepository();
        $this->desempate = new TrabalhoRegraDesempateRepository();
        $this->avaliadores = new TrabalhoAvaliadorRepository();
        $this->termos = new EventoTrabalhoTermoRepository();
        $this->trabalhos = new TrabalhoRepository();
        $this->autores = new TrabalhoAutorRepository();
        $this->designacoes = new TrabalhoDesignacaoRepository();
        $this->notas = new TrabalhoNotaRepository();
    }

    private function buscarEventoOu404($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        return $evento;
    }

    /**
     * Configurações: dados do processo (prazos, métodos, sigilo,
     * agregação de nota, obrigatoriedade de telefone) mais os catálogos
     * de eixos temáticos e naturezas, todos na mesma tela.
     */
    public function index($eventoId)
    {
        $evento = $this->buscarEventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_config') {
            RoleMiddleware::exigir(['administrador']);
            $this->salvarConfig($eventoId);
            flashSucesso('Configurações de Trabalhos salvas.');
            $this->redirecionar('trabalhos/index/' . $eventoId);
            return;
        }

        $configAtual = $this->config->buscarPorEvento($eventoId);

        $this->renderizar('admin/trabalhos/index', [
            'evento' => $evento,
            'config' => $configAtual,
            'situacaoAtual' => $configAtual !== null ? $configAtual['status'] : 'rascunho',
            'extensoesReconhecidas' => TrabalhoArquivoValidador::extensoesReconhecidasPeloSistema(),
        ], 'Trabalhos: ' . $evento['nome'], ['tipo' => 'trabalhos', 'id' => (int) $eventoId]);
    }

    private function contarProximaOrdem(array $lista)
    {
        return count($lista);
    }

    /**
     * Eixos tematicos em aba propria, com o mesmo status de Criterios e
     * Avaliadores.
     */
    public function eixos($eventoId)
    {
        $evento = $this->buscarEventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $this->eixos->criar($eventoId, trim($_POST['nome']), trim($_POST['descricao']), $this->contarProximaOrdem($this->eixos->listarPorEvento($eventoId)));
            flashSucesso('Eixo temático cadastrado.');
            $this->redirecionar('trabalhos/eixos/' . $eventoId);
            return;
        }

        $this->renderizar('admin/trabalhos/eixos', [
            'evento' => $evento,
            'eixos' => $this->eixos->listarPorEvento($eventoId),
        ], 'Eixos temáticos: ' . $evento['nome'], ['tipo' => 'trabalhosEixos', 'id' => (int) $eventoId]);
    }

    public function naturezas($eventoId)
    {
        $evento = $this->buscarEventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $this->naturezas->criar($eventoId, trim($_POST['nome']), trim($_POST['descricao']), $this->contarProximaOrdem($this->naturezas->listarPorEvento($eventoId)));
            flashSucesso('Natureza cadastrada.');
            $this->redirecionar('trabalhos/naturezas/' . $eventoId);
            return;
        }

        $this->renderizar('admin/trabalhos/naturezas', [
            'evento' => $evento,
            'naturezas' => $this->naturezas->listarPorEvento($eventoId),
        ], 'Naturezas do trabalho: ' . $evento['nome'], ['tipo' => 'trabalhosNaturezas', 'id' => (int) $eventoId]);
    }

    /**
     * Fase 51: declaracoes que o autor aceita ao submeter (normas do edital,
     * tratamento de dados pessoais, autorizacao de publicacao nos Anais...).
     * Sao dados do evento, nao texto fixo de codigo: cada edicao cadastra os
     * seus, e o que foi aceito fica congelado em trabalho_termos_aceitos.
     */
    public function termos($eventoId)
    {
        $evento = $this->buscarEventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $this->termos->criar($eventoId, [
                'rotulo' => trim($_POST['rotulo']),
                'texto_html' => isset($_POST['texto_html']) ? sanitizarHtmlRico($_POST['texto_html']) : '',
                'obrigatorio' => isset($_POST['obrigatorio']) ? 1 : 0,
                'ativo' => isset($_POST['ativo']) ? 1 : 0,
            ]);
            flashSucesso('Declaração cadastrada.');
            $this->redirecionar('trabalhos/termos/' . $eventoId);
            return;
        }

        $this->renderizar('admin/trabalhos/termos', [
            'evento' => $evento,
            'termos' => $this->termos->listar($eventoId),
        ], 'Declarações: ' . $evento['nome'], ['tipo' => 'trabalhosTermos', 'id' => (int) $eventoId]);
    }

    public function termoEditar($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $termo = $this->termos->buscarPorId($id);

        if ($termo === null) {
            http_response_code(404);
            exit('Declaração não encontrada.');
        }

        $eventoId = (int) $termo['evento_id'];
        $evento = $this->buscarEventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->termos->atualizar($eventoId, $id, [
                'rotulo' => trim($_POST['rotulo']),
                'texto_html' => isset($_POST['texto_html']) ? sanitizarHtmlRico($_POST['texto_html']) : '',
                'obrigatorio' => isset($_POST['obrigatorio']) ? 1 : 0,
                'ativo' => isset($_POST['ativo']) ? 1 : 0,
            ]);
            flashSucesso('Declaração atualizada.');
            $this->redirecionar('trabalhos/termos/' . $eventoId);
            return;
        }

        $this->renderizar('admin/trabalhos/termo_form', [
            'evento' => $evento,
            'termo' => $termo,
        ], 'Editar declaração: ' . $evento['nome'], ['tipo' => 'trabalhosTermos', 'id' => $eventoId]);
    }

    public function termoRemover($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $eventoId = isset($_POST['evento_id']) ? (int) $_POST['evento_id'] : 0;
        $termo = $this->termos->buscarDoEvento($eventoId, $id);

        if ($termo !== null) {
            if ($this->termos->possuiAceites($id)) {
                flashAlerta('Esta declaração já foi aceita em submissões e não pode ser removida. Desative-a para não aparecer em novas submissões.');
            } else {
                $this->termos->remover($eventoId, $id);
                flashSucesso('Declaração removida.');
            }
        }

        $this->redirecionar('trabalhos/termos/' . $eventoId);
    }

    public function termoReordenar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);

        // Fase 53 (correcao): o arrastar-e-soltar (reordenar-arrastar.js) envia
        // a lista em JSON no corpo da requisicao, nao em $_POST; ler so'
        // $_POST fazia a ordem nunca ser gravada, sem nenhum erro na tela.
        // Mesmo padrao de criterioReordenar().
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->termos->reordenar($eventoId, $ids);

        echo json_encode(['ok' => true]);
    }

    private function salvarConfig($eventoId)
    {
        $metodos = isset($_POST['metodos']) && is_array($_POST['metodos']) ? array_values($_POST['metodos']) : [];
        $extensoes = isset($_POST['extensoes']) && is_array($_POST['extensoes']) ? array_values($_POST['extensoes']) : [];

        $this->config->salvar($eventoId, [
            'data_abertura_submissao' => !empty($_POST['data_abertura_submissao']) ? str_replace('T', ' ', $_POST['data_abertura_submissao']) : null,
            'data_fim_submissao' => !empty($_POST['data_fim_submissao']) ? str_replace('T', ' ', $_POST['data_fim_submissao']) : null,
            'data_inicio_avaliacao' => !empty($_POST['data_inicio_avaliacao']) ? str_replace('T', ' ', $_POST['data_inicio_avaliacao']) : null,
            'data_fim_avaliacao' => !empty($_POST['data_fim_avaliacao']) ? str_replace('T', ' ', $_POST['data_fim_avaliacao']) : null,
            'quantidade_maxima_autores' => (int) $_POST['quantidade_maxima_autores'],
            'permite_multiplos_trabalhos_por_pessoa' => isset($_POST['permite_multiplos_trabalhos_por_pessoa']) ? 1 : 0,
            'quantidade_avaliadores_por_trabalho' => (int) $_POST['quantidade_avaliadores_por_trabalho'],
            'sigilo_cego' => isset($_POST['sigilo_cego']) ? 1 : 0,
            'metodo_agregacao_nota' => $_POST['metodo_agregacao_nota'] === 'mediana' ? 'mediana' : 'media_aritmetica',
            'casas_decimais' => isset($_POST['casas_decimais']) ? max(0, min(4, (int) $_POST['casas_decimais'])) : 2,
            'metodos_submissao_json' => json_encode($metodos),
            'extensoes_editavel_json' => json_encode($extensoes),
            'tamanho_maximo_mb' => (int) $_POST['tamanho_maximo_mb'],
            'exige_telefone_contato' => isset($_POST['exige_telefone_contato']) ? 1 : 0,
            'nota_corte_aprovacao' => $_POST['nota_corte_aprovacao'] !== '' ? (float) $_POST['nota_corte_aprovacao'] : null,
            'regra_selecao_tipo' => in_array($_POST['regra_selecao_tipo'], ['numero_fixo', 'percentual', 'todos_aprovados'], true) ? $_POST['regra_selecao_tipo'] : 'todos_aprovados',
            'regra_selecao_valor' => $_POST['regra_selecao_valor'] !== '' ? (float) $_POST['regra_selecao_valor'] : null,
            'status' => in_array($_POST['situacao'], ['rascunho', 'publicado', 'encerrado'], true) ? $_POST['situacao'] : 'rascunho',
            'inscrever_autores_ao_submeter' => isset($_POST['inscrever_autores_ao_submeter']) ? 1 : 0,
            'mensagem_recebimento_html' => isset($_POST['mensagem_recebimento_html']) && trim(strip_tags($_POST['mensagem_recebimento_html'])) !== ''
                ? sanitizarHtmlRico($_POST['mensagem_recebimento_html'])
                : null,
            'resultado_exibe_nota' => isset($_POST['resultado_exibe_nota']) ? 1 : 0,
            'resultado_exibe_posicao' => isset($_POST['resultado_exibe_posicao']) ? 1 : 0,
            'resultado_exibe_criterios' => isset($_POST['resultado_exibe_criterios']) ? 1 : 0,
            'mensagem_resultado_html' => isset($_POST['mensagem_resultado_html']) && trim(strip_tags($_POST['mensagem_resultado_html'])) !== ''
                ? sanitizarHtmlRico($_POST['mensagem_resultado_html'])
                : null,
        ]);
    }

    public function eixoRemover($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $eixo = $this->eixos->buscarPorId($id);

        if ($eixo !== null) {
            try {
                $this->eixos->remover($id);
                flashSucesso('Eixo temático removido.');
            } catch (\PDOException $e) {
                flashErro($e->getCode() === '23000' ? 'Não é possível remover: já existe trabalho com este eixo.' : 'Não foi possível remover.');
            }
        }

        $this->redirecionar('trabalhos/eixos/' . ($eixo !== null ? $eixo['evento_id'] : ''));
    }

    public function naturezaRemover($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $natureza = $this->naturezas->buscarPorId($id);

        if ($natureza !== null) {
            try {
                $this->naturezas->remover($id);
                flashSucesso('Natureza removida.');
            } catch (\PDOException $e) {
                flashErro($e->getCode() === '23000' ? 'Não é possível remover: já existe trabalho com esta natureza.' : 'Não foi possível remover.');
            }
        }

        $this->redirecionar('trabalhos/naturezas/' . ($natureza !== null ? $natureza['evento_id'] : ''));
    }

    public function criterios($eventoId)
    {
        $evento = $this->buscarEventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);

            // O resumo dos criterios para o avaliador tem formulario proprio nesta
            // tela; 'acao' distingue os dois.
            if (isset($_POST['acao']) && $_POST['acao'] === 'salvar_resumo') {
                // Fase 53 (correcao): o texto e' exibido ao avaliador; passa
                // pelo mesmo filtro dos demais editores ricos ao salvar (e
                // de novo ao exibir, ver avaliacaoTrabalhos/notar).
                $html = trim($_POST['criterios_resumo_html']) !== '' ? sanitizarHtmlRico($_POST['criterios_resumo_html']) : null;
                $this->config->atualizarResumoCriterios($eventoId, $html);
                flashSucesso('Resumo dos critérios salvo.');
                $this->redirecionar('trabalhos/criterios/' . $eventoId);
                return;
            }

            $ordem = count($this->criterios->listarPorEvento($eventoId));
            $this->criterios->criar($eventoId, trim($_POST['nome']), trim($_POST['descricao']), (float) $_POST['nota_maxima'], $ordem);
            flashSucesso('Critério cadastrado.');
            $this->redirecionar('trabalhos/criterios/' . $eventoId);
            return;
        }

        $config = $this->config->buscarPorEvento($eventoId);

        $this->renderizar('admin/trabalhos/criterios', [
            'evento' => $evento,
            'criterios' => $this->criterios->listarPorEvento($eventoId),
            'notaMaximaTotal' => $this->criterios->notaMaximaTotal($eventoId),
            'criteriosResumoHtml' => $config !== null ? $config['criterios_resumo_html'] : '',
        ], 'Critérios de avaliação: ' . $evento['nome'], ['tipo' => 'trabalhosCriterios', 'id' => (int) $eventoId]);
    }

    /**
     * Regra de desempate em aba propria: ela decide COMO desempatar dois
     * trabalhos, nao O QUE e' avaliado.
     */
    public function desempate($eventoId)
    {
        $evento = $this->buscarEventoOu404($eventoId);

        $this->renderizar('admin/trabalhos/desempate', [
            'evento' => $evento,
            'criterios' => $this->criterios->listarPorEvento($eventoId),
            'regrasDesempate' => $this->desempate->listarPorEvento($eventoId),
        ], 'Regras de desempate: ' . $evento['nome'], ['tipo' => 'trabalhosDesempate', 'id' => (int) $eventoId]);
    }

    public function criterioReordenar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);

        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->criterios->reordenar($eventoId, $ids);

        echo json_encode(['ok' => true]);
    }

    public function criterioRemover($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $criterio = $this->criterios->buscarPorId($id);

        if ($criterio !== null) {
            try {
                $this->criterios->remover($id);
                flashSucesso('Critério removido.');
            } catch (\PDOException $e) {
                flashErro($e->getCode() === '23000' ? 'Não é possível remover: critério já usado em nota lançada ou regra de desempate.' : 'Não foi possível remover.');
            }
        }

        $this->redirecionar('trabalhos/criterios/' . ($criterio !== null ? $criterio['evento_id'] : ''));
    }

    public function desempateNovo($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $ordem = count($this->desempate->listarPorEvento($eventoId));
        $criterioId = ($_POST['tipo'] === 'criterio' && !empty($_POST['criterio_id'])) ? (int) $_POST['criterio_id'] : null;

        $this->desempate->criar($eventoId, $_POST['tipo'] === 'criterio' ? 'criterio' : 'data_submissao', $criterioId, $ordem, $_POST['direcao'] === 'asc' ? 'asc' : 'desc');
        flashSucesso('Regra de desempate adicionada.');
        $this->redirecionar('trabalhos/desempate/' . $eventoId);
    }

    public function desempateRemover($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $regra = $this->desempate->buscarPorId($id);

        if ($regra !== null) {
            $this->desempate->remover($id);
            flashSucesso('Regra de desempate removida.');
        }

        $this->redirecionar('trabalhos/desempate/' . ($regra !== null ? $regra['evento_id'] : ''));
    }

    /**
     * Busca em tempo real de usuario ja cadastrado, no contrato JSON de
     * AtividadeAdminController::buscarUsuarios(): so' preenche nome e e-mail no
     * formulario de convite, nunca cria vinculo sozinho.
     */
    public function buscarUsuarios()
    {
        header('Content-Type: application/json');
        $termo = trim(isset($_GET['q']) ? $_GET['q'] : '');

        if (strlen($termo) < 2) {
            echo json_encode([]);
            return;
        }

        echo json_encode((new \App\Repositories\UsuarioRepository())->buscarPorTermo($termo));
    }

    public function avaliadores($eventoId)
    {
        $evento = $this->buscarEventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);

            if ($nome === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flashErro('Informe nome e um e-mail válido.');
            } else {
                try {
                    (new TrabalhoAvaliadorConviteService())->convidar($nome, $email, $evento, Auth::usuarioId());
                    flashSucesso('Avaliador convidado.');
                } catch (\RuntimeException $e) {
                    flashErro($e->getMessage());
                }
            }

            $this->redirecionar('trabalhos/avaliadores/' . $eventoId);
            return;
        }

        $this->renderizar('admin/trabalhos/avaliadores', [
            'evento' => $evento,
            'avaliadores' => $this->avaliadores->listarPorEvento($eventoId),
        ], 'Avaliadores de Trabalhos: ' . $evento['nome'], ['tipo' => 'trabalhosAvaliadores', 'id' => (int) $eventoId]);
    }

    public function avaliadorRemover($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $avaliador = $this->avaliadores->buscarPorId($id);

        if ($avaliador !== null) {
            $this->avaliadores->remover($id);
            flashSucesso('Avaliador removido.');
        }

        $this->redirecionar('trabalhos/avaliadores/' . ($avaliador !== null ? $avaliador['evento_id'] : ''));
    }

    private static $rotulosSituacao = [
        'submetido' => 'Submetido',
        'desclassificado' => 'Desclassificado',
        'aprovado' => 'Aprovado',
        'reprovado' => 'Reprovado',
    ];

    public function recebidos($eventoId)
    {
        $evento = $this->buscarEventoOu404($eventoId);

        $trabalhos = $this->trabalhos->listarPorEvento($eventoId);
        $config = $this->config->buscarPorEvento($eventoId);
        $quantidadeConfigurada = $config !== null ? (int) $config['quantidade_avaliadores_por_trabalho'] : 0;
        $contagens = $this->designacoes->contarPorTrabalhosDoEvento($eventoId);

        foreach ($trabalhos as &$trabalho) {
            $trabalho['situacao_rotulo'] = self::$rotulosSituacao[$trabalho['status']];
            $trabalho['total_designacoes'] = isset($contagens[$trabalho['id']]) ? $contagens[$trabalho['id']] : 0;
        }
        unset($trabalho);

        $this->renderizar('admin/trabalhos/recebidos', [
            'evento' => $evento,
            'trabalhos' => $trabalhos,
            'quantidadeConfigurada' => $quantidadeConfigurada,
        ], 'Trabalhos recebidos: ' . $evento['nome'], ['tipo' => 'trabalhosRecebidos', 'id' => (int) $eventoId]);
    }

    public function recebidoVer($id)
    {
        $trabalho = $this->trabalhos->buscarComDetalhes($id);

        if ($trabalho === null) {
            http_response_code(404);
            exit('Trabalho não encontrado.');
        }

        $evento = $this->eventos->buscarPorId($trabalho['evento_id']);
        $trabalho['situacao_rotulo'] = self::$rotulosSituacao[$trabalho['status']];
        $config = $this->config->buscarPorEvento($trabalho['evento_id']);

        $this->renderizar('admin/trabalhos/ver', [
            'evento' => $evento,
            'trabalho' => $trabalho,
            'autores' => $this->autores->listarPorTrabalho($id),
            'avaliadoresDisponiveis' => $this->avaliadores->listarPorEvento($trabalho['evento_id']),
            'designacoes' => $this->designacoes->listarPorTrabalho($id),
            'criterios' => $this->criterios->listarPorEvento($trabalho['evento_id']),
            'cpfFormatado' => CpfValidador::formatar($trabalho['autor_principal_cpf']),
            'quantidadeConfigurada' => $config !== null ? (int) $config['quantidade_avaliadores_por_trabalho'] : 0,
        ], 'Trabalho: ' . $trabalho['titulo'], ['tipo' => 'trabalhosRecebidos', 'id' => (int) $trabalho['evento_id']]);
    }

    /**
     * Admin ve as 2 versoes (avaliacao e publicacao), diferente do
     * avaliador (TrabalhoAvaliacaoController::arquivo(), que so' serve a
     * versao sem identificacao).
     */
    public function arquivoAvaliacao($id)
    {
        $this->servirArquivo($id, 'arquivo_avaliacao_path');
    }

    public function arquivoPublicacao($id)
    {
        $this->servirArquivo($id, 'arquivo_publicacao_path');
    }

    private function servirArquivo($id, $coluna)
    {
        $trabalho = $this->trabalhos->buscarPorId($id);

        if ($trabalho === null || empty($trabalho[$coluna])) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        $caminho = TrabalhoArquivoValidador::caminhoFisico($trabalho[$coluna]);

        if ($caminho === null) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        header('Content-Type: ' . TrabalhoArquivoValidador::contentTypePara($trabalho[$coluna]));
        header('Content-Disposition: attachment; filename="trabalho-' . (int) $id . '.' . pathinfo($caminho, PATHINFO_EXTENSION) . '"');
        header('Content-Length: ' . filesize($caminho));
        readfile($caminho);
        exit;
    }

    public function recebidoDesignar($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $trabalho = $this->trabalhos->buscarPorId($id);

        if ($trabalho === null) {
            http_response_code(404);
            exit('Trabalho não encontrado.');
        }

        $usuarioId = (int) $_POST['usuario_id'];

        if ($this->autores->usuarioEhAutor($id, $usuarioId)) {
            flashErro('Este usuário é autor deste trabalho e não pode ser designado como avaliador dele.');
            $this->redirecionar('trabalhos/recebidoVer/' . $id);
            return;
        }

        try {
            $this->designacoes->criar($id, $usuarioId, Auth::usuarioId());
            flashSucesso('Avaliador designado.');
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
        }

        $this->redirecionar('trabalhos/recebidoVer/' . $id);
    }

    /**
     * Desfaz uma designacao individual (trabalho_designacoes), diferente de
     * avaliadorRemover(), que tira a pessoa do conjunto de avaliadores do evento.
     */
    public function recebidoDesignacaoRemover($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $designacao = $this->designacoes->buscarPorId($id);

        if ($designacao === null) {
            http_response_code(404);
            exit('Designação não encontrada.');
        }

        $this->designacoes->remover($id);
        flashSucesso('Designação removida.');
        $this->redirecionar('trabalhos/recebidoVer/' . $designacao['trabalho_id']);
    }

    public function recebidoDesclassificar($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $trabalho = $this->trabalhos->buscarPorId($id);

        if ($trabalho === null) {
            http_response_code(404);
            exit('Trabalho não encontrado.');
        }

        $motivo = trim($_POST['motivo']);

        if ($motivo === '') {
            flashErro('Informe o motivo da desclassificação.');
        } else {
            $this->trabalhos->desclassificar($id, $motivo);
            $mensagem = 'Trabalho desclassificado.';

            // Fase 58: o bonus "ser autor de trabalho submetido" perde a base
            // para quem ficou sem nenhum trabalho valido no evento. E'
            // anulacao PELO SISTEMA, com aviso no sino, e volta sozinha se a
            // pessoa submeter outro trabalho. Bloco de protecao proprio: a
            // desclassificacao ja' foi gravada.
            try {
                $evento = $this->eventos->buscarPorId($trabalho['evento_id']);
                $anulados = $evento !== null ? (new BonusApuracaoService())->reapurarAutoresDoTrabalho($evento, (int) $id) : [];

                foreach ($anulados as $anulado) {
                    (new NotificacaoPainelRepository())->criar(
                        (int) $anulado['usuario_id'],
                        'bonus_anulacao',
                        'Pontos de bônus removidos',
                        'O bônus "' . $anulado['nome'] . '" em ' . $evento['nome'] . ' foi anulado. ' . $anulado['motivo'],
                        ['url' => url('eventoApp/index/' . (int) $evento['id'])]
                    );
                }

                if ($anulados !== []) {
                    $mensagem .= ' ' . count($anulados) . (count($anulados) === 1 ? ' bônus de autor foi anulado' : ' bônus de autores foram anulados') . ', com aviso a cada pessoa.';
                }
            } catch (\Throwable $e) {
                error_log('[Bonus] Falha ao reapurar os autores do trabalho ' . (int) $id . ': ' . $e->getMessage());
            }

            flashSucesso($mensagem);
        }

        $this->redirecionar('trabalhos/recebidoVer/' . $id);
    }

    public function resultado($eventoId)
    {
        $evento = $this->buscarEventoOu404($eventoId);

        $servico = new TrabalhoResultadoService();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);

            $acao = isset($_POST['acao']) ? $_POST['acao'] : '';

            try {
                if ($acao === 'publicar') {
                    $servico->publicarResultado($eventoId, Auth::usuarioId());
                    $this->avisarAutoresDoResultado($eventoId);
                } elseif ($acao === 'reabrir') {
                    $servico->reabrirResultado($eventoId, Auth::usuarioId());
                    flashSucesso('Resultado reaberto. Os autores deixaram de ver situação e pontuação até uma nova publicação.');
                } else {
                    flashErro('Ação desconhecida.');
                }
            } catch (\RuntimeException $e) {
                flashErro($e->getMessage());
            }

            $this->redirecionar('trabalhos/resultado/' . $eventoId);
            return;
        }

        $config = $this->config->buscarPorEvento($eventoId);
        $ranking = $servico->calcularRanking($eventoId);
        $publicadoPor = null;

        if ($config !== null && !empty($config['resultado_publicado_por'])) {
            $usuario = (new UsuarioRepository())->buscarPorId($config['resultado_publicado_por']);
            $publicadoPor = $usuario !== null ? $usuario['nome'] : null;
        }

        $semNota = 0;

        foreach ($ranking as $linha) {
            if ($linha['nota'] === null) {
                $semNota++;
            }
        }

        $this->renderizar('admin/trabalhos/resultado', [
            'evento' => $evento,
            'ranking' => $ranking,
            'selecionados' => $config !== null ? $servico->calcularSelecao($ranking, $config) : [],
            'publicadoEm' => $config !== null && !empty($config['resultado_publicado_em']) ? $config['resultado_publicado_em'] : null,
            'publicadoPor' => $publicadoPor,
            'semNota' => $semNota,
            'casasDecimais' => TrabalhoConfigRepository::casasDecimais($config),
        ], 'Resultado de Trabalhos: ' . $evento['nome'], ['tipo' => 'trabalhosResultado', 'id' => (int) $eventoId]);
    }

    /**
     * Fase 52: o resultado ja foi publicado e confirmado; uma falha ao criar
     * os avisos (sino e e-mail) nunca desfaz a publicacao, so' avisa o Admin
     * para comunicar os autores por outro meio.
     */
    private function avisarAutoresDoResultado($eventoId)
    {
        try {
            $avisos = (new TrabalhoResultadoAvisoService())->avisarAutores($eventoId, Auth::usuarioId());
            flashSucesso(
                'Resultado publicado. Avisos aos autores: ' . $avisos['sinos'] . ' no aplicativo e '
                . $avisos['emails'] . ' e-mail(s) na fila de envio (saem dez por minuto).'
            );
        } catch (\Throwable $e) {
            error_log('[Trabalhos] falha ao avisar os autores do evento ' . (int) $eventoId . ': ' . $e->getMessage());
            flashAlerta('Resultado publicado, mas não foi possível criar os avisos aos autores. Avise-os por outro meio.');
        }
    }
}
