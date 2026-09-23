<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoAutorRepository;
use App\Repositories\EventoTrabalhoTermoRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoCriterioRepository;
use App\Repositories\TrabalhoEixoTematicoRepository;
use App\Repositories\TrabalhoNaturezaRepository;
use App\Repositories\TrabalhoRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Repositories\UsuarioRepository;
use App\Services\TrabalhoSubmissaoService;

/**
 * Fase 49: submissao de Trabalhos pelo proprio participante - navegador
 * comum, fora do aplicativo instalavel (decisao do plano mestre: a
 * submissao de trabalho cientifico fica de fora do app). Exige conta
 * (login/cadastro autoatendido), reaproveitando o mesmo mecanismo de
 * retorno pos-login ja usado por eventoApp/eventoInscricao
 * (AuthController::redirecionarPosLogin()).
 */
class TrabalhoController extends Controller
{
    private $eventos;
    private $config;
    private $eixos;
    private $naturezas;
    private $criterios;
    private $trabalhos;
    private $autores;
    private $usuarios;
    private $usuarioPerfil;

    public function __construct()
    {
        $this->eventos = new SemanaInovacaoRepository();
        $this->config = new TrabalhoConfigRepository();
        $this->eixos = new TrabalhoEixoTematicoRepository();
        $this->naturezas = new TrabalhoNaturezaRepository();
        $this->criterios = new TrabalhoCriterioRepository();
        $this->trabalhos = new TrabalhoRepository();
        $this->autores = new TrabalhoAutorRepository();
        $this->usuarios = new UsuarioRepository();
        $this->usuarioPerfil = new UsuarioPerfilRepository();
    }

    private function exigirLogin($eventoId)
    {
        if (Auth::autenticado()) {
            return;
        }

        $_SESSION['retorno_apos_login'] = [
            'destino' => 'trabalho/formulario/' . (int) $eventoId,
            'expira_em' => time() + 1800,
        ];

        // Fase 51: a porta de entrada e' a do Evento (identidade visual
        // propria, Fase 48B), nao a do Concurso - quem chega aqui veio da
        // pagina publica do evento e nunca deveria ver a marca do Premio de
        // Inovacao no meio do caminho. O retorno para o formulario continua
        // funcionando: AuthController::entrarComResultado() consulta
        // redirecionarPosLogin() antes do destino padrao do contexto.
        $this->redirecionar('auth/loginEvento');
        exit;
    }

    public function formulario($eventoId)
    {
        $this->exigirLogin($eventoId);

        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $config = $this->config->buscarPorEvento($eventoId);

        if ($config === null || $config['status'] !== 'publicado') {
            $this->renderizar('trabalho/indisponivel', ['evento' => $evento, 'motivo' => null], 'Submissão de Trabalhos');
            return;
        }

        // Achado do usuário na revisão de fumaça (19/09/2026): antes disto,
        // o formulário inteiro aparecia mesmo fora do prazo, e só avisava
        // "o prazo ainda não começou" depois de a pessoa preencher tudo e
        // tentar enviar. A checagem de verdade (que decide se salva)
        // continua em TrabalhoSubmissaoService::validarPrazo(), esta aqui
        // só evita mostrar o formulário fora de hora.
        $agora = date('Y-m-d H:i:s');

        if ($config['data_abertura_submissao'] !== null && $agora < $config['data_abertura_submissao']) {
            $motivo = 'A submissão de trabalhos ainda não abriu. Abre em ' . formatarDataHora($config['data_abertura_submissao']) . '.';
            $this->renderizar('trabalho/indisponivel', ['evento' => $evento, 'motivo' => $motivo], 'Submissão de Trabalhos');
            return;
        }

        if ($config['data_fim_submissao'] !== null && $agora > $config['data_fim_submissao']) {
            $motivo = 'O prazo de submissão de trabalhos já terminou em ' . formatarDataHora($config['data_fim_submissao']) . '.';
            $this->renderizar('trabalho/indisponivel', ['evento' => $evento, 'motivo' => $motivo], 'Submissão de Trabalhos');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarSubmissao($eventoId, $config);
            return;
        }

        $usuario = $this->usuarios->buscarPorId(Auth::usuarioId());
        $perfilPessoa = $this->usuarioPerfil->buscarPorUsuarioId(Auth::usuarioId());

        $this->renderizar('trabalho/formulario', [
            'evento' => $evento,
            'config' => $config,
            'eixos' => $this->eixos->listarPorEvento($eventoId),
            'naturezas' => $this->naturezas->listarPorEvento($eventoId),
            'metodosHabilitados' => $this->config->metodosHabilitados($eventoId),
            'extensoesHabilitadas' => $this->config->extensoesEditavelHabilitadas($eventoId),
            'usuario' => $usuario,
            'perfilPessoa' => $perfilPessoa,
            'termos' => (new EventoTrabalhoTermoRepository())->listarAtivos($eventoId),
            'termosMarcados' => [],
            'erro' => null,
        ], 'Submeter trabalho: ' . $evento['nome']);
    }

