<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\CampoDinamicoRepository;
use App\Repositories\FormularioDinamicoRepository;

class FormularioDinamicoService
{
    private $formularios;
    private $campos;

    public function __construct()
    {
        $this->formularios = new FormularioDinamicoRepository();
        $this->campos = new CampoDinamicoRepository();
    }

    public function publicar($id)
    {
        $formulario = $this->formularios->buscarPorId($id);

        if ($formulario === null) {
            return ['sucesso' => false, 'mensagem' => 'Formulário não encontrado.'];
        }

        if ($this->campos->contarPorFormulario($id) === 0) {
            return ['sucesso' => false, 'mensagem' => 'Adicione ao menos um campo antes de publicar.'];
        }

        $this->formularios->atualizarStatus($id, 'publicado');

        return ['sucesso' => true];
    }

    public function arquivar($id)
    {
        $this->formularios->atualizarStatus($id, 'arquivado');

        return ['sucesso' => true];
    }

    public function duplicar($id, $concursoDestinoId)
    {
        $formulario = $this->formularios->buscarPorId($id);

        if ($formulario === null) {
            return ['sucesso' => false, 'mensagem' => 'Formulário não encontrado.'];
        }

        $novoId = $this->formularios->criar(
            $concursoDestinoId,
            $formulario['nome'],
            $formulario['descricao'],
            (int) $formulario['versao'] + 1,
            'rascunho'
        );

        $this->campos->copiarTodosParaOutroFormulario($id, $novoId);

        return ['sucesso' => true, 'novo_id' => $novoId];
    }
}
