<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EtapaRepository;
use App\Repositories\ModeloDocumentoRepository;

/**
 * Fase 50: mesma pergunta ("quais modelos de documento a equipe pode ver
 * agora") era respondida so' dentro de RequerimentoController::index() (pra
 * listar os modelos clicaveis) - extraida pra tambem decidir, no painel do
 * participante, se o botao "Requerimentos" deve aparecer. Mesmo padrao ja
 * adotado por EventoEtapaService pra Mentoria/Oficina: uma unica fonte de
 * verdade, sem duplicar a consulta.
 */
class RequerimentoElegibilidadeService
{
    private $modelos;
    private $etapas;
    private $acessoEtapa;

    public function __construct()
    {
        $this->modelos = new ModeloDocumentoRepository();
        $this->etapas = new EtapaRepository();
        $this->acessoEtapa = new AcessoEtapaService();
    }

    public function listarDisponiveis(array $equipe, array $trilha)
    {
        $disponiveis = [];

        foreach ($this->modelos->listarAtivosPorTrilha($trilha['id']) as $modelo) {
            $etapaDoModelo = $this->etapas->buscarPorId($modelo['etapa_id']);

            if ($this->acessoEtapa->motivoBloqueio($etapaDoModelo, $equipe['id']) === null) {
                $disponiveis[] = $modelo;
            }
        }

        return $disponiveis;
    }

    public function existeDisponivel(array $equipe, array $trilha)
    {
        return !empty($this->listarDisponiveis($equipe, $trilha));
    }
}