    private function processarSubmissao($eventoId, array $config)
    {
        $coautores = [];

        if (!empty($_POST['coautor_nome']) && is_array($_POST['coautor_nome'])) {
            foreach ($_POST['coautor_nome'] as $indice => $nome) {
                $nome = trim($nome);

                if ($nome === '') {
                    continue;
                }

                $coautores[] = [
                    'nome' => $nome,
                    'cpf' => isset($_POST['coautor_cpf'][$indice]) ? trim($_POST['coautor_cpf'][$indice]) : '',
                    'email' => isset($_POST['coautor_email'][$indice]) ? trim($_POST['coautor_email'][$indice]) : '',
                    'cargo' => isset($_POST['coautor_cargo'][$indice]) ? trim($_POST['coautor_cargo'][$indice]) : '',
                    'orgao_origem' => isset($_POST['coautor_orgao_origem'][$indice]) ? trim($_POST['coautor_orgao_origem'][$indice]) : '',
                ];
            }
        }

        $dadosAutorPrincipal = [
            'nome' => trim($_POST['autor_nome']),
            'cpf' => trim($_POST['autor_cpf']),
            'email' => trim($_POST['autor_email']),
            'cargo' => isset($_POST['autor_cargo']) ? trim($_POST['autor_cargo']) : '',
            'orgao_origem' => isset($_POST['autor_orgao_origem']) ? trim($_POST['autor_orgao_origem']) : '',
        ];

        $dadosTrabalho = [
            'eixo_tematico_id' => !empty($_POST['eixo_tematico_id']) ? (int) $_POST['eixo_tematico_id'] : null,
            'natureza_id' => !empty($_POST['natureza_id']) ? (int) $_POST['natureza_id'] : null,
            'titulo' => trim($_POST['titulo']),
            'telefone_contato' => isset($_POST['telefone_contato']) ? trim($_POST['telefone_contato']) : '',
            'metodo_submissao' => isset($_POST['metodo_submissao']) ? $_POST['metodo_submissao'] : '',
            'conteudo_html' => isset($_POST['conteudo_html']) ? $_POST['conteudo_html'] : null,
            'link_avaliacao' => isset($_POST['link_avaliacao']) ? trim($_POST['link_avaliacao']) : null,
            'link_publicacao' => isset($_POST['link_publicacao']) ? trim($_POST['link_publicacao']) : null,
        ];

        $arquivosEnviados = [];

        if (isset($_FILES['arquivo_avaliacao']) && $_FILES['arquivo_avaliacao']['error'] !== UPLOAD_ERR_NO_FILE) {
            $arquivosEnviados['arquivo_avaliacao'] = $_FILES['arquivo_avaliacao'];
        }

        if (isset($_FILES['arquivo_publicacao']) && $_FILES['arquivo_publicacao']['error'] !== UPLOAD_ERR_NO_FILE) {
            $arquivosEnviados['arquivo_publicacao'] = $_FILES['arquivo_publicacao'];
        }

        $termosAceitos = [];

        if (!empty($_POST['termos_aceitos']) && is_array($_POST['termos_aceitos'])) {
            $termosAceitos = array_map('intval', $_POST['termos_aceitos']);
        }

        try {
            $trabalhoId = (new TrabalhoSubmissaoService())->submeter($eventoId, Auth::usuarioId(), $dadosAutorPrincipal, $dadosTrabalho, $coautores, $arquivosEnviados, $termosAceitos);
            flashSucesso('Trabalho submetido com sucesso.');
            $this->redirecionar('trabalho/ver/' . $trabalhoId);
        } catch (\RuntimeException $e) {
            $evento = $this->eventos->buscarPorId($eventoId);
            $usuario = $this->usuarios->buscarPorId(Auth::usuarioId());
            $perfilPessoa = $this->usuarioPerfil->buscarPorUsuarioId(Auth::usuarioId());

            $this->renderizar('trabalho/formulario', [
                'evento' => $evento,
                'config' => $config,
                'eixos' => $this->eixos->listarPorEvento($eventoId),
                'naturezas' => $this->naturezas->listarPorEvento($eventoId),
                'metodosHabilitados' => $this->config->metodosHabilitados($eventoId),
                'extensoesHabilitadas' => $this->config->extensoesEditavelHabilitadas($eventoId),
                'usuario' => $usuario,
                'perfilPessoa' => $perfilPessoa,
                'termos' => (new EventoTrabalhoTermoRepository())->listarAtivos($eventoId),
                'termosMarcados' => $termosAceitos,
                'erro' => $e->getMessage(),
            ], 'Submeter trabalho: ' . $evento['nome']);
        }
    }

    public function meusTrabalhos()
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('auth/login');
            return;
        }

        $rotulosSituacao = [
            'submetido' => 'Submetido',
            'desclassificado' => 'Desclassificado',
            'aprovado' => 'Aprovado',
            'reprovado' => 'Reprovado',
        ];

        $trabalhos = $this->autores->listarTrabalhosDoUsuario(Auth::usuarioId());

        foreach ($trabalhos as &$trabalho) {
            $trabalho['situacao_rotulo'] = $rotulosSituacao[$trabalho['status']];
        }
        unset($trabalho);

        $this->renderizar('trabalho/meusTrabalhos', [
            'trabalhos' => $trabalhos,
        ], 'Meus trabalhos');
    }

    public function ver($id)
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('auth/login');
            return;
        }

        $trabalho = $this->trabalhos->buscarComDetalhes($id);

        if ($trabalho === null || (int) $trabalho['autor_principal_usuario_id'] !== (int) Auth::usuarioId()) {
            http_response_code(404);
            exit('Trabalho não encontrado.');
        }

        $rotulosSituacao = [
            'submetido' => 'Submetido',
            'desclassificado' => 'Desclassificado',
            'aprovado' => 'Aprovado',
            'reprovado' => 'Reprovado',
        ];
        $trabalho['situacao_rotulo'] = $rotulosSituacao[$trabalho['status']];
        $trabalho['foi_desclassificado'] = $trabalho['status'] === 'desclassificado';

        $this->renderizar('trabalho/ver', [
            'trabalho' => $trabalho,
            'autores' => $this->autores->listarPorTrabalho($id),
        ], $trabalho['titulo']);
    }
}
