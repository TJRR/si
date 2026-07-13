<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
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
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Meu perfil');

        unset($_SESSION['flash']);
    }
}
