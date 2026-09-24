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
use App\Services\TrabalhoSubmissaoException;
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
        $this->redirecionar('auth/loginEvento/' . (int) $eventoId);
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

        $this->renderizarFormulario($evento, $config);
    }

    /**
     * Reabertura da Fase 51 (achados da equipe de Teste Cego): um so' lugar
     * monta os dados da tela do formulario, tanto na primeira exibicao
     * quanto na volta depois de um erro. $valores e' o que a pessoa ja
     * tinha preenchido (a tela mostra tudo de novo; so' os arquivos
     * precisam ser escolhidos outra vez, limitacao do navegador), e
     * $erro, quando o erro sabe o campo, faz a tela destacar esse campo.
     */
    private function renderizarFormulario(array $evento, array $config, array $valores = [], array $termosMarcados = [], \RuntimeException $erro = null)
    {
        $eventoId = $evento['id'];
        $usuario = $this->usuarios->buscarPorId(Auth::usuarioId());
        $perfilPessoa = $this->usuarioPerfil->buscarPorUsuarioId(Auth::usuarioId());
        $comCampo = $erro instanceof TrabalhoSubmissaoException;

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
            'termosMarcados' => $termosMarcados,
            'valores' => $valores,
            'erro' => $erro !== null ? $erro->getMessage() : null,
            'campoErro' => $comCampo ? $erro->campo() : null,
            'indiceErro' => $comCampo ? $erro->indice() : null,
        ], 'Submeter trabalho: ' . $evento['nome']);
    }

    private function processarSubmissao($eventoId, array $config)
    {
        $coautores = [];

        if (!empty($_POST['coautor_nome']) && is_array($_POST['coautor_nome'])) {
            foreach ($_POST['coautor_nome'] as $indice => $nome) {
                $nome = trim($nome);

                // Bloco de coautor inteiro em branco e' ignorado; com qualquer
                // dado preenchido, o servico exige nome, CPF e e-mail.
                $cpfDigitado = isset($_POST['coautor_cpf'][$indice]) ? trim($_POST['coautor_cpf'][$indice]) : '';
                $emailDigitado = isset($_POST['coautor_email'][$indice]) ? trim($_POST['coautor_email'][$indice]) : '';

                if ($nome === '' && $cpfDigitado === '' && $emailDigitado === '') {
                    continue;
                }

                $coautores[] = [
                    'indice' => $indice,
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

        $servico = new TrabalhoSubmissaoService();

        try {
            $trabalhoId = $servico->submeter($eventoId, Auth::usuarioId(), $dadosAutorPrincipal, $dadosTrabalho, $coautores, $arquivosEnviados, $termosAceitos);
        } catch (\RuntimeException $e) {
            $this->renderizarFormulario($this->eventos->buscarPorId($eventoId), $config, $_POST, $termosAceitos, $e);
            return;
        }

        // Um unico aviso na tela, com tudo que aconteceu: recebimento,
        // inscricao dos autores e e-mail.
        $aviso = $this->montarAvisoRecebimento($servico->resumoUltimaSubmissao());

        if ($aviso['falhou_email']) {
            flashAlerta($aviso['mensagem']);
        } else {
            flashSucesso($aviso['mensagem']);
        }

        $this->redirecionar('trabalho/ver/' . $trabalhoId);
    }

    /**
     * Texto do aviso unico depois de uma submissao com sucesso. Coautor sem
     * inscricao automatica (conta em analise, ou opcao desligada) e e-mail
     * que nao saiu entram na mesma frase, para a pessoa nunca achar que
     * tudo deu certo quando nao deu.
     */
    private function montarAvisoRecebimento($resumo)
    {
        $mensagem = 'Trabalho recebido. Protocolo nº ' . (int) $resumo['trabalho_id'] . '.';
        $pessoas = $resumo['pessoas'];
        $coautores = array_slice($pessoas, 1);

        if ($resumo['inscricao_automatica']) {
            $inscritos = [];
            $semInscricao = [];

            foreach ($coautores as $coautor) {
                if (in_array($coautor['inscricao'], ['nova', 'ja_inscrito'], true)) {
                    $inscritos[] = $coautor['nome'];
                } else {
                    $semInscricao[] = $coautor['nome'];
                }
            }

            if (empty($inscritos)) {
                $mensagem .= ' Você está inscrito(a) no evento.';
            } elseif (count($inscritos) === 1) {
                $mensagem .= ' Você e o(a) coautor(a) ' . $inscritos[0] . ' estão inscritos(as) no evento.';
            } else {
                $mensagem .= ' Você e os coautores ' . implode(', ', $inscritos) . ' estão inscritos no evento.';
            }

            if (!empty($semInscricao)) {
                $mensagem .= ' A inscrição de ' . implode(', ', $semInscricao) . ' não foi automática (o cadastro dessa pessoa no sistema está em análise): ela deve se inscrever pelo formulário do evento.';
            }
        }

        $enviados = [];
        $falhas = [];

        foreach ($pessoas as $pessoa) {
            if ($pessoa['email_enviado'] === true) {
                $enviados[] = $pessoa['email'];
            } elseif ($pessoa['email_enviado'] === false) {
                $falhas[] = $pessoa['email'];
            }
        }

        if (!empty($enviados)) {
            $mensagem .= ' Enviamos um e-mail de confirmação para ' . implode(', ', $enviados) . '.';
        }

        if (!empty($falhas)) {
            $mensagem .= ' Não foi possível enviar o e-mail de confirmação para ' . implode(', ', $falhas) . ' agora; guarde o número do protocolo.';
        }

        return ['mensagem' => $mensagem, 'falhou_email' => !empty($falhas)];
    }

    public function meusTrabalhos()
    {
        if (!Auth::autenticado()) {
            // Reabertura da Fase 51: autor sem sessao (ou com a sessao
            // vencida) entra pela porta do Evento, nunca pela do Concurso.
            $this->redirecionar('auth/loginEvento');
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
            // Reabertura da Fase 51: autor sem sessao (ou com a sessao
            // vencida) entra pela porta do Evento, nunca pela do Concurso.
            $this->redirecionar('auth/loginEvento');
            return;
        }

        $trabalho = $this->trabalhos->buscarComDetalhes($id);

        // Reabertura da Fase 51: qualquer autor com conta (principal ou
        // coautor) acompanha o trabalho, somente leitura.
        if ($trabalho === null || !$this->autores->usuarioEhAutor($id, Auth::usuarioId())) {
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
