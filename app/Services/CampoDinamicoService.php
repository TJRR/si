<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\CampoDinamicoRepository;
use App\Repositories\FormularioDinamicoRepository;

class CampoDinamicoService
{
    const TIPOS = [
        'texto' => 'Texto',
        'numero' => 'Numero',
        'cpf' => 'CPF',
        'email' => 'E-mail',
        'telefone' => 'Telefone',
        'link_youtube' => 'Link do YouTube',
        'selecao_tema_desafio' => 'Selecao de Tema/Desafio',
        'upload_pdf' => 'Upload de PDF',
        'grupo_participantes' => 'Grupo repetivel de participantes',
    ];

    private $campos;
    private $formularios;

    public function __construct()
    {
        $this->campos = new CampoDinamicoRepository();
        $this->formularios = new FormularioDinamicoRepository();
    }

    public function criar($formularioId, $rotulo, $tipo, $obrigatorio, array $configPost)
    {
        $erro = $this->validarEstruturaEditavel($formularioId);

        if ($erro !== null) {
            return ['sucesso' => false, 'mensagem' => $erro];
        }

        if ($rotulo === '') {
            return ['sucesso' => false, 'mensagem' => 'Informe o rotulo do campo.'];
        }

        if (!isset(self::TIPOS[$tipo])) {
            return ['sucesso' => false, 'mensagem' => 'Tipo de campo invalido.'];
        }

        $config = $this->montarConfig($tipo, $configPost);
        $id = $this->campos->criar($formularioId, $rotulo, $tipo, $obrigatorio, $config);

        return ['sucesso' => true, 'id' => $id];
    }

    public function atualizar($id, $rotulo, $tipo, $obrigatorio, array $configPost)
    {
        $campo = $this->campos->buscarPorId($id);

        if ($campo === null) {
            return ['sucesso' => false, 'mensagem' => 'Campo nao encontrado.'];
        }

        $erro = $this->validarEstruturaEditavel($campo['formulario_id']);

        if ($erro !== null) {
            return ['sucesso' => false, 'mensagem' => $erro];
        }

        if ($rotulo === '') {
            return ['sucesso' => false, 'mensagem' => 'Informe o rotulo do campo.'];
        }

        if (!isset(self::TIPOS[$tipo])) {
            return ['sucesso' => false, 'mensagem' => 'Tipo de campo invalido.'];
        }

        $config = $this->montarConfig($tipo, $configPost);
        $this->campos->atualizar($id, $rotulo, $tipo, $obrigatorio, $config);

        return ['sucesso' => true];
    }

    public function remover($id)
    {
        $campo = $this->campos->buscarPorId($id);

        if ($campo === null) {
            return ['sucesso' => false, 'mensagem' => 'Campo nao encontrado.'];
        }

        $erro = $this->validarEstruturaEditavel($campo['formulario_id']);

        if ($erro !== null) {
            return ['sucesso' => false, 'mensagem' => $erro];
        }

        $this->campos->remover($id);

        return ['sucesso' => true];
    }

    public function mover($id, $direcao)
    {
        $campo = $this->campos->buscarPorId($id);

        if ($campo === null) {
            return ['sucesso' => false, 'mensagem' => 'Campo nao encontrado.'];
        }

        $erro = $this->validarEstruturaEditavel($campo['formulario_id']);

        if ($erro !== null) {
            return ['sucesso' => false, 'mensagem' => $erro];
        }

        $this->campos->mover($id, $direcao);

        return ['sucesso' => true];
    }

    private function validarEstruturaEditavel($formularioId)
    {
        $formulario = $this->formularios->buscarPorId($formularioId);

        if ($formulario === null) {
            return 'Formulario nao encontrado.';
        }

        if ($formulario['status'] !== 'rascunho') {
            return 'Este formulario ja foi publicado. Duplique-o para alterar a estrutura de campos.';
        }

        return null;
    }

    private function montarConfig($tipo, array $configPost)
    {
        if ($tipo === 'upload_pdf') {
            return ['tamanho_maximo_mb' => 15];
        }

        if ($tipo === 'grupo_participantes') {
            $minimo = isset($configPost['minimo_repeticoes']) ? (int) $configPost['minimo_repeticoes'] : 1;
            $maximo = isset($configPost['maximo_repeticoes']) ? (int) $configPost['maximo_repeticoes'] : 10;

            return [
                'minimo_repeticoes' => max(1, $minimo),
                'maximo_repeticoes' => max($minimo, $maximo),
            ];
        }

        return [];
    }
}
