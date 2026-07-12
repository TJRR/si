<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\AvaliadorCategoriaRepository;
use App\Repositories\CategoriaAvaliadorRepository;
use App\Repositories\ConcursoRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AcessoParticipanteService;

class UsuarioAdminController extends Controller
{
    private $usuarios;
    private $perfis;
    private $concursos;
    private $categoriasAvaliador;
    private $avaliadorCategorias;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->usuarios = new UsuarioRepository();
        $this->perfis = new PerfilRepository();
        $this->concursos = new ConcursoRepository();
        $this->categoriasAvaliador = new CategoriaAvaliadorRepository();
        $this->avaliadorCategorias = new AvaliadorCategoriaRepository();
    }

    public function index()
    {
        $filtroConcursoId = (isset($_GET['concurso_id']) && $_GET['concurso_id'] !== '') ? (int) $_GET['concurso_id'] : null;
        $lista = $this->usuarios->listarTodos($filtroConcursoId);

        $categoriasPorConcurso = [];
        foreach ($this->concursos->listar() as $concurso) {
            $categoriasPorConcurso[(int) $concurso['id']] = $this->categoriasAvaliador->listarPorConcurso($concurso['id']);
        }

        foreach ($lista as &$usuario) {
            $usuario['perfis'] = $this->usuarios->perfisDoUsuario($usuario['id']);

            foreach ($usuario['perfis'] as &$vinculo) {
                if ($vinculo['perfil'] === 'avaliador' && $vinculo['concurso_id'] !== null) {
                    $vinculo['categoria_atual'] = $this->avaliadorCategorias->categoriaDoUsuario($usuario['id'], $vinculo['concurso_id']);
                }
            }
            unset($vinculo);
        }
        unset($usuario);

        $this->renderizar('admin/usuarios', [
            'usuarios' => $lista,
            'perfis' => $this->perfis->listar(),
            'concursos' => $this->concursos->listar(),
            'categoriasPorConcurso' => $categoriasPorConcurso,
            'filtroConcursoId' => $filtroConcursoId,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Usuários');

        unset($_SESSION['flash']);
    }

    public function aprovar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $perfilChave = isset($_POST['perfil']) ? $_POST['perfil'] : '';
        $concursoId = (isset($_POST['concurso_id']) && $_POST['concurso_id'] !== '') ? (int) $_POST['concurso_id'] : null;
        $categoriaAvaliadorId = (isset($_POST['categoria_avaliador_id']) && $_POST['categoria_avaliador_id'] !== '')
            ? (int) $_POST['categoria_avaliador_id']
            : null;

        $perfil = $this->perfis->buscarPorChave($perfilChave);

        if ($perfil === null) {
            $_SESSION['flash'] = 'Selecione um perfil válido antes de aprovar.';
            $this->redirecionar('usuarios/index');
            return;
        }

        $this->usuarios->atualizarStatus($id, 'aprovado');
        $this->perfis->atribuir($id, $perfil['id'], $concursoId);

        if ($perfil['chave'] === 'avaliador' && $categoriaAvaliadorId !== null && $concursoId !== null
            && $this->categoriaPertenceAoConcurso($categoriaAvaliadorId, $concursoId)) {
            $this->avaliadorCategorias->atribuir($id, $concursoId, $categoriaAvaliadorId);
        }

        $this->redirecionar('usuarios/index');
    }

    public function definirCategoria()
    {
        $usuarioId = (int) (isset($_POST['usuario_id']) ? $_POST['usuario_id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $categoriaAvaliadorId = (int) (isset($_POST['categoria_avaliador_id']) ? $_POST['categoria_avaliador_id'] : 0);

        if ($usuarioId > 0 && $concursoId > 0 && $categoriaAvaliadorId > 0
            && $this->categoriaPertenceAoConcurso($categoriaAvaliadorId, $concursoId)) {
            $this->avaliadorCategorias->atribuir($usuarioId, $concursoId, $categoriaAvaliadorId);
            $_SESSION['flash'] = 'Categoria de avaliador atualizada.';
        }

        $this->redirecionar('usuarios/index');
    }

    private function categoriaPertenceAoConcurso($categoriaAvaliadorId, $concursoId)
    {
        $categoria = $this->categoriasAvaliador->buscarPorId($categoriaAvaliadorId);

        return $categoria !== null && (int) $categoria['concurso_id'] === (int) $concursoId;
    }

    public function rejeitar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->usuarios->atualizarStatus($id, 'rejeitado');
        $this->redirecionar('usuarios/index');
    }

    public function suspender()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->usuarios->atualizarAtivo($id, false);
        $this->redirecionar('usuarios/index');
    }

    public function reativar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->usuarios->atualizarAtivo($id, true);
        $this->redirecionar('usuarios/index');
    }

    public function convidar()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $perfilChave = isset($_POST['perfil']) ? $_POST['perfil'] : '';
            $concursoId = (isset($_POST['concurso_id']) && $_POST['concurso_id'] !== '') ? (int) $_POST['concurso_id'] : null;
            $categoriaAvaliadorId = (isset($_POST['categoria_avaliador_id']) && $_POST['categoria_avaliador_id'] !== '')
                ? (int) $_POST['categoria_avaliador_id']
                : null;

            $perfil = $this->perfis->buscarPorChave($perfilChave);

            if ($nome === '' || $email === '') {
                $erro = 'Informe nome e e-mail.';
            } elseif ($perfil === null) {
                $erro = 'Selecione um perfil válido.';
            } elseif ($categoriaAvaliadorId !== null && !$this->categoriaPertenceAoConcurso($categoriaAvaliadorId, $concursoId)) {
                $erro = 'A categoria escolhida não pertence ao concurso selecionado.';
            } else {
                $resultado = (new AcessoParticipanteService())->convidarUsuario($nome, $email, $perfil['id'], $concursoId);

                if ($perfil['chave'] === 'avaliador' && $categoriaAvaliadorId !== null && $concursoId !== null) {
                    $this->avaliadorCategorias->atribuir($resultado['usuario_id'], $concursoId, $categoriaAvaliadorId);
                }

                $_SESSION['flash'] = 'Usuário convidado com sucesso.';
                $this->redirecionar('usuarios/index');
                return;
            }
        }

        $categoriasPorConcurso = [];
        foreach ($this->concursos->listar() as $concurso) {
            $categoriasPorConcurso[(int) $concurso['id']] = $this->categoriasAvaliador->listarPorConcurso($concurso['id']);
        }

        $this->renderizar('admin/usuarios_convidar', [
            'erro' => $erro,
            'perfis' => $this->perfis->listar(),
            'concursos' => $this->concursos->listar(),
            'categoriasPorConcurso' => $categoriasPorConcurso,
        ], 'Convidar usuário');
    }
}
