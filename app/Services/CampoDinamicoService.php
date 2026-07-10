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
        'numero' => 'Número',
        'cpf' => 'CPF',
        'email' => 'E-mail',
        'telefone' => 'Telefone',
        'link_youtube' => 'Link do YouTube',
        'selecao_tema_desafio' => 'Seleção de Tema/Desafio',
        'upload_pdf' => 'Upload de PDF',
        'grupo_participantes' => 'Grupo repetível de participantes',
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
            return ['sucesso' => false, 'mensagem' => 'Informe o rótulo do campo.'];
        }

        if (!isset(self::TIPOS[$tipo])) {
            return ['sucesso' => false, 'mensagem' => 'Tipo de campo inválido.'];
        }

        $config = $this->montarConfig($tipo, $configPost);
        $id = $this->campos->criar($formularioId, $rotulo, $tipo, $obrigatorio, $config);

        return ['sucesso' => true, 'id' => $id];
    }

    public function atualizar($id, $rotulo, $tipo, $obrigatorio, array $configPost)
    {
        $campo = $this->campos->buscarPorId($id);

        if ($campo === null) {
            return ['sucesso' => false, 'mensagem' => 'Campo não encontrado.'];
        }

        $erro = $this->validarEstruturaEditavel($campo['formulario_id']);

        if ($erro !== null) {
            return ['sucesso' => false, 'mensagem' => $erro];
        }

        if ($rotulo === '') {
            return ['sucesso' => false, 'mensagem' => 'Informe o rótulo do campo.'];
        }

        if (!isset(self::TIPOS[$tipo])) {
            return ['sucesso' => false, 'mensagem' => 'Tipo de campo inválido.'];
        }

        $configAnterior = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : [];
        $config = $this->montarConfig($tipo, $configPost, $configAnterior);
        $this->campos->atualizar($id, $rotulo, $tipo, $obrigatorio, $config);

        return ['sucesso' => true];
    }

    public function remover($id)
    {
        $campo = $this->campos->buscarPorId($id);

        if ($campo === null) {
            return ['sucesso' => false, 'mensagem' => 'Campo não encontrado.'];
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
            return ['sucesso' => false, 'mensagem' => 'Campo não encontrado.'];
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
            return 'Formulário não encontrado.';
        }

        if ($formulario['status'] !== 'rascunho') {
            return 'Este formulário já foi publicado. Duplique-o para alterar a estrutura de campos.';
        }

        return null;
    }

    /**
     * $configAnterior preserva chaves de uso interno do sistema (ex. "_papel",
     * usada pela Inscricao de Equipe para saber "isso e o CPF do participante
     * 3" independente do rotulo) que o Admin nao edita pela UI - sem isso, uma
     * simples edicao de rotulo/obrigatoriedade apagaria essa marca.
     */
    private function montarConfig($tipo, array $configPost, array $configAnterior = [])
    {
        if ($tipo === 'upload_pdf') {
            $config = ['tamanho_maximo_mb' => 15];
        } elseif ($tipo === 'grupo_participantes') {
            $minimo = isset($configPost['minimo_repeticoes']) ? (int) $configPost['minimo_repeticoes'] : 1;
            $maximo = isset($configPost['maximo_repeticoes']) ? (int) $configPost['maximo_repeticoes'] : 10;

            $config = [
                'minimo_repeticoes' => max(1, $minimo),
                'maximo_repeticoes' => max($minimo, $maximo),
            ];
        } else {
            $config = [];
        }

        if (isset($configAnterior['_papel'])) {
            $config['_papel'] = $configAnterior['_papel'];
        }

        return $config;
    }
}
