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
                    $valor = ($tema !== null ? $tema['nome'] . ' — ' : '') . $desafio['pergunta'];
                }
            }

            $conteudo[] = ['campo' => $campo, 'valor' => $valor];
        }

        return $conteudo;
    }
}
