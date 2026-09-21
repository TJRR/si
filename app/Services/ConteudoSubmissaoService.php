<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\CampoDinamicoRepository;
use App\Repositories\DesafioRepository;
use App\Repositories\TemaRepository;

/**
 * Fase 17 (Bug 5): extraido de AvaliacaoController::montarConteudoSubmissao()
 * (era privado, so' usado na tela do avaliador) - agora tambem reaproveitado
 * pelo popup "Ver submissão" do Admin na aba Resultado, sem duplicar a
 * logica de resolver o campo selecao_tema_desafio (Tema + Desafio).
 */
class ConteudoSubmissaoService
{
    /**
     * Tipos de campo nunca expostos fora do contexto "dono" da submissao (o
     * proprio participante/equipe, ou um avaliador/admin ja autorizado PARA
     * AQUELA submissao especifica): grupo_participantes/cpf/email/telefone
     * sao dado pessoal; upload_pdf fica de fora porque o download
     * (AvaliacaoController::baixarArquivo) exige autorizacao pontual daquela
     * submissao - um link pra ele apontando pra OUTRA submissao (ex.: popup
     * de comparacao com etapa anterior) simplesmente nao teria autorizacao
     * e quebraria com 403.
     *
     * Movida de ResultadoPublicoController (Fase 23) pra ca' na Fase 37, pra
     * ser reaproveitada tambem por AvaliacaoController::popupComparacaoEtapa()
     * sem acoplar os dois controllers entre si.
     */
    const TIPOS_CAMPO_SENSIVEIS = ['grupo_participantes', 'cpf', 'email', 'telefone', 'upload_pdf'];

    private $camposDinamicos;
    private $temas;
    private $desafios;

    public function __construct()
    {
        $this->camposDinamicos = new CampoDinamicoRepository();
        $this->temas = new TemaRepository();
        $this->desafios = new DesafioRepository();
    }

    public function montar(array $submissao)
    {
        if ($submissao['formulario_dinamico_id'] === null) {
            return [];
        }

        $campos = $this->camposDinamicos->listarPorFormulario($submissao['formulario_dinamico_id']);
        $dados = json_decode((string) $submissao['dados_json'], true);
        $valores = isset($dados['campos']) && is_array($dados['campos']) ? $dados['campos'] : [];

        $conteudo = [];

        foreach ($campos as $campo) {
            $valor = array_key_exists((string) $campo['id'], $valores) ? $valores[(string) $campo['id']] : null;

            if ($campo['tipo'] === 'selecao_tema_desafio' && $valor !== null) {
                $desafio = $this->desafios->buscarPorId((int) $valor);

                if ($desafio !== null) {
                    $tema = $this->temas->buscarPorId($desafio['tema_id']);
                    $valor = ($tema !== null ? $tema['nome'] . ': ' : '') . $desafio['pergunta'];
                }
            }

            $conteudo[] = ['campo' => $campo, 'valor' => $valor];
        }

        return $conteudo;
    }
}
