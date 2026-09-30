<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\BonusRepository;
use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PesquisaConfigRepository;
use App\Repositories\PesquisaPerguntaRepository;
use App\Repositories\PesquisaRespondenteRepository;
use App\Repositories\PesquisaRespostaRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\PesquisaService;

/**
 * Fase 57: Pesquisa de satisfacao (no da arvore irmao de Bonus). Suporte le;
 * toda gravacao exige Administrador.
 *
 * A pesquisa e' ANONIMA: nenhuma tela deste controlador mostra o que uma
 * pessoa respondeu, e nao existe consulta que ligue as duas coisas. A unica
 * lista nominal e' a de quem ainda nao respondeu, usada para o convite, e
 * ela nunca aparece ao lado de resposta nenhuma.
 */
class PesquisaAdminController extends Controller
{
    private $eventos;
    private $config;
    private $perguntas;
    private $respondentes;
    private $respostas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->config = new PesquisaConfigRepository();
        $this->perguntas = new PesquisaPerguntaRepository();
        $this->respondentes = new PesquisaRespondenteRepository();
        $this->respostas = new PesquisaRespostaRepository();
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

    private function perguntaDoEventoOu404(array $evento, $perguntaId)
    {
        $pergunta = $this->perguntas->buscarPorId($perguntaId);

        if ($pergunta === null || (int) $pergunta['evento_id'] !== (int) $evento['id']) {
            http_response_code(404);
            exit('Pergunta não encontrada neste evento.');
        }

        return $pergunta;
    }

    /**
     * Resultado. Abaixo do minimo de respostas, mostra so' o total: com
     * poucas respostas, o texto livre identifica sozinho quem escreveu, e a
     * relacao de quem respondeu e' nominal.
     */
    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $servico = new PesquisaService();

        $totalRespondentes = $this->respondentes->contarPorEvento($id);
        $totalInscritos = count($this->respondentes->inscritosSemResposta($id)) + $totalRespondentes;
        $abrirNumeros = $totalRespondentes >= PesquisaService::MINIMO_PARA_EXIBIR;
        $resultado = [];

        if ($abrirNumeros) {
            foreach ($this->perguntas->listarPorEvento($id) as $pergunta) {
                $resultado[] = $this->resultadoDaPergunta($servico, $pergunta);
            }
        }

        $config = $this->config->vigente($id);

