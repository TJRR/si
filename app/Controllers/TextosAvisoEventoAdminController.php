<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoModeloAvisoRepository;
use App\Services\EventoModeloAvisoService;

/**
 * Textos dos avisos por correio eletronico exclusivos do Evento. Os avisos
 * do Concurso nao aparecem aqui e continuam com o texto do codigo.
 */
class TextosAvisoEventoAdminController extends Controller
{
    private $modelos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->modelos = new EventoModeloAvisoRepository();
    }

    public function index()
    {
        $this->renderizar('admin/textos_aviso_evento/index', [
            'avisos' => EventoModeloAvisoService::AVISOS,
            'gravados' => $this->modelos->listarPorChave(),
        ], 'Textos dos avisos do Evento', ['tipo' => 'configuracaoTextosAvisos', 'id' => null]);
    }

    public function editar($chave = null)
    {
        $chave = (string) $chave;

        if (!EventoModeloAvisoService::existe($chave)) {
            flashErro('Aviso não encontrado.');
            $this->redirecionar('textosAvisoEvento/index');
            return;
        }

        $aviso = EventoModeloAvisoService::AVISOS[$chave];
        $gravado = $this->modelos->buscarPorChave($chave);
        $problemas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $assunto = trim(isset($_POST['assunto']) ? (string) $_POST['assunto'] : '');
            $corpo = trim(sanitizarHtmlRico(isset($_POST['corpo_html']) ? (string) $_POST['corpo_html'] : ''));
            $problemas = (new EventoModeloAvisoService())->problemasDoModelo($chave, $assunto, $corpo);

            if ($problemas === []) {
                $this->modelos->salvar($chave, $assunto, $corpo, Auth::usuarioId());
                flashSucesso('Texto do aviso "' . $aviso['nome'] . '" salvo. Os próximos envios já usam este texto.');
                $this->redirecionar('textosAvisoEvento/index');
                return;
            }
        } else {
            $assunto = $gravado !== null ? $gravado['assunto'] : $aviso['assunto'];
            $corpo = $gravado !== null ? $gravado['corpo_html'] : $aviso['corpo'];
        }

        $this->renderizar('admin/textos_aviso_evento/form', [
            'chave' => $chave,
            'aviso' => $aviso,
            'gravado' => $gravado,
            'assunto' => $assunto,
            'corpo' => $corpo,
            'problemas' => $problemas,
            'assuntoMaximo' => EventoModeloAvisoService::ASSUNTO_MAXIMO,
        ], 'Texto do aviso: ' . $aviso['nome'], ['tipo' => 'configuracaoTextosAvisos', 'id' => null]);
    }

    public function restaurar($chave = null)
    {
        $chave = (string) $chave;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !EventoModeloAvisoService::existe($chave)) {
            $this->redirecionar('textosAvisoEvento/index');
            return;
        }

        $this->modelos->remover($chave);
        flashSucesso('O aviso "' . EventoModeloAvisoService::AVISOS[$chave]['nome'] . '" voltou ao texto padrão.');
        $this->redirecionar('textosAvisoEvento/index');
    }
}
