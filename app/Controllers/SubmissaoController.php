<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Repositories\SubmissaoRepository;
use App\Services\CampoDinamicoService;
use App\Services\SubmissaoService;

class SubmissaoController extends Controller
{
    public function preencher($etapaId)
    {
        $preparo = (new SubmissaoService())->preparar($etapaId);

        if (!$preparo['sucesso']) {
            http_response_code(404);
            $this->renderizar('publico/formulario', [
                'erroGeral' => $preparo['mensagem'],
                'preparo' => null,
                'erros' => [],
                'tipos' => CampoDinamicoService::TIPOS,
            ], 'Formulario indisponivel');
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
        $resultado = (new SubmissaoService())->processar($etapaId, $_POST, $_FILES);

        if ($resultado['sucesso']) {
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
            exit('Submissao nao encontrada.');
        }

        $this->renderizar('publico/sucesso', ['submissao' => $submissao], 'Submissao enviada');
    }
}