        $this->renderizar('admin/pesquisa/index', [
            'evento' => $evento,
            'config' => $config,
            'janelaTexto' => $servico->janelaTexto($evento, $config),
            'totalRespondentes' => $totalRespondentes,
            'totalInscritos' => $totalInscritos,
            'abrirNumeros' => $abrirNumeros,
            'minimoParaExibir' => PesquisaService::MINIMO_PARA_EXIBIR,
            'resultado' => $resultado,
            'temBonusDaPesquisa' => $this->temBonusDaPesquisa($id),
            'ultimoConvite' => $this->ultimoConvite($id),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Pesquisa de satisfação: ' . $evento['nome'], ['tipo' => 'pesquisa', 'id' => $id]);
    }

    public function perguntas($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $this->renderizar('admin/pesquisa/perguntas', [
            'evento' => $evento,
            'perguntas' => $this->perguntas->listarPorEvento($id),
            'tipos' => PesquisaService::TIPOS_PERGUNTA,
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Perguntas da pesquisa: ' . $evento['nome'], ['tipo' => 'pesquisaPerguntas', 'id' => $id]);
    }

    public function novaPergunta($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDaPergunta();
            $erros = $this->validarPergunta($dados);

            if ($erros === []) {
                $this->perguntas->criar($id, $dados);
                flashSucesso('Pergunta cadastrada.');
                $this->redirecionar('pesquisa/perguntas/' . $id);
                return;
            }

            $this->renderizarFormularioPergunta($evento, $dados, $erros, null, false);
            return;
        }

        $this->renderizarFormularioPergunta($evento, [
            'enunciado' => '',
            'tipo' => 'escala',
            'obrigatoria' => 0,
            'texto_ajuda' => null,
            'config' => null,
            'ativa' => 1,
        ], [], null, false);
    }

    /**
     * Pergunta com resposta e' congelada no que muda o significado do que
     * ja' foi respondido: enunciado, tipo e opcoes. A resposta guarda a
     * POSICAO da opcao, entao reordenar a lista mudaria o resultado em
     * silencio. Continuam editaveis o texto de ajuda, a obrigatoriedade e a
     * situacao.
     */
    public function editarPergunta($eventoId, $perguntaId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $pergunta = $this->perguntaDoEventoOu404($evento, $perguntaId);
        $congelada = $this->perguntas->temResposta((int) $pergunta['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDaPergunta();

            if ($congelada) {
                $campos = [
                    'obrigatoria' => $dados['obrigatoria'],
                    'texto_ajuda' => $dados['texto_ajuda'],
                    'ativa' => $dados['ativa'],
                ];
                $this->perguntas->atualizar((int) $pergunta['id'], $campos);
                flashAlerta('Esta pergunta já tem respostas, então o enunciado, o tipo e as opções não mudam. O restante foi salvo.');
                $this->redirecionar('pesquisa/perguntas/' . $id);
                return;
            }

            $erros = $this->validarPergunta($dados);

            if ($erros === []) {
                $this->perguntas->atualizar((int) $pergunta['id'], [
                    'enunciado' => $dados['enunciado'],
                    'tipo' => $dados['tipo'],
                    'obrigatoria' => $dados['obrigatoria'],
                    'texto_ajuda' => $dados['texto_ajuda'],
                    'config_json' => $dados['config'] !== null ? json_encode($dados['config']) : null,
                    'ativa' => $dados['ativa'],
                ]);
                flashSucesso('Pergunta atualizada.');
                $this->redirecionar('pesquisa/perguntas/' . $id);
                return;
            }

            $this->renderizarFormularioPergunta($evento, $dados, $erros, $pergunta, $congelada);
            return;
        }

        $servico = new PesquisaService();
        $config = $servico->configDa($pergunta);

        $this->renderizarFormularioPergunta($evento, [
            'enunciado' => $pergunta['enunciado'],
            'tipo' => $pergunta['tipo'],
            'obrigatoria' => (int) $pergunta['obrigatoria'],
            'texto_ajuda' => $pergunta['texto_ajuda'],
            'config' => $config !== [] ? $config : null,
            'ativa' => (int) $pergunta['ativa'],
        ], [], $pergunta, $congelada);
    }

    /**
     * "Remover" so' apaga de verdade a pergunta que nunca foi respondida;
     * com resposta, ela e' desativada, some do formulario e continua no
     * resultado.
     */
    public function removerPergunta($eventoId, $perguntaId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('pesquisa/perguntas/' . $id);
            return;
        }

        $pergunta = $this->perguntaDoEventoOu404($evento, $perguntaId);

        if ($this->perguntas->temResposta((int) $pergunta['id'])) {
            $this->perguntas->atualizar((int) $pergunta['id'], ['ativa' => 0]);
            flashAlerta('Esta pergunta já tem respostas, então foi desativada em vez de removida: ela sai do formulário e continua no resultado.');
            $this->redirecionar('pesquisa/perguntas/' . $id);
            return;
        }

        $this->perguntas->remover((int) $pergunta['id']);
        flashSucesso('Pergunta removida.');
        $this->redirecionar('pesquisa/perguntas/' . $id);
    }

    public function reordenarPerguntas($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $corpo = json_decode(file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? $corpo['ids'] : [];

        $this->perguntas->reordenar((int) $evento['id'], $ids);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function configuracoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $this->config->salvar($id, [
                'ativo' => isset($_POST['ativo']),
                'data_inicio' => isset($_POST['data_inicio']) ? $_POST['data_inicio'] : null,
                'data_fim' => isset($_POST['data_fim']) ? $_POST['data_fim'] : null,
                'titulo' => isset($_POST['titulo']) ? $_POST['titulo'] : null,
                'texto_abertura_html' => isset($_POST['texto_abertura_html']) ? sanitizarHtmlRico($_POST['texto_abertura_html']) : null,
                'convite_assunto' => isset($_POST['convite_assunto']) ? $_POST['convite_assunto'] : null,
                'convite_corpo_html' => isset($_POST['convite_corpo_html']) ? sanitizarHtmlRico($_POST['convite_corpo_html']) : null,
            ]);
            flashSucesso('Configurações da pesquisa salvas.');
            $this->redirecionar('pesquisa/configuracoes/' . $id);
            return;
        }

        $config = $this->config->vigente($id);

        $this->renderizar('admin/pesquisa/configuracoes', [
            'evento' => $evento,
            'config' => $config,
            'tituloVigente' => $this->config->tituloDe($config),
            'totalPerguntasAtivas' => count($this->perguntas->listarAtivas($id)),
            'temBonusDaPesquisa' => $this->temBonusDaPesquisa($id),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Configurações da pesquisa: ' . $evento['nome'], ['tipo' => 'pesquisaConfiguracoes', 'id' => $id]);
    }

    /**
     * Convite a responder: aviso no sino e mensagem por correio eletronico,
     * so' para quem ainda nao respondeu. A mensagem reaproveita a fila que
     * ja' existe (evento_comunicacoes, processada a dez por minuto por
     * database/processar_comunicacao_evento.php), entao nenhuma rotina nova
     * e' criada nesta fase.
     */
    public function convidar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('pesquisa/index/' . $id);
            return;
        }

        $config = $this->config->vigente($id);

        if ((int) $config['ativo'] !== 1) {
            flashErro('Ative a pesquisa antes de convidar os inscritos.');
            $this->redirecionar('pesquisa/index/' . $id);
            return;
        }

        if ($this->perguntas->listarAtivas($id) === []) {
            flashErro('Cadastre ao menos uma pergunta ativa antes de convidar os inscritos.');
            $this->redirecionar('pesquisa/index/' . $id);
            return;
        }

        $anterior = $this->ultimoConvite($id);

        if ($anterior !== null && $anterior['concluido_em'] === null) {
            flashAlerta('O convite anterior ainda está sendo enviado. Espere a fila terminar antes de disparar outro.');
            $this->redirecionar('pesquisa/index/' . $id);
            return;
        }

        $pendentes = $this->respondentes->inscritosSemResposta($id);

        if ($pendentes === []) {
            flashAlerta('Todos os inscritos já responderam à pesquisa.');
            $this->redirecionar('pesquisa/index/' . $id);
            return;
        }

        $titulo = $this->config->tituloDe($config);
        $assunto = !empty($config['convite_assunto']) ? $config['convite_assunto'] : $titulo . ': ' . $evento['nome'];
        $corpo = !empty($config['convite_corpo_html'])
            ? $config['convite_corpo_html']
            : '<p>A sua opinião sobre ' . htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8') . ' ajuda a organizar as próximas edições. Responder leva poucos minutos, e as respostas são guardadas separadas do seu nome.</p>';

        $inscricaoIds = [];

        foreach ($pendentes as $pendente) {
            $inscricaoIds[] = (int) $pendente['id'];
        }

        $comunicacaoId = (new EventoComunicacaoRepository())->criarCampanhaComDestinatarios([
            'evento_id' => $id,
            'autor_usuario_id' => Auth::usuarioId(),
            'assunto' => $assunto,
            'corpo_html' => $corpo,
        ], $inscricaoIds);

        $sino = new NotificacaoPainelRepository();

        foreach ($pendentes as $pendente) {
            $sino->criar(
                (int) $pendente['usuario_id'],
                'pesquisa_convite',
                $titulo,
                'A pesquisa de ' . $evento['nome'] . ' está aberta. Responder leva poucos minutos.',
                ['url' => url('eventoApp/pesquisa/' . $id)]
            );
        }

        $this->config->registrarConvite($id, $comunicacaoId);

        flashSucesso(count($pendentes) . ' inscritos foram avisados no aplicativo. As mensagens por correio eletrônico saem em lotes, dez por minuto.');
        $this->redirecionar('pesquisa/index/' . $id);
    }

    /**
     * Respostas em CSV, uma linha por envio, sem nome e sem horario, na
     * ordem aleatoria do identificador de envio: nem o arquivo que circula
     * fora do sistema carrega a ordem de chegada.
     */
    public function exportarRespostas($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $servico = new PesquisaService();
        $perguntas = $this->perguntas->listarPorEvento($id);
        $envios = $this->respostas->porEnvioDoEvento($id);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pesquisa-satisfacao-' . $id . '.csv"');

        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF"); // BOM UTF-8, pro Excel abrir acentuacao certo.

        $cabecalho = ['Data'];

        foreach ($perguntas as $pergunta) {
            $cabecalho[] = $pergunta['enunciado'];
        }

        fputcsv($saida, $cabecalho, ';');

        foreach ($envios as $envio) {
            $linha = [$envio['respondido_em']];

            foreach ($perguntas as $pergunta) {
                $linha[] = $this->valorLegivel($servico, $pergunta, isset($envio['respostas'][(int) $pergunta['id']]) ? $envio['respostas'][(int) $pergunta['id']] : []);
            }

            fputcsv($saida, $linha, ';');
        }

        fclose($saida);
        exit;
    }

    private function resultadoDaPergunta(PesquisaService $servico, array $pergunta)
    {
        $id = (int) $pergunta['id'];
        $tipo = $pergunta['tipo'];
        $item = [
            'id' => $id,
            'enunciado' => $pergunta['enunciado'],
            'tipo' => $tipo,
            'ativa' => (int) $pergunta['ativa'],
            'envios' => $this->respostas->contarEnvios($id),
            'distribuicao' => [],
            'opcoes' => [],
            'media' => null,
            'textos' => [],
        ];

        if ($tipo === 'texto') {
            $item['textos'] = $this->respostas->textos($id);

            return $item;
        }

        $distribuicao = $this->respostas->distribuicao($id);
        $item['distribuicao'] = $distribuicao;

        if ($tipo === 'escala') {
            $soma = 0;
            $total = 0;

            foreach ($distribuicao as $nota => $quantas) {
                $soma += $nota * $quantas;
                $total += $quantas;
            }

            $item['media'] = $total > 0 ? round($soma / $total, 2) : null;

            return $item;
        }

        $item['opcoes'] = $servico->opcoesDa($pergunta);

        return $item;
    }

    private function valorLegivel(PesquisaService $servico, array $pergunta, array $respostas)
    {
        if ($respostas === []) {
            return '';
        }

        if ($pergunta['tipo'] === 'texto') {
            return $respostas[0]['valor_texto'];
        }

        if ($pergunta['tipo'] === 'escala') {
            return (int) $respostas[0]['valor_numero'];
        }

        $opcoes = $servico->opcoesDa($pergunta);
        $escolhidas = [];

        foreach ($respostas as $resposta) {
            $indice = ((int) $resposta['valor_numero']) - 1;
            $escolhidas[] = isset($opcoes[$indice]) ? $opcoes[$indice] : (string) $resposta['valor_numero'];
        }

        return implode(' | ', $escolhidas);
    }

    private function dadosDaPergunta()
    {
        $tipo = isset($_POST['tipo']) ? (string) $_POST['tipo'] : '';

        if (!isset(PesquisaService::TIPOS_PERGUNTA[$tipo])) {
            $tipo = 'escala';
        }

        $config = null;

        if ($tipo === 'lista_opcoes' || $tipo === 'multipla_escolha') {
            $config = ['opcoes' => $this->opcoesDoFormulario()];
        } elseif ($tipo === 'escala') {
            $minimo = trim(isset($_POST['rotulo_minimo']) ? (string) $_POST['rotulo_minimo'] : '');
            $maximo = trim(isset($_POST['rotulo_maximo']) ? (string) $_POST['rotulo_maximo'] : '');

            if ($minimo !== '' || $maximo !== '') {
                $config = ['rotulo_minimo' => $minimo, 'rotulo_maximo' => $maximo];
            }
        }

        $ajuda = trim(isset($_POST['texto_ajuda']) ? (string) $_POST['texto_ajuda'] : '');

        return [
            'enunciado' => mb_substr(trim(isset($_POST['enunciado']) ? (string) $_POST['enunciado'] : ''), 0, 300),
            'tipo' => $tipo,
            'obrigatoria' => isset($_POST['obrigatoria']) ? 1 : 0,
            'texto_ajuda' => $ajuda !== '' ? mb_substr($ajuda, 0, 255) : null,
            'config' => $config,
            'ativa' => isset($_POST['ativa']) ? 1 : 0,
        ];
    }

    private function opcoesDoFormulario()
    {
        $bruto = isset($_POST['opcoes']) ? (string) $_POST['opcoes'] : '';
        $opcoes = [];

        foreach (explode("\n", $bruto) as $linha) {
            $linha = trim($linha);

            if ($linha !== '' && !in_array($linha, $opcoes, true)) {
                $opcoes[] = mb_substr($linha, 0, 200);
            }
        }

        return $opcoes;
    }

    private function validarPergunta(array $dados)
    {
        $erros = [];

        if ($dados['enunciado'] === '') {
            $erros['enunciado'] = 'Escreva a pergunta.';
        }

        if (($dados['tipo'] === 'lista_opcoes' || $dados['tipo'] === 'multipla_escolha')
            && (!isset($dados['config']['opcoes']) || count($dados['config']['opcoes']) < 2)) {
            $erros['opcoes'] = 'Informe ao menos duas opções, uma por linha.';
        }

        return $erros;
    }

    private function renderizarFormularioPergunta(array $evento, array $dados, array $erros, $pergunta, $congelada)
    {
        $id = (int) $evento['id'];

        $this->renderizar('admin/pesquisa/pergunta_form', [
            'evento' => $evento,
            'pergunta' => $pergunta,
            'dados' => $dados,
            'erros' => $erros,
            'congelada' => $congelada,
            'tipos' => PesquisaService::TIPOS_PERGUNTA,
            'escalaMinima' => PesquisaService::ESCALA_MINIMA,
            'escalaMaxima' => PesquisaService::ESCALA_MAXIMA,
        ], ($pergunta === null ? 'Nova pergunta: ' : 'Editar pergunta: ') . $evento['nome'], ['tipo' => 'pesquisaPerguntas', 'id' => $id]);
    }

    /**
     * Existe bonus ativo do tipo responder_pesquisa? Sem ele, responder nao
     * credita nada, e a tela avisa, com o caminho para o cadastro.
     */
    private function temBonusDaPesquisa($eventoId)
    {
        foreach ((new BonusRepository())->listarAtivos($eventoId) as $bonus) {
            if ($bonus['tipo'] === 'responder_pesquisa') {
                return true;
            }
        }

        return false;
    }

    private function ultimoConvite($eventoId)
    {
        $config = $this->config->vigente($eventoId);

        if (empty($config['convite_comunicacao_id'])) {
            return null;
        }

        return (new EventoComunicacaoRepository())->buscarPorId((int) $config['convite_comunicacao_id']);
    }
}
