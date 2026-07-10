<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Repositories\EquipeRepository;
use App\Services\InscricaoService;

class InscricaoController extends Controller
{
    public function formulario($etapaId)
    {
        $preparo = (new InscricaoService())->preparar($etapaId);

        if (!$preparo['sucesso']) {
            http_response_code(404);
            $this->renderizar('publico/inscricao', [
                'erroGeral' => $preparo['mensagem'],
                'preparo' => null,
                'erros' => [],
            ], 'Inscrição indisponível');
            return;
        }

        $this->renderizar('publico/inscricao', [
            'erroGeral' => null,
            'preparo' => $preparo,
            'erros' => [],
        ], $preparo['formulario']['nome']);
    }

    public function enviar($etapaId)
    {
        $resultado = (new InscricaoService())->processar($etapaId, $_POST);

        if ($resultado['sucesso']) {
            $this->redirecionar('inscricao/sucesso/' . $resultado['equipe_id']);
            return;
        }

        $preparo = (new InscricaoService())->preparar($etapaId);

        $this->renderizar('publico/inscricao', [
            'erroGeral' => $resultado['mensagem'],
            'preparo' => $preparo['sucesso'] ? $preparo : null,
            'erros' => isset($resultado['erros']) ? $resultado['erros'] : [],
        ], 'Corrija os erros');
    }

    public function sucesso($equipeId)
    {
        $equipe = (new EquipeRepository())->buscarPorId($equipeId);

        if ($equipe === null) {
            http_response_code(404);
            exit('Equipe não encontrada.');
        }

        $this->renderizar('publico/inscricao_sucesso', ['equipe' => $equipe], 'Inscrição enviada');
    }
}
