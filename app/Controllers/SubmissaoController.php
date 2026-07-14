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
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\CampoDinamicoService;
use App\Services\SubmissaoService;

class SubmissaoController extends Controller
{
    public function preencher($etapaId)
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);

        $equipeId = $this->equipeAutorizadaOuAbortar($etapaId);

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

        $equipeId = $this->equipeAutorizadaOuAbortar($etapaId);

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
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);

        $submissao = (new SubmissaoRepository())->buscarPorId($submissaoId);

        if ($submissao === null) {
            http_response_code(404);
            exit('Submissão não encontrada.');
        }

        $this->renderizar('publico/sucesso', ['submissao' => $submissao], 'Submissão enviada');
    }

    /**
     * Wrapper de equipeHomologadaDoParticipante() para preencher()/enviar():
     * trata tanto o retorno null (mensagem generica ja existente) quanto a
     * RuntimeException especifica da trava de classificacao (item 4 da Fase 12).
     */
    private function equipeAutorizadaOuAbortar($etapaId)
    {
        try {
            $equipeId = $this->equipeHomologadaDoParticipante($etapaId);
        } catch (\RuntimeException $e) {
            http_response_code(403);
            exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }

        if ($equipeId === null) {
            http_response_code(403);
            exit('Acesso negado: sua inscrição ainda não foi homologada, ou não pertence à trilha desta etapa.');
        }

        return $equipeId;
    }

    /**
     * So quem foi homologado e tem acesso liberado (perfil "participante")
     * pode submeter, e so na trilha da propria equipe - retorna o equipe_id
     * ja validado, ou null se nao autorizado (homologacao/trilha).
     *
     * Para etapas com ordem > 1 cuja etapa anterior tenha
     * mecanismo_avaliacao = 'avaliadores' (ou seja, e' uma etapa avaliada por
     * avaliadores, nao um cadastro homologado pelo Admin nem uma etapa sem
     * avaliacao), exige que a equipe tenha sido classificada no resultado
     * publicado da etapa anterior - lanca RuntimeException com mensagem
     * especifica quando bloqueia por esse motivo.
     */
    private function equipeHomologadaDoParticipante($etapaId)
    {
        $etapas = new EtapaRepository();
        $etapa = $etapas->buscarPorId($etapaId);

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

        if ((int) $etapa['ordem'] > 1) {
            $this->exigirClassificacaoNaEtapaAnterior($etapa, (int) $equipe['id'], $etapas);
        }

        return (int) $equipe['id'];
    }

    private function exigirClassificacaoNaEtapaAnterior(array $etapa, $equipeId, EtapaRepository $etapas)
    {
        $etapaAnterior = $etapas->buscarAnteriorNaTrilha($etapa['trilha_id'], (int) $etapa['ordem']);

        if ($etapaAnterior === null) {
            return;
        }

        if ($etapaAnterior['mecanismo_avaliacao'] !== 'avaliadores') {
            return;
        }

        $resultados = new ResultadoEtapaRepository();

        if (!$resultados->jaPublicado($etapaAnterior['id'])) {
            throw new \RuntimeException(
                'O resultado da etapa anterior ("' . $etapaAnterior['nome'] . '") ainda não foi publicado.'
            );
        }

        $submissaoAnterior = (new SubmissaoRepository())->buscarPorEquipeEEtapa($equipeId, $etapaAnterior['id']);
        $resultado = $submissaoAnterior !== null
            ? $resultados->buscarPorSubmissaoEEtapa($submissaoAnterior['id'], $etapaAnterior['id'])
            : null;

        if ($resultado === null || !$resultado['classificado']) {
            throw new \RuntimeException(
                'Sua equipe não foi classificada na etapa anterior ("' . $etapaAnterior['nome'] . '").'
            );
        }
    }
}
