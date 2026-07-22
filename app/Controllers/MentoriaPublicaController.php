<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Repositories\ConcursoRepository;
use App\Repositories\MentoriaRepository;

/**
 * Fase 19 (#106): pagina publica (sem login) so' de transparencia - mostra
 * todos os horarios de mentoria do concurso ativo (vagos e reservados,
 * so' com o nome da equipe que reservou, nunca CPF/e-mail/telefone). O
 * agendamento em si so' acontece pelo painel do participante
 * (MentoriaController) - esta tela nao tem nenhuma acao, so' listagem.
 */
class MentoriaPublicaController extends Controller
{
    public function index()
    {
        $concursoAtivo = (new ConcursoRepository())->buscarAtivo();

        if ($concursoAtivo === null) {
            http_response_code(404);
            exit('Nenhuma edição ativa no momento.');
        }

        $this->renderizar('publico/mentorias', [
            'concurso' => $concursoAtivo,
            'horarios' => (new MentoriaRepository())->listarPorConcurso($concursoAtivo['id']),
        ], 'Mentorias — ' . $concursoAtivo['nome']);
    }
}
