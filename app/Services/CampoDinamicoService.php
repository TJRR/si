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
        'texto_longo' => 'Texto longo',
        'numero' => 'Número',
        'cpf' => 'CPF',
        'email' => 'E-mail',
        'telefone' => 'Telefone',
        'link_youtube' => 'Link do YouTube',
        'link_externo' => 'Link externo (URL)',
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

        $erroPapelDocumento = $this->validarPapelDocumentoUnico($formularioId, $configPost, null);

        if ($erroPapelDocumento !== null) {
            return ['sucesso' => false, 'mensagem' => $erroPapelDocumento];
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

        $erroPapelDocumento = $this->validarPapelDocumentoUnico($campo['formulario_id'], $configPost, $id);

        if ($erroPapelDocumento !== null) {
            return ['sucesso' => false, 'mensagem' => $erroPapelDocumento];
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

        // Fase 30: marcacao editavel pelo Administrador (diferente de
        // "_papel", que e' so' interna do motor de inscricao) - identifica
        // qual campo de submissao alimenta [[equipe.solucao_proposta]] nos
        // modelos de documento. So' um por trilha, ja validado antes de
        // chegar aqui (ver validarPapelDocumentoUnico(), chamado por
        // criar()/atualizar()).
        if (!empty($configPost['papel_documento_solucao_proposta'])) {
            $config['_papel_documento'] = 'solucao_proposta';
        }

        return $config;
    }

    /**
     * Fase 30: no maximo um campo marcado como "solucao_proposta" por
     * trilha - dois campos marcados deixariam a resolucao de
     * [[equipe.solucao_proposta]] (ModeloDocumentoService) ambigua, ja que
     * ela para no primeiro que encontrar, em silencio.
     */
    private function validarPapelDocumentoUnico($formularioId, array $configPost, $ignorarCampoId)
    {
        if (empty($configPost['papel_documento_solucao_proposta'])) {
            return null;
        }

        $etapaRepository = new \App\Repositories\EtapaRepository();
        $etapaDoFormulario = $etapaRepository->buscarPorFormularioId($formularioId);

        if ($etapaDoFormulario === null) {
            return null;
        }

        foreach ($etapaRepository->listarPorTrilha($etapaDoFormulario['trilha_id']) as $etapaDaTrilha) {
            if ($etapaDaTrilha['formulario_dinamico_id'] === null) {
                continue;
            }

            foreach ($this->campos->listarPorFormulario($etapaDaTrilha['formulario_dinamico_id']) as $campoExistente) {
                if ($ignorarCampoId !== null && (int) $campoExistente['id'] === (int) $ignorarCampoId) {
                    continue;
                }

                $configExistente = $campoExistente['config_json'] !== null ? json_decode($campoExistente['config_json'], true) : [];

                if (isset($configExistente['_papel_documento']) && $configExistente['_papel_documento'] === 'solucao_proposta') {
                    return 'Já existe um campo marcado como "Solução proposta" nesta trilha ("' . $campoExistente['rotulo'] . '"). Desmarque-o antes de marcar outro.';
                }
            }
        }

        return null;
    }
}
