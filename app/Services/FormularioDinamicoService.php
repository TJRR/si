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

    /**
     * Tira o formulario do ar mantendo-o ainda "vivo" (pode voltar a ser
     * publicado, pode ser duplicado) — diferente de arquivar(), que e um
     * passo mais definitivo (arquivado nao pode mais ser duplicado).
     */
    public function despublicar($id)
    {
        $this->formularios->atualizarStatus($id, 'despublicado');

        return ['sucesso' => true];
    }

    /**
     * So faz sentido a partir de "despublicado" — a tela so oferece o botao
     * nesse estado, mas o metodo em si nao valida o estado de origem (mesmo
     * padrao ja usado em publicar()/desarquivar()).
     */
    public function arquivar($id)
    {
        $this->formularios->atualizarStatus($id, 'arquivado');

        return ['sucesso' => true];
    }

    public function desarquivar($id)
    {
        $this->formularios->atualizarStatus($id, 'despublicado');

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
