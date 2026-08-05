<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Repositories\ConcursoRepository;
use App\Repositories\OficinaRepository;

/**
 * Fase 24: pagina publica (sem login) so' de transparencia - mostra os
 * horarios de oficina do concurso ativo e as equipes inscritas, nunca o
 * link do Google Meet (evita que qualquer visitante entre na sala sem
 * ter se inscrito) - mesmo criterio de MentoriaPublicaController.
 */
class OficinaPublicaController extends Controller
{
    public function index()
    {
        $concursoAtivo = (new ConcursoRepository())->buscarAtivo();

        if ($concursoAtivo === null) {
            http_response_code(404);
            exit('Nenhuma edição ativa no momento.');
        }

        $oficinas = new OficinaRepository();
        $horarios = $oficinas->listarPorConcurso($concursoAtivo['id']);

        foreach ($horarios as &$horario) {
            $horario['inscritos'] = $oficinas->listarInscritos($horario['id']);
        }
        unset($horario);

        $this->renderizar('publico/oficinas', [
            'concurso' => $concursoAtivo,
            'horarios' => $horarios,
        ], 'Oficinas — ' . $concursoAtivo['nome']);
    }
}
