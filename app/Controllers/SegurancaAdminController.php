<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Auth;
use App\Core\Cifra;
use App\Core\Controller;
use App\Core\Mailer;
use App\Middleware\RoleMiddleware;
use App\Repositories\CredencialSistemaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\UsuarioRepository;
use App\Services\GoogleCalendarService;
use App\Services\GoogleCalendarSyncService;

/**
 * Fase 35 (Parte C): aba "Segurança 🔐" dentro de Configurações - onde as
 * credenciais de integracao sao trocadas sem depender de acesso ao servidor.
 *
 * Duas telas de proposito:
 *
 * - index() e' a ANTESSALA. Nao mostra nada sensivel, nao audita e nao
 *   avisa ninguem. Ela existe porque abrir a tela seguinte dispara aviso a
 *   todos os outros administradores globais, e isso e' irreversivel: sem a
 *   antessala, quem clicasse na aba por curiosidade ja teria alertado os
 *   colegas, e aviso que dispara a toa e' aviso que as pessoas aprendem a
 *   ignorar.
 *
 * - credenciais() e' a tela real. Audita e avisa SEMPRE que e' desenhada,
 *   venha de onde vier - e nao no clique da antessala. Auditar o clique
 *   deixaria de fora justamente o caminho torto (digitar
 *   seguranca/credenciais direto no endereco), que e' o unico que
 *   interessa vigiar.
 *
 * Perfil exigido: administrador GLOBAL. RoleMiddleware::exigir() sem
 * $concursoId e' exatamente esse criterio (Auth::temPerfil so' aceita
 * vinculo com concurso_id NULL quando nao ha concurso pra comparar). Nao
 * trocar por exigirEmQualquerConcurso(): credencial e' recurso
 * institucional, nao de uma edicao.
 */
class SegurancaAdminController extends Controller
{
    /**
     * Minutos entre um aviso e o proximo, na mesma sessao. Abrir a tela,
     * gravar e voltar sao tres desenhos da mesma tela - sem a trava, cada
     * administrador receberia tres e-mails por visita, e o sinal viraria
     * ruido em uma semana.
     */
    const MINUTOS_ENTRE_AVISOS = 30;

