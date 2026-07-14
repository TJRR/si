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
        $filtroPerfil = isset($_GET['perfil']) && $_GET['perfil'] !== '' ? $_GET['perfil'] : null;
        $busca = isset($_GET['busca']) && trim($_GET['busca']) !== '' ? trim($_GET['busca']) : null;
        $ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'nome';
        $direcao = isset($_GET['direcao']) && $_GET['direcao'] === 'desc' ? 'desc' : 'asc';

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

        if ($filtroPerfil !== null) {
            $lista = array_values(array_filter($lista, function ($usuario) use ($filtroPerfil) {
                foreach ($usuario['perfis'] as $vinculo) {
                    if ($vinculo['perfil'] === $filtroPerfil) {
                        return true;
                    }
                }

                return false;
            }));
        }

        if ($busca !== null) {
            $lista = array_values(array_filter($lista, function ($usuario) use ($busca) {
                return $this->usuarioBateComBusca($usuario, $busca);
            }));
        }

        $lista = $this->ordenarUsuarios($lista, $ordenar, $direcao);

        $this->renderizar('admin/usuarios', [
            'usuarios' => $lista,
            'perfis' => $this->perfis->listar(),
            'concursos' => $this->concursos->listar(),
            'categoriasPorConcurso' => $categoriasPorConcurso,
            'filtroConcursoId' => $filtroConcursoId,
            'filtroPerfil' => $filtroPerfil,
            'busca' => $busca,
            'ordenar' => $ordenar,
            'direcao' => $direcao,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Usuários');

        unset($_SESSION['flash']);
    }

    /**
     * Busca livre da tela Usuarios - casa nome, e-mail, status, nomes de
     * perfil e tipo de acesso (Senha/Google/Nenhum ainda), o mesmo texto
     * que ja aparece nas colunas da tabela.
     */
    private function usuarioBateComBusca(array $usuario, $busca)
    {
        $tiposAcesso = [];
        if ($usuario['senha_hash'] !== null) {
            $tiposAcesso[] = 'Senha';
        }
        if ($usuario['google_id'] !== null) {
            $tiposAcesso[] = 'Google';
        }
        $textoAcesso = !empty($tiposAcesso) ? implode(' ', $tiposAcesso) : 'Nenhum ainda';

        $nomesPerfis = implode(' ', array_map(function ($vinculo) {
            return $vinculo['perfil_nome'];
        }, $usuario['perfis']));

        $textoBusca = mb_strtolower(
            $usuario['nome'] . ' ' . $usuario['email'] . ' ' . $usuario['status'] . ' ' . $nomesPerfis . ' ' . $textoAcesso
        );

        return mb_strpos($textoBusca, mb_strtolower($busca)) !== false;
    }

    private function ordenarUsuarios(array $lista, $ordenar, $direcao)
    {
        $chaveDe = function ($usuario) use ($ordenar) {
            switch ($ordenar) {
                case 'email':
                    return mb_strtolower($usuario['email']);
                case 'status':
                    return mb_strtolower($usuario['status']);
                case 'perfis':
                    return mb_strtolower(implode(', ', array_map(function ($vinculo) {
                        return $vinculo['perfil_nome'];
                    }, $usuario['perfis'])));
                case 'acesso':
                    return ($usuario['senha_hash'] !== null ? '1' : '0') . ($usuario['google_id'] !== null ? '1' : '0');
                case 'nome':
                default:
                    return mb_strtolower($usuario['nome']);
            }
        };

        usort($lista, function ($a, $b) use ($chaveDe) {
            return strcmp($chaveDe($a), $chaveDe($b));
        });

        if ($direcao === 'desc') {
            $lista = array_reverse($lista);
        }

        return $lista;
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

    public function editar($id)
    {
        $usuario = $this->usuarios->buscarPorId($id);

        if ($usuario === null) {
            http_response_code(404);
            exit('Usuário não encontrado.');
        }

        $perfisDoUsuario = $this->usuarios->perfisDoUsuario($id);
        $vinculoAtual = !empty($perfisDoUsuario) ? $perfisDoUsuario[0] : null;

        if ($vinculoAtual !== null && $vinculoAtual['perfil'] === 'avaliador' && $vinculoAtual['concurso_id'] !== null) {
            $vinculoAtual['categoria_atual'] = $this->avaliadorCategorias->categoriaDoUsuario($id, $vinculoAtual['concurso_id']);
        }

        $categoriasPorConcurso = [];
        foreach ($this->concursos->listar() as $concurso) {
            $categoriasPorConcurso[(int) $concurso['id']] = $this->categoriasAvaliador->listarPorConcurso($concurso['id']);
        }

        $this->renderizar('admin/usuarios_editar', [
            'usuario' => $usuario,
            'vinculoAtual' => $vinculoAtual,
            'perfis' => $this->perfis->listar(),
            'concursos' => $this->concursos->listar(),
            'categoriasPorConcurso' => $categoriasPorConcurso,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Editar usuário — ' . $usuario['nome']);

        unset($_SESSION['flash']);
    }

    /**
     * Um unico "Salvar" na tela de edicao: nome + perfil (a regra do projeto
     * e que um usuario tem no maximo 1 perfil, entao trocar o perfil aqui
     * substitui o vinculo anterior por completo, em vez de somar mais um).
     */
    public function salvarEdicao()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
        $perfilChave = isset($_POST['perfil']) ? $_POST['perfil'] : '';
        $concursoId = (isset($_POST['concurso_id']) && $_POST['concurso_id'] !== '') ? (int) $_POST['concurso_id'] : null;
        $categoriaAvaliadorId = (isset($_POST['categoria_avaliador_id']) && $_POST['categoria_avaliador_id'] !== '')
            ? (int) $_POST['categoria_avaliador_id']
            : null;

        if ($nome !== '') {
            $this->usuarios->atualizarNome($id, $nome);
        }

        $perfil = $this->perfis->buscarPorChave($perfilChave);

        if ($perfil !== null) {
            $this->perfis->substituirPerfil($id, $perfil['id'], $concursoId);

            if ($perfil['chave'] === 'avaliador' && $categoriaAvaliadorId !== null && $concursoId !== null
                && $this->categoriaPertenceAoConcurso($categoriaAvaliadorId, $concursoId)) {
                $this->avaliadorCategorias->atribuir($id, $concursoId, $categoriaAvaliadorId);
            }
        }

        $_SESSION['flash'] = 'Usuário atualizado.';
        $this->redirecionar('usuarios/editar/' . $id);
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
