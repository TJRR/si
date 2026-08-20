<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\UsuarioRepository;
use App\Services\ImagemService;

/**
 * Tela "Meu perfil" (nome + foto), liberada para qualquer perfil autenticado
 * — ao contrario dos demais controllers admin, nao usa RoleMiddleware::exigir
 * porque nao exige nenhum perfil especifico, so estar logado.
 */
class MeuPerfilController extends Controller
{
    private $usuarios;
    private $imagens;

    public function __construct()
    {
        if (!Auth::autenticado()) {
            header('Location: ' . url('auth/login'));
            exit;
        }

        $this->usuarios = new UsuarioRepository();
        $this->imagens = new ImagemService();
    }

    public function index()
    {
        $usuario = $this->usuarios->buscarPorId(Auth::usuarioId());
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');

            if ($nome === '') {
                $erro = 'Informe o nome.';
            } else {
                $this->usuarios->atualizarNome($usuario['id'], $nome);
                $_SESSION['usuario_nome'] = $nome;

                if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                        $erro = 'Falha ao enviar a foto.';
                    } else {
                        try {
                            $novoCaminho = $this->imagens->salvar($_FILES['foto'], 'usuarios', 400, 400);

                            if (!empty($usuario['foto_path'])) {
                                $this->imagens->remover($usuario['foto_path']);
                            }

                            $this->usuarios->atualizarFoto($usuario['id'], $novoCaminho);
                        } catch (\RuntimeException $e) {
                            $erro = $e->getMessage();
                        }
                    }
                }

                if ($erro === null) {
                    $_SESSION['flash'] = 'Perfil atualizado.';
                    $this->redirecionar('meuPerfil/index');
                    return;
                }
            }

            $usuario = $this->usuarios->buscarPorId($usuario['id']);
        }

        $this->renderizar('meuPerfil/index', [
            'usuario' => $usuario,
            'erro' => $erro,
            'destinoPainel' => Auth::destinoPainel(),
        ], 'Meu perfil', ['tipo' => 'perfilDados', 'id' => null]);
    }

    /**
     * Fase 33: troca de senha pelo proprio usuario. So' existe para conta que
     * JA' tem senha (coluna "Acesso: Senha" da tela de Usuarios) - conta que
     * entra so' pelo Google nao tem senha atual pra conferir, e o caminho dela
     * e' o convite/"esqueci minha senha", que define a primeira senha por
     * token de uso unico.
     *
     * O modo "visualizar como outro usuario" nao precisa de checagem aqui: o
     * Router ja' bloqueia toda requisicao nao-GET durante a visualizacao. A
     * view esconde o bloco mesmo assim, pra nao exibir um formulario de senha
     * de outra pessoa a um administrador.
     *
     * Erros saem por flash (nunca re-renderizando o formulario): campo de
     * senha nao se repopula, entao nao ha' nada a preservar.
     */
    public function alterarSenha()
    {
        $usuario = $this->usuarios->buscarPorId(Auth::usuarioId());

        if ($usuario === null || $usuario['senha_hash'] === null) {
            flashErro('Esta conta não usa senha para entrar.');
            $this->redirecionar('meuPerfil/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderizar('meuPerfil/senha', [
                'destinoPainel' => Auth::destinoPainel(),
            ], 'Alterar senha', ['tipo' => 'perfilSenha', 'id' => null]);
            return;
        }

        $atual = isset($_POST['senha_atual']) ? $_POST['senha_atual'] : '';
        $nova = isset($_POST['senha_nova']) ? $_POST['senha_nova'] : '';
        $confirmacao = isset($_POST['confirmacao']) ? $_POST['confirmacao'] : '';

        if (!password_verify($atual, $usuario['senha_hash'])) {
            flashErro('Senha atual incorreta.');
        } elseif (strlen($nova) < 8) {
            // Mesmo minimo de AuthController::definirSenha() - a regra da senha
            // e' uma so' no sistema inteiro.
            flashErro('A nova senha deve ter ao menos 8 caracteres.');
        } elseif ($nova !== $confirmacao) {
            flashErro('As senhas não conferem.');
        } elseif ($nova === $atual) {
            flashErro('A nova senha deve ser diferente da atual.');
        } else {
            $this->usuarios->definirSenha($usuario['id'], password_hash($nova, PASSWORD_DEFAULT));

            // Link de "definir senha" ainda pendente (convite ou recuperacao)
            // deixa de valer: quem acabou de provar a senha atual nao pode
            // ficar com um link antigo de pe' servindo de segunda porta.
            (new TokenSenhaRepository())->invalidarPendentes($usuario['id'], 'definir');

            // Id de sessao novo depois de trocar credencial, mesmo cuidado que
            // Auth::login() ja' toma.
            session_regenerate_id(true);

            flashSucesso('Senha alterada.');
        }

        $this->redirecionar('meuPerfil/index');
    }

    /**
     * Fase 17 (Melhoria 2): inicia a visualizacao somente leitura como outro
     * usuario - restrito a nao-administradores (decisao do usuario) e sem
     * aninhar (nao pode visualizar-como estando ja em visualizacao).
     */
    public function visualizarComo()
    {
        if (!Auth::possuiPerfil('administrador') || Auth::estaVisualizandoComoOutro()) {
            http_response_code(403);
            exit('Acesso negado.');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderizar('meuPerfil/visualizar', [
                'destinoPainel' => Auth::destinoPainel(),
                'usuariosParaVisualizar' => $this->usuarios->listarAtivosNaoAdministradores(),
            ], 'Visualizar como outro usuário', ['tipo' => 'perfilVisualizar', 'id' => null]);
            return;
        }

        $usuarioAlvoId = (int) (isset($_POST['usuario_id']) ? $_POST['usuario_id'] : 0);
        $alvo = $this->usuarios->buscarPorId($usuarioAlvoId);

        if ($alvo === null || $alvo['status'] !== 'aprovado' || !$alvo['ativo']) {
            flashErro('Usuário não encontrado ou inativo.');
            $this->redirecionar('meuPerfil/index');
            return;
        }

        $perfisAlvo = $this->usuarios->perfisDoUsuario($alvo['id']);

        foreach ($perfisAlvo as $vinculo) {
            if ($vinculo['perfil'] === 'administrador') {
                flashErro('Não é possível visualizar como outro Administrador.');
                $this->redirecionar('meuPerfil/index');
                return;
            }
        }

        Auditoria::registrar('iniciar_visualizacao_como', 'usuarios', $alvo['id'], null, ['admin_id' => Auth::usuarioId()]);
        Auth::iniciarVisualizacaoComo($alvo['id'], $alvo['nome'], $perfisAlvo);

        $this->redirecionar(Auth::destinoPainel());
    }

    /**
     * Unica rota liberada durante a visualizacao (ver Router::despachar()) -
     * restaura a identidade real do Admin.
     */
    public function pararVisualizacao()
    {
        $original = Auth::usuarioOriginal();

        if ($original !== null) {
            Auditoria::registrar('parar_visualizacao_como', 'usuarios', Auth::usuarioId(), null, ['admin_id' => $original['usuario_id']], null, $original['usuario_id']);
        }

        Auth::pararVisualizacaoComo();
        $this->redirecionar('home/administrativo');
    }
}