    private $credenciais;
    private $notificacoes;
    private $perfis;
    private $usuarios;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->credenciais = new CredencialSistemaRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->perfis = new PerfilRepository();
        $this->usuarios = new UsuarioRepository();
    }

    public function index()
    {
        $this->renderizar('admin/seguranca/antessala', [], 'Segurança', ['tipo' => 'configuracaoSeguranca', 'id' => 0]);
    }

    public function abrir()
    {
        $_SESSION['seguranca_confirmada'] = true;
        $this->redirecionar('seguranca/credenciais');
    }

    public function credenciais()
    {
        if (empty($_SESSION['seguranca_confirmada'])) {
            $this->redirecionar('seguranca/index');
            return;
        }

        $this->registrarAcesso();

        $this->renderizar('admin/seguranca/credenciais', [
            'grupos' => $this->gruposParaTela(),
            'chaveMestraConfigurada' => Cifra::chaveMestraConfigurada(),
            'possuiCredencialNoBanco' => $this->credenciais->possuiAlgumaCredencial(),
            'resultadoTeste' => isset($_SESSION['seguranca_teste']) ? $_SESSION['seguranca_teste'] : null,
        ], 'Segurança: credenciais', ['tipo' => 'configuracaoSeguranca', 'id' => 0]);

        unset($_SESSION['seguranca_teste']);
    }

    public function salvar()
    {
        if (empty($_SESSION['seguranca_confirmada'])) {
            http_response_code(403);
            exit('Acesso negado: abra a tela pela aba Segurança.');
        }

        if (!Cifra::chaveMestraConfigurada()) {
            flashErro('Não há chave-mestra configurada em config/local.php: sem ela não é possível gravar credencial nenhuma.');
            $this->redirecionar('seguranca/credenciais');
            return;
        }

        $grupo = isset($_POST['grupo']) ? $_POST['grupo'] : '';
        $campos = $this->camposDoGrupo($grupo);

        if (empty($campos)) {
            http_response_code(400);
            exit('Grupo de credenciais desconhecido.');
        }

        $erro = $this->validarEntrada($grupo);

        if ($erro !== null) {
            flashErro($erro);
            $this->redirecionar('seguranca/credenciais');
            return;
        }

        $valores = [];

        foreach ($campos as $campo) {
            $valores[$campo] = trim(isset($_POST[$campo]) ? $_POST[$campo] : '');
        }

        $alterados = $this->credenciais->salvarGrupo($grupo, $valores);

        $this->avisarDemaisAdministradores(
            'Credenciais de segurança alteradas',
            'alterou credenciais do grupo "' . $grupo . '" (campos: ' . implode(', ', $alterados) . ').'
        );

        flashSucesso('Credenciais gravadas. Os demais administradores foram avisados.');
        $this->redirecionar('seguranca/credenciais');
    }

    /**
     * Confirma que a credencial do Google funciona sem exibi-la: pede um
     * token e lista as agendas visiveis. Mesma leitura de
     * database/testar_google_calendar.php, que exigia acesso ao servidor -
     * aqui vira um botao. Nao cria, nao altera e nao apaga nada.
     */
    public function testar()
    {
        if (empty($_SESSION['seguranca_confirmada'])) {
            http_response_code(403);
            exit('Acesso negado: abra a tela pela aba Segurança.');
        }

        $email = trim(isset($_POST['email_teste']) ? $_POST['email_teste'] : '');

        if ($email === '') {
            flashErro('Informe o e-mail institucional cuja agenda deve ser consultada.');
            $this->redirecionar('seguranca/credenciais');
            return;
        }

        $token = \App\Core\GoogleServiceAccountAuth::obterAccessToken($email, GoogleCalendarSyncService::ESCOPOS);

        if ($token === null) {
            $_SESSION['seguranca_teste'] = [
                'ok' => false,
                'mensagem' => 'Não foi possível obter autorização do Google. Verifique a conta de serviço, a chave privada e se o Client ID está autorizado no Admin Console do Workspace.',
            ];
            $this->redirecionar('seguranca/credenciais');
            return;
        }

        $calendarios = GoogleCalendarService::listarCalendarios($token);

        $_SESSION['seguranca_teste'] = $calendarios === null
            ? ['ok' => false, 'mensagem' => 'Autorização obtida, mas a listagem de agendas falhou. Confira se a API do Google Calendar está ativa no projeto.']
            : ['ok' => true, 'mensagem' => 'Conexão bem-sucedida. Agendas visíveis para ' . $email . ': ' . count($calendarios) . '.'];

        $this->redirecionar('seguranca/credenciais');
    }

    /**
     * Campos de cada grupo, na ordem em que aparecem na tela. Espelha o que
     * havia nos blocos de config/local.php - grupo novo entra aqui e no
     * $sigilosos de CredencialSistemaRepository, em nenhum outro lugar.
     */
    private function camposDoGrupo($grupo)
    {
        $mapa = [
            'google_service_account' => ['client_email', 'private_key', 'token_uri'],
            'google_oauth' => ['client_id', 'client_secret', 'redirect_uri'],
            'smtp' => ['host', 'port', 'user', 'pass', 'from_email', 'from_name'],
        ];

        return isset($mapa[$grupo]) ? $mapa[$grupo] : [];
    }

    private function gruposParaTela()
    {
        $rotulos = [
            'google_service_account' => 'Google: Conta de Serviço (Agenda e Meet)',
            'google_oauth' => 'Google: Login dos usuários',
            'smtp' => 'Envio de e-mail',
        ];

        $grupos = [];

        foreach ($rotulos as $grupo => $rotulo) {
            $grupos[] = [
                'chave' => $grupo,
                'rotulo' => $rotulo,
                'campos' => $this->camposDoGrupo($grupo),
                'sigilosos' => $this->camposSigilosos($grupo),
                'itens' => $this->credenciais->obterGrupoParaTela($grupo),
            ];
        }

        return $grupos;
    }

    private function camposSigilosos($grupo)
    {
        $mapa = [
            'google_service_account' => ['private_key'],
            'google_oauth' => ['client_secret'],
            'smtp' => ['pass'],
        ];

        return isset($mapa[$grupo]) ? $mapa[$grupo] : [];
    }

    /**
     * Mesma checagem de formato que o modulo GPI faz - pega colagem errada
     * na hora, em vez de deixar o erro aparecer so' na proxima tentativa de
     * criar um evento.
     */
    private function validarEntrada($grupo)
    {
        if ($grupo === 'google_service_account') {
            $email = trim(isset($_POST['client_email']) ? $_POST['client_email'] : '');

            if ($email !== '' && !preg_match('/^[^@\s]+@[^@\s]+\.iam\.gserviceaccount\.com$/', $email)) {
                return 'E-mail da Conta de Serviço em formato inesperado: deve ser algo como nome@projeto.iam.gserviceaccount.com.';
            }

            $chave = trim(isset($_POST['private_key']) ? $_POST['private_key'] : '');

            if ($chave !== '' && strpos($chave, '-----BEGIN') !== 0) {
                return 'A chave privada deve começar com "-----BEGIN PRIVATE KEY-----". Cole o conteúdo do campo "private_key" do arquivo .json baixado do Google.';
            }
        }

        return null;
    }

    private function registrarAcesso()
    {
        Auditoria::registrar(
            'acessar',
            'credenciais_sistema',
            null,
            null,
            null,
            'Abriu a tela de credenciais de segurança'
        );

        if (!$this->avisoLiberado()) {
            return;
        }

        $this->avisarDemaisAdministradores(
            'Acesso à aba Segurança',
            'abriu a tela de credenciais de segurança.'
        );
    }

    private function avisoLiberado()
    {
        $ultimo = isset($_SESSION['seguranca_ultimo_aviso']) ? (int) $_SESSION['seguranca_ultimo_aviso'] : 0;

        if ($ultimo > 0 && (time() - $ultimo) < (self::MINUTOS_ENTRE_AVISOS * 60)) {
            return false;
        }

        $_SESSION['seguranca_ultimo_aviso'] = time();

        return true;
    }

    /**
     * Aviso no painel e por e-mail para os DEMAIS administradores globais.
     * NUNCA leva valor de credencial - so' quem, o que e quando.
     *
     * O laco percorre apenas administradores globais, que sao poucos. O
     * envio e' sincrono no proprio pedido: nunca ampliar isso para todos os
     * administradores de todos os concursos, que foi exatamente o que
     * causou o defeito de ~1 minuto por gravacao corrigido na Parte A.
     */
    private function avisarDemaisAdministradores($titulo, $acao)
    {
        $autor = $this->usuarios->buscarPorId(Auth::usuarioId());
        $nomeAutor = $autor !== null ? $autor['nome'] : 'Um administrador';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'origem desconhecida';
        $quando = date('d/m/Y H:i');

        $mensagem = $nomeAutor . ' ' . $acao . ' Em ' . $quando . ', a partir de ' . $ip . '.';

        foreach ($this->perfis->listarUsuariosGlobaisPorPerfil('administrador') as $administrador) {
            if ((int) $administrador['id'] === (int) Auth::usuarioId()) {
                continue;
            }

            $this->notificacoes->criar(
                (int) $administrador['id'],
                'seguranca_acesso',
                $titulo,
                $mensagem,
                ['url' => url('seguranca/index')]
            );

            if (!empty($administrador['email'])) {
                Mailer::enviar(
                    $administrador['email'],
                    '[Prêmio de Inovação] ' . $titulo,
                    '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p>Este aviso é automático e não contém nenhum valor de credencial.</p>'
                );
            }
        }
    }
}
