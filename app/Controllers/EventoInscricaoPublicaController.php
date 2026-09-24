<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\EventoCampoInscricaoRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AuthService;
use App\Services\NotificacaoService;
use App\Validation\CpfValidador;

/**
 * Fase 39/40: tela publica de inscricao em Evento. Nome do controller evita
 * "EventoController" generico - "evento" ja' e' o termo usado no sistema pra
 * evento de agenda do Google (Mentoria/Oficina), ver decisao de arquitetura
 * do plano da funcionalidade. Renomeado na Fase 40 de "SemanaInovacaoController"
 * (nome especifico do evento atual) para este nome generico, coerente com N
 * eventos simultaneos podendo existir ao mesmo tempo (ver
 * SemanaInovacaoRepository::listar()).
 *
 * Qualquer conta ja' aprovada (equipe, avaliador, colaborador, inscrito ou
 * visitante sem nenhum perfil) pode se inscrever - por isso a checagem aqui e'
 * Auth::autenticado() direto, sem RoleMiddleware (que exige perfil
 * especifico). Quem nao tem conta faz login/cadastro pelas telas ja'
 * existentes (auth/login, eventoInscricao/cadastrar, auth/google) e volta a
 * esta mesma URL pra concluir a inscricao (ver
 * AuthController::redirecionarPosLogin(), Fase 40).
 *
 * Fase 39 (correcao pos-teste): "Documento" e' o unico campo estrutural -
 * os demais vem de EventoCampoInscricaoRepository (configuraveis pelo
 * Admin, sub-aba "Formulario de inscricao" do evento), montados/validados
 * dinamicamente aqui, mesmo espirito de SubmissaoService::processar() mas
 * bem mais simples (so' 2 tipos: texto e lista_opcoes).
 *
 * Fase 40: todas as acoes aceitam $id (evento_id) na URL - com N eventos
 * divulgados simultaneamente na home, cada bloco precisa levar ao formulario
 * do seu proprio evento, nao sempre ao "ativo mais recente" (limitacao da
 * Fase 39, so' tinha 1 evento). $id omitido mantem o fallback antigo, para
 * compatibilidade de quem acessa a rota sem parametro.
 */
class EventoInscricaoPublicaController extends Controller
{
    public function index($id = null)
    {
        $repositorioEventos = new SemanaInovacaoRepository();
        $evento = $id !== null ? $repositorioEventos->buscarPorId($id) : $repositorioEventos->buscarAtivoMaisRecente();

        if ($evento === null) {
            $this->renderizar('publico/evento_inscricao', [
                'evento' => null,
                'campos' => [],
                'inscricao' => null,
                'respostas' => [],
                'erroGeral' => null,
                'erros' => [],
                'dados' => [],
            ], 'Semana de Inovação');
            return;
        }

        if (!Auth::autenticado()) {
            // Fase 41: so' grava o retorno proprio quando nao ha' um ja'
            // valido na sessao - preserva a intencao de quem chegou aqui
            // vindo do shell do aplicativo (EventoAppController::index()),
            // que precisa continuar apontando pra ele mesmo apos o login, em
            // vez de ser sobrescrito sempre que esta tela e' visitada.
            $retornoExistente = isset($_SESSION['retorno_apos_login']) ? $_SESSION['retorno_apos_login'] : null;
            $retornoAindaValido = is_array($retornoExistente)
                && isset($retornoExistente['expira_em'])
                && time() < $retornoExistente['expira_em'];

            if (!$retornoAindaValido) {
                $_SESSION['retorno_apos_login'] = [
                    'destino' => 'eventoInscricao/index/' . $evento['id'],
                    'expira_em' => time() + 1800,
                ];
            }
        }

        $campos = (new EventoCampoInscricaoRepository())->listarPorEvento($evento['id']);
        $inscricao = Auth::autenticado()
            ? (new EventoInscricaoRepository())->buscarPorEventoEUsuario($evento['id'], Auth::usuarioId())
            : null;

        // Fase 41: quem ja esta inscrito nao ve mais a confirmacao aqui -
        // esta tela e' o formulario PUBLICO (site comum), desvinculado do
        // aplicativo. Os dados da inscricao moram em EventoAppController::
        // inscricao(), dentro do fluxo autenticado do app.
        if ($inscricao !== null) {
            $this->redirecionar('eventoApp/inscricao/' . $evento['id']);
            return;
        }

        $respostas = [];

        $this->renderizar('publico/evento_inscricao', [
            'evento' => $evento,
            'campos' => $campos,
            'autenticado' => Auth::autenticado(),
            'inscricao' => $inscricao,
            'respostas' => $respostas,
            'erroGeral' => null,
            'erros' => [],
            'dados' => [],
        ], $evento['nome']);
    }

