<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\CampoDinamicoService;
use App\Services\SubmissaoService;

class SubmissaoController extends Controller
{
    public function preencher($etapaId)
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);

        $equipeId = $this->equipeHomologadaDoParticipante($etapaId);

        if ($equipeId === null) {
            http_response_code(403);
            exit('Acesso negado: sua inscrição ainda não foi homologada, ou não pertence à trilha desta etapa.');
        }

        $preparo = (new SubmissaoService())->preparar($etapaId);

        if (!$preparo['sucesso']) {
            http_response_code(404);
            $this->renderizar('publico/formulario', [
                'erroGeral' => $preparo['mensagem'],
                'preparo' => null,
                'erros' => [],
                'tipos' => CampoDinamicoService::TIPOS,
            ], 'Formulário indisponível');
            return;
        }

        $this->renderizar('publico/formulario', [
            'erroGeral' => null,
            'preparo' => $preparo,
            'erros' => [],
            'tipos' => CampoDinamicoService::TIPOS,
        ], $preparo['formulario']['nome']);
    }

    public function enviar($etapaId)
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);

        $equipeId = $this->equipeHomologadaDoParticipante($etapaId);

        if ($equipeId === null) {
            http_response_code(403);
            exit('Acesso negado: sua inscrição ainda não foi homologada, ou não pertence à trilha desta etapa.');
        }

        $resultado = (new SubmissaoService())->processar($etapaId, $_POST, $_FILES);

        if ($resultado['sucesso']) {
            (new SubmissaoRepository())->vincularEquipe($resultado['submissao_id'], $equipeId);
            $this->redirecionar('submissao/sucesso/' . $resultado['submissao_id']);
            return;
        }

        $preparo = (new SubmissaoService())->preparar($etapaId);

        $this->renderizar('publico/formulario', [
            'erroGeral' => $resultado['mensagem'],
            'preparo' => $preparo['sucesso'] ? $preparo : null,
            'erros' => isset($resultado['erros']) ? $resultado['erros'] : [],
            'tipos' => CampoDinamicoService::TIPOS,
        ], 'Corrija os erros');
    }

    public function sucesso($submissaoId)
    {
        $submissao = (new SubmissaoRepository())->buscarPorId($submissaoId);

        if ($submissao === null) {
            http_response_code(404);
            exit('Submissão não encontrada.');
        }

        $this->renderizar('publico/sucesso', ['submissao' => $submissao], 'Submissão enviada');
    }

    /**
     * So quem foi homologado e tem acesso liberado (perfil "participante")
     * pode submeter, e so na trilha da propria equipe - retorna o equipe_id
     * ja validado, ou null se nao autorizado.
     */
    private function equipeHomologadaDoParticipante($etapaId)
    {
        $etapa = (new EtapaRepository())->buscarPorId($etapaId);

        if ($etapa === null) {
            return null;
        }

        $participantes = (new UsuarioParticipanteRepository())->participantesDoUsuario(Auth::usuarioId());

        if (empty($participantes)) {
            return null;
        }

        $equipes = new EquipeRepository();
        $equipe = $equipes->buscarPorParticipante($participantes[0]['id']);

        if ($equipe === null || (int) $equipe['trilha_id'] !== (int) $etapa['trilha_id']) {
            return null;
        }

        $vinculo = $equipes->buscarVinculo($equipe['id'], $participantes[0]['id']);

        if ($vinculo === null || $vinculo['status_homologacao'] !== 'homologado') {
            return null;
        }

        return (int) $equipe['id'];
    }
}