    /**
     * Fase 40: cadastro dedicado do fluxo do evento (e-mail/senha) - perfil
     * "inscrito" auto-aprovado, alternativo ao "Continuar com Google" (ver
     * AuthController::google()). Login automatico ao final, direto de volta
     * pro formulario do evento que originou o cadastro.
     */
    public function cadastrar($id = null)
    {
        $repositorioEventos = new SemanaInovacaoRepository();
        $evento = $id !== null ? $repositorioEventos->buscarPorId($id) : $repositorioEventos->buscarAtivoMaisRecente();

        if ($evento === null) {
            http_response_code(404);
            exit('Nenhum evento disponível para inscrição no momento.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

            if ($nome === '' || $email === '' || $senha === '') {
                $erro = 'Preencha nome, e-mail e senha.';
            } else {
                $resultado = (new AuthService())->cadastrarInscrito($nome, $email, $senha);

                if ($resultado['sucesso']) {
                    $usuario = (new UsuarioRepository())->buscarPorId($resultado['usuario_id']);
                    $perfis = (new UsuarioRepository())->perfisDoUsuario($resultado['usuario_id']);
                    Auth::login($usuario, $perfis);
                    $this->redirecionar('eventoInscricao/index/' . $evento['id']);
                    return;
                }

                $erro = $resultado['mensagem'];
            }
        }

        $this->renderizar('publico/evento_inscricao_cadastro', [
            'evento' => $evento,
            'erro' => $erro,
        ], 'Criar cadastro: ' . $evento['nome']);
    }

    public function inscrever()
    {
        if (!Auth::autenticado()) {
            // Reabertura da Fase 51: sem sessao (ou com a sessao vencida no
            // meio do preenchimento), volta para a inscricao do proprio
            // evento, que oferece cadastro e entrada pelo fluxo do evento,
            // nunca para o login do Concurso.
            $eventoIdPost = (int) (isset($_POST['evento_id']) ? $_POST['evento_id'] : 0);
            $this->redirecionar('eventoInscricao/index' . ($eventoIdPost > 0 ? '/' . $eventoIdPost : ''));
            return;
        }

        $repositorioEventos = new SemanaInovacaoRepository();
        $eventoId = (int) (isset($_POST['evento_id']) ? $_POST['evento_id'] : 0);
        $evento = $eventoId > 0 ? $repositorioEventos->buscarPorId($eventoId) : $repositorioEventos->buscarAtivoMaisRecente();

        if ($evento === null) {
            http_response_code(404);
            exit('Nenhum evento disponível para inscrição no momento.');
        }

        $repository = new EventoInscricaoRepository();

        if ($repository->estaInscrito($evento['id'], Auth::usuarioId())) {
            $this->redirecionar('eventoInscricao/index/' . $evento['id']);
            return;
        }

        $campos = (new EventoCampoInscricaoRepository())->listarPorEvento($evento['id']);
        $documento = trim(isset($_POST['documento']) ? $_POST['documento'] : '');
        $dados = [];
        $respostas = [];
        $erros = [];
        $tipoDocumentoEscolhido = null;
        $cargoEscolhido = null;
        $orgaoOrigemEscolhido = null;

        foreach ($campos as $campo) {
            $nomePost = 'campo_' . $campo['id'];
            $valor = trim(isset($_POST[$nomePost]) ? $_POST[$nomePost] : '');

            if ($campo['tipo'] === 'lista_opcoes') {
                $config = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : null;
                $opcoes = $config !== null && isset($config['opcoes']) ? $config['opcoes'] : [];

                if ($valor !== '' && !in_array($valor, $opcoes, true)) {
                    $valor = '';
                }
            }

            if ($campo['obrigatorio'] && $valor === '') {
                $erros[$nomePost] = 'Campo obrigatório.';
            }

            $dados[$nomePost] = $valor;
            $respostas[$campo['id']] = $valor !== '' ? $valor : null;

            if ($campo['rotulo'] === EventoCampoInscricaoRepository::ROTULO_TIPO_DOCUMENTO) {
                $tipoDocumentoEscolhido = $valor !== '' ? $valor : null;
            } elseif ($campo['rotulo'] === 'Cargo') {
                $cargoEscolhido = $valor !== '' ? $valor : null;
            } elseif ($campo['rotulo'] === 'Órgão de origem') {
                $orgaoOrigemEscolhido = $valor !== '' ? $valor : null;
            }
        }

        // Fase 42 (correcao pos-teste de fumaca): "documento" e' texto livre
        // porque o tipo (RG/CPF/RNE/Passaporte) e' escolhido no campo
        // configuravel acima - so' valida formato de CPF (mesmo algoritmo de
        // digito verificador ja usado no cadastro do Concurso,
        // App\Validation\CpfValidador) quando "CPF" foi o tipo escolhido.
        // Gravado so' com digitos, mesmo padrao ja usado la'.
        if ($documento === '') {
            $erros['documento'] = 'Informe o número do documento de identificação.';
        } elseif ($tipoDocumentoEscolhido === 'CPF') {
            if (!CpfValidador::valido($documento)) {
                $erros['documento'] = 'CPF inválido.';
            } else {
                $documento = CpfValidador::apenasDigitos($documento);
            }
        }

        if (!empty($erros)) {
            $this->renderizar('publico/evento_inscricao', [
                'evento' => $evento,
                'campos' => $campos,
                'autenticado' => true,
                'inscricao' => null,
                'respostas' => [],
                'erroGeral' => 'Corrija os campos indicados.',
                'erros' => $erros,
                'dados' => $dados + ['documento' => $documento],
            ], $evento['nome']);
            return;
        }

        // Fase 49B: documento/cargo/órgão de origem são dado da PESSOA, não
        // do vínculo dela com este evento - gravados direto em
        // usuarios_perfil (upsert parcial, preserva o que já existir),
        // nunca em evento_inscricoes.
        $camposPerfil = ['documento' => $documento, 'tipo_documento' => $tipoDocumentoEscolhido !== null ? $tipoDocumentoEscolhido : 'CPF'];

        if ($cargoEscolhido !== null) {
            $camposPerfil['cargo'] = $cargoEscolhido;
        }

        if ($orgaoOrigemEscolhido !== null) {
            $camposPerfil['orgao_origem'] = $orgaoOrigemEscolhido;
        }

        (new UsuarioPerfilRepository())->atualizarParcial(Auth::usuarioId(), $camposPerfil);

        $repository->inscrever($evento['id'], Auth::usuarioId(), $respostas, $evento['modo_credenciamento']);

        $usuario = (new UsuarioRepository())->buscarPorId(Auth::usuarioId());

        if ($usuario !== null) {
            (new NotificacaoService())->confirmarInscricaoEvento($usuario['email'], $usuario['nome'], $evento);
        }

        // Fase 44: mesma confirmacao tambem dentro do aplicativo (sino de
        // notificacoes), alem do e-mail acima - ver EventoAppController::
        // index() pro destino do link.
        (new NotificacaoPainelRepository())->criar(
            Auth::usuarioId(),
            'evento_inscricao_confirmada',
            'Inscrição confirmada: ' . $evento['nome'],
            'Sua inscrição em ' . $evento['nome'] . ' foi confirmada.',
            ['url' => url('eventoApp/index/' . $evento['id'])]
        );

        flashSucesso('Inscrição confirmada com sucesso.');
        // Fase 41: destino final passa a ser o painel do aplicativo (a
        // pessoa acabou de se tornar "inscrita") em vez desta mesma tela de
        // inscricao - que continua alcancavel a partir de la' pelo link "Ver
        // minha inscrição".
        $this->redirecionar('eventoApp/index/' . $evento['id']);
    }
}
